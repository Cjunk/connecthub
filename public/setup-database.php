<?php
/**
 * Database Setup Script for Local Development
 * Run this script to create the database and tables
 */

// Load configuration
require_once '../config/constants.php';

echo "<h1>ConnectHub Database Setup</h1>\n";

try {
    // First, connect without specifying database to create it
    $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<p>✅ Connected to MySQL server</p>\n";
    
    // Read and execute schema
    $schema = file_get_contents('../database/schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p>✅ Database and tables created successfully</p>\n";
    
    // Check if we should load seed data
    $seeds = file_get_contents('../database/seeds.sql');
    if (!empty($seeds)) {
        $seedStatements = explode(';', $seeds);
        
        foreach ($seedStatements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        echo "<p>✅ Seed data loaded successfully</p>\n";
    }
    
    echo "<h3>Database Setup Complete!</h3>\n";
    echo "<p>You can now use the following test accounts:</p>\n";
    echo "<ul>\n";
    echo "<li><strong>Admin:</strong> admin@connecthub.local / password123</li>\n";
    echo "<li><strong>Organizer:</strong> organizer@connecthub.local / password123</li>\n";
    echo "<li><strong>Member:</strong> member@connecthub.local / password123</li>\n";
    echo "</ul>\n";
    echo "<p><a href='login.php'>Go to Login Page</a></p>\n";
    
} catch (PDOException $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<h3>Common Issues:</h3>\n";
    echo "<ul>\n";
    echo "<li>Make sure XAMPP MySQL is running</li>\n";
    echo "<li>Check your database credentials in config/local_config.php</li>\n";
    echo "<li>Ensure MySQL user has permission to create databases</li>\n";
    echo "</ul>\n";
}
?>