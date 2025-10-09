<?php
// === INITIALIZATION ===
require_once '../config/constants.php';
require_once '../config/bootstrap.php';
require_once '../config/ads.php';

// === AUTHENTICATION CHECK ===
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

// === MODELS & DATA LOADING ===
require_once '../src/models/User.php';
require_once '../src/models/Group.php';
require_once '../src/models/Event.php';

$currentUser = getCurrentUser();
$pageTitle = 'Dashboard';

// Initialize models
$userModel = new User();
$groupModel = new Group();
$eventModel = new Event();

// === USER DATA RETRIEVAL ===
$userGroups = $groupModel->getUserGroups($currentUser['id']);
$groupCount = count($userGroups);
$hasMembership = $userModel->hasMembership($currentUser['id']);
$upcomingEvents = $eventModel->getUpcomingForUser($currentUser['id'], 5);

// === USER STATUS CALCULATIONS ===
$isNewUser = (new DateTime($currentUser['created_at']))->diff(new DateTime())->days === 0;
$needsOnboarding = ($groupCount == 0 && !$hasMembership);

?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info alert-permanent border-0 shadow-sm" style="border-radius: 15px; background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);">
                <div class="d-flex align-items-center">
                    <!-- Welcome Banner Image Placeholder -->
                    <div class="me-3">
                        <img src="<?php echo BASE_URL; ?>/assets/images/welcome-banner.png" 
                             alt="Welcome Banner" 
                             class="rounded-circle" 
                             style="width: 60px; height: 60px; object-fit: cover; border: 3px solid #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                        <!-- Fallback icon when image not found -->
                        <i class="fas fa-user-circle fa-2x text-primary" style="display: none;"></i>
                    </div>
                    <div>
                        <h4 class="alert-heading mb-1 text-primary fw-bold">
                            Welcome back, <?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?>!
                        </h4>
                        <p class="mb-0 text-muted">
                            Ready to connect with your community today?
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($needsOnboarding): ?>
    <!-- === NEW USER ONBOARDING SECTION === -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #fff3cd 0%, #d1ecf1 100%); border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-rocket fa-3x text-warning"></i>
                        </div>
                        <h3 class="fw-bold text-dark">🎉 Welcome to ConnectHub!</h3>
                        <p class="lead text-muted mb-4">You're just 3 steps away from connecting with amazing communities!</p>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border-0 h-100" style="background: rgba(255,255,255,0.8);">
                                <div class="card-body text-center p-3">
                                    <div class="mb-3">
                                        <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-crown fa-2x text-white"></i>
                                        </div>
                                    </div>
                                    <h5 class="fw-bold">Step 1: Get Membership</h5>
                                    <p class="small text-muted mb-3">Unlock premium features and RSVP to unlimited events</p>
                                    <a href="<?php echo BASE_URL; ?>/membership.php" class="btn btn-warning btn-sm fw-bold">
                                        <i class="fas fa-star me-1"></i>Get Started - $100/year
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border-0 h-100" style="background: rgba(255,255,255,0.8);">
                                <div class="card-body text-center p-3">
                                    <div class="mb-3">
                                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-users fa-2x text-white"></i>
                                        </div>
                                    </div>
                                    <h5 class="fw-bold">Step 2: Join Groups</h5>
                                    <p class="small text-muted mb-3">Find communities that match your interests and passions</p>
                                    <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-success btn-sm">
                                        <i class="fas fa-search me-1"></i>Discover Groups
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border-0 h-100" style="background: rgba(255,255,255,0.8);">
                                <div class="card-body text-center p-3">
                                    <div class="mb-3">
                                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="fas fa-calendar-plus fa-2x text-white"></i>
                                        </div>
                                    </div>
                                    <h5 class="fw-bold">Step 3: Attend Events</h5>
                                    <p class="small text-muted mb-3">RSVP to exciting events and meet like-minded people</p>
                                    <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-calendar me-1"></i>Browse Events
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p class="small text-muted mb-2">
                            <i class="fas fa-info-circle me-1"></i>
                            Need help getting started? <a href="#" class="text-decoration-none">Check out our quick tour</a>
                        </p>
                        <div class="progress" style="height: 8px; border-radius: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 33%;" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">1 of 3 steps completed - Account created! ✅</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- === DASHBOARD STATS & ACTIONS ROW === -->
    <div class="row mb-4">
        <!-- User Statistics -->
        <div class="col-lg-6">
            <div class="row">
                <div class="col-6 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-2">
                            <i class="fas fa-users fa-lg text-primary mb-1"></i>
                            <h5 class="card-title mb-0"><?php echo count($userGroups); ?></h5>
                            <small class="text-muted">Groups Joined</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-2">
                            <i class="fas fa-calendar-check fa-lg text-success mb-1"></i>
                            <h5 class="card-title mb-0">0</h5>
                            <small class="text-muted">Events Attended</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-2">
                            <i class="fas fa-handshake fa-lg text-info mb-1"></i>
                            <h5 class="card-title mb-0">0</h5>
                            <small class="text-muted">Connections Made</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-2">
                            <i class="fas fa-star fa-lg text-warning mb-1"></i>
                            <h5 class="card-title mb-0">0</h5>
                            <small class="text-muted">Reviews Given</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions - Compact -->
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-bolt me-1"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body p-2">
                    <div class="d-grid gap-1">
                        <?php if ($hasMembership): ?>
                        <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-search me-1"></i>Find Events
                        </a>
                        <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-users me-1"></i>Browse Groups
                        </a>
                        <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/membership.php" class="btn btn-sm btn-warning">
                            <i class="fas fa-crown me-1"></i>Get Membership
                        </a>
                        <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-search me-1"></i>Browse Events
                        </a>
                        <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-users me-1"></i>Browse Groups
                        </a>
                        <?php endif; ?>
                        <?php if (isOrganizer() && $hasMembership): ?>
                        <a href="<?php echo BASE_URL; ?>/create-group.php" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i>Create Group
                        </a>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                        <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-sm btn-danger">
                            <i class="fas fa-shield-alt me-1"></i>Admin Panel
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ad Space -->
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-center p-2">
                    <?php echo getAdCode('dashboard'); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- === MAIN DASHBOARD CONTENT === -->
    <div class="row">
        <!-- Events & Activity Section -->
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
                        <?php if (!$hasMembership): ?>
                        <div class="text-center py-4">
                            <div class="mb-4">
                                <i class="fas fa-calendar-heart fa-4x text-warning mb-3"></i>
                            </div>
                            <h5 class="fw-bold text-primary mb-3">🎪 Amazing Events Await!</h5>
                            <p class="text-muted mb-3">Join groups and get membership to discover exciting events in your area!</p>
                            
                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <div class="bg-light rounded p-2">
                                        <i class="fas fa-users text-success"></i>
                                        <small class="d-block">Networking Events</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-2">
                                        <i class="fas fa-gamepad text-info"></i>
                                        <small class="d-block">Gaming Nights</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-2">
                                        <i class="fas fa-music text-purple"></i>
                                        <small class="d-block">Music & Arts</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light rounded p-2">
                                        <i class="fas fa-hiking text-success"></i>
                                        <small class="d-block">Outdoor Adventures</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="<?php echo BASE_URL; ?>/membership.php" class="btn btn-warning fw-bold">
                                    <i class="fas fa-crown me-2"></i>Get Membership & Start Exploring!
                                </a>
                                <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-outline-primary">
                                    <i class="fas fa-eye me-2"></i>Preview Available Events
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No upcoming events</h6>
                            <p class="text-muted">Check out our events page to find something interesting!</p>
                            <a href="<?php echo BASE_URL; ?>/events.php" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Browse Events
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                        <div class="d-flex align-items-center border-bottom py-3">
                            <div class="event-date me-3">
                                <div><?php echo formatDate($event['event_date'], 'M'); ?></div>
                                <div class="fs-4"><?php echo formatDate($event['event_date'], 'd'); ?></div>
                            </div>
                            
                            <!-- Event Image -->
                            <div class="me-3">
                                <?php if (!empty($event['cover_image'])): ?>
                                    <img src="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($event['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($event['title']); ?>" 
                                         class="rounded" 
                                         style="width: 50px; height: 50px; object-fit: cover; border: 2px solid #e9ecef;">
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px; border: 2px solid #e9ecef;">
                                        <i class="fas fa-calendar-alt text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="<?php echo BASE_URL; ?>/event-detail.php?slug=<?php echo $event['slug']; ?>&from=dashboard" 
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
                                    <?php echo date('g:i A', strtotime($event['start_time'])); ?>
                                    <?php if ($event['attendee_count'] > 0): ?>
                                        • <?php echo $event['attendee_count']; ?> attending
                                    <?php endif; ?>
                                </small>
                            </div>
                            
                            <div class="ms-3">
                                <span class="badge bg-primary rounded-pill"><?php echo $event['attendee_count']; ?> attending</span>
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
            <!-- Sidebar Ad -->
            <?php if (ADS_SIDEBAR): ?>
            <div class="card mb-4">
                <div class="card-body text-center p-2">
                    <?php echo getAdCode('sidebar'); ?>
                </div>
            </div>
            <?php endif; ?>
            
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
                                <span class="badge bg-<?php echo $group['role'] === 'owner' ? 'warning' : ($group['role'] === 'co_host' ? 'info' : ($group['role'] === 'moderator' ? 'success' : 'primary')); ?>">
                                    <?php 
                                    $roleDisplay = ucfirst($group['role']);
                                    if ($group['role'] === 'owner') {
                                        $roleDisplay = '<i class="fas fa-crown me-1"></i>' . $roleDisplay;
                                    } elseif ($group['role'] === 'co_host') {
                                        $roleDisplay = '<i class="fas fa-star me-1"></i>Co-Host';
                                    } elseif ($group['role'] === 'moderator') {
                                        $roleDisplay = '<i class="fas fa-shield me-1"></i>' . $roleDisplay;
                                    }
                                    echo $roleDisplay;
                                    ?>
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