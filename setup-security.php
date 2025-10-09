<?php
/**
 * Quick Setup Script for Security Features
 * Creates login_attempts table if it doesn't exist
 */

require_once 'config/config.php';
require_once 'config/bootstrap.php';

echo "Checking and setting up security features...\n";

try {
    $db = Database::getInstance();
    
    // Check if table exists
    $checkSql = "SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'login_attempts'
    )";
    
    $exists = $db->fetch($checkSql);
    
    if (!$exists['exists']) {
        echo "Creating login_attempts table...\n";
        
        // Create table with simplified SQL
        $createSql = "
        CREATE TABLE login_attempts (
            id SERIAL PRIMARY KEY,
            ip_address INET NOT NULL,
            email VARCHAR(255),
            success BOOLEAN NOT NULL DEFAULT false,
            attempted_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        );
        
        CREATE INDEX idx_login_attempts_ip_time ON login_attempts(ip_address, attempted_at);
        CREATE INDEX idx_login_attempts_success_time ON login_attempts(success, attempted_at);
        ";
        
        $db->query($createSql);
        echo "✅ Table created successfully!\n";
    } else {
        echo "✅ Table already exists!\n";
    }
    
    // Test the Security service
    echo "Testing Security service...\n";
    $testIp = '127.0.0.1';
    
    // This should work now
    $blocked = Security::tooManyAttempts($testIp);
    echo "✅ Security service working! (Blocked: " . ($blocked ? 'yes' : 'no') . ")\n";
    
    echo "\n🎉 Security setup complete!\n";
    echo "You can now:\n";
    echo "- Use the enhanced authentication system\n";
    echo "- Monitor security via /security-dashboard.php (admin only)\n";
    echo "- Rate limiting is active (5 attempts per 15 minutes)\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}