<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

// Ensure User model is loaded
require_once '../src/models/User.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$currentUser = getCurrentUser();
$pageTitle = 'Dashboard';

// Load group data
require_once '../src/models/Group.php';
$groupModel = new Group();

// Get user's groups
$userGroups = $groupModel->getUserGroups($currentUser['id']);
$upcomingEvents = []; // Will be implemented when events table is created

?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="card-title mb-2">
                                Welcome back, <?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?>!
                            </h2>
                            <p class="card-text mb-0">
                                Ready to connect with your community today?
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="stats-card p-3 rounded">
                                <h4 class="mb-0">Active</h4>
                                <small>Member Status</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <h4 class="card-title"><?php echo count($userGroups); ?></h4>
                    <p class="card-text text-muted">Groups Joined</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                    <h4 class="card-title">0</h4>
                    <p class="card-text text-muted">Events Attended</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-handshake fa-2x text-info mb-2"></i>
                    <h4 class="card-title">0</h4>
                    <p class="card-text text-muted">Connections Made</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center h-100">
                <div class="card-body">
                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                    <h4 class="card-title">0</h4>
                    <p class="card-text text-muted">Reviews Given</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Membership Status -->
    <?php if ($currentUser['role'] === 'member' && (!$currentUser['membership_expires'] || new DateTime() > new DateTime($currentUser['membership_expires']))): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>Membership Required
                        </h5>
                        <p class="mb-0">
                            To join groups and attend events, please complete your annual membership payment.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="<?php echo BASE_URL; ?>/membership.php" class="btn btn-warning">
                            <i class="fas fa-credit-card me-2"></i>Pay Membership
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Upcoming Events -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Upcoming Events
                    </h5>
                    <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingEvents)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No upcoming events</h6>
                            <p class="text-muted">Check out our events page to find something interesting!</p>
                            <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Browse Events
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                        <div class="d-flex align-items-center border-bottom py-3">
                            <div class="event-date me-3">
                                <div><?php echo formatDate($event['start_datetime'], 'M'); ?></div>
                                <div class="fs-4"><?php echo formatDate($event['start_datetime'], 'd'); ?></div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="<?php echo BASE_URL; ?>/event/<?php echo $event['slug']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </a>
                                </h6>
                                <p class="text-muted mb-1">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo htmlspecialchars($event['group_name']); ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo formatDateTime($event['start_datetime'], 'g:i A'); ?>
                                </small>
                            </div>
                            <div class="ms-3">
                                <span class="badge bg-primary"><?php echo $event['current_attendees']; ?> attending</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-activity me-2"></i>Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No recent activity</h6>
                        <p class="text-muted">Start joining groups and attending events to see your activity here!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>Find Events
                        </a>
                        <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-outline-success">
                            <i class="fas fa-users me-2"></i>Browse Groups
                        </a>
                        <?php if (isOrganizer()): ?>
                        <hr>
                        <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Create Group
                        </a>
                        <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Create Event
                        </a>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                        <hr>
                        <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-danger">
                            <i class="fas fa-shield-alt me-2"></i>Admin Panel
                        </a>
                        <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-warning">
                            <i class="fas fa-users-cog me-2"></i>User Management
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Your Groups -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Your Groups
                    </h5>
                    <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($userGroups)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No groups joined yet</h6>
                            <p class="text-muted">Join groups to connect with like-minded people!</p>
                            <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Browse Groups
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_slice($userGroups, 0, 3) as $group): ?>
                        <div class="d-flex align-items-center border-bottom py-3">
                            <div class="group-avatar me-3">
                                <?php if ($group['cover_image']): ?>
                                    <img src="<?php echo htmlspecialchars($group['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($group['name']); ?>" 
                                         class="rounded-circle" width="50" height="50" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-users text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="<?php echo BASE_URL; ?>/group-detail.php?slug=<?php echo $group['slug']; ?>" 
                                       class="text-decoration-none">
                                        <?php echo htmlspecialchars($group['name']); ?>
                                    </a>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo number_format($group['member_count']); ?> members
                                </small>
                            </div>
                            <div class="ms-3">
                                <span class="badge bg-<?php echo $group['role'] === 'creator' ? 'success' : 'primary'; ?>">
                                    <?php echo ucfirst($group['role']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($userGroups) > 3): ?>
                        <div class="text-center pt-3">
                            <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-sm btn-outline-primary">
                                View All <?php echo count($userGroups); ?> Groups
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../src/views/layouts/footer.php'; ?>