<?php
/**
 * API endpoint to get available holidays
 */

require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Check permissions
    Auth::requirePermission('view');
    
    $pdo = DatabaseConfig::getConnection();
    
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
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $holidays = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'holidays' => $holidays
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load holidays: ' . $e->getMessage()
    ]);
}
?>
