<?php
/**
 * Migration Script: Create login_attempts table
 * Run this script to set up rate limiting functionality
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/bootstrap.php';

echo "Running login_attempts table migration...\n";

try {
    $db = Database::getInstance();
    $sql = file_get_contents(__DIR__ . '/migrations/create_login_attempts_table.sql');
    
    $db->query($sql);
    
    echo "✅ Migration completed successfully!\n";
    echo "✅ login_attempts table created with indexes\n";
    echo "✅ Cleanup function created\n";
    echo "✅ Rate limiting is now active\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}