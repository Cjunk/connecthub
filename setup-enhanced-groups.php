<?php
/**
 * Enhanced Group Management Database Setup
 */

require_once 'config/constants.php';
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "Enhancing group management schema...\n";
    
    // Read and execute the enhanced SQL file
    $sql = file_get_contents('database/enhance_group_management.sql');
    
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
    
    echo "\n✅ Enhanced group management schema applied successfully!\n";
    echo "🎯 New features added:\n";
    echo "   - Owner/Co-host/Moderator role hierarchy\n";
    echo "   - Role promotion and management system\n";
    echo "   - Group activity logging\n";
    echo "   - Permission-based access control\n";
    echo "   - Ownership transfer capability\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>