-- ConnectHub PostgreSQL Schema
-- This creates the proper PostgreSQL schema that matches the application code

-- Create the database if it doesn't exist
-- Note: This line should be run separately if the database doesn't exist
-- CREATE DATABASE connecthub;

-- Users table (PostgreSQL version)
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    bio TEXT,
    city VARCHAR(100),
    interests TEXT,
    role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('member', 'organizer', 'admin', 'super_admin')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'pending', 'suspended')),
    membership_expires TIMESTAMP WITH TIME ZONE,
    membership_paid BOOLEAN DEFAULT FALSE,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_membership_expires ON users(membership_expires);

-- Insert sample admin user (password: password123)
INSERT INTO users (name, email, password_hash, role, status, email_verified, membership_paid) VALUES
('Admin User', 'admin@connecthub.local', '$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG', 'super_admin', 'active', TRUE, TRUE)
ON CONFLICT (email) DO NOTHING;

-- Insert sample organizer (password: password123)
INSERT INTO users (name, email, password_hash, role, status, email_verified, membership_paid, membership_expires) VALUES
('John Organizer', 'organizer@connecthub.local', '$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG', 'organizer', 'active', TRUE, TRUE, CURRENT_TIMESTAMP + INTERVAL '1 year')
ON CONFLICT (email) DO NOTHING;

-- Insert sample member (password: password123)
INSERT INTO users (name, email, password_hash, role, status, email_verified, membership_paid) VALUES
('Jane Member', 'member@connecthub.local', '$2y$10$YCFskheSqNwfNMhLQxTtMe8VskobNfDdpn5ORIIGKdoYlU3o.G8MG', 'member', 'active', TRUE, FALSE)
ON CONFLICT (email) DO NOTHING;