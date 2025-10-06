<?php
/**
 * Setup Events Database Tables
 * Run this script to create the events-related tables
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Setting up Events database tables...\n";
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/create_events_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute the SQL
    $connection->exec($sql);
    
    echo "✓ Events database tables created successfully!\n";
    echo "✓ Event categories populated\n";
    echo "✓ Sample events added\n";
    echo "✓ Database indexes created\n";
    echo "\nEvents system is now ready to use!\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up events tables: " . $e->getMessage() . "\n";
    exit(1);
}
?>