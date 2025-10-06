<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

$pageTitle = 'Login';
?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-sign-in-alt me-2"></i>Login</h2>
                        <p class="text-muted">Welcome back to ConnectHub</p>
                    </div>
                    
                    <form method="POST" action="<?php echo BASE_URL; ?>/auth/login.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
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

<?php include '../src/views/layouts/footer.php'; ?>