<?php
/**
 * Database Connection Test
 */

require_once 'config/config.php';

echo "Testing database connection...\n";

try {
    // Try basic connection first
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : '5432') . ";dbname=" . DB_NAME;
    echo "DSN: $dsn\n";
    echo "User: " . DB_USER . "\n";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Basic PDO connection successful!\n";
    
    // Test a simple query
    $result = $pdo->query("SELECT version()")->fetch();
    echo "✅ PostgreSQL version: " . $result['version'] . "\n";
    
    // Check if login_attempts table exists
    $checkTable = $pdo->query("
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'login_attempts'
        )
    ")->fetch();
    
    if ($checkTable['exists']) {
        echo "✅ login_attempts table exists\n";
    } else {
        echo "❌ login_attempts table does not exist\n";
        echo "Creating table...\n";
        
        $createSql = "
        CREATE TABLE login_attempts (
            id SERIAL PRIMARY KEY,
            ip_address INET NOT NULL,
            email VARCHAR(255),
            success BOOLEAN NOT NULL DEFAULT false,
            attempted_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
        )";
        
        $pdo->exec($createSql);
        echo "✅ Table created!\n";
        
        // Create indexes
        $pdo->exec("CREATE INDEX idx_login_attempts_ip_time ON login_attempts(ip_address, attempted_at)");
        $pdo->exec("CREATE INDEX idx_login_attempts_success_time ON login_attempts(success, attempted_at)");
        echo "✅ Indexes created!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}