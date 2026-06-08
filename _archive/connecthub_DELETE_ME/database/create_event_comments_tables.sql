-- Event Comments and Discussion System Migration
-- This script creates the complete event commenting system with threading support

-- Create event_comments table if it doesn't exist
CREATE TABLE IF NOT EXISTS event_comments (
    id SERIAL PRIMARY KEY,
    event_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    parent_id INTEGER,  -- For threaded replies (NULL = top-level comment)
    comment TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'hidden', 'deleted')),
    likes_count INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

    -- Foreign key constraints
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES event_comments(id) ON DELETE CASCADE
);

-- Create event_media table if it doesn't exist
CREATE TABLE IF NOT EXISTS event_media (
    id SERIAL PRIMARY KEY,
    event_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL CHECK (file_type IN ('image', 'video', 'document', 'audio')),
    file_size INTEGER NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'deleted')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

    -- Foreign key constraints
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create comment_likes table if it doesn't exist
CREATE TABLE IF NOT EXISTS comment_likes (
    id SERIAL PRIMARY KEY,
    comment_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    reaction_type VARCHAR(20) DEFAULT 'like' CHECK (reaction_type IN ('like', 'love', 'laugh', 'angry', 'sad', 'wow', 'thumbs_up', 'thumbs_down')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,

    -- Unique constraint to prevent duplicate reactions
    UNIQUE(comment_id, user_id),

    -- Foreign key constraints
    FOREIGN KEY (comment_id) REFERENCES event_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_event_comments_event ON event_comments(event_id);
CREATE INDEX IF NOT EXISTS idx_event_comments_user ON event_comments(user_id);
CREATE INDEX IF NOT EXISTS idx_event_comments_parent ON event_comments(parent_id);
CREATE INDEX IF NOT EXISTS idx_event_comments_status ON event_comments(status);
CREATE INDEX IF NOT EXISTS idx_event_comments_created ON event_comments(created_at);

CREATE INDEX IF NOT EXISTS idx_event_media_event ON event_media(event_id);
CREATE INDEX IF NOT EXISTS idx_event_media_user ON event_media(user_id);
CREATE INDEX IF NOT EXISTS idx_event_media_type ON event_media(file_type);

CREATE INDEX IF NOT EXISTS idx_comment_likes_comment ON comment_likes(comment_id);
CREATE INDEX IF NOT EXISTS idx_comment_likes_user ON comment_likes(user_id);

-- Add trigger to update likes_count when reactions are added/removed
CREATE OR REPLACE FUNCTION update_comment_likes_count()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        UPDATE event_comments SET likes_count = likes_count + 1 WHERE id = NEW.comment_id;
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        UPDATE event_comments SET likes_count = likes_count - 1 WHERE id = OLD.comment_id;
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Create trigger if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgname = 'trigger_update_comment_likes') THEN
        CREATE TRIGGER trigger_update_comment_likes
            AFTER INSERT OR DELETE ON comment_likes
            FOR EACH ROW EXECUTE FUNCTION update_comment_likes_count();
    END IF;
END $$;

-- Add helpful comments
COMMENT ON TABLE event_comments IS 'Event discussion comments with threading support';
COMMENT ON TABLE event_media IS 'Media attachments for events (images, videos, documents)';
COMMENT ON TABLE comment_likes IS 'User reactions to event comments';

COMMENT ON COLUMN event_comments.parent_id IS 'NULL for top-level comments, references parent comment ID for replies';
COMMENT ON COLUMN event_comments.likes_count IS 'Cached count of likes/reactions for performance';
COMMENT ON COLUMN event_media.file_type IS 'Type of media: image, video, document, audio';
COMMENT ON COLUMN comment_likes.reaction_type IS 'Type of reaction: like, love, laugh, angry, sad, wow, thumbs_up, thumbs_down';