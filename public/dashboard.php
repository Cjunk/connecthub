<?php
// === INITIALIZATION ===
require_once '../config/constants.php';
require_once '../config/bootstrap.php';
require_once '../config/ads.php';
require_once '../src/models/User.php';
require_once '../src/models/Group.php';
require_once '../src/models/Event.php';

// === AUTH CHECK ===
if (!isLoggedIn()) redirect(BASE_URL . '/login.php');

// === DATA ===
$currentUser    = getCurrentUser();
$userModel      = new User();
$groupModel     = new Group();
$eventModel     = new Event();

$userGroups     = $groupModel->getUserGroups($currentUser['id']);
$hasMembership  = $userModel->hasMembership($currentUser['id']);
$upcomingEvents = $eventModel->getUpcomingForUser($currentUser['id'], 5);

$isNewUser      = strtotime($currentUser['created_at']) > strtotime('-1 day');
$needsOnboarding = empty($userGroups) && !$hasMembership;

// === HELPERS ===
function roleBadge(string $role): string {
    return match($role) {
        'owner'     => '<span class="badge bg-warning"><i class="fas fa-crown me-1"></i>Owner</span>',
        'co_host'   => '<span class="badge bg-info"><i class="fas fa-star me-1"></i>Co-Host</span>',
        'moderator' => '<span class="badge bg-success"><i class="fas fa-shield me-1"></i>Moderator</span>',
        default     => '<span class="badge bg-primary">Member</span>'
    };
}

function emptyBlock($icon, $title, $text, $btnLink, $btnLabel, $btnClass='primary') {
    echo <<<HTML
    <div class="text-center py-4">
        <i class="fas fa-$icon fa-3x text-$btnClass mb-3"></i>
        <h6 class="text-muted">$title</h6>
        <p class="text-muted">$text</p>
        <a href="$btnLink" class="btn btn-$btnClass">
            <i class="fas fa-search me-2"></i>$btnLabel
        </a>
    </div>
    HTML;
}

include '../src/views/layouts/header.php';
?>

<div class="container">

    <!-- === WELCOME === -->
    <div class="alert alert-info border-0 shadow-sm mb-4 rounded-4" style="background:linear-gradient(135deg,#e3f2fd 0%,#f3e5f5 100%)">
        <div class="d-flex align-items-center">
            <img src="<?= BASE_URL; ?>/assets/images/welcome-banner.png"
                 alt="Welcome Banner" width="60" height="60"
                 class="rounded-circle me-3" style="object-fit:cover;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.1)"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
            <i class="fas fa-user-circle fa-2x text-primary" style="display:none"></i>
            <div>
                <h4 class="fw-bold text-primary mb-1">
                    Welcome back, <?= htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?>!
                </h4>
                <p class="text-muted mb-0">Ready to connect with your community today?</p>
            </div>
        </div>
    </div>

    <?php if ($needsOnboarding): ?>
    <!-- === ONBOARDING === -->
    <?php
        $steps = [
            ['color'=>'warning','icon'=>'crown','title'=>'Get Membership','desc'=>'Unlock premium features and RSVP to unlimited events','link'=>'/membership.php','btn'=>'Get Started - $100/year'],
            ['color'=>'success','icon'=>'users','title'=>'Join Groups','desc'=>'Find communities that match your interests','link'=>'/groups.php','btn'=>'Discover Groups'],
            ['color'=>'primary','icon'=>'calendar-plus','title'=>'Attend Events','desc'=>'RSVP to exciting events and meet like-minded people','link'=>'/events.php','btn'=>'Browse Events']
        ];
    ?>
    <div class="card border-0 shadow-sm mb-4 rounded-4" style="background:linear-gradient(135deg,#fff3cd 0%,#d1ecf1 100%)">
        <div class="card-body p-4 text-center">
            <i class="fas fa-rocket fa-3x text-warning mb-3"></i>
            <h3 class="fw-bold text-dark">🎉 Welcome to ConnectHub!</h3>
            <p class="lead text-muted mb-4">You're just 3 steps away from connecting with amazing communities!</p>

            <div class="row g-3">
                <?php foreach ($steps as $i => $s): ?>
                    <div class="col-md-4">
                        <div class="card border-0 h-100" style="background:rgba(255,255,255,0.8)">
                            <div class="card-body text-center p-3">
                                <div class="mb-3">
                                    <div class="bg-<?= $s['color']; ?> rounded-circle d-inline-flex align-items-center justify-content-center" style="width:60px;height:60px">
                                        <i class="fas fa-<?= $s['icon']; ?> fa-2x text-white"></i>
                                    </div>
                                </div>
                                <h5 class="fw-bold">Step <?= $i+1; ?>: <?= $s['title']; ?></h5>
                                <p class="small text-muted mb-3"><?= $s['desc']; ?></p>
                                <a href="<?= BASE_URL . $s['link']; ?>" class="btn btn-sm btn-<?= $s['color']; ?> fw-bold">
                                    <i class="fas fa-star me-1"></i><?= $s['btn']; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4">
                <p class="small text-muted mb-2"><i class="fas fa-info-circle me-1"></i>Need help? <a href="#" class="text-decoration-none">Quick tour</a></p>
                <div class="progress rounded-pill" style="height:8px"><div class="progress-bar bg-warning" style="width:33%"></div></div>
                <small class="text-muted">1 of 3 steps completed – Account created ✅</small>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- === STATS & ACTIONS === -->
    <?php
    $stats = [
        ['icon'=>'users','color'=>'primary','value'=>count($userGroups),'label'=>'Groups Joined'],
        ['icon'=>'calendar-check','color'=>'success','value'=>0,'label'=>'Events Attended'],
        ['icon'=>'handshake','color'=>'info','value'=>0,'label'=>'Connections Made'],
        ['icon'=>'star','color'=>'warning','value'=>0,'label'=>'Reviews Given'],
    ];
    ?>
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="row">
                <?php foreach ($stats as $s): ?>
                <div class="col-6 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-2">
                            <i class="fas fa-<?= $s['icon']; ?> fa-lg text-<?= $s['color']; ?> mb-1"></i>
                            <h5 class="mb-0"><?= $s['value']; ?></h5>
                            <small class="text-muted"><?= $s['label']; ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-bolt me-1"></i>Quick Actions</h6></div>
                <div class="card-body p-2 d-grid gap-1">
                    <?php if ($hasMembership): ?>
                        <a href="<?= BASE_URL; ?>/events.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Find Events</a>
                        <a href="<?= BASE_URL; ?>/groups.php" class="btn btn-sm btn-outline-success"><i class="fas fa-users me-1"></i>Browse Groups</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL; ?>/membership.php" class="btn btn-sm btn-warning"><i class="fas fa-crown me-1"></i>Get Membership</a>
                        <a href="<?= BASE_URL; ?>/events.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-search me-1"></i>Browse Events</a>
                        <a href="<?= BASE_URL; ?>/groups.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-users me-1"></i>Browse Groups</a>
                    <?php endif; ?>

                    <?php if (isOrganizer() && $hasMembership): ?>
                        <a href="<?= BASE_URL; ?>/create-group.php" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Create Group</a>
                    <?php endif; ?>

                    <?php if (isAdmin()): ?>
                        <a href="<?= BASE_URL; ?>/under-construction.php" class="btn btn-sm btn-danger"><i class="fas fa-shield-alt me-1"></i>Admin Panel</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- AD SPACE -->
        <div class="col-lg-3">
            <div class="card h-100 d-flex align-items-center justify-content-center p-2">
                <?= getAdCode('dashboard'); ?>
            </div>
        </div>
    </div>

    <!-- === MAIN CONTENT === -->
    <div class="row">
        <!-- LEFT: EVENTS & ACTIVITY -->
        <div class="col-lg-8">
            <!-- UPCOMING EVENTS -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Events</h5>
                    <a href="<?= BASE_URL; ?>/events.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingEvents)): ?>
                        <?php if (!$hasMembership): ?>
                            <?php emptyBlock('calendar-heart','🎪 Amazing Events Await!','Join groups and get membership to discover events!',BASE_URL.'/membership.php','Get Membership & Start Exploring!','warning'); ?>
                        <?php else: ?>
                            <?php emptyBlock('calendar-times','No upcoming events','Check out our events page for more.',BASE_URL.'/events.php','Browse Events'); ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <div class="d-flex align-items-center border-bottom py-3">
                                <div class="text-center me-3">
                                    <div><?= formatDate($event['event_date'],'M'); ?></div>
                                    <div class="fs-4"><?= formatDate($event['event_date'],'d'); ?></div>
                                </div>
                                <img src="<?= !empty($event['cover_image']) ? BASE_URL.'/'.htmlspecialchars($event['cover_image']) : BASE_URL.'/assets/images/default-event.png'; ?>"
                                     alt="<?= htmlspecialchars($event['title']); ?>"
                                     class="rounded me-3" width="50" height="50" style="object-fit:cover;border:2px solid #e9ecef">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="<?= BASE_URL; ?>/event-detail.php?slug=<?= $event['slug']; ?>&from=dashboard" class="text-decoration-none">
                                            <?= htmlspecialchars($event['title']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted"><i class="fas fa-users me-1"></i><?= htmlspecialchars($event['group_name']); ?> • <?= date('g:i A', strtotime($event['start_time'])); ?></small>
                                </div>
                                <span class="badge bg-primary rounded-pill ms-3"><?= $event['attendee_count']; ?> attending</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RECENT ACTIVITY -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-activity me-2"></i>Recent Activity</h5></div>
                <div class="card-body text-center py-4">
                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No recent activity</h6>
                    <p class="text-muted">Join groups and attend events to see your activity here!</p>
                </div>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR -->
        <div class="col-lg-4">
            <?php if (ADS_SIDEBAR): ?>
                <div class="card mb-4"><div class="card-body text-center p-2"><?= getAdCode('sidebar'); ?></div></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Your Groups</h5>
                    <a href="<?= BASE_URL; ?>/groups.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($userGroups)): ?>
                        <?php emptyBlock('user-friends','No groups joined yet','Join groups to connect with like-minded people!',BASE_URL.'/groups.php','Browse Groups'); ?>
                    <?php else: ?>
                        <?php foreach (array_slice($userGroups,0,3) as $g): ?>
                            <div class="d-flex align-items-center border-bottom py-3">
                                <img src="<?= htmlspecialchars($g['cover_image'] ?: BASE_URL.'/assets/images/default-group.png'); ?>"
                                     alt="<?= htmlspecialchars($g['name']); ?>"
                                     class="rounded-circle me-3" width="50" height="50" style="object-fit:cover">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="<?= BASE_URL; ?>/group-detail.php?slug=<?= $g['slug']; ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($g['name']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted"><i class="fas fa-users me-1"></i><?= number_format($g['member_count']); ?> members</small>
                                </div>
                                <?= roleBadge($g['role']); ?>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($userGroups) > 3): ?>
                            <div class="text-center pt-3">
                                <a href="<?= BASE_URL; ?>/groups.php" class="btn btn-sm btn-outline-primary">
                                    View All <?= count($userGroups); ?> Groups
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
