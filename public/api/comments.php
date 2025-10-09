<?php
/**
 * Comments API Endpoint
 * Handles AJAX requests for event comments
 */

session_start();

require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../../src/controllers/CommentController.php';

// Set JSON response headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Handle CORS if needed
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    exit(0);
}

// Catch all errors and convert to JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        'error' => 'PHP Error: ' . $errstr,
        'file' => $errfile,
        'line' => $errline,
        'type' => $errno
    ]);
    exit;
});

set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Uncaught Exception: ' . $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine()
    ]);
    exit;
});

try {
    $controller = new CommentController();
    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'submit_comment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            $controller->submitComment();
            break;

        case 'toggle_like':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            $controller->toggleLike();
            break;

        case 'delete_comment':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            $controller->deleteComment();
            break;

        case 'upload_media':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            $controller->uploadMedia();
            break;

        case 'get_comments':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            $controller->getComments();
            break;

        case 'get_media':
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            $controller->getMedia();
            break;

        default:
            throw new Exception('Invalid action', 400);
    }

} catch (Exception $e) {
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);

    echo json_encode([
        'error' => $e->getMessage(),
        'status' => $statusCode
    ]);
}
?>