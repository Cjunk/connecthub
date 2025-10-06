-- Enhanced Group Management Schema
-- Add better role management and ownership clarity

-- Update group_memberships to have better role definitions
ALTER TABLE group_memberships DROP CONSTRAINT IF EXISTS group_memberships_role_check;
ALTER TABLE group_memberships ADD CONSTRAINT group_memberships_role_check 
CHECK (role IN ('owner', 'co_host', 'moderator', 'member'));

-- Add promoted_by field to track who promoted users to roles
ALTER TABLE group_memberships ADD COLUMN IF NOT EXISTS promoted_by INTEGER REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE group_memberships ADD COLUMN IF NOT EXISTS promoted_at TIMESTAMP DEFAULT NULL;

-- Add role permissions table to define what each role can do
CREATE TABLE IF NOT EXISTS group_role_permissions (
    id SERIAL PRIMARY KEY,
    role VARCHAR(20) NOT NULL,
    permission VARCHAR(50) NOT NULL,
    description TEXT,
    UNIQUE(role, permission)
);

-- Insert role permissions
INSERT INTO group_role_permissions (role, permission, description) VALUES
-- Owner permissions (can do everything)
('owner', 'manage_group', 'Edit group details, delete group'),
('owner', 'manage_members', 'Add/remove members, change member roles'),
('owner', 'promote_co_hosts', 'Promote members to co-host role'),
('owner', 'create_events', 'Create and manage group events'),
('owner', 'moderate_discussions', 'Moderate group discussions and comments'),
('owner', 'manage_settings', 'Change group privacy and settings'),
('owner', 'transfer_ownership', 'Transfer group ownership to another member'),

-- Co-host permissions (most management capabilities)
('co_host', 'manage_members', 'Add/remove members, moderate member roles'),
('co_host', 'create_events', 'Create and manage group events'),
('co_host', 'moderate_discussions', 'Moderate group discussions and comments'),
('co_host', 'promote_moderators', 'Promote members to moderator role'),

-- Moderator permissions (limited management)
('moderator', 'moderate_discussions', 'Moderate group discussions and comments'),
('moderator', 'manage_events', 'Help manage group events'),

-- Member permissions (basic participation)
('member', 'participate', 'Participate in group activities and discussions'),
('member', 'create_discussions', 'Create discussion topics')
ON CONFLICT (role, permission) DO NOTHING;

-- Add group activity log to track important actions
CREATE TABLE IF NOT EXISTS group_activity_log (
    id SERIAL PRIMARY KEY,
    group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    action VARCHAR(50) NOT NULL,
    details JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_group_activity_log_group ON group_activity_log(group_id);
CREATE INDEX IF NOT EXISTS idx_group_activity_log_action ON group_activity_log(action);

-- Update existing creator roles to owner roles
UPDATE group_memberships SET role = 'owner' WHERE role = 'creator';

-- Function to log group activities
CREATE OR REPLACE FUNCTION log_group_activity(
    p_group_id INTEGER,
    p_user_id INTEGER,
    p_action VARCHAR(50),
    p_details JSONB DEFAULT NULL
) RETURNS VOID AS $$
BEGIN
    INSERT INTO group_activity_log (group_id, user_id, action, details)
    VALUES (p_group_id, p_user_id, p_action, p_details);
END;
$$ LANGUAGE plpgsql;

-- Trigger to automatically log role changes
CREATE OR REPLACE FUNCTION log_role_changes() RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'UPDATE' AND OLD.role != NEW.role THEN
        PERFORM log_group_activity(
            NEW.group_id,
            NEW.promoted_by,
            'role_changed',
            jsonb_build_object(
                'target_user_id', NEW.user_id,
                'old_role', OLD.role,
                'new_role', NEW.role
            )
        );
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER group_role_change_log 
    AFTER UPDATE ON group_memberships
    FOR EACH ROW EXECUTE FUNCTION log_role_changes();