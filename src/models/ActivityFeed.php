<?php

class ActivityFeed
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getRecentPublic(int $limit = 20): array
    {
        $sql = "
            SELECT
                id,
                actor_user_id,
                activity_type,
                entity_type,
                entity_id,
                group_id,
                event_id,
                title,
                message,
                image_url,
                visibility,
                metadata,
                created_at
            FROM activity_feed
            WHERE visibility = 'public'
            ORDER BY created_at DESC
            LIMIT :limit
        ";

        return $this->db->fetchAll($sql, [
            'limit' => $limit
        ]);
    }

    public function getAfterId(int $afterId, int $limit = 20): array
    {
        $sql = "
            SELECT
                id,
                actor_user_id,
                activity_type,
                entity_type,
                entity_id,
                group_id,
                event_id,
                title,
                message,
                image_url,
                visibility,
                metadata,
                created_at
            FROM activity_feed
            WHERE visibility = 'public'
              AND id > :after_id
            ORDER BY id DESC
            LIMIT :limit
        ";

        return $this->db->fetchAll($sql, [
            'after_id' => $afterId,
            'limit' => $limit
        ]);
    }
}