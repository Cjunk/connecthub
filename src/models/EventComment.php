<?php
/**
 * EventComment Model
 * Handles event comments and threaded discussions
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/User.php';

class EventComment {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new comment
     */
    public function create($data) {
        $sql = "INSERT INTO event_comments (event_id, user_id, parent_id, comment, status)
                VALUES (:event_id, :user_id, :parent_id, :comment, :status)
                RETURNING id";

        $params = [
            ':event_id' => $data['event_id'],
            ':user_id' => $data['user_id'],
            ':parent_id' => $data['parent_id'] ?? null,
            ':comment' => trim($data['comment']),
            ':status' => $data['status'] ?? 'active'
        ];

        $result = $this->db->fetch($sql, $params);
        return $result ? $result['id'] : false;
    }

    /**
     * Get comments for an event (threaded)
     */
    public function getByEventId($eventId, $includeHidden = false) {
        $statusFilter = $includeHidden ? "" : "AND ec.status = 'active'";

        $sql = "SELECT
                    ec.*,
                    u.name as author_name,
                    u.email as author_email,
                    u.role as author_role,
                    COUNT(cl.id) as likes_count,
                    CASE WHEN cl_user.id IS NOT NULL THEN true ELSE false END as user_liked
                FROM event_comments ec
                JOIN users u ON ec.user_id = u.id
                LEFT JOIN comment_likes cl ON ec.id = cl.comment_id
                LEFT JOIN comment_likes cl_user ON ec.id = cl_user.comment_id AND cl_user.user_id = :user_id
                WHERE ec.event_id = :event_id {$statusFilter}
                GROUP BY ec.id, u.name, u.email, u.role, cl_user.id
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
        $sql = "SELECT ec.*, u.name as author_name, u.email as author_email, u.role as author_role
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

        // Check if user is event organizer or group admin
        $sql = "SELECT e.created_by, gm.role
                FROM event_comments ec
                JOIN events e ON ec.event_id = e.id
                LEFT JOIN group_memberships gm ON e.group_id = gm.group_id AND gm.user_id = :user_id
                WHERE ec.id = :comment_id";

        $result = $this->db->fetch($sql, [
            ':comment_id' => $commentId,
            ':user_id' => $userId
        ]);

        if (!$result) return false;

        // Event creator can manage comments
        if ($result['created_by'] == $userId) return true;

        // Group owners and co-hosts can manage comments
        if (in_array($result['role'], ['owner', 'co_host'])) return true;

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
}