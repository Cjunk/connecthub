<?php
/**
 * Fix PostgreSQL Users Table Schema
 * This script updates the users table to match what the application code expects
 */

require_once '../config/constants.php';
require_once '../config/database.php';

echo "<h1>Fixing PostgreSQL Users Table Schema</h1>";

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    echo "<h2>1. Checking current users table structure...</h2>";
    
    // Check if users table exists and what columns it has
    $columns = $db->fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users' AND table_schema = 'public' ORDER BY ordinal_position");
    
    if (empty($columns)) {
        echo "<p>❌ Users table doesn't exist. Creating it...</p>";
        
        // Read and execute the PostgreSQL schema
        $schema = file_get_contents('../database/postgresql_users_schema.sql');
        $connection->exec($schema);
        
        echo "<p>✅ Users table created successfully!</p>";
        
    } else {
        echo "<p>ℹ️ Users table exists with columns:</p>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li><strong>" . $column['column_name'] . "</strong> (" . $column['data_type'] . ")</li>";
        }
        echo "</ul>";
        
        // Check if we have the right columns
        $columnNames = array_column($columns, 'column_name');
        $requiredColumns = ['name', 'email', 'password_hash', 'phone', 'bio', 'city', 'interests', 'role', 'membership_expires'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $required) {
            if (!in_array($required, $columnNames)) {
                $missingColumns[] = $required;
            }
        }
        
        if (!empty($missingColumns)) {
            echo "<h3>⚠️ Missing required columns:</h3>";
            echo "<ul>";
            foreach ($missingColumns as $missing) {
                echo "<li>$missing</li>";
            }
            echo "</ul>";
            
            // Add missing columns
            echo "<h3>🔧 Adding missing columns...</h3>";
            
            if (in_array('name', $missingColumns)) {
                try {
                    $connection->exec("ALTER TABLE users ADD COLUMN name VARCHAR(255)");
                    echo "<p>✅ Added 'name' column</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Error adding 'name' column: " . $e->getMessage() . "</p>";
                }
            }
            
            if (in_array('phone', $missingColumns)) {
                try {
                    $connection->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20)");
                    echo "<p>✅ Added 'phone' column</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Error adding 'phone' column: " . $e->getMessage() . "</p>";
                }
            }
            
            if (in_array('bio', $missingColumns)) {
                try {
                    $connection->exec("ALTER TABLE users ADD COLUMN bio TEXT");
                    echo "<p>✅ Added 'bio' column</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Error adding 'bio' column: " . $e->getMessage() . "</p>";
                }
            }
            
            if (in_array('city', $missingColumns)) {
                try {
                    $connection->exec("ALTER TABLE users ADD COLUMN city VARCHAR(100)");
                    echo "<p>✅ Added 'city' column</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Error adding 'city' column: " . $e->getMessage() . "</p>";
                }
            }
            
            if (in_array('interests', $missingColumns)) {
                try {
                    $connection->exec("ALTER TABLE users ADD COLUMN interests TEXT");
                    echo "<p>✅ Added 'interests' column</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Error adding 'interests' column: " . $e->getMessage() . "</p>";
                }
            }
            
            if (in_array('role', $missingColumns)) {
                try {
                    $connection->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'member' CHECK (role IN ('member', 'organizer', 'admin', 'super_admin'))");
                    echo "<p>✅ Added 'role' column</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Error adding 'role' column: " . $e->getMessage() . "</p>";
                }
            }
            
            if (in_array('membership_expires', $missingColumns)) {
                try {
                    $connection->exec("ALTER TABLE users ADD COLUMN membership_expires TIMESTAMP WITH TIME ZONE");
                    echo "<p>✅ Added 'membership_expires' column</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Error adding 'membership_expires' column: " . $e->getMessage() . "</p>";
                }
            }
            
            // If we have first_name and last_name but no name, combine them
            if (in_array('first_name', $columnNames) && in_array('last_name', $columnNames) && in_array('name', $missingColumns)) {
                try {
                    $connection->exec("UPDATE users SET name = CONCAT(first_name, ' ', last_name) WHERE name IS NULL");
                    echo "<p>✅ Populated 'name' column from first_name and last_name</p>";
                } catch (Exception $e) {
                    echo "<p>⚠️ Error populating 'name' column: " . $e->getMessage() . "</p>";
                }
            }
            
        } else {
            echo "<h3>✅ All required columns are present!</h3>";
        }
    }
    
    echo "<h2>2. Testing User Model...</h2>";
    
    try {
        require_once '../src/models/User.php';
        $userModel = new User();
        echo "<p>✅ User model loaded successfully</p>";
        
        // Test membership check for existing users
        $testUsers = $db->fetchAll("SELECT id, email FROM users LIMIT 3");
        foreach ($testUsers as $user) {
            $hasMembership = $userModel->hasMembership($user['id']);
            echo "<p>User " . htmlspecialchars($user['email']) . ": " . ($hasMembership ? "✅ Has membership" : "❌ No membership") . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ User model error: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>3. Current users table final structure:</h2>";
    $finalColumns = $db->fetchAll("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users' AND table_schema = 'public' ORDER BY ordinal_position");
    echo "<ul>";
    foreach ($finalColumns as $column) {
        echo "<li><strong>" . $column['column_name'] . "</strong> (" . $column['data_type'] . ")</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<p>✅ <strong>Schema update complete!</strong></p>";
    echo "<p>You can now try registering a new user.</p>";
    echo "<p><a href='register.php' class='btn btn-primary'>Go to Registration</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Make sure PostgreSQL is running</li>";
    echo "<li>Check database credentials in config/local_config.php</li>";
    echo "<li>Verify the database exists and is accessible</li>";
    echo "</ul>";
}
?>