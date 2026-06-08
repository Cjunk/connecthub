-- Login Attempts Table for Rate Limiting
-- Run this migration to enable brute-force protection

CREATE TABLE IF NOT EXISTS login_attempts (
    id SERIAL PRIMARY KEY,
    ip_address INET NOT NULL,
    email VARCHAR(255),
    success BOOLEAN NOT NULL DEFAULT false,
    attempted_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    user_agent TEXT
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts(ip_address, attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_success_time ON login_attempts(success, attempted_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_email_time ON login_attempts(email, attempted_at) WHERE email IS NOT NULL;

-- Cleanup old records automatically (PostgreSQL specific)
-- This creates a function to clean up records older than 7 days
CREATE OR REPLACE FUNCTION cleanup_old_login_attempts()
RETURNS void AS $$
BEGIN
    DELETE FROM login_attempts 
    WHERE attempted_at < NOW() - INTERVAL '7 days';
END;
$$ LANGUAGE plpgsql;

-- Schedule cleanup to run daily (optional - can also be done via cron)
-- This requires pg_cron extension: CREATE EXTENSION IF NOT EXISTS pg_cron;
-- SELECT cron.schedule('cleanup-login-attempts', '0 2 * * *', 'SELECT cleanup_old_login_attempts();');

COMMENT ON TABLE login_attempts IS 'Tracks login attempts for rate limiting and security monitoring';
COMMENT ON COLUMN login_attempts.ip_address IS 'Client IP address (supports IPv4 and IPv6)';
COMMENT ON COLUMN login_attempts.email IS 'Email address attempted (null for invalid emails)';
COMMENT ON COLUMN login_attempts.success IS 'Whether the login attempt was successful';
COMMENT ON COLUMN login_attempts.attempted_at IS 'Timestamp of the attempt';
COMMENT ON COLUMN login_attempts.user_agent IS 'Browser user agent (for additional tracking)';