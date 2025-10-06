-- ConnectHub Events Database Schema
-- Execute this SQL in your PostgreSQL database

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    created_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    timezone VARCHAR(50) DEFAULT 'America/Phoenix',
    location_type VARCHAR(20) DEFAULT 'in_person' CHECK (location_type IN ('in_person', 'online', 'hybrid')),
    venue_name VARCHAR(255),
    venue_address TEXT,
    online_link VARCHAR(500),
    max_attendees INTEGER DEFAULT NULL, -- NULL means unlimited
    registration_deadline TIMESTAMP DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'cancelled', 'completed')),
    cover_image VARCHAR(255),
    tags TEXT[], -- PostgreSQL array for tags
    requirements TEXT, -- What attendees need to bring/know
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Event attendees (RSVPs)
CREATE TABLE IF NOT EXISTS event_attendees (
    id SERIAL PRIMARY KEY,
    event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'going' CHECK (status IN ('going', 'maybe', 'not_going', 'waitlist')),
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    checked_in BOOLEAN DEFAULT FALSE,
    checked_in_at TIMESTAMP DEFAULT NULL,
    notes TEXT, -- Special requirements or notes from attendee
    UNIQUE(event_id, user_id) -- Prevent duplicate RSVPs
);

-- Event categories (can be different from group categories)
CREATE TABLE IF NOT EXISTS event_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50), -- Font Awesome icon class
    color VARCHAR(7), -- Hex color code
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default event categories
INSERT INTO event_categories (name, description, icon, color, display_order) VALUES
('Workshop', 'Hands-on learning sessions and skill development', 'fas fa-tools', '#28a745', 1),
('Networking', 'Professional networking and social meetups', 'fas fa-handshake', '#17a2b8', 2),
('Speaker Event', 'Presentations, talks, and educational sessions', 'fas fa-microphone', '#6f42c1', 3),
('Social', 'Casual gatherings and social activities', 'fas fa-users', '#fd7e14', 4),
('Competition', 'Contests, hackathons, and competitive events', 'fas fa-trophy', '#ffc107', 5),
('Community Service', 'Volunteer work and community giving', 'fas fa-heart', '#dc3545', 6),
('Outdoor Activity', 'Hiking, sports, and outdoor adventures', 'fas fa-mountain', '#20c997', 7),
('Conference', 'Multi-session professional events', 'fas fa-building', '#6c757d', 8),
('Meetup', 'Regular group meetings and discussions', 'fas fa-coffee', '#e83e8c', 9),
('Other', 'Miscellaneous events and activities', 'fas fa-calendar', '#007bff', 10)
ON CONFLICT (name) DO NOTHING;

-- Event comments/discussions
CREATE TABLE IF NOT EXISTS event_comments (
    id SERIAL PRIMARY KEY,
    event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    parent_id INTEGER REFERENCES event_comments(id) ON DELETE CASCADE, -- For replies
    comment TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'hidden', 'deleted')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Event photos/media
CREATE TABLE IF NOT EXISTS event_media (
    id SERIAL PRIMARY KEY,
    event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    uploaded_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(20) NOT NULL, -- 'image', 'video', 'document'
    original_name VARCHAR(255),
    file_size INTEGER,
    caption TEXT,
    display_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_events_group ON events(group_id);
CREATE INDEX IF NOT EXISTS idx_events_date ON events(event_date);
CREATE INDEX IF NOT EXISTS idx_events_status ON events(status);
CREATE INDEX IF NOT EXISTS idx_events_created_by ON events(created_by);
CREATE INDEX IF NOT EXISTS idx_event_attendees_event ON event_attendees(event_id);
CREATE INDEX IF NOT EXISTS idx_event_attendees_user ON event_attendees(user_id);
CREATE INDEX IF NOT EXISTS idx_event_attendees_status ON event_attendees(status);
CREATE INDEX IF NOT EXISTS idx_event_comments_event ON event_comments(event_id);

-- Update timestamp trigger for events
CREATE TRIGGER update_events_updated_at BEFORE UPDATE ON events
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_event_comments_updated_at BEFORE UPDATE ON event_comments
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Sample events for testing (will be created for existing groups)
INSERT INTO events (title, slug, description, group_id, created_by, event_date, start_time, end_time, location_type, venue_name, venue_address, status) VALUES
('Tech Talk: AI in Web Development', 'tech-talk-ai-web-development', 'Join us for an exciting presentation about how AI is transforming web development. We''ll cover practical applications, tools, and future trends.', 1, 1, CURRENT_DATE + INTERVAL '7 days', '19:00:00', '21:00:00', 'in_person', 'Phoenix Tech Hub', '123 Main St, Phoenix, AZ 85001', 'published'),
('Morning Hike: South Mountain', 'morning-hike-south-mountain', 'Start your weekend with a refreshing hike up South Mountain! We''ll meet at the trailhead and hike together. All skill levels welcome.', 2, 1, CURRENT_DATE + INTERVAL '5 days', '07:00:00', '10:00:00', 'in_person', 'South Mountain Park', 'South Mountain Park, Phoenix, AZ', 'published'),
('Startup Pitch Night', 'startup-pitch-night', 'Local entrepreneurs will pitch their startup ideas to fellow founders and potential investors. Great networking opportunity!', 3, 1, CURRENT_DATE + INTERVAL '10 days', '18:30:00', '21:00:00', 'hybrid', 'Innovation Center', '456 Business Blvd, Phoenix, AZ', 'published'),
('Photography Walk: Downtown Phoenix', 'photography-walk-downtown-phoenix', 'Explore downtown Phoenix with your camera! We''ll visit interesting architecture, street art, and capture the city''s energy.', 4, 1, CURRENT_DATE + INTERVAL '3 days', '16:00:00', '18:00:00', 'in_person', 'Roosevelt Row', 'Roosevelt Row, Phoenix, AZ', 'published'),
('Foodie Tour: Best Tacos in Phoenix', 'foodie-tour-best-tacos-phoenix', 'Join fellow food lovers as we discover the best taco spots in Phoenix! We''ll visit 3-4 locations and rate our favorites.', 5, 1, CURRENT_DATE + INTERVAL '14 days', '12:00:00', '15:00:00', 'in_person', 'Various Locations', 'Central Phoenix area', 'published')
ON CONFLICT (slug) DO NOTHING;

-- Add sample RSVPs for the events
INSERT INTO event_attendees (event_id, user_id, status) VALUES
(1, 1, 'going'),
(2, 1, 'going'),
(3, 1, 'going'),
(4, 1, 'going'),
(5, 1, 'going')
ON CONFLICT (event_id, user_id) DO NOTHING;