<?php
// === INITIALIZATION ===
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/config/ads.php';
require_once __DIR__ . '/src/models/User.php';
require_once __DIR__ . '/src/models/Group.php';
require_once __DIR__ . '/src/models/Event.php';

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
        $visibleEvents = $eventModel->getUpcoming(5);
    } else {
        $visibleEvents = [];
    }
}

$isNewUser        = strtotime($currentUser['created_at']) > strtotime('-1 day');
$needsOnboarding  = empty($userGroups) && !$hasMembership;

// === HERO PHOTOS (placeholders for now; swap with your own later) ===
$heroPhotos = [
    'https://picsum.photos/seed/blue-mountains/1600/1066',
    'https://picsum.photos/seed/katoomba/1600/1066',
    'https://picsum.photos/seed/three-sisters/1600/1066',
    'https://picsum.photos/seed/wentworth-falls/1600/1066',
    'https://picsum.photos/seed/grose-valley/1600/1066',
    'https://picsum.photos/seed/megalong/1600/1066',
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

include __DIR__ . '/src/views/dashboard/index.php';

