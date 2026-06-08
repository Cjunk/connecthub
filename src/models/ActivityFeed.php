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
                af.id,
                af.actor_user_id,
                af.activity_type,
                af.entity_type,
                af.entity_id,
                af.group_id,
                af.event_id,
                af.title,
                af.message,
                af.image_url,
                af.visibility,
                af.metadata,
                af.created_at,
                g.name AS group_name,
                g.slug AS group_slug,
                e.title AS event_title,
                e.slug AS event_slug
            FROM activity_feed af
            LEFT JOIN groups g ON g.id = af.group_id
            LEFT JOIN events e ON e.id = af.event_id
            WHERE af.visibility = 'public'
            ORDER BY af.created_at DESC
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
                af.id,
                af.actor_user_id,
                af.activity_type,
                af.entity_type,
                af.entity_id,
                af.group_id,
                af.event_id,
                af.title,
                af.message,
                af.image_url,
                af.visibility,
                af.metadata,
                af.created_at,
                g.name AS group_name,
                g.slug AS group_slug,
                e.title AS event_title,
                e.slug AS event_slug
            FROM activity_feed af
            LEFT JOIN groups g ON g.id = af.group_id
            LEFT JOIN events e ON e.id = af.event_id
            WHERE af.visibility = 'public'
              AND af.id > :after_id
            ORDER BY af.id DESC
            LIMIT :limit
        ";

        return $this->db->fetchAll($sql, [
            'after_id' => $afterId,
            'limit' => $limit
        ]);
    }
}

