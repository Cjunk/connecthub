<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/bootstrap.php';

$pageTitle = 'Under Construction';
?>

<?php include __DIR__ . '/src/views/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/under-construction.css">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <!-- Construction Icon -->
            <div class="construction-icon mb-4">
                <i class="fas fa-hard-hat fa-6x text-warning mb-3"></i>
                <div class="construction-tools">
                    <i class="fas fa-hammer fa-2x text-secondary me-3"></i>
                    <i class="fas fa-wrench fa-2x text-secondary me-3"></i>
                    <i class="fas fa-screwdriver fa-2x text-secondary"></i>
                </div>
            </div>

            <!-- Main Message -->
            <h1 class="display-4 fw-bold text-warning mb-3">
                <i class="fas fa-cog fa-spin me-3"></i>Under Construction
            </h1>
            
            <h2 class="h4 text-muted mb-4">We're Building Something Amazing!</h2>
            
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-body p-5">
                    <p class="lead mb-4">
                        🚧 This feature is currently under development. Our team is working hard to bring you 
                        an amazing experience!
                    </p>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="feature-preview p-3 bg-light rounded">
                                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                                <h6>User Profiles</h6>
                                <small class="text-muted">Coming Soon</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-preview p-3 bg-light rounded">
                                <i class="fas fa-calendar-plus fa-2x text-success mb-2"></i>
                                <h6>Event Creation</h6>
                                <small class="text-muted">In Progress</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-preview p-3 bg-light rounded">
                                <i class="fas fa-credit-card fa-2x text-info mb-2"></i>
                                <h6>Payment System</h6>
                                <small class="text-muted">Coming Soon</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <h6 class="text-start">Development Progress</h6>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" 
                                 role="progressbar" style="width: 35%" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100">
                                35% Complete
                            </div>
                        </div>
                        <small class="text-muted">Foundation & Core Features</small>
                    </div>
                    
                    <!-- What's Working -->
                    <div class="alert alert-success">
                        <h6 class="alert-heading">
                            <i class="fas fa-check-circle me-2"></i>What's Already Working:
                        </h6>
                        <ul class="list-unstyled mb-0">
                            <li>✅ User Registration & Authentication</li>
                            <li>✅ Responsive Design</li>
                            <li>✅ Security Framework</li>
                            <li>✅ Database Structure</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="d-flex gap-3 justify-content-center flex-wrap mb-4">
                <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
                
                <?php if (!isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
                <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/dashboard.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <?php endif; ?>
                
                <button class="btn btn-outline-secondary btn-lg" onclick="window.history.back()">
                    <i class="fas fa-arrow-left me-2"></i>Go Back
                </button>
            </div>
            
            <!-- Timeline -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(45deg, #007bff, #28a745);">
                    <h5 class="mb-0">
                        <i class="fas fa-road me-2"></i>Development Roadmap
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item completed">
                            <i class="fas fa-check-circle text-success"></i>
                            <div class="timeline-content">
                                <h6>Phase 1: Foundation</h6>
                                <p class="text-muted mb-0">Core architecture and security ✅</p>
                            </div>
                        </div>
                        <div class="timeline-item active">
                            <i class="fas fa-cog fa-spin text-warning"></i>
                            <div class="timeline-content">
                                <h6>Phase 2: Core Features</h6>
                                <p class="text-muted mb-0">User management, groups, events 🚧</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <i class="fas fa-clock text-muted"></i>
                            <div class="timeline-content">
                                <h6>Phase 3: Advanced Features</h6>
                                <p class="text-muted mb-0">Payments, notifications, API 📅</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/src/views/layouts/footer.php'; ?>

