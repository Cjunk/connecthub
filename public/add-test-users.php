<?php
/**
 * Insert Test Users into PostgreSQL Database
 */

// Load configuration
require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../src/models/User.php';

echo "<h1>Adding Test Users to ConnectHub</h1>\n";

try {
    $userModel = new User();
    
    $testUsers = [
        [
            'name' => 'John Organizer',
            'email' => 'john@connecthub.local',
            'password' => 'password123',
            'phone' => '555-0101',
            'bio' => 'Experienced event organizer and community builder.',
            'city' => 'San Francisco',
            'interests' => 'Technology, Networking, Community Building',
            'role' => 'organizer'
        ],
        [
            'name' => 'Jane Member',
            'email' => 'jane@connecthub.local',
            'password' => 'password123',
            'phone' => '555-0102',
            'bio' => 'Tech enthusiast looking to connect with like-minded people.',
            'city' => 'San Francisco',
            'interests' => 'Technology, Programming, Startups',
            'role' => 'member'
        ],
        [
            'name' => 'Mike Developer',
            'email' => 'mike@connecthub.local',
            'password' => 'password123',
            'phone' => '555-0103',
            'bio' => 'Full-stack developer and meetup enthusiast.',
            'city' => 'San Jose',
            'interests' => 'Programming, Web Development, Open Source',
            'role' => 'member'
        ],
        [
            'name' => 'Sarah Admin',
            'email' => 'admin@connecthub.local',
            'password' => 'admin123',
            'phone' => '555-0100',
            'bio' => 'Platform administrator and community manager.',
            'city' => 'San Francisco',
            'interests' => 'Community Management, Events, Technology',
            'role' => 'admin'
        ],
        [
            'name' => 'Super User',
            'email' => 'super@connecthub.local',
            'password' => 'super123',
            'phone' => '555-0099',
            'bio' => 'System super administrator with full access.',
            'city' => 'San Francisco',
            'interests' => 'System Administration, Technology, Security',
            'role' => 'super_admin'
        ]
    ];
    
    echo "<ul>\n";
    
    foreach ($testUsers as $userData) {
        try {
            // Check if user already exists
            $existingUser = $userModel->findByEmail($userData['email']);
            
            if ($existingUser) {
                echo "<li>⚠️ User already exists: {$userData['email']}</li>\n";
                continue;
            }
            
            $userId = $userModel->create($userData);
            $role = ucfirst($userData['role']);
            
            echo "<li>✅ Created {$role}: {$userData['name']} ({$userData['email']}) - ID: {$userId}</li>\n";
            
        } catch (Exception $e) {
            echo "<li>❌ Failed to create {$userData['name']}: " . $e->getMessage() . "</li>\n";
        }
    }
    
    echo "</ul>\n";
    echo "<h3>Test Users Created Successfully!</h3>\n";
    echo "<p><strong>Login Credentials:</strong></p>\n";
    echo "<ul>\n";
    echo "<li><strong>Organizer:</strong> john@connecthub.local / password123</li>\n";
    echo "<li><strong>Member:</strong> jane@connecthub.local / password123</li>\n";
    echo "<li><strong>Developer:</strong> mike@connecthub.local / password123</li>\n";
    echo "<li><strong>Admin:</strong> admin@connecthub.local / admin123</li>\n";
    echo "<li><strong>Super Admin:</strong> super@connecthub.local / super123</li>\n";
    echo "</ul>\n";
    echo "<p><a href='login.php' class='btn btn-primary'>Go to Login Page</a></p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>\n";
    echo "<h3>Troubleshooting:</h3>\n";
    echo "<ul>\n";
    echo "<li>Make sure PostgreSQL is running</li>\n";
    echo "<li>Check database credentials in config/local_config.php</li>\n";
    echo "<li>Ensure the 'users' table exists</li>\n";
    echo "<li>Verify the connecthub_admin user has proper permissions</li>\n";
    echo "</ul>\n";
}
?>