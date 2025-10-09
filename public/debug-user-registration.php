<?php
/**
 * Debug User Registration Issue
 */

require_once '../config/constants.php';
require_once '../config/bootstrap.php';

echo "<h1>Debug User Registration</h1>";

try {
    $db = Database::getInstance();
    
    // Check what columns exist in the users table
    echo "<h2>1. Users Table Structure:</h2>";
    $columns = $db->fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users' AND table_schema = 'public' ORDER BY ordinal_position");
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li><strong>" . $column['column_name'] . "</strong> (" . $column['data_type'] . ")</li>";
    }
    echo "</ul>";
    
    // Check if any users exist
    echo "<h2>2. Existing Users:</h2>";
    $users = $db->fetchAll("SELECT * FROM users LIMIT 5");
    
    if (empty($users)) {
        echo "<p>No users found in database.</p>";
    } else {
        echo "<p>Found " . count($users) . " users:</p>";
        echo "<pre>";
        print_r($users);
        echo "</pre>";
    }
    
    // Test if we can create a simple user
    echo "<h2>3. Test User Creation:</h2>";
    
    // Try the current User model approach
    echo "<h3>Current User Model Approach:</h3>";
    try {
        $userModel = new User();
        echo "<p>✅ User model loaded successfully</p>";
        
        // Check if a test user already exists
        $testEmail = 'debug-test@connecthub.local';
        $existing = $userModel->findByEmail($testEmail);
        
        if ($existing) {
            echo "<p>🔄 Test user already exists: " . htmlspecialchars($existing['email']) . "</p>";
            echo "<pre>";
            print_r($existing);
            echo "</pre>";
        } else {
            echo "<p>ℹ️ No existing test user found, attempting to create one...</p>";
            
            $testData = [
                'name' => 'Debug Test User',
                'email' => $testEmail,
                'password' => 'password123',
                'role' => 'member'
            ];
            
            try {
                $userId = $userModel->create($testData);
                echo "<p>✅ User created successfully with ID: $userId</p>";
                
                // Now test the membership check
                $hasMembership = $userModel->hasMembership($userId);
                echo "<p>Membership status: " . ($hasMembership ? "✅ Has membership" : "❌ No membership") . "</p>";
                
            } catch (Exception $e) {
                echo "<p>❌ User creation failed: " . $e->getMessage() . "</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>❌ User model error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='register.php'>Back to Registration</a></p>";
?>