<?php
/**
 * Authentication Controller
 */

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Show login form
     */
    public function showLogin() {
        if (isLoggedIn()) {
            redirect(BASE_URL . '/dashboard.php');
        }
        
        include '../src/views/auth/login.php';
    }
    
    /**
     * Process login
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/login.php');
        }
        
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Validate CSRF token
        if (!verifyCSRFToken($csrfToken)) {
            setFlashMessage('error', 'Invalid request. Please try again.');
            redirect(BASE_URL . '/login.php');
        }
        
        // Validate input
        if (empty($email) || empty($password)) {
            setFlashMessage('error', 'Please fill in all fields.');
            redirect(BASE_URL . '/login.php');
        }
        
        try {
            $user = $this->userModel->authenticate($email, $password);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                setFlashMessage('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!');
                redirect(BASE_URL . '/dashboard.php');
            } else {
                setFlashMessage('error', 'Invalid email or password.');
                redirect(BASE_URL . '/login.php');
            }
        } catch (Exception $e) {
            setFlashMessage('error', $e->getMessage());
            redirect(BASE_URL . '/login.php');
        }
    }
    
    /**
     * Show registration form
     */
    public function showRegister() {
        if (isLoggedIn()) {
            redirect(BASE_URL . '/dashboard.php');
        }
        
        include '../src/views/auth/register.php';
    }
    
    /**
     * Process registration
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/register.php');
        }
        
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'phone' => sanitize($_POST['phone'] ?? ''),
            'city' => sanitize($_POST['city'] ?? ''),
            'interests' => sanitize($_POST['interests'] ?? ''),
            'bio' => sanitize($_POST['bio'] ?? ''),
            'role' => isset($_POST['is_organizer']) ? 'organizer' : 'member',
            'csrf_token' => $_POST['csrf_token'] ?? ''
        ];
        
        // Validate CSRF token
        if (!verifyCSRFToken($data['csrf_token'])) {
            setFlashMessage('error', 'Invalid request. Please try again.');
            redirect(BASE_URL . '/register.php');
        }
        
        // Validation
        $errors = $this->validateRegistration($data);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                setFlashMessage('error', $error);
            }
            redirect(BASE_URL . '/register.php');
        }
        
        try {
            $userId = $this->userModel->create($data);
            
            // Log the user in
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $data['name'];
            $_SESSION['user_email'] = $data['email'];
            $_SESSION['user_role'] = $data['role'];
            
            setFlashMessage('success', 'Registration successful! Welcome to ConnectHub.');
            redirect(BASE_URL . '/dashboard.php');
        } catch (Exception $e) {
            setFlashMessage('error', 'Registration failed. Please try again.');
            redirect(BASE_URL . '/register.php');
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        session_destroy();
        setFlashMessage('success', 'You have been logged out successfully.');
        redirect(BASE_URL . '/index.php');
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistration($data) {
        $errors = [];
        
        // Required fields
        if (empty($data['name'])) {
            $errors[] = 'Full name is required.';
        } elseif (strlen($data['name']) < 2) {
            $errors[] = 'Name must be at least 2 characters long.';
        }
        
        if (empty($data['email'])) {
            $errors[] = 'Email is required.';
        } elseif (!isValidEmail($data['email'])) {
            $errors[] = 'Please enter a valid email address.';
        } elseif ($this->userModel->findByEmail($data['email'])) {
            $errors[] = 'Email already exists.';
        }
        
        if (empty($data['password'])) {
            $errors[] = 'Password is required.';
        } elseif (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }
        
        return $errors;
    }
}