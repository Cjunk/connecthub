<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

$pageTitle = 'Register';
?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container mt-3">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-3">
                    <div class="text-center mb-3">
                        <h4><i class="fas fa-user-plus me-2"></i>Join ConnectHub</h4>
                        <p class="text-muted small mb-0">Create your account and start connecting</p>
                    </div>
                    
                    <form method="POST" action="<?php echo BASE_URL; ?>/auth/register.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-2">
                            <label for="name" class="form-label small">Full Name *</label>
                            <input type="text" class="form-control form-control-sm" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-2">
                            <label for="email" class="form-label small">Email Address *</label>
                            <input type="email" class="form-control form-control-sm" id="email" name="email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-6 mb-2">
                                <label for="phone" class="form-label small">Phone</label>
                                <input type="tel" class="form-control form-control-sm" id="phone" name="phone" placeholder="(555) 123-4567">
                            </div>
                            <div class="col-6 mb-2">
                                <label for="city" class="form-label small">City</label>
                                <input type="text" class="form-control form-control-sm" id="city" name="city" placeholder="San Francisco">
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <label for="interests" class="form-label small">Interests</label>
                            <input type="text" class="form-control form-control-sm" id="interests" name="interests" placeholder="Technology, Sports, Music, etc.">
                        </div>
                        
                        <div class="mb-2">
                            <label for="bio" class="form-label small">Bio</label>
                            <textarea class="form-control form-control-sm" id="bio" name="bio" rows="2" placeholder="Tell us about yourself..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-6 mb-2">
                                <label for="password" class="form-label small">Password *</label>
                                <input type="password" class="form-control form-control-sm" id="password" name="password" required>
                                <small class="text-muted">Min. 8 characters</small>
                            </div>
                            <div class="col-6 mb-2">
                                <label for="confirm_password" class="form-label small">Confirm *</label>
                                <input type="password" class="form-control form-control-sm" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="mb-2 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label small" for="terms">
                                I agree to the <a href="<?php echo BASE_URL; ?>/under-construction.php" target="_blank">Terms of Service</a> 
                                and <a href="<?php echo BASE_URL; ?>/under-construction.php" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-2">
                <p class="small mb-0">Already have an account? <a href="<?php echo BASE_URL; ?>/login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include '../src/views/layouts/footer.php'; ?>