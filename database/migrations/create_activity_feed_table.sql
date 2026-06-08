-- ConnectHub Activity Feed
-- Creates a basic activity feed table for dashboard live-feed style updates.

CREATE TABLE IF NOT EXISTS activity_feed (
    id BIGSERIAL PRIMARY KEY,

    actor_user_id INTEGER NULL,
    activity_type VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INTEGER NULL,

    group_id INTEGER NULL,
    event_id INTEGER NULL,

    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    image_url TEXT NULL,

    visibility VARCHAR(30) NOT NULL DEFAULT 'public',
    metadata JSONB NULL DEFAULT '{}'::jsonb,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_activity_feed_created_at
ON activity_feed (created_at DESC);

CREATE INDEX IF NOT EXISTS idx_activity_feed_visibility
ON activity_feed (visibility);

CREATE INDEX IF NOT EXISTS idx_activity_feed_type
ON activity_feed (activity_type);

CREATE INDEX IF NOT EXISTS idx_activity_feed_actor_user
ON activity_feed (actor_user_id);

CREATE INDEX IF NOT EXISTS idx_activity_feed_event
ON activity_feed (event_id);

CREATE INDEX IF NOT EXISTS idx_activity_feed_group
ON activity_feed (group_id);
