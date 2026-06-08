-- ConnectHub Groups Database Schema
-- Execute this SQL in your PostgreSQL database

-- Groups table
CREATE TABLE IF NOT EXISTS groups (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    category VARCHAR(100),
    privacy_level VARCHAR(20) DEFAULT 'public' CHECK (privacy_level IN ('public', 'private', 'secret')),
    location VARCHAR(255),
    max_members INTEGER DEFAULT NULL, -- NULL means unlimited
    created_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'suspended')),
    cover_image VARCHAR(255),
    tags TEXT[], -- PostgreSQL array for tags
    rules TEXT,
    meeting_frequency VARCHAR(50), -- 'weekly', 'monthly', 'irregular', etc.
    website_url VARCHAR(255),
    social_links JSONB -- Store social media links as JSON
);

-- Group memberships (join table for users and groups)
CREATE TABLE IF NOT EXISTS group_memberships (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('member', 'moderator', 'admin', 'creator')),
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'banned', 'pending')),
    UNIQUE(user_id, group_id) -- Prevent duplicate memberships
);

-- Group categories (predefined categories for groups)
CREATE TABLE IF NOT EXISTS group_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50), -- Font Awesome icon class
    color VARCHAR(7), -- Hex color code
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default group categories
INSERT INTO group_categories (name, description, icon, color, display_order) VALUES
('Technology', 'Programming, AI, Web Development, Tech Meetups', 'fas fa-laptop-code', '#007bff', 1),
('Business', 'Entrepreneurship, Networking, Startups, Professional Development', 'fas fa-briefcase', '#28a745', 2),
('Sports & Fitness', 'Running, Cycling, Gym, Team Sports, Outdoor Activities', 'fas fa-dumbbell', '#fd7e14', 3),
('Arts & Culture', 'Photography, Music, Theatre, Art Galleries, Creative Writing', 'fas fa-palette', '#e83e8c', 4),
('Food & Drink', 'Cooking, Wine Tasting, Restaurant Tours, Coffee Meetups', 'fas fa-utensils', '#ffc107', 5),
('Books & Education', 'Book Clubs, Language Exchange, Academic Discussions', 'fas fa-book', '#6f42c1', 6),
('Games & Hobbies', 'Board Games, Video Games, Collectibles, Crafts', 'fas fa-gamepad', '#20c997', 7),
('Health & Wellness', 'Meditation, Yoga, Mental Health, Nutrition', 'fas fa-heart', '#dc3545', 8),
('Social & Networking', 'General Socializing, Professional Networking, Community Building', 'fas fa-users', '#6c757d', 9),
('Other', 'Miscellaneous activities and interests', 'fas fa-ellipsis-h', '#17a2b8', 10)
ON CONFLICT (name) DO NOTHING;

-- Group join requests (for private groups)
CREATE TABLE IF NOT EXISTS group_join_requests (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    message TEXT,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP DEFAULT NULL,
    responded_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE(user_id, group_id) -- Prevent duplicate requests
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_groups_category ON groups(category);
CREATE INDEX IF NOT EXISTS idx_groups_privacy ON groups(privacy_level);
CREATE INDEX IF NOT EXISTS idx_groups_status ON groups(status);
CREATE INDEX IF NOT EXISTS idx_groups_created_by ON groups(created_by);
CREATE INDEX IF NOT EXISTS idx_group_memberships_user ON group_memberships(user_id);
CREATE INDEX IF NOT EXISTS idx_group_memberships_group ON group_memberships(group_id);
CREATE INDEX IF NOT EXISTS idx_group_memberships_status ON group_memberships(status);
CREATE INDEX IF NOT EXISTS idx_group_join_requests_status ON group_join_requests(status);

-- Update timestamp trigger function
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Add trigger to update updated_at on groups table
CREATE TRIGGER update_groups_updated_at BEFORE UPDATE ON groups
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Add some sample data for testing
INSERT INTO groups (name, slug, description, category, privacy_level, location, created_by) VALUES
('Tech Enthusiasts Phoenix', 'tech-enthusiasts-phoenix', 'A community for developers, designers, and tech professionals in Phoenix area. We meet monthly to discuss latest trends, share knowledge, and network.', 'Technology', 'public', 'Phoenix, AZ', 1),
('Phoenix Hiking Club', 'phoenix-hiking-club', 'Explore beautiful trails around Phoenix with fellow hiking enthusiasts. All skill levels welcome!', 'Sports & Fitness', 'public', 'Phoenix, AZ', 1),
('Startup Founders Network', 'startup-founders-network', 'Connect with fellow entrepreneurs and startup founders. Share experiences, get advice, and build valuable connections.', 'Business', 'private', 'Phoenix, AZ', 1),
('Phoenix Photography Guild', 'phoenix-photography-guild', 'For photography lovers of all levels. Monthly photo walks, workshops, and portfolio reviews.', 'Arts & Culture', 'public', 'Phoenix, AZ', 1),
('Foodie Adventures Phoenix', 'foodie-adventures-phoenix', 'Discover the best restaurants, food trucks, and culinary experiences in Phoenix. Monthly restaurant tours and cooking meetups.', 'Food & Drink', 'public', 'Phoenix, AZ', 1)
ON CONFLICT (slug) DO NOTHING;

-- Auto-join the creator to their groups as admin
INSERT INTO group_memberships (user_id, group_id, role) 
SELECT g.created_by, g.id, 'creator'
FROM groups g
LEFT JOIN group_memberships gm ON g.id = gm.group_id AND g.created_by = gm.user_id
WHERE gm.id IS NULL;