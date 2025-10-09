<?php
/**
 * Quick Membership Banner Debug
 */

require_once '../config/constants.php';
require_once '../config/bootstrap.php';

echo "<h1>🔍 Membership Banner Debug</h1>";

if (!isLoggedIn()) {
    echo "<p>❌ Not logged in</p>";
    exit;
}

$currentUser = getCurrentUser();
echo "<h2>Current User:</h2>";
echo "<pre>Name: " . htmlspecialchars($currentUser['name']) . "</pre>";
echo "<pre>Role: " . htmlspecialchars($currentUser['role']) . "</pre>";

try {
    $userModel = new User();
    $hasMembership = $userModel->hasMembership($currentUser['id']);
    
    echo "<h2>Membership Check:</h2>";
    echo "<p><strong>hasMembership():</strong> " . ($hasMembership ? "✅ TRUE" : "❌ FALSE") . "</p>";
    
    $isNewUser = (new DateTime($currentUser['created_at']))->diff(new DateTime())->days === 0;
    echo "<p><strong>isNewUser:</strong> " . ($isNewUser ? "✅ TRUE" : "❌ FALSE") . "</p>";
    
    echo "<h2>Banner Should Show:</h2>";
    echo "<p><strong>Condition (!hasMembership):</strong> " . (!$hasMembership ? "✅ YES - Banner should show" : "❌ NO - Banner should NOT show") . "</p>";
    
    // Get full user record
    $fullUser = $userModel->findById($currentUser['id']);
    echo "<h2>Database Record:</h2>";
    echo "<pre>";
    print_r($fullUser);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?>