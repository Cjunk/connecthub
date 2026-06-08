<?php
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/bootstrap.php';

$pageTitle = 'Login';
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-sign-in-alt me-2"></i>Login</h2>
                        <p class="text-muted d-flex align-items-center justify-content-center gap-2 mb-0">
                            <img src="assets/images/uhura-logo.svg" alt="Uhura" style="width: 22px; height: 22px; object-fit: contain;" onerror="this.style.display='none'">
                            <span style="font-weight: 600; letter-spacing: 0.02em;">Welcome back to Uhura</span>
                        </p>
                    </div>
                    
                    <form method="POST" action="<?php echo BASE_URL; ?>/auth/login.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="login" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="login" name="login" required autocomplete="username">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                        
                        <div class="text-center">
                            <a href="<?php echo BASE_URL; ?>/forgot-password.php" class="text-muted">Forgot your password?</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <p>Don't have an account? <a href="<?php echo BASE_URL; ?>/register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>


