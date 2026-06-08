<?php
/**
 * API endpoint to get shifts
 */

require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Check permissions
    Auth::requirePermission('view');
    
    $pdo = DatabaseConfig::getConnection();
    
    $query = "
        SELECT id, name, description 
        FROM shifts 
        ORDER BY name
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $shifts = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'shifts' => $shifts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load shifts: ' . $e->getMessage()
    ]);
}
?>
