<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

$pageTitle = 'Register';
?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-user-plus me-2"></i>Join ConnectHub</h2>
                        <p class="text-muted">Create your account and start connecting</p>
                    </div>
                    
                    <form method="POST" action="<?php echo BASE_URL; ?>/auth/register.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="form-text">Enter your full name (first and last)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="(555) 123-4567">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" placeholder="San Francisco">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="interests" class="form-label">Interests</label>
                            <input type="text" class="form-control" id="interests" name="interests" placeholder="Technology, Sports, Music, etc.">
                            <div class="form-text">Comma-separated list of your interests</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell us a bit about yourself..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Min. 8 characters</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_organizer" name="is_organizer">
                            <label class="form-check-label" for="is_organizer">
                                <strong>I want to be an event organizer</strong>
                                <small class="text-muted d-block">Organizers can create and manage events (no membership fee required)</small>
                            </label>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="<?php echo BASE_URL; ?>/under-construction.php" target="_blank">Terms of Service</a> 
                                and <a href="<?php echo BASE_URL; ?>/under-construction.php" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                        
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Membership Info:</strong> Regular members pay annual fee. 
                                Organizers create events for free!
                            </small>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="<?php echo BASE_URL; ?>/login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include '../src/views/layouts/footer.php'; ?>