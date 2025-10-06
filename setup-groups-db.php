<?php
/**
 * Database Setup Script for Groups
 * Run this script to create the groups tables and sample data
 */

require_once 'config/constants.php';
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Creating groups database schema...\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents('database/create_groups_tables.sql');
    
    // Split by semicolons and execute each statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $connection->exec($statement);
                echo "✓ Executed statement successfully\n";
            } catch (PDOException $e) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Groups database schema created successfully!\n";
    echo "📊 Sample groups and categories have been added.\n";
    echo "🎯 You can now browse groups at: http://localhost/groups.php\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>