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

// Fallback to public/published upcoming events so non-members still see events
$visibleEvents = $upcomingEvents;
if (empty($visibleEvents)) {
    if (method_exists($eventModel, 'getUpcomingPublic')) {
        $visibleEvents = $eventModel->getUpcomingPublic(5);
    } elseif (method_exists($eventModel, 'getUpcoming')) {
        // Assumes this returns only published/public events
        $visibleEvents = $eventModel->getUpcoming(5);
    } else {
        $visibleEvents = [];
    }
}

$isNewUser        = strtotime($currentUser['created_at']) > strtotime('-1 day');
$needsOnboarding  = empty($userGroups) && !$hasMembership;

// === HERO PHOTOS (placeholders for now; swap with your own later) ===
$heroPhotos = [
    'https://picsum.photos/seed/blue-mountains/1200/800',
    'https://picsum.photos/seed/katoomba/1200/800',
    'https://picsum.photos/seed/three-sisters/1200/800',
    'https://picsum.photos/seed/wentworth-falls/1200/800',
    'https://picsum.photos/seed/grose-valley/1200/800',
    'https://picsum.photos/seed/megalong/1200/800',
];

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

    <!-- HERO: Photo Pile -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
          <h3 class="mb-2 fw-semibold">Explore the Blue Mountains</h3>
          <a href="<?= BASE_URL ?>/events.php" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-calendar-alt me-1"></i> Find an adventure
          </a>
        </div>

        <div class="photo-pile">
          <?php foreach ($heroPhotos as $i => $src): ?>
            <a href="<?= htmlspecialchars($src) ?>" target="_blank" rel="noopener"
               class="polaroid p<?= $i+1 ?>" aria-label="Open photo <?= $i+1 ?>">
              <img src="<?= htmlspecialchars($src) ?>"
                   alt="Scenic photo <?= $i+1 ?>"
                   loading="lazy" decoding="async">
              <span class="caption">Outdoors • NSW</span>
            </a>
          <?php endforeach; ?>
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
                    <?php if (empty($visibleEvents)): ?>
                        <?php emptyBlock('calendar-times','No upcoming events','Check out our events page for more.',BASE_URL.'/events.php','Browse Events'); ?>
                    <?php else: ?>
                        <?php foreach ($visibleEvents as $event): ?>
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
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i><?= htmlspecialchars($event['group_name'] ?? 'ConnectHub') ?>
                                        • <?= date('g:i A', strtotime($event['start_time'])); ?>
                                    </small>
                                </div>
                                <?php if (isset($event['attendee_count'])): ?>
                                    <span class="badge bg-primary rounded-pill ms-3"><?= (int)$event['attendee_count']; ?> attending</span>
                                <?php endif; ?>
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

<style>
/* --- Photo pile styling --- */
.photo-pile{
  display:grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap:18px;
  justify-items:center;
}
.polaroid{
  position:relative;
  display:block;
  width:180px;
  aspect-ratio: 4/3;
  background:#fff;
  padding:8px 8px 26px;
  border-radius:8px;
  box-shadow:0 8px 24px rgba(0,0,0,.12);
  transform-origin:center center;
  transition:transform .18s ease, box-shadow .18s ease;
  text-decoration:none;
}
.polaroid img{
  width:100%; height:100%;
  object-fit:cover; object-position:center;
  border-radius:4px;
  display:block;
}
.polaroid .caption{
  position:absolute; left:10px; right:10px; bottom:6px;
  font-size:.8rem; color:#555;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
/* “Pile” look via slight rotations/offsets */
.polaroid.p1{ transform:rotate(-5deg) translateY(4px); }
.polaroid.p2{ transform:rotate(3deg) translateY(-6px); }
.polaroid.p3{ transform:rotate(-2deg) translateY(2px); }
.polaroid.p4{ transform:rotate(6deg) translateY(-4px); }
.polaroid.p5{ transform:rotate(-4deg) translateY(3px); }
.polaroid.p6{ transform:rotate(2deg) translateY(-5px); }
/* Hover brings card to focus */
@media (hover:hover){
  .polaroid:hover{
    transform:rotate(0) scale(1.03);
    z-index:2;
    box-shadow:0 12px 30px rgba(0,0,0,.18);
  }
}
/* Responsive sizing */
@media (max-width: 576px){
  .polaroid{ width: 44vw; }
}
@media (min-width: 1200px){
  .polaroid{ width: 200px; }
}
</style>

<?php include '../src/views/layouts/footer.php'; ?>
