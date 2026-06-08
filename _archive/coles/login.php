<?php
/**
 * Login Page for Coles Preferences System
 */

require_once 'includes/auth.php';

// Start session
Auth::startSession();

// If already logged in, redirect to dashboard
if (Auth::isLoggedIn()) {
    header("Location: dashboard");
    exit;
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $result = Auth::login($username, $password);
        
        if ($result['success']) {
            // Login successful, redirect
            // Always redirect to clean dashboard URL
            header("Location: dashboard");
            exit;
        } else {
            $error = $result['error'];
            // Log the error for debugging
            error_log("Login failed for user: $username - Error: " . $result['error']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Coles Preferences System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --coles-red: #E30613;
            --coles-dark-red: #C20510;
            --coles-light-red: #F5E6E7;
            --coles-orange: #FF6B35;
            --coles-green: #008751;
            --coles-dark-green: #006B3F;
        }
        
        body {
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(255,255,255,0.05) 0%, transparent 50%),
                linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.03) 50%, transparent 100%);
            pointer-events: none;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(227, 6, 19, 0.3);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            color: white;
            padding: 3rem 2rem 2rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .coles-logo {
            font-size: 2.5rem;
            font-weight: 800;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: -1px;
            margin-bottom: 0.5rem;
        }
        
        .eastern-creek-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
            margin-bottom: 0.5rem;
        }
        
        .system-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }
        
        .login-body {
            padding: 2.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--coles-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 6, 19, 0.15);
            transform: translateY(-1px);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--coles-red) 0%, var(--coles-dark-red) 100%);
            border: none;
            padding: 14px;
            font-weight: 600;
            border-radius: 10px;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(227, 6, 19, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, var(--coles-dark-red) 0%, #A5040D 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(227, 6, 19, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .demo-credentials {
            background: rgba(227, 6, 19, 0.05);
            border: 1px solid rgba(227, 6, 19, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .demo-credentials h6 {
            color: var(--coles-red);
            margin-bottom: 0.5rem;
        }
        
        .demo-credentials code {
            background: rgba(227, 6, 19, 0.1);
            color: var(--coles-dark-red);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="coles-logo">
                <i class="fas fa-store me-2"></i>COLES
            </div>
            <div class="eastern-creek-subtitle">Eastern Creek Headquarters</div>
            <div class="system-subtitle">
                <i class="fas fa-calendar-alt me-2"></i>
                Holiday Preferences System
            </div>
        </div>
        
        <div class="login-body">
            <!-- Error Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i>Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                           required autocomplete="username">
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           required autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-login btn-primary w-100">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </button>
            </form>
            
            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <h6><i class="fas fa-info-circle me-2"></i>Login Credentials</h6>
                <div class="row">
                    <div class="col-12 mb-2">
                        <strong>Admin:</strong> <code>admin</code> / <code>Admin123!</code>
                    </div>
                    <div class="col-12 mb-2">
                        <strong>Manager:</strong> <code>manager1</code> / <code>Manager123!</code>
                    </div>
                    <div class="col-12">
                        <strong>Viewer:</strong> <code>viewer1</code> / <code>Viewer123!</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
