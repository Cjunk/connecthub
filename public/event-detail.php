<?php
/**
 * Event Detail Page
 * Display detailed information about an event and handle RSVPs
 */

session_start();
require_once __DIR__ . '/../src/models/Event.php';
require_once __DIR__ . '/../src/models/Group.php';
require_once __DIR__ . '/../src/helpers/functions.php';

$event = new Event();
$group = new Group();
$errors = [];
$success = '';

// Get event slug from URL
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: /connecthub/public/events.php');
    exit;
}

// Get event details
$eventData = $event->getBySlug($slug);
if (!$eventData) {
    $_SESSION['error'] = "Event not found.";
    header('Location: /connecthub/public/events.php');
    exit;
}

// Get user's RSVP status if logged in
$userRsvp = null;
if (isset($_SESSION['user_id'])) {
    $userRsvp = $event->getUserRSVP($eventData['id'], $_SESSION['user_id']);
}

// Handle RSVP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rsvp') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Please log in to RSVP to events.";
        header('Location: /connecthub/public/login.php');
        exit;
    }
    
    $rsvpStatus = $_POST['rsvp_status'] ?? 'going';
    $notes = trim($_POST['notes'] ?? '');
    
    if ($event->rsvp($eventData['id'], $_SESSION['user_id'], $rsvpStatus, $notes)) {
        $_SESSION['success'] = "Your RSVP has been updated!";
        header('Location: /connecthub/public/event-detail.php?slug=' . $slug);
        exit;
    } else {
        $errors[] = "Failed to update RSVP. Please try again.";
    }
}

// Get event attendees
$attendees = $event->getAttendees($eventData['id'], 'going');
$maybeAttendees = $event->getAttendees($eventData['id'], 'maybe');

// Check if user can manage this event
$canManage = false;
if (isset($_SESSION['user_id'])) {
    $canManage = $event->canManageEvent($eventData['id'], $_SESSION['user_id']);
}

// Format date and time
$eventDateTime = new DateTime($eventData['event_date'] . ' ' . $eventData['start_time']);
$isUpcoming = $eventDateTime > new DateTime();
$isPast = $eventDateTime < new DateTime();

$pageTitle = htmlspecialchars($eventData['title']);
require_once __DIR__ . '/../src/views/layouts/header.php';
?>

<div class="container mt-4">
    <!-- Event Header -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center mb-4">
                <a href="/connecthub/public/group-detail.php?slug=<?= htmlspecialchars($eventData['group_slug']) ?>" 
                   class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left"></i> Back to Group
                </a>
                <div class="flex-grow-1">
                    <h1 class="mb-1"><?= htmlspecialchars($eventData['title']) ?></h1>
                    <p class="text-muted mb-0">
                        Organized by <strong><?= htmlspecialchars($eventData['group_name']) ?></strong>
                    </p>
                </div>
                <?php if ($canManage): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog"></i> Manage
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/connecthub/public/edit-event.php?id=<?= $eventData['id'] ?>">
                                <i class="fas fa-edit"></i> Edit Event
                            </a></li>
                            <li><a class="dropdown-item" href="/connecthub/public/event-attendees.php?id=<?= $eventData['id'] ?>">
                                <i class="fas fa-users"></i> View Attendees
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="confirmCancelEvent()">
                                <i class="fas fa-times-circle"></i> Cancel Event
                            </a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Error/Success Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Main Event Content -->
        <div class="col-lg-8">
            <!-- Event Status Badge -->
            <?php if ($eventData['status'] === 'cancelled'): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fas fa-exclamation-triangle"></i> This event has been cancelled.
                </div>
            <?php elseif ($isPast): ?>
                <div class="alert alert-info mb-4">
                    <i class="fas fa-check-circle"></i> This event has ended.
                </div>
            <?php endif; ?>

            <!-- Event Details Card -->
            <div class="card shadow-sm mb-4">
                <?php if ($eventData['cover_image']): ?>
                    <div class="card-img-top">
                        <img src="http://localhost/<?= htmlspecialchars($eventData['cover_image']) ?>" 
                             alt="<?= htmlspecialchars($eventData['title']) ?>" 
                             class="img-fluid w-100" style="height: 300px; object-fit: cover;">
                    </div>
                <?php endif; ?>
                <div class="card-body">
                    <!-- Date & Time -->
                    <div class="row mb-4">
                        <div class="col-auto">
                            <div class="event-date-badge text-center p-3 bg-primary text-white rounded">
                                <div class="fw-bold"><?= $eventDateTime->format('M') ?></div>
                                <div class="h4 mb-0"><?= $eventDateTime->format('d') ?></div>
                            </div>
                        </div>
                        <div class="col">
                            <h5 class="mb-1">
                                <i class="fas fa-calendar text-primary"></i> 
                                <?= $eventDateTime->format('l, F j, Y') ?>
                            </h5>
                            <p class="text-muted mb-1">
                                <i class="fas fa-clock text-primary"></i> 
                                <?= date('g:i A', strtotime($eventData['start_time'])) ?>
                                <?php if ($eventData['end_time']): ?>
                                    - <?= date('g:i A', strtotime($eventData['end_time'])) ?>
                                <?php endif; ?>
                            </p>
                            <small class="text-muted"><?= htmlspecialchars($eventData['timezone'] ?? 'America/Phoenix') ?></small>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="mb-4">
                        <h5 class="mb-2">
                            <i class="fas fa-map-marker-alt text-primary"></i> Location
                        </h5>
                        <?php if ($eventData['location_type'] === 'online'): ?>
                            <p class="mb-1"><span class="badge bg-info">Online Event</span></p>
                            <?php if ($eventData['online_link'] && $userRsvp && $userRsvp['status'] === 'going'): ?>
                                <a href="<?= htmlspecialchars($eventData['online_link']) ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-video"></i> Join Online
                                </a>
                            <?php elseif ($eventData['online_link']): ?>
                                <p class="text-muted"><i class="fas fa-lock"></i> Online link available to confirmed attendees</p>
                            <?php endif; ?>
                        <?php elseif ($eventData['location_type'] === 'hybrid'): ?>
                            <p class="mb-1"><span class="badge bg-warning">Hybrid Event</span></p>
                            <p class="mb-1">
                                <strong><?= htmlspecialchars($eventData['venue_name']) ?></strong><br>
                                <?= htmlspecialchars($eventData['venue_address']) ?>
                            </p>
                            <?php if ($eventData['online_link'] && $userRsvp && $userRsvp['status'] === 'going'): ?>
                                <a href="<?= htmlspecialchars($eventData['online_link']) ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-video"></i> Join Online
                                </a>
                            <?php elseif ($eventData['online_link']): ?>
                                <p class="text-muted"><i class="fas fa-lock"></i> Online link available to confirmed attendees</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="mb-1"><span class="badge bg-success">In-Person Event</span></p>
                            <p class="mb-0">
                                <strong><?= htmlspecialchars($eventData['venue_name']) ?></strong><br>
                                <?= htmlspecialchars($eventData['venue_address']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <?php if ($eventData['description']): ?>
                        <div class="mb-4">
                            <h5 class="mb-2">
                                <i class="fas fa-info-circle text-primary"></i> About This Event
                            </h5>
                            <div class="event-description">
                                <?= nl2br(htmlspecialchars($eventData['description'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Requirements -->
                    <?php if ($eventData['requirements']): ?>
                        <div class="mb-4">
                            <h5 class="mb-2">
                                <i class="fas fa-list-check text-primary"></i> What to Bring / Requirements
                            </h5>
                            <div class="alert alert-light">
                                <?= nl2br(htmlspecialchars($eventData['requirements'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tags -->
                    <?php if (!empty($eventData['tags'])): ?>
                        <div class="mb-4">
                            <h6 class="mb-2">Tags:</h6>
                            <?php 
                            $tags = str_getcsv($eventData['tags'], ',', '"');
                            foreach ($tags as $tag): 
                                $tag = trim($tag, '{}');
                            ?>
                                <span class="badge bg-secondary me-1"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- RSVP Card -->
            <?php if ($isUpcoming && $eventData['status'] !== 'cancelled'): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <h5 class="card-title">Join This Event</h5>
                            <p class="text-muted">Please log in to RSVP to this event.</p>
                            <a href="/connecthub/public/login.php" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt"></i> Log In to RSVP
                            </a>
                        <?php else: ?>
                            <h5 class="card-title">Your RSVP</h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="rsvp">
                                
                                <div class="mb-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="rsvp_status" 
                                               id="rsvp_going" value="going" 
                                               <?= ($userRsvp && $userRsvp['status'] === 'going') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold text-success" for="rsvp_going">
                                            <i class="fas fa-check-circle"></i> Going
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="rsvp_status" 
                                               id="rsvp_maybe" value="maybe" 
                                               <?= ($userRsvp && $userRsvp['status'] === 'maybe') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold text-warning" for="rsvp_maybe">
                                            <i class="fas fa-question-circle"></i> Maybe
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="rsvp_status" 
                                               id="rsvp_not_going" value="not_going" 
                                               <?= ($userRsvp && $userRsvp['status'] === 'not_going') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold text-danger" for="rsvp_not_going">
                                            <i class="fas fa-times-circle"></i> Can't go
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes (optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2" 
                                              placeholder="Any special requirements or notes..."><?= $userRsvp ? htmlspecialchars($userRsvp['notes']) : '' ?></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-calendar-check"></i> Update RSVP
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Event Stats -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Event Details</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Attendees:</span>
                        <strong class="text-success"><?= $eventData['attendee_count'] ?> going</strong>
                    </div>
                    
                    <?php if ($eventData['maybe_count'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Maybe attending:</span>
                            <strong class="text-warning"><?= $eventData['maybe_count'] ?></strong>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($eventData['max_attendees']): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Spots available:</span>
                            <strong><?= max(0, $eventData['max_attendees'] - $eventData['attendee_count']) ?></strong>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Price:</span>
                        <strong><?= $eventData['price'] > 0 ? '$' . number_format($eventData['price'], 2) : 'Free' ?></strong>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <span>Organizer:</span>
                        <strong><?= htmlspecialchars($eventData['organizer_name']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Attendees Preview -->
            <?php if (!empty($attendees)): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Attendees (<?= count($attendees) ?>)</h5>
                        
                        <div class="attendees-list">
                            <?php foreach (array_slice($attendees, 0, 10) as $attendee): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar-sm me-2">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 32px; height: 32px; font-size: 14px;">
                                            <?= strtoupper(substr($attendee['name'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <span class="small"><?= htmlspecialchars($attendee['name']) ?></span>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($attendees) > 10): ?>
                                <div class="small text-muted">
                                    And <?= count($attendees) - 10 ?> more...
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($canManage): ?>
                            <a href="/connecthub/public/event-attendees.php?id=<?= $eventData['id'] ?>" 
                               class="btn btn-sm btn-outline-primary w-100 mt-3">
                                View All Attendees
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<script>
function confirmCancelEvent() {
    if (confirm('Are you sure you want to cancel this event? This action cannot be undone.')) {
        // Add cancel event functionality here
        window.location.href = '/connecthub/public/cancel-event.php?id=<?= $eventData['id'] ?>';
    }
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../src/views/layouts/footer.php'; ?>