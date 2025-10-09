<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Extend session by updating last_activity
$_SESSION['last_activity'] = time();

// Regenerate session ID for security
session_regenerate_id(true);

echo json_encode([
    'success' => true,
    'message' => 'Session extended successfully',
    'time_remaining' => getSessionTimeRemaining()
]);
?>