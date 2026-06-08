<?php
/**
 * EventComment Model
 * Handles event comments and threaded discussions
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/User.php';

class EventComment {
    private $db;
    private $userColumns = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new comment
     */
    public function create($data) {
        $sql = "INSERT INTO event_comments (event_id, user_id, parent_id, comment, status)
            VALUES (:event_id, :user_id, :parent_id, :comment, :status)";

        $params = [
            ':event_id' => $data['event_id'],
            ':user_id' => $data['user_id'],
            ':parent_id' => $data['parent_id'] ?? null,
            ':comment' => trim($data['comment']),
            ':status' => $data['status'] ?? 'active'
        ];

        $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $result = $this->db->fetch($sql . ' RETURNING id', $params);
            return $result ? (int)$result['id'] : false;
        }

        $this->db->query($sql, $params);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Get comments for an event (threaded)
     */
    public function getByEventId($eventId, $includeHidden = false) {
        $statusFilter = $includeHidden ? "" : "AND ec.status = 'active'";

        $sql = "SELECT
                    ec.*,
                    " . $this->authorNameExpression('u') . " as author_name,
                    u.email as author_email,
                    u.role as author_role,
                    COALESCE(likes.like_count, 0) as likes_count,
                    CASE WHEN cl_user.id IS NOT NULL THEN true ELSE false END as user_liked
                FROM event_comments ec
                JOIN users u ON ec.user_id = u.id
                LEFT JOIN (
                    SELECT comment_id, COUNT(*) as like_count
                    FROM comment_likes
                    GROUP BY comment_id
                ) likes ON ec.id = likes.comment_id
                LEFT JOIN comment_likes cl_user ON ec.id = cl_user.comment_id AND cl_user.user_id = :user_id
                WHERE ec.event_id = :event_id {$statusFilter}
                ORDER BY ec.created_at ASC";

        $comments = $this->db->fetchAll($sql, [
            ':event_id' => $eventId,
            ':user_id' => $_SESSION['user_id'] ?? null
        ]);

        // Build threaded structure
        return $this->buildThreadedComments($comments);
    }

    /**
     * Build threaded comment structure from flat array
     */
    private function buildThreadedComments($comments) {
        $threaded = [];
        $byId = [];

        // First pass: index by ID
        foreach ($comments as $comment) {
            $byId[$comment['id']] = $comment;
            $byId[$comment['id']]['replies'] = [];
        }

        // Second pass: build threads
        foreach ($comments as $comment) {
            if ($comment['parent_id']) {
                // This is a reply
                if (isset($byId[$comment['parent_id']])) {
                    $byId[$comment['parent_id']]['replies'][] = &$byId[$comment['id']];
                }
            } else {
                // This is a top-level comment
                $threaded[] = &$byId[$comment['id']];
            }
        }

        return $threaded;
    }

    /**
     * Get single comment by ID
     */
    public function getById($id) {
        $sql = "SELECT ec.*, " . $this->authorNameExpression('u') . " as author_name, u.email as author_email, u.role as author_role
                FROM event_comments ec
                JOIN users u ON ec.user_id = u.id
                WHERE ec.id = :id";

        return $this->db->fetch($sql, [':id' => $id]);
    }

    /**
     * Update comment
     */
    public function update($id, $data) {
        $allowedFields = ['comment', 'status'];
        $updateFields = [];
        $params = [':id' => $id];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($updateFields)) {
            return false;
        }

        $sql = "UPDATE event_comments SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        return $this->db->query($sql, $params);
    }

    /**
     * Delete comment (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE event_comments SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        return $this->db->query($sql, [':id' => $id]);
    }

    /**
     * Check if user can manage comment
     */
    public function canManageComment($commentId, $userId) {
        // Get comment and check ownership
        $comment = $this->getById($commentId);
        if (!$comment) return false;

        // Comment author can always manage their own comments
        if ($comment['user_id'] == $userId) return true;

    // Check if user is group owner (active) for the event's group
    $sql = "SELECT gm.role
        FROM event_comments ec
        JOIN events e ON ec.event_id = e.id
        LEFT JOIN group_memberships gm 
               ON e.group_id = gm.group_id 
              AND gm.user_id = :user_id
              AND gm.status = 'active'
        WHERE ec.id = :comment_id";

        $result = $this->db->fetch($sql, [
            ':comment_id' => $commentId,
            ':user_id' => $userId
        ]);

    if (!$result) return false;

    // Only group owners can manage others' comments
    if (isset($result['role']) && $result['role'] === 'owner') return true;

        // Admins and super admins can manage all comments
        $userRole = $_SESSION['user_role'] ?? '';
        if (in_array($userRole, ['admin', 'super_admin'])) return true;

        return false;
    }

    /**
     * Check if user can comment on event
     */
    public function canCommentOnEvent($eventId, $userId) {
        // Must be logged in
        if (!$userId) return false;

        // Check if user has membership (organizers and admins don't need to pay)
        $userModel = new User();
        if (!$userModel->hasMembership($userId)) return false;

        // Event organizers can always comment
        $event = $this->db->fetch('SELECT created_by FROM events WHERE id = ?', [$eventId]);
        if ($event && $event['created_by'] == $userId) return true;

        // Members can comment on any event (not just attendees)
        return true;
    }

    /**
     * Get comment count for event
     */
    public function getCommentCount($eventId) {
        $sql = "SELECT COUNT(*) as count FROM event_comments WHERE event_id = :event_id AND status = 'active'";
        $result = $this->db->fetch($sql, [':event_id' => $eventId]);
        return $result ? (int)$result['count'] : 0;
    }

    private function authorNameExpression($alias) {
        if ($this->hasUserColumn('first_name') || $this->hasUserColumn('last_name')) {
            if ($this->hasUserColumn('username')) {
                return "COALESCE(NULLIF(CONCAT(COALESCE(" . $alias . ".first_name, ''), ' ', COALESCE(" . $alias . ".last_name, '')), ' '), " . $alias . ".username, " . $alias . ".email)";
            }
            return "COALESCE(NULLIF(CONCAT(COALESCE(" . $alias . ".first_name, ''), ' ', COALESCE(" . $alias . ".last_name, '')), ' '), " . $alias . ".email)";
        }

        if ($this->hasUserColumn('name')) {
            return "COALESCE(NULLIF(" . $alias . ".name, ''), " . $alias . ".email)";
        }

        if ($this->hasUserColumn('username')) {
            return "COALESCE(NULLIF(" . $alias . ".username, ''), " . $alias . ".email)";
        }

        return $alias . ".email";
    }

    private function hasUserColumn($columnName) {
        return in_array($columnName, $this->getUserColumns(), true);
    }

    private function getUserColumns() {
        if ($this->userColumns !== null) {
            return $this->userColumns;
        }

        $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'users'";
        } else {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users'";
        }

        $rows = $this->db->fetchAll($sql);
        $this->userColumns = array_map(static function ($row) {
            return $row['column_name'];
        }, $rows);

        return $this->userColumns;
    }
}
