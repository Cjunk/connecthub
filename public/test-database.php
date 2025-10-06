<?php
/**
 * Database Connection Test
 * Test your database connection and create database if needed
 */

require_once __DIR__ . '/../config/constants.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test - ConnectHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-database me-2"></i>Database Connection Test</h4>
                    </div>
                    <div class="card-body">
                        
                        <h5>Current Database Settings:</h5>
                        <div class="alert alert-light">
                            <ul class="mb-0">
                                <li><strong>Host:</strong> <?= DB_HOST ?></li>
                                <li><strong>Database:</strong> <?= DB_NAME ?></li>
                                <li><strong>Username:</strong> <?= DB_USER ?></li>
                                <li><strong>Password:</strong> <?= empty(DB_PASS) ? '(empty)' : '(set)' ?></li>
                            </ul>
                        </div>

                        <h5>Connection Tests:</h5>
                        
                        <?php
                        // Test 1: Can we connect to MySQL server without specifying database?
                        echo "<div class='mb-3'>";
                        echo "<h6>1. Testing MySQL Server Connection...</h6>";
                        try {
                            $dsn = "mysql:host=" . DB_HOST;
                            $pdo = new PDO($dsn, DB_USER, DB_PASS);
                            echo "<div class='alert alert-success'><i class='fas fa-check'></i> ✅ MySQL server connection successful!</div>";
                            
                            // Test 2: Does the database exist?
                            echo "<h6>2. Checking if database exists...</h6>";
                            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
                            $stmt->execute([DB_NAME]);
                            $dbExists = $stmt->fetch();
                            
                            if ($dbExists) {
                                echo "<div class='alert alert-success'><i class='fas fa-check'></i> ✅ Database '" . DB_NAME . "' exists!</div>";
                                
                                // Test 3: Can we connect to the specific database?
                                echo "<h6>3. Testing specific database connection...</h6>";
                                try {
                                    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                                    $pdo2 = new PDO($dsn, DB_USER, DB_PASS);
                                    echo "<div class='alert alert-success'><i class='fas fa-check'></i> ✅ Database connection successful!</div>";
                                    
                                    // Test 4: Check if basic tables exist
                                    echo "<h6>4. Checking existing tables...</h6>";
                                    $stmt = $pdo2->query("SHOW TABLES");
                                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                    
                                    if (empty($tables)) {
                                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Database is empty - no tables found.</div>";
                                    } else {
                                        echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i> Found tables: " . implode(', ', $tables) . "</div>";
                                        
                                        // Check for events table specifically
                                        if (in_array('events', $tables)) {
                                            echo "<div class='alert alert-success'><i class='fas fa-check'></i> ✅ Events table already exists!</div>";
                                        } else {
                                            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Events table does not exist - setup needed.</div>";
                                        }
                                    }
                                    
                                } catch (PDOException $e) {
                                    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> ❌ Database connection failed: " . $e->getMessage() . "</div>";
                                }
                                
                            } else {
                                echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> ⚠️ Database '" . DB_NAME . "' does not exist!</div>";
                                
                                // Offer to create the database
                                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_db'])) {
                                    try {
                                        $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                                        echo "<div class='alert alert-success'><i class='fas fa-check'></i> ✅ Database '" . DB_NAME . "' created successfully!</div>";
                                        echo "<script>setTimeout(() => window.location.reload(), 2000);</script>";
                                    } catch (PDOException $e) {
                                        echo "<div class='alert alert-danger'><i class='fas fa-times'></i> ❌ Failed to create database: " . $e->getMessage() . "</div>";
                                    }
                                } else {
                                    echo "<form method='POST' class='mt-3'>";
                                    echo "<button type='submit' name='create_db' class='btn btn-warning'>";
                                    echo "<i class='fas fa-plus'></i> Create Database '" . DB_NAME . "'";
                                    echo "</button>";
                                    echo "</form>";
                                }
                            }
                            
                        } catch (PDOException $e) {
                            echo "<div class='alert alert-danger'><i class='fas fa-times'></i> ❌ MySQL server connection failed: " . $e->getMessage() . "</div>";
                            
                            echo "<div class='alert alert-info mt-3'>";
                            echo "<h6>Common Solutions:</h6>";
                            echo "<ul>";
                            echo "<li>Make sure XAMPP/WAMP/MAMP is running</li>";
                            echo "<li>Check if MySQL service is started</li>";
                            echo "<li>Verify your database credentials</li>";
                            echo "<li>Try connecting with a different database username/password</li>";
                            echo "</ul>";
                            echo "</div>";
                        }
                        echo "</div>";
                        ?>
                        
                        <div class="mt-4">
                            <a href="http://localhost/setup-events.php" class="btn btn-primary">
                                <i class="fas fa-calendar"></i> Go to Events Setup
                            </a>
                            <a href="http://localhost/" class="btn btn-secondary">
                                <i class="fas fa-home"></i> Back to Homepage
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>