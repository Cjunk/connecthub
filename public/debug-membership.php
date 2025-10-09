<?php
/**
 * Debug Membership Logic
 */

require_once '../config/constants.php';
require_once '../config/bootstrap.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo "<h1>Debug Membership - Not Logged In</h1>";
    echo "<p>Please log in first: <a href='login.php'>Login</a></p>";
    exit;
}

$currentUser = getCurrentUser();
echo "<h1>Debug Membership Logic</h1>";

echo "<h2>Current User Info:</h2>";
echo "<pre>";
print_r($currentUser);
echo "</pre>";

echo "<h2>Session Info:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

try {
    $userModel = new User();
    
    echo "<h2>Membership Check Results:</h2>";
    
    // Test membership check
    $hasMembership = $userModel->hasMembership($currentUser['id']);
    echo "<p><strong>hasMembership():</strong> " . ($hasMembership ? "✅ TRUE (has membership)" : "❌ FALSE (no membership)") . "</p>";
    
    // Check membership_expires field specifically
    $user = $userModel->findById($currentUser['id']);
    echo "<h3>Full User Record:</h3>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // Check membership field variations
    $membershipField = null;
    $membershipValue = null;
    
    if (isset($user['membership_expires'])) {
        $membershipField = 'membership_expires';
        $membershipValue = $user['membership_expires'];
    } elseif (isset($user['membership_expires_at'])) {
        $membershipField = 'membership_expires_at';
        $membershipValue = $user['membership_expires_at'];
    }
    
    echo "<h3>Membership Field Analysis:</h3>";
    echo "<p><strong>Field found:</strong> " . ($membershipField ?? 'NONE') . "</p>";
    echo "<p><strong>Value:</strong> " . ($membershipValue ?? 'NULL') . "</p>";
    
    if ($membershipValue) {
        try {
            $expiryDate = new DateTime($membershipValue);
            $now = new DateTime();
            $isExpired = $now > $expiryDate;
            
            echo "<p><strong>Expiry Date:</strong> " . $expiryDate->format('Y-m-d H:i:s') . "</p>";
            echo "<p><strong>Current Date:</strong> " . $now->format('Y-m-d H:i:s') . "</p>";
            echo "<p><strong>Is Expired:</strong> " . ($isExpired ? "❌ YES (expired)" : "✅ NO (active)") . "</p>";
        } catch (Exception $e) {
            echo "<p><strong>Date Parse Error:</strong> " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h3>Role Check:</h3>";
    $role = $user['role'] ?? 'unknown';
    echo "<p><strong>User Role:</strong> $role</p>";
    
    $roleExempt = in_array($role, ['organizer', 'admin', 'super_admin']);
    echo "<p><strong>Role Exempt from Payment:</strong> " . ($roleExempt ? "✅ YES" : "❌ NO") . "</p>";
    
    echo "<h3>Final Logic:</h3>";
    if ($roleExempt) {
        echo "<p>✅ User has membership because they are an organizer/admin</p>";
    } elseif ($membershipValue && !$isExpired) {
        echo "<p>✅ User has membership because it's not expired</p>";
    } else {
        echo "<p>❌ User does NOT have membership</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?>