<?php
/**
 * Debug script to test holidays loading
 */

require_once 'includes/auth.php';

try {
    echo "<h2>Testing Holidays Database Query</h2>";
    
    $pdo = DatabaseConfig::getConnection();
    
    // First, let's see what tables exist
    echo "<h3>Available Tables:</h3>";
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll();
    echo "<pre>";
    foreach ($tables as $table) {
        print_r($table);
    }
    echo "</pre>";
    
    // Check holidays table structure
    echo "<h3>Holidays Table Structure:</h3>";
    $result = $pdo->query("DESCRIBE holidays");
    $columns = $result->fetchAll();
    echo "<pre>";
    foreach ($columns as $column) {
        print_r($column);
    }
    echo "</pre>";
    
    // Check holiday_types table structure
    echo "<h3>Holiday Types Table Structure:</h3>";
    try {
        $result = $pdo->query("DESCRIBE holiday_types");
        $columns = $result->fetchAll();
        echo "<pre>";
        foreach ($columns as $column) {
            print_r($column);
        }
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
    
    // Test the actual query
    echo "<h3>Testing Current Query:</h3>";
    $query = "
        SELECT 
            h.id,
            ht.name as holiday_name,
            ht.name as holiday_type,
            h.holiday_date,
            h.year_num,
            h.jurisdiction
        FROM holidays h
        LEFT JOIN holiday_types ht ON h.type_id = ht.id
        WHERE h.year_num >= 2023
        ORDER BY h.holiday_date, ht.name
        LIMIT 5
    ";
    
    echo "<pre>Query: " . $query . "</pre>";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $holidays = $stmt->fetchAll();
    
    echo "<h4>Results:</h4>";
    echo "<pre>";
    foreach ($holidays as $holiday) {
        print_r($holiday);
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h3>Error Details:</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}
?>
