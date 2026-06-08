<?php
/**
 * API endpoint to get employees for a specific shift
 */

require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Check permissions
    Auth::requirePermission('view');
    
    $shiftId = $_GET['shift_id'] ?? null;
    if (!$shiftId) {
        throw new Exception('Shift ID is required');
    }
    
    $user = Auth::getCurrentUser();
    
    // Check if user can access this shift
    if (!Auth::hasPermission('view', $shiftId)) {
        throw new Exception('Access denied: You can only view your assigned shift');
    }
    
    $pdo = DatabaseConfig::getConnection();
    
    $query = "
        SELECT 
            tm.id,
            tm.sapid,
            tm.team_member,
            tm.preferred_name,
            tm.manager_group,
            tm.shift_id,
            s.name as shift_name,
            ht1.name as first_preference,
            ht2.name as second_preference,
            ht3.name as third_preference,
            COALESCE(
                (SELECT COUNT(*) FROM preferences WHERE employee_id = tm.id), 
                0
            ) as pending_approvals
        FROM team_members tm
        LEFT JOIN shifts s ON tm.shift_id = s.id
        LEFT JOIN preferences pr1 ON tm.id = pr1.employee_id AND pr1.rank_num = 1
        LEFT JOIN holidays h1 ON pr1.holiday_id = h1.id
        LEFT JOIN holiday_types ht1 ON h1.type_id = ht1.id
        LEFT JOIN preferences pr2 ON tm.id = pr2.employee_id AND pr2.rank_num = 2
        LEFT JOIN holidays h2 ON pr2.holiday_id = h2.id
        LEFT JOIN holiday_types ht2 ON h2.type_id = ht2.id
        LEFT JOIN preferences pr3 ON tm.id = pr3.employee_id AND pr3.rank_num = 3
        LEFT JOIN holidays h3 ON pr3.holiday_id = h3.id
        LEFT JOIN holiday_types ht3 ON h3.type_id = ht3.id
        WHERE tm.shift_id = ?
        ORDER BY tm.team_member
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$shiftId]);
    $employees = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'employees' => $employees
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
