<?php

class ActivityFeedService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function record(array $data): bool
    {
        $activityType = $data['activity_type'] ?? '';
        $entityType   = $data['entity_type'] ?? null;
        $entityId     = $data['entity_id'] ?? null;
        $actorUserId  = $data['actor_user_id'] ?? null;
        $groupId      = $data['group_id'] ?? null;
        $eventId      = $data['event_id'] ?? null;
        $title        = trim($data['title'] ?? '');
        $message      = trim($data['message'] ?? '');
        $imageUrl     = $data['image_url'] ?? null;
        $visibility   = $data['visibility'] ?? 'public';
        $metadata     = $data['metadata'] ?? [];

        if ($activityType === '' || $title === '' || $message === '') {
            return false;
        }

        // Basic anti-spam/dedupe:
        // Do not insert same activity by same user on same entity within 5 minutes.
        $recent = $this->db->fetch("
            SELECT id
            FROM activity_feed
            WHERE activity_type = :activity_type
              AND COALESCE(actor_user_id, 0) = COALESCE(:actor_user_id, 0)
              AND COALESCE(entity_type, '') = COALESCE(:entity_type, '')
              AND COALESCE(entity_id, 0) = COALESCE(:entity_id, 0)
              AND created_at > NOW() - INTERVAL '5 minutes'
            ORDER BY created_at DESC
            LIMIT 1
        ", [
            'activity_type' => $activityType,
            'actor_user_id' => $actorUserId,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
        ]);

        if ($recent) {
            return false;
        }

        $this->db->query("
            INSERT INTO activity_feed (
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
            )
            VALUES (
                :actor_user_id,
                :activity_type,
                :entity_type,
                :entity_id,
                :group_id,
                :event_id,
                :title,
                :message,
                :image_url,
                :visibility,
                :metadata,
                NOW()
            )
        ", [
            'actor_user_id' => $actorUserId,
            'activity_type' => $activityType,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'group_id'      => $groupId,
            'event_id'      => $eventId,
            'title'         => $title,
            'message'       => $message,
            'image_url'     => $imageUrl,
            'visibility'    => $visibility,
            'metadata'      => json_encode($metadata),
        ]);

        return true;
    }
}