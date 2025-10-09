<?php
/**
 * CommentLike Model
 * Handles comment reactions/likes
 */

require_once __DIR__ . '/../../config/database.php';

class CommentLike {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Add or update a reaction to a comment
     */
    public function toggleReaction($commentId, $userId, $reactionType = 'like') {
        // Check if reaction already exists
        $existing = $this->getUserReaction($commentId, $userId);

        if ($existing) {
            if ($existing['reaction_type'] === $reactionType) {
                // Same reaction - remove it
                return $this->removeReaction($commentId, $userId);
            } else {
                // Different reaction - update it
                return $this->updateReaction($commentId, $userId, $reactionType);
            }
        } else {
            // No existing reaction - add it
            return $this->addReaction($commentId, $userId, $reactionType);
        }
    }

    /**
     * Add a new reaction
     */
    private function addReaction($commentId, $userId, $reactionType) {
        $sql = "INSERT INTO comment_likes (comment_id, user_id, reaction_type)
                VALUES (:comment_id, :user_id, :reaction_type)";

        return $this->db->query($sql, [
            ':comment_id' => $commentId,
            ':user_id' => $userId,
            ':reaction_type' => $reactionType
        ]);
    }

    /**
     * Update existing reaction
     */
    private function updateReaction($commentId, $userId, $reactionType) {
        $sql = "UPDATE comment_likes SET reaction_type = :reaction_type WHERE comment_id = :comment_id AND user_id = :user_id";

        return $this->db->query($sql, [
            ':comment_id' => $commentId,
            ':user_id' => $userId,
            ':reaction_type' => $reactionType
        ]);
    }

    /**
     * Remove a reaction
     */
    private function removeReaction($commentId, $userId) {
        $sql = "DELETE FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id";

        return $this->db->query($sql, [
            ':comment_id' => $commentId,
            ':user_id' => $userId
        ]);
    }

    /**
     * Get user's reaction to a comment
     */
    public function getUserReaction($commentId, $userId) {
        $sql = "SELECT * FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id";

        return $this->db->fetch($sql, [
            ':comment_id' => $commentId,
            ':user_id' => $userId
        ]);
    }

    /**
     * Get reaction counts for a comment
     */
    public function getReactionCounts($commentId) {
        $sql = "SELECT reaction_type, COUNT(*) as count
                FROM comment_likes
                WHERE comment_id = :comment_id
                GROUP BY reaction_type
                ORDER BY count DESC";

        $results = $this->db->fetchAll($sql, [':comment_id' => $commentId]);

        // Convert to associative array
        $counts = [];
        foreach ($results as $result) {
            $counts[$result['reaction_type']] = (int)$result['count'];
        }

        return $counts;
    }

    /**
     * Get total likes count for a comment
     */
    public function getTotalLikes($commentId) {
        $sql = "SELECT COUNT(*) as total FROM comment_likes WHERE comment_id = :comment_id";

        $result = $this->db->fetch($sql, [':comment_id' => $commentId]);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Get popular reactions for a comment (top 3)
     */
    public function getPopularReactions($commentId, $limit = 3) {
        $sql = "SELECT reaction_type, COUNT(*) as count
                FROM comment_likes
                WHERE comment_id = :comment_id
                GROUP BY reaction_type
                ORDER BY count DESC
                LIMIT :limit";

        return $this->db->fetchAll($sql, [
            ':comment_id' => $commentId,
            ':limit' => $limit
        ]);
    }

    /**
     * Get reaction summary for multiple comments
     */
    public function getReactionsSummary($commentIds) {
        if (empty($commentIds)) return [];

        $placeholders = str_repeat('?,', count($commentIds) - 1) . '?';

        $sql = "SELECT comment_id, reaction_type, COUNT(*) as count
                FROM comment_likes
                WHERE comment_id IN ($placeholders)
                GROUP BY comment_id, reaction_type
                ORDER BY comment_id, count DESC";

        $results = $this->db->fetchAll($sql, $commentIds);

        // Group by comment_id
        $summary = [];
        foreach ($results as $result) {
            $commentId = $result['comment_id'];
            if (!isset($summary[$commentId])) {
                $summary[$commentId] = [];
            }
            $summary[$commentId][$result['reaction_type']] = (int)$result['count'];
        }

        return $summary;
    }

    /**
     * Get user's reactions for multiple comments
     */
    public function getUserReactions($commentIds, $userId) {
        if (empty($commentIds)) return [];

        $placeholders = str_repeat('?,', count($commentIds) - 1) . '?';

        $sql = "SELECT comment_id, reaction_type
                FROM comment_likes
                WHERE comment_id IN ($placeholders) AND user_id = ?";

        $results = $this->db->fetchAll($sql, array_merge($commentIds, [$userId]));

        // Convert to associative array
        $userReactions = [];
        foreach ($results as $result) {
            $userReactions[$result['comment_id']] = $result['reaction_type'];
        }

        return $userReactions;
    }
}