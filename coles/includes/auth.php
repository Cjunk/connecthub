<?php
/**
 * Authentication System for Coles Preferences
 * Handles user login, logout, sessions, and permissions
 */

require_once 'config.php';

class Auth {
    
    /**
     * Start secure session
     */
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_strict_mode', 1);
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }
    
    /**
     * Login user with username and password
     */
    public static function login($username, $password) {
        try {
            $pdo = DatabaseConfig::getConnection();
            
            // Get user from database
            $stmt = $pdo->prepare("
                SELECT id, username, email, password_hash, full_name, role, shift_id 
                FROM users 
                WHERE username = ? AND active = true
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Password is correct, create session
                self::startSession();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['shift_id'] = $user['shift_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Update last login
                $updateStmt = $pdo->prepare("
                    UPDATE users 
                    SET last_login = NOW(), updated_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$user['id']]);
                
                // Create session record
                $sessionStmt = $pdo->prepare("
                    INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        user_id = VALUES(user_id),
                        ip_address = VALUES(ip_address),
                        user_agent = VALUES(user_agent),
                        updated_at = NOW()
                ");
                $sessionStmt->execute([
                    $user['id'],
                    session_id(),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'error' => 'Invalid username or password'];
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Logout current user
     */
    public static function logout() {
        self::startSession();
        
        if (isset($_SESSION['user_id'])) {
            try {
                // Remove session from database
                $pdo = DatabaseConfig::getConnection();
                $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE session_id = ?");
                $stmt->execute([session_id()]);
            } catch (Exception $e) {
                error_log("Logout cleanup error: " . $e->getMessage());
            }
        }
        
        // Clear session
        $_SESSION = [];
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current user data
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role'],
            'shift_id' => $_SESSION['shift_id'],
            'email' => $_SESSION['email']
        ];
    }
    
    /**
     * Check if user has specific permission
     */
    public static function hasPermission($action, $resourceShiftId = null) {
        $user = self::getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Admin has all permissions
        if ($user['role'] === 'admin') {
            return true;
        }
        
        // Shift manager permissions
        if ($user['role'] === 'shift_manager') {
            if (in_array($action, ['view', 'edit'])) {
                return $resourceShiftId === null || $resourceShiftId == $user['shift_id'];
            }
        }
        
        // Viewer permissions
        if ($user['role'] === 'viewer') {
            return $action === 'view';
        }
        
        return false;
    }
    
    /**
     * Require user to be logged in (redirect if not)
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            // use clean login URL, no query string
            header("Location: login");
            exit;
        }
    }

    
    /**
     * Require specific permission (send 403 if not authorized)
     */
    public static function requirePermission($action, $resourceShiftId = null) {
        self::requireLogin();
        
        if (!self::hasPermission($action, $resourceShiftId)) {
            http_response_code(403);
            die(json_encode(['success' => false, 'error' => 'Access denied']));
        }
    }
    
    /**
     * Get redirect URL after login based on user role
     */
    public static function getRedirectUrl() {
        $user = self::getCurrentUser();
        if (!$user) {
            return 'login';
        }
    
        // Redirect shift managers to their shift view (optional)
        if ($user['role'] === 'shift_manager' && $user['shift_id']) {
            return 'shift?id=' . $user['shift_id'];
        }
    
        // Default to clean dashboard URL
        return 'dashboard';
    }

}
?>
