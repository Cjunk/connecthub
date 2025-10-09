<?php
/**
 * Authentication Controller (Platinum Security)
 * - Session fixation protection (regenerate on login)
 * - Secure logout (cookie + session wipe)
 * - Database-backed rate limiting with rolling window
 * - Enhanced IP detection with proxy support
 * - Email normalization for consistency
 * - Comprehensive brute-force defense
 */

class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /** Show login form */
    public function showLogin() {
        if (isLoggedIn()) redirect(BASE_URL . '/dashboard.php');
        include '../src/views/auth/login.php';
    }

    /** Process login */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/login.php');

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrfToken)) {
            setFlashMessage('error', 'Invalid request. Please try again.');
            redirect(BASE_URL . '/login.php');
        }

        $email    = $this->normalizeEmail($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            setFlashMessage('error', 'Please fill in all fields.');
            redirect(BASE_URL . '/login.php');
        }

        // --- Rate limit check (database-backed brute-force protection) ---
        $ip = $this->clientIp();
        try {
            if (Security::tooManyAttempts($ip, 5, 900)) { // 5 attempts in 15 minutes
                setFlashMessage('error', 'Too many failed attempts. Please wait 15 minutes before trying again.');
                redirect(BASE_URL . '/login.php');
            }
        } catch (Exception $e) {
            // If rate limiting fails, log it but continue with login
            error_log("Rate limiting check failed: " . $e->getMessage());
        }

        try {
            $user = $this->userModel->authenticate($email, $password);
            if ($user) {
                // Record successful login and reset attempt counter
                try {
                    Security::recordAttempt($ip, true, $email);
                } catch (Exception $e) {
                    error_log("Failed to record successful login attempt: " . $e->getMessage());
                }

                // Session fixation protection
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }

                $_SESSION['user_id']       = $user['id'];
                $_SESSION['user_name']     = $user['name'];
                $_SESSION['user_email']    = $user['email'];
                $_SESSION['user_role']     = $user['role'];
                $_SESSION['last_activity'] = time();

                setFlashMessage('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!');
                redirect(BASE_URL . '/dashboard.php');
            }

            // Fail path - record failed attempt
            try {
                Security::recordAttempt($ip, false, $email);
            } catch (Exception $e) {
                error_log("Failed to record failed login attempt: " . $e->getMessage());
            }
            setFlashMessage('error', 'Invalid email or password.');
            redirect(BASE_URL . '/login.php');

        } catch (Exception $e) {
            // Avoid leaking internals; log server-side if needed
            try {
                Security::recordAttempt($ip, false, $email);
            } catch (Exception $secException) {
                error_log("Failed to record failed login attempt: " . $secException->getMessage());
            }
            setFlashMessage('error', 'Something went wrong. Please try again.');
            redirect(BASE_URL . '/login.php');
        }
    }

    /** Show registration form */
    public function showRegister() {
        if (isLoggedIn()) redirect(BASE_URL . '/dashboard.php');
        include '../src/views/auth/register.php';
    }

    /** Process registration */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/register.php');

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($csrfToken)) {
            setFlashMessage('error', 'Invalid request. Please try again.');
            redirect(BASE_URL . '/register.php');
        }

        $data = [
            'name'             => sanitize($_POST['name'] ?? ''),
            'email'            => $this->normalizeEmail($_POST['email'] ?? ''),
            'password'         => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'phone'            => sanitize($_POST['phone'] ?? ''),
            'city'             => sanitize($_POST['city'] ?? ''),
            'interests'        => sanitize($_POST['interests'] ?? ''),
            'bio'              => sanitize($_POST['bio'] ?? ''),
            'role'             => 'member',
        ];

        $errors = $this->validateRegistration($data);
        if (!empty($errors)) {
            foreach ($errors as $e) setFlashMessage('error', $e);
            redirect(BASE_URL . '/register.php');
        }

        try {
            $userId = $this->userModel->create($data);

            // Fresh session for new user
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
            $_SESSION['user_id']       = $userId;
            $_SESSION['user_name']     = $data['name'];
            $_SESSION['user_email']    = $data['email'];
            $_SESSION['user_role']     = $data['role'];
            $_SESSION['last_activity'] = time();

            setFlashMessage('success', 'Registration successful! Welcome to ConnectHub.');
            redirect(BASE_URL . '/dashboard.php');
        } catch (Exception $e) {
            setFlashMessage('error', 'Registration failed. Please try again.');
            redirect(BASE_URL . '/login.php');
        }
    }

    /** Logout (secure wipe) */
    public function logout() {
        // Clear all session data
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();

        setFlashMessage('success', 'You have been logged out successfully.');
        redirect(BASE_URL . '/index.php');
    }

    /** Registration validation */
    private function validateRegistration(array $data): array {
        $errors = [];

        // Name
        if ($data['name'] === '') {
            $errors[] = 'Full name is required.';
        } elseif (mb_strlen($data['name']) < 2) {
            $errors[] = 'Name must be at least 2 characters long.';
        }

        // Email
        if ($data['email'] === '') {
            $errors[] = 'Email is required.';
        } elseif (!isValidEmail($data['email'])) {
            $errors[] = 'Please enter a valid email address.';
        } elseif ($this->userModel->findByEmail($data['email'])) {
            $errors[] = 'Email already exists.';
        }

        // Password
        if ($data['password'] === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        }
        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }

        return $errors;
    }

    /** Normalize emails for consistent auth & uniqueness */
    private function normalizeEmail(string $email): string {
        return strtolower(trim($email));
    }

    /** Enhanced IP resolver with proxy support */
    private function clientIp(): string {
        // Check for IP from shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy (like Cloudflare)
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Can contain multiple IPs, get the first one
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        // Check for IP from remote address
        elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        
        return '0.0.0.0';
    }
}