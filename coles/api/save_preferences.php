<?php
/**
 * API endpoint to save employee preferences
 */

require_once '../includes/auth.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }
    
    // Check permissions
    Auth::requirePermission('edit');
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $employeeId = $input['employee_id'] ?? null;
    $preferences = $input['preferences'] ?? [];
    
    if (!$employeeId) {
        throw new Exception('Employee ID is required');
    }
    
    $user = Auth::getCurrentUser();
    $pdo = DatabaseConfig::getConnection();
    
    // Verify employee exists and user has permission
    $empQuery = "SELECT shift_id FROM team_members WHERE id = ?";
    $empStmt = $pdo->prepare($empQuery);
    $empStmt->execute([$employeeId]);
    $employee = $empStmt->fetch();
    
    if (!$employee) {
        throw new Exception('Employee not found');
    }
    
    // Check permission for this specific employee's shift
    if (!Auth::hasPermission('edit', $employee['shift_id'])) {
        throw new Exception('Access denied: You can only edit employees in your shift');
    }
    
    // Validate preferences
    $holidayIds = array_map(fn($p) => $p['holiday_id'], $preferences);
    if (count($holidayIds) !== count(array_unique($holidayIds))) {
        throw new Exception('Cannot select the same holiday for multiple preferences');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Delete existing preferences
        $deleteStmt = $pdo->prepare("DELETE FROM preferences WHERE employee_id = ?");
        $deleteStmt->execute([$employeeId]);
        
        // Insert new preferences
        if (!empty($preferences)) {
            $insertStmt = $pdo->prepare("
                INSERT INTO preferences (employee_id, holiday_id, rank_num)
                VALUES (?, ?, ?)
            ");
            
            foreach ($preferences as $pref) {
                $insertStmt->execute([
                    $employeeId,
                    $pref['holiday_id'],
                    $pref['rank']
                ]);
            }
        }
        
        // Update team member's updated_at timestamp
        $updateStmt = $pdo->prepare("
            UPDATE team_members 
            SET updated_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([$employeeId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Preferences updated successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
