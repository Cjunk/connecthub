<?php
/**
 * API endpoint to get individual employee with preferences
 */

require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Check permissions
    Auth::requirePermission('view');
    
    $employeeId = $_GET['id'] ?? null;
    if (!$employeeId) {
        throw new Exception('Employee ID is required');
    }
    
    $user = Auth::getCurrentUser();
    $pdo = DatabaseConfig::getConnection();
    
    // Get employee basic info with permission check
    $query = "
        SELECT 
            tm.id,
            tm.sapid,
            tm.team_member,
            tm.preferred_name,
            tm.manager_group,
            tm.shift_id,
            s.name as shift_name
        FROM team_members tm
        LEFT JOIN shifts s ON tm.shift_id = s.id
        WHERE tm.id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        throw new Exception('Employee not found');
    }
    
    // Check if user can access this employee
    if ($user['role'] === 'shift_manager' && $employee['shift_id'] != $user['shift_id']) {
        throw new Exception('Access denied: You can only view employees in your shift');
    }
    
    // Get employee preferences
    $prefQuery = "
        SELECT 
            p.rank_num as rank,
            p.holiday_id,
            ht.name as holiday_name
        FROM preferences p
        LEFT JOIN holidays h ON p.holiday_id = h.id
        LEFT JOIN holiday_types ht ON h.type_id = ht.id
        WHERE p.employee_id = ?
        ORDER BY p.rank_num
    ";
    
    $prefStmt = $pdo->prepare($prefQuery);
    $prefStmt->execute([$employeeId]);
    $preferences = $prefStmt->fetchAll();
    
    $employee['preferences'] = $preferences;
    
    echo json_encode([
        'success' => true,
        'employee' => $employee
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getMessage() === 'Employee not found' ? 404 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
