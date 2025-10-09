<?php
/**
 * Fix User Role for Testing
 */

require_once '../config/constants.php';
require_once '../config/bootstrap.php';

if (!isLoggedIn()) {
    echo "<h1>❌ Not logged in</h1>";
    echo "<p><a href='login.php'>Login first</a></p>";
    exit;
}

$currentUser = getCurrentUser();
echo "<h1>🔧 Fix User Role for Testing</h1>";
echo "<p><strong>Current User:</strong> " . htmlspecialchars($currentUser['name']) . " (" . htmlspecialchars($currentUser['email']) . ")</p>";
echo "<p><strong>Current Role:</strong> " . htmlspecialchars($currentUser['role']) . "</p>";

if (isset($_GET['action']) && $_GET['action'] === 'fix') {
    try {
        $db = Database::getInstance();
        
        // Update the user's role to member
        $db->query("UPDATE users SET role = 'member' WHERE id = :id", [':id' => $currentUser['id']]);
        
        // Update the session
        $_SESSION['user_role'] = 'member';
        
        echo "<div class='alert alert-success'>";
        echo "<h4>✅ Fixed!</h4>";
        echo "<p>User role changed from 'organizer' to 'member'</p>";
        echo "<p>Now the membership banner should show and they should need to pay.</p>";
        echo "</div>";
        
        echo "<p><a href='dashboard.php' class='btn btn-primary'>Go to Dashboard (should show membership banner now)</a></p>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>❌ Error</h4>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='alert alert-warning'>";
    echo "<h4>⚠️ Issue Found</h4>";
    echo "<p>This user has role 'organizer' which exempts them from membership requirements.</p>";
    echo "<p>For testing purposes, we should change them to 'member' role.</p>";
    echo "</div>";
    
    echo "<p><a href='?action=fix' class='btn btn-warning'>Change Role to 'member' (for testing)</a></p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?>