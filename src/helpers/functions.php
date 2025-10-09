<?php
/**
 * Utility Functions
 */

// Load required models
require_once __DIR__ . '/../models/User.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in and session is valid
 */
function isLoggedIn() {
    // Check if user_id is set
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Check session timeout (2 hours = 7200 seconds)
    $timeout = 7200;
    
    // Check if last activity is set
    if (isset($_SESSION['last_activity'])) {
        // If more than timeout seconds have passed since last activity
        if (time() - $_SESSION['last_activity'] > $timeout) {
            // Session has expired
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Get remaining session time in seconds
 */
function getSessionTimeRemaining() {
    if (!isset($_SESSION['last_activity'])) {
        return 0;
    }
    
    $timeout = 7200; // 2 hours
    $elapsed = time() - $_SESSION['last_activity'];
    $remaining = $timeout - $elapsed;
    
    return max(0, $remaining);
}

/**
 * Check if session is expiring soon (within 5 minutes)
 */
function isSessionExpiringSoon() {
    return getSessionTimeRemaining() <= 300; // 5 minutes
}
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userModel = new User();
    return $userModel->findById($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

/**
 * Check if user is organizer or higher
 */
function isOrganizer() {
    return hasRole('organizer') || isAdmin();
}

/**
 * Check if user is admin or super_admin
 */
function isAdmin() {
    return hasRole('admin') || hasRole('super_admin');
}

/**
 * Check if user is super admin
 */
function isSuperAdmin() {
    return hasRole('super_admin');
}

/**
 * Check if user has minimum role level
 */
function hasMinimumRole($minimumRole) {
    $user = getCurrentUser();
    if (!$user) return false;
    
    $roleHierarchy = [
        'member' => 1,
        'organizer' => 2,
        'admin' => 3,
        'super_admin' => 4
    ];
    
    $userLevel = $roleHierarchy[$user['role']] ?? 0;
    $requiredLevel = $roleHierarchy[$minimumRole] ?? 0;
    
    return $userLevel >= $requiredLevel;
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = 'M d, Y g:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Upload file
 */
function uploadFile($file, $directory, $allowedTypes = null) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('No file uploaded');
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File size exceeds maximum allowed size');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($allowedTypes && !in_array($extension, $allowedTypes)) {
        throw new Exception('File type not allowed');
    }
    
    // Create directory if it doesn't exist
    $uploadPath = UPLOADS_PATH . '/' . $directory;
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadPath . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    return $directory . '/' . $filename;
}

/**
 * Send email
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    // This is a basic implementation
    // In production, you should use a proper email library like PHPMailer or SwiftMailer
    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    
    if ($isHTML) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Check if user has valid membership
 */
function hasValidMembership($user = null) {
    if (!$user) {
        $user = getCurrentUser();
    }
    
    if (!$user) {
        return false;
    }
    
    // Use the User model's hasMembership method for consistency
    try {
        $userModel = new User();
        return $userModel->hasMembership($user['id']);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Flash message functions
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Pagination helper
 */
function generatePagination($currentPage, $totalPages, $baseUrl) {
    $html = '<nav><ul class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">&laquo; Previous</a></li>';
    }
    
    // Page numbers
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $active = ($i == $currentPage) ? 'class="active"' : '';
        $html .= '<li ' . $active . '><a href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next &raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}