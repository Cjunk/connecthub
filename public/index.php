<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

$pageTitle = 'Welcome to ConnectHub';
?>

<?php include '../src/views/layouts/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section text-white">
    <div class="container">
        <div class="row align-items-center min-vh-50">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4 text-white">Connect. Meet. Grow.</h1>
                <p class="lead mb-4 text-white">
                    Join thousands of people in your area who are making meaningful connections through 
                    local events and groups. Discover your community today.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <?php if (!isLoggedIn()): ?>
                        <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Join ConnectHub
                        </a>
                        <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-calendar me-2"></i>Browse Events
                        </a>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-light btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-calendar me-2"></i>Find Events
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-users fa-10x opacity-75"></i>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">Why Choose ConnectHub?</h2>
            <p class="lead text-muted">Everything you need to build meaningful connections</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-calendar-alt fa-2x"></i>
                        </div>
                        <h4>Discover Events</h4>
                        <p class="text-muted">Find interesting events in your area. From tech meetups to cooking classes, there's something for everyone.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                        <h4>Join Groups</h4>
                        <p class="text-muted">Connect with like-minded people through interest-based groups. Build lasting friendships and professional networks.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                        <h4>Earn Rewards</h4>
                        <p class="text-muted">Organize events and earn points! Active organizers get rewarded for building the community.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold">Popular Categories</h2>
            <p class="lead text-muted">Explore groups and events by category</p>
        </div>
        
        <div class="row g-3">
            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                <a href="<?php echo BASE_URL; ?>/under-construction.php?cat=technology" class="text-decoration-none">
                    <div class="card text-center border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <i class="fas fa-laptop-code fa-2x text-primary mb-2"></i>
                            <h6 class="card-title mb-0">Technology</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                <a href="<?php echo BASE_URL; ?>/under-construction.php?cat=business" class="text-decoration-none">
                    <div class="card text-center border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <i class="fas fa-briefcase fa-2x text-success mb-2"></i>
                            <h6 class="card-title mb-0">Business</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                <a href="<?php echo BASE_URL; ?>/under-construction.php?cat=arts-culture" class="text-decoration-none">
                    <div class="card text-center border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <i class="fas fa-palette fa-2x text-danger mb-2"></i>
                            <h6 class="card-title mb-0">Arts</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                <a href="<?php echo BASE_URL; ?>/under-construction.php?cat=sports-fitness" class="text-decoration-none">
                    <div class="card text-center border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <i class="fas fa-dumbbell fa-2x text-warning mb-2"></i>
                            <h6 class="card-title mb-0">Sports</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                <a href="<?php echo BASE_URL; ?>/under-construction.php?cat=food-drink" class="text-decoration-none">
                    <div class="card text-center border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <i class="fas fa-utensils fa-2x text-info mb-2"></i>
                            <h6 class="card-title mb-0">Food</h6>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-2 col-md-3 col-sm-4 col-6">
                <a href="<?php echo BASE_URL; ?>/under-construction.php" class="text-decoration-none">
                    <div class="card text-center border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <i class="fas fa-ellipsis-h fa-2x text-secondary mb-2"></i>
                            <h6 class="card-title mb-0">More</h6>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<?php if (!isLoggedIn()): ?>
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="display-5 fw-bold mb-3">Ready to Get Started?</h2>
        <p class="lead mb-4">Join our community and start making connections today!</p>
        <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-light btn-lg">
            <i class="fas fa-user-plus me-2"></i>Create Your Account
        </a>
    </div>
</section>
<?php endif; ?>

<style>
.hero-section {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}
.min-vh-50 {
    min-height: 50vh;
}
.fa-10x {
    font-size: 10em;
}
.feature-icon {
    transition: transform 0.3s ease;
}
.card:hover .feature-icon {
    transform: scale(1.1);
}
</style>

<?php include '../src/views/layouts/footer.php'; ?>