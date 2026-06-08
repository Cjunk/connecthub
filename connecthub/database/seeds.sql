-- Insert default categories
INSERT INTO categories (name, slug, description, icon, color) VALUES
('Technology', 'technology', 'Tech meetups, programming, and software development', 'fas fa-laptop-code', '#007bff'),
('Business', 'business', 'Networking, entrepreneurship, and professional development', 'fas fa-briefcase', '#28a745'),
('Arts & Culture', 'arts-culture', 'Creative arts, museums, and cultural events', 'fas fa-palette', '#dc3545'),
('Sports & Fitness', 'sports-fitness', 'Sports activities, fitness groups, and outdoor adventures', 'fas fa-dumbbell', '#fd7e14'),
('Food & Drink', 'food-drink', 'Culinary experiences, wine tasting, and food lovers', 'fas fa-utensils', '#e83e8c'),
('Education', 'education', 'Learning groups, workshops, and skill development', 'fas fa-graduation-cap', '#6f42c1'),
('Music', 'music', 'Concerts, music appreciation, and musical performances', 'fas fa-music', '#20c997'),
('Health & Wellness', 'health-wellness', 'Mental health, wellness practices, and healthy living', 'fas fa-heart', '#17a2b8'),
('Photography', 'photography', 'Photo walks, photography techniques, and visual arts', 'fas fa-camera', '#ffc107'),
('Travel', 'travel', 'Travel groups, cultural exchange, and adventure trips', 'fas fa-plane', '#6c757d');

-- Insert a default admin user (password: password123)
INSERT INTO users (username, email, password_hash, first_name, last_name, role, status, email_verified, membership_paid) VALUES
('admin', 'admin@connecthub.local', '$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG', 'Admin', 'User', 'super_admin', 1, TRUE, TRUE);

-- Insert sample data for testing (password: password123)
INSERT INTO users (username, email, password_hash, first_name, last_name, role, status, email_verified, membership_paid, membership_expires_at) VALUES
('organizer', 'organizer@connecthub.local', '$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG', 'John', 'Organizer', 'organizer', 1, TRUE, TRUE, DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('member', 'member@connecthub.local', '$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG', 'Jane', 'Member', 'member', 1, TRUE, TRUE, DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('testuser', 'test@connecthub.local', '$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG', 'Test', 'User', 'member', 1, TRUE, FALSE, NULL);

-- Insert sample groups
INSERT INTO groups (name, slug, description, privacy, category_id, location_city, location_state, location_country, organizer_id) VALUES
('Tech Innovators Meetup', 'tech-innovators-meetup', 'A community for technology enthusiasts and innovators to share ideas and network.', 'public', 1, 'San Francisco', 'CA', 'USA', 2),
('Business Leaders Network', 'business-leaders-network', 'Professional networking group for business leaders and entrepreneurs.', 'public', 2, 'New York', 'NY', 'USA', 2),
('Photography Enthusiasts', 'photography-enthusiasts', 'Group for amateur and professional photographers to share techniques and organize photo walks.', 'public', 9, 'Los Angeles', 'CA', 'USA', 2);