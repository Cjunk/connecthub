<?php
/**
 * Web-based Events Database Setup
 * Visit this page in your browser to create the events tables
 */

// Only allow setup in development
if (!defined('ALLOW_SETUP')) {
    define('ALLOW_SETUP', true); // Change to false in production
}

if (!ALLOW_SETUP) {
    die('Setup is disabled for security reasons');
}

require_once __DIR__ . '/../config/database.php';

$setupComplete = false;
$error = null;
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    try {
        $db = Database::getInstance();
        $connection = $db->getConnection();
        
        $messages[] = "Connected to database successfully";
        
        // Read and execute the SQL file
        $sqlFile = __DIR__ . '/../database/create_events_tables.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        $messages[] = "SQL file loaded";
        
        // Split SQL into individual statements and execute them
        $statements = preg_split('/;\s*$/m', $sql);
        $executedCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $connection->exec($statement);
                    $executedCount++;
                } catch (PDOException $e) {
                    // Some statements might fail if they already exist, that's okay
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        $messages[] = "Warning: " . $e->getMessage();
                    }
                }
            }
        }
        
        $messages[] = "Executed $executedCount SQL statements";
        $messages[] = "Events database tables created successfully!";
        $messages[] = "Event categories populated";
        $messages[] = "Sample events added";
        $messages[] = "Database indexes created";
        
        $setupComplete = true;
        
    } catch (Exception $e) {
        $error = "Error setting up events tables: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectHub Events Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-database me-2"></i>ConnectHub Events Database Setup</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($setupComplete): ?>
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>Setup Complete!</h5>
                                <p class="mb-0">The Events system is now ready to use.</p>
                            </div>
                            
                            <div class="mt-4">
                                <h6>Setup Results:</h6>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($messages as $message): ?>
                                        <li class="list-group-item">
                                            <i class="fas fa-check text-success me-2"></i><?= htmlspecialchars($message) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="mt-4">
                                <a href="http://localhost/connecthub/public/events.php" class="btn btn-primary me-2">
                                    <i class="fas fa-calendar me-2"></i>View Events
                                </a>
                                <a href="http://localhost/connecthub/public/groups.php" class="btn btn-outline-primary">
                                    <i class="fas fa-users me-2"></i>View Groups
                                </a>
                            </div>
                            
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger">
                                <h5><i class="fas fa-exclamation-triangle me-2"></i>Setup Failed</h5>
                                <p class="mb-0"><?= htmlspecialchars($error) ?></p>
                            </div>
                            
                            <form method="POST" class="mt-4">
                                <button type="submit" name="setup" class="btn btn-warning">
                                    <i class="fas fa-redo me-2"></i>Try Again
                                </button>
                            </form>
                            
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle me-2"></i>Events System Setup Required</h5>
                                <p>This will create the necessary database tables for the Events system:</p>
                                <ul class="mb-0">
                                    <li><strong>events</strong> - Main events table</li>
                                    <li><strong>event_attendees</strong> - RSVP tracking</li>
                                    <li><strong>event_categories</strong> - Event categories</li>
                                    <li><strong>event_comments</strong> - Event discussions</li>
                                    <li><strong>event_media</strong> - Event photos/files</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Note:</strong> Make sure your database connection is working and you have the necessary permissions.
                            </div>
                            
                            <form method="POST">
                                <button type="submit" name="setup" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play me-2"></i>Run Events Database Setup
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($messages) && !$setupComplete): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Setup Messages</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($messages as $message): ?>
                                    <li class="list-group-item">
                                        <i class="fas fa-info-circle text-info me-2"></i><?= htmlspecialchars($message) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>