<?php
/**
 * Debug and Fix Membership Issues
 */

require_once '../config/constants.php';
require_once '../config/bootstrap.php';

echo "<h1>🔧 Membership Debug & Fix</h1>";

// Check if user is logged in
if (!isLoggedIn()) {
    echo "<p>❌ Please log in first: <a href='login.php'>Login</a></p>";
    exit;
}

$currentUser = getCurrentUser();
echo "<h2>1. Current User Analysis</h2>";
echo "<p><strong>User:</strong> " . htmlspecialchars($currentUser['email']) . "</p>";
echo "<p><strong>Role:</strong> " . htmlspecialchars($currentUser['role']) . "</p>";

try {
    $userModel = new User();
    
    // Get full user record from database
    $fullUser = $userModel->findById($currentUser['id']);
    echo "<h3>Full Database Record:</h3>";
    echo "<pre>";
    print_r($fullUser);
    echo "</pre>";
    
    // Test membership logic step by step
    echo "<h2>2. Membership Logic Test</h2>";
    
    // Step 1: Role check
    $isRoleExempt = in_array($fullUser['role'], ['organizer', 'admin', 'super_admin']);
    echo "<p>✅ <strong>Step 1 - Role Check:</strong> " . 
         ($isRoleExempt ? "✅ EXEMPT (role: {$fullUser['role']})" : "❌ NOT EXEMPT (role: {$fullUser['role']})") . "</p>";
    
    if ($isRoleExempt) {
        echo "<p>🎯 <strong>RESULT:</strong> User should have membership due to role exemption</p>";
    } else {
        // Step 2: Check membership dates
        echo "<p>✅ <strong>Step 2 - Date Fields Check:</strong></p>";
        
        $membershipField = null;
        $membershipValue = null;
        
        if (isset($fullUser['membership_expires']) && !empty($fullUser['membership_expires'])) {
            $membershipField = 'membership_expires';
            $membershipValue = $fullUser['membership_expires'];
        } elseif (isset($fullUser['membership_expires_at']) && !empty($fullUser['membership_expires_at'])) {
            $membershipField = 'membership_expires_at';
            $membershipValue = $fullUser['membership_expires_at'];
        }
        
        echo "<ul>";
        echo "<li><strong>membership_expires:</strong> " . ($fullUser['membership_expires'] ?? 'NULL') . "</li>";
        echo "<li><strong>membership_expires_at:</strong> " . ($fullUser['membership_expires_at'] ?? 'NULL') . "</li>";
        echo "<li><strong>Selected field:</strong> " . ($membershipField ?? 'NONE') . "</li>";
        echo "<li><strong>Selected value:</strong> " . ($membershipValue ?? 'NULL') . "</li>";
        echo "</ul>";
        
        if (!$membershipValue) {
            echo "<p>🎯 <strong>RESULT:</strong> User should NOT have membership (no expiry date set)</p>";
        } else {
            try {
                $expiryDate = new DateTime($membershipValue);
                $now = new DateTime();
                $isExpired = $now > $expiryDate;
                
                echo "<p>✅ <strong>Step 3 - Date Comparison:</strong></p>";
                echo "<ul>";
                echo "<li><strong>Expiry Date:</strong> " . $expiryDate->format('Y-m-d H:i:s') . "</li>";
                echo "<li><strong>Current Date:</strong> " . $now->format('Y-m-d H:i:s') . "</li>";
                echo "<li><strong>Is Expired:</strong> " . ($isExpired ? "❌ YES" : "✅ NO") . "</li>";
                echo "</ul>";
                
                echo "<p>🎯 <strong>RESULT:</strong> User should " . 
                     ($isExpired ? "NOT have" : "have") . " membership</p>";
            } catch (Exception $e) {
                echo "<p>❌ <strong>Date Parse Error:</strong> " . $e->getMessage() . "</p>";
                echo "<p>🎯 <strong>RESULT:</strong> User should NOT have membership (invalid date)</p>";
            }
        }
    }
    
    // Test the actual hasMembership method
    echo "<h2>3. Actual hasMembership() Result</h2>";
    $hasMembership = $userModel->hasMembership($currentUser['id']);
    echo "<p><strong>hasMembership():</strong> " . ($hasMembership ? "✅ TRUE" : "❌ FALSE") . "</p>";
    
    // Fix the user if they're a regular member without membership
    echo "<h2>4. Fix Options</h2>";
    
    if ($fullUser['role'] === 'member') {
        echo "<div class='alert alert-warning'>";
        echo "<h5>🔧 Fix Available</h5>";
        echo "<p>This user is a regular member. They should NOT have membership unless they've paid.</p>";
        
        if (isset($_GET['fix']) && $_GET['fix'] === 'remove_membership') {
            // Remove any membership dates
            $db = Database::getInstance();
            $db->query("UPDATE users SET membership_expires = NULL, membership_expires_at = NULL WHERE id = :id", [':id' => $currentUser['id']]);
            echo "<p>✅ <strong>FIXED:</strong> Removed membership dates. User now requires payment.</p>";
            echo "<p><a href='membership.php' class='btn btn-success'>Go to Payment Page</a></p>";
        } else {
            echo "<p><a href='?fix=remove_membership' class='btn btn-warning'>Remove Membership (Force Payment Required)</a></p>";
        }
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<p><a href='dashboard.php'>Back to Dashboard</a> | <a href='membership.php'>Try Membership Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>