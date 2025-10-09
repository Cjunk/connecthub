<?php
/**
 * ConnectHub - Event Detail Page (Lean + Fixed)
 */

session_start();

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/bootstrap.php';                // ensure autoload/DB/helpers
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/Event.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/controllers/CommentController.php';

$eventModel = new Event();
$userModel  = new User();
$commentController = new CommentController();

$slug = $_GET['slug'] ?? '';
if (!$slug) { header('Location: /events.php'); exit; }

$event = $eventModel->getBySlug($slug);
if (!$event) { $_SESSION['error'] = "Event not found."; header('Location: /events.php'); exit; }

$userId            = $_SESSION['user_id'] ?? null;
$userHasMembership = $userId ? $userModel->hasMembership($userId) : false;
$userRsvp          = $userId ? $eventModel->getUserRSVP($event['id'], $userId) : null;
$canManage         = $userId ? $eventModel->canManageEvent($event['id'], $userId) : false;

$dt  = new DateTime("{$event['event_date']} {$event['start_time']}");
$now = new DateTime();
$isUpcoming = $dt > $now;
$isPast     = $dt < $now;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rsvp') {
    if (!$userId) { $_SESSION['error'] = "Please log in to RSVP to events."; header('Location: /login.php'); exit; }
    if (!$userHasMembership) { $_SESSION['error'] = "Membership required to RSVP to events. Please upgrade to continue."; header('Location: /membership.php'); exit; }
    $status = $_POST['rsvp_status'] ?? 'going';
    $notes  = trim($_POST['notes'] ?? '');
    $ok = $eventModel->rsvp($event['id'], $userId, $status, $notes);
    $_SESSION[$ok ? 'success' : 'error'] = $ok ? "Your RSVP has been updated!" : "Failed to update RSVP. Please try again.";
    header("Location: /event-detail.php?slug=".$slug); exit;
}

$attendees = $eventModel->getAttendees($event['id'], 'going');
$maybe     = $eventModel->getAttendees($event['id'], 'maybe');

$pageTitle = htmlspecialchars($event['title']);
require_once __DIR__ . '/../src/views/layouts/header.php';
?>

<div class="container mt-4">
    <!-- Flash messages -->
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- Header + Back -->
    <div class="d-flex align-items-center mb-4">
        <?php $from = $_GET['from'] ?? 'group'; ?>
        <?php if ($from === 'dashboard'): ?>
            <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/group-detail.php?slug=<?= htmlspecialchars($event['group_slug']) ?>" class="btn btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i> Back to Group
            </a>
        <?php endif; ?>

        <div class="flex-grow-1">
            <h1 class="mb-1"><?= $pageTitle ?></h1>
            <p class="text-muted mb-0">Organized by <strong><?= htmlspecialchars($event['group_name']) ?></strong></p>
        </div>

        <?php if ($canManage): ?>
            <div class="dropdown">
                <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-cog"></i> Manage</button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="/edit-event.php?id=<?= $event['id'] ?>"><i class="fas fa-edit"></i> Edit Event</a></li>
                    <li><a class="dropdown-item" href="/event-attendees.php?id=<?= $event['id'] ?>"><i class="fas fa-users"></i> View Attendees</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="confirmCancelEvent()"><i class="fas fa-times-circle"></i> Cancel Event</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Main -->
        <div class="col-lg-8">
            <?php if ($event['status'] === 'cancelled'): ?>
                <div class="alert alert-danger mb-4"><i class="fas fa-exclamation-triangle"></i> This event has been cancelled.</div>
            <?php elseif ($isPast): ?>
                <div class="alert alert-info mb-4"><i class="fas fa-check-circle"></i> This event has ended.</div>
            <?php endif; ?>

            <div class="card shadow-sm mb-4">
                <?php if ($event['cover_image']): ?>
                    <img src="<?= BASE_URL . '/' . htmlspecialchars($event['cover_image']) ?>" alt="<?= $pageTitle ?>" class="img-fluid w-100 rounded-top" style="height:300px;object-fit:cover;">
                <?php endif; ?>
                <div class="card-body">
                    <!-- Date/time -->
                    <div class="d-flex mb-4">
                        <div class="p-3 text-white bg-primary rounded text-center me-3">
                            <div class="fw-bold"><?= $dt->format('M') ?></div>
                            <div class="h4 mb-0"><?= $dt->format('d') ?></div>
                        </div>
                        <div>
                            <h5><i class="fas fa-calendar text-primary"></i> <?= $dt->format('l, F j, Y') ?></h5>
                            <p class="text-muted mb-0">
                                <i class="fas fa-clock text-primary"></i>
                                <?= date('g:i A', strtotime($event['start_time'])) ?>
                                <?= $event['end_time'] ? ' - ' . date('g:i A', strtotime($event['end_time'])) : '' ?>
                            </p>
                            <small class="text-muted"><?= htmlspecialchars($event['timezone'] ?? 'America/Phoenix') ?></small>
                        </div>
                    </div>

                    <!-- Location -->
                    <h5 class="mb-2"><i class="fas fa-map-marker-alt text-primary"></i> Location</h5>
                    <?php
                        $locType = $event['location_type'];
                        $venue   = htmlspecialchars($event['venue_name'] ?? '');
                        $addr    = htmlspecialchars($event['venue_address'] ?? '');
                        $link    = htmlspecialchars($event['online_link'] ?? '');
                    ?>
                    <?php if ($locType === 'online'): ?>
                        <p class="mb-1"><span class="badge bg-info">Online Event</span></p>
                        <?php if ($link && $userRsvp && $userRsvp['status'] === 'going'): ?>
                            <a href="<?= $link ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fas fa-video"></i> Join Online</a>
                        <?php elseif ($link): ?>
                            <p class="text-muted"><i class="fas fa-lock"></i> Online link available to confirmed attendees</p>
                        <?php endif; ?>
                    <?php elseif ($locType === 'hybrid'): ?>
                        <p class="mb-1"><span class="badge bg-warning">Hybrid Event</span></p>
                        <p class="mb-1"><strong><?= $venue ?></strong><br><?= $addr ?></p>
                        <?php if ($link && $userRsvp && $userRsvp['status'] === 'going'): ?>
                            <a href="<?= $link ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="fas fa-video"></i> Join Online</a>
                        <?php elseif ($link): ?>
                            <p class="text-muted"><i class="fas fa-lock"></i> Online link available to confirmed attendees</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="mb-1"><span class="badge bg-success">In-Person Event</span></p>
                        <p class="mb-0"><strong><?= $venue ?></strong><br><?= $addr ?></p>
                    <?php endif; ?>

                    <!-- Description / Requirements -->
                    <?php if (!empty($event['description'])): ?>
                        <div class="mt-4">
                            <h5 class="mb-2"><i class="fas fa-info-circle text-primary"></i> About This Event</h5>
                            <div class="event-description"><?= nl2br(htmlspecialchars($event['description'])) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($event['requirements'])): ?>
                        <div class="mt-4">
                            <h5 class="mb-2"><i class="fas fa-list-check text-primary"></i> What to Bring / Requirements</h5>
                            <div class="alert alert-light"><?= nl2br(htmlspecialchars($event['requirements'])) ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Tags -->
                    <?php if (!empty($event['tags'])): ?>
                        <div class="mt-4">
                            <h6 class="mb-2">Tags:</h6>
                            <?php foreach (str_getcsv($event['tags'], ',','"') as $tag): ?>
                                <span class="badge bg-secondary me-1"><?= htmlspecialchars(trim($tag, '{}')) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comments Section -->
            <?php if ($userId && $userHasMembership): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-comments text-primary"></i> Event Discussion</h5>
                    </div>
                    <div class="card-body">
                        <div id="comments-container">
                            <?php /* IMPORTANT: echo the HTML returned by controller */ ?>
                            <?= $commentController->renderComments($event['id']); ?>
                        </div>
                    </div>
                </div>
            <?php elseif (!$userId): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-comments fa-2x text-muted mb-3"></i>
                        <h5>Join the Discussion</h5>
                        <p class="text-muted">Please log in to participate in event discussions.</p>
                        <a href="/login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Log In</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <i class="fas fa-crown fa-2x text-warning mb-3"></i>
                        <h5>Membership Required</h5>
                        <p class="text-muted">Event discussions require an active membership.</p>
                        <a href="/membership.php" class="btn btn-warning"><i class="fas fa-star"></i> Get Membership</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- RSVP -->
            <?php if ($isUpcoming && $event['status'] !== 'cancelled'): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <?php if (!$userId): ?>
                            <h5 class="card-title">Join This Event</h5>
                            <p class="text-muted">Please log in to RSVP to this event.</p>
                            <a href="/login.php" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt"></i> Log In to RSVP</a>
                        <?php elseif (!$userHasMembership): ?>
                            <h5 class="card-title">Membership Required</h5>
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-crown me-2"></i><strong>Premium Feature</strong><br>
                                <small>RSVP to events requires an active membership.</small>
                            </div>
                            <a href="/membership.php" class="btn btn-warning w-100"><i class="fas fa-star me-2"></i>Get Membership - $100/year</a>
                        <?php else: ?>
                            <h5 class="card-title">Your RSVP</h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="rsvp">
                                <?php
                                    $iconMap = ['going'=>'check', 'maybe'=>'question', 'not_going'=>'times'];
                                    foreach (['going'=>'success','maybe'=>'warning','not_going'=>'danger'] as $val=>$color):
                                        $checked = ($userRsvp && $userRsvp['status'] === $val) ? 'checked' : '';
                                        $icon = $iconMap[$val];
                                ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="rsvp_status" id="rsvp_<?= $val ?>" value="<?= $val ?>" <?= $checked ?>>
                                        <label class="form-check-label fw-bold text-<?= $color ?>" for="rsvp_<?= $val ?>">
                                            <i class="fas fa-<?= $icon ?>-circle"></i> <?= $val === 'not_going' ? "Can't go" : ucfirst(str_replace('_',' ',$val)) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes (optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any special requirements or notes..."><?= $userRsvp ? htmlspecialchars($userRsvp['notes']) : '' ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-calendar-check"></i> Update RSVP</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Event Stats -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Event Details</h5>
                    <?php
                        $stats = [
                            'Attendees'        => "<span class='text-success'>{$event['attendee_count']} going</span>",
                            'Maybe attending'  => !empty($event['maybe_count']) ? "<span class='text-warning'>{$event['maybe_count']}</span>" : '',
                            'Spots available'  => !empty($event['max_attendees']) ? max(0, $event['max_attendees'] - $event['attendee_count']) : '',
                            'Price'            => $event['price'] > 0 ? '$'.number_format($event['price'],2) : 'Free',
                            'Organizer'        => htmlspecialchars($event['organizer_name']),
                        ];
                        foreach ($stats as $label => $val) {
                            if ($val !== '' && $val !== null) {
                                echo "<div class='d-flex justify-content-between mb-2'><span>$label:</span><strong>$val</strong></div>";
                            }
                        }
                    ?>
                </div>
            </div>

            <!-- Attendees -->
            <?php if (!empty($attendees)): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Attendees (<?= count($attendees) ?>)</h5>
                        <?php foreach (array_slice($attendees, 0, 10) as $a): ?>
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                     style="width:32px;height:32px;font-size:14px;">
                                    <?= strtoupper(substr($a['name'], 0, 1)) ?>
                                </div>
                                <span class="small"><?= htmlspecialchars($a['name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($attendees) > 10): ?>
                            <div class="small text-muted">And <?= count($attendees) - 10 ?> more...</div>
                        <?php endif; ?>
                        <?php if ($canManage): ?>
                            <a href="/event-attendees.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-primary w-100 mt-3">View All Attendees</a>
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
        window.location.href = '/cancel-event.php?id=<?= $event['id'] ?>';
    }
}
</script>
<?php endif; ?>

<?php if ($userId && $userHasMembership): ?>
<script>
$(document).ready(function() {
    // Submit comment
    $(document).on('submit', '.comment-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const formData = new FormData(this);
        formData.append('action', 'submit_comment');

        $.ajax({
            url: 'api/comments.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('AJAX Success:', response);
                if (response.success) {
                    $('#comments-container').html(response.html);
                    form.find('textarea').val('');
                    form.find('input[type="file"]').val('');
                } else {
                    alert('Error: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.status, xhr.responseText, status, error);
                alert('Failed to submit comment. Please try again.');
            }
        });
    });

    // Toggle like
    $(document).on('click', '.like-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const formData = new FormData();
        formData.append('action', 'toggle_like');
        formData.append('comment_id', btn.data('comment-id'));

        $.post('api/comments.php', formData, function(response) {
            if (response.success) {
                btn.find('.like-count').text(response.like_count);
                btn.toggleClass('text-danger', response.user_liked);
            } else {
                alert('Error: ' + (response.message || 'Unknown error'));
            }
        }, 'json');
    });

    // Delete comment
    $(document).on('click', '.delete-comment', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this comment?')) return;
        const commentId = $(this).data('comment-id');
        const formData = new FormData();
        formData.append('action', 'delete_comment');
        formData.append('comment_id', commentId);

        $.post('api/comments.php', formData, function(response) {
            if (response.success) {
                $('#comments-container').html(response.html);
            } else {
                alert('Error: ' + (response.message || 'Unknown error'));
            }
        }, 'json');
    });

    // Show reply form
    $(document).on('click', '.reply-btn', function(e) {
        e.preventDefault();
        const id = $(this).data('comment-id');
        $('.reply-form').not('#reply-form-' + id).slideUp();
        $('#reply-form-' + id).slideToggle();
    });

    // File upload preview
    $(document).on('change', '.media-upload', function() {
        const file = this.files[0];
        const preview = $(this).siblings('.file-preview');
        if (!file) { preview.empty(); return; }

        const reader = new FileReader();
        reader.onload = function(e) {
            if (file.type.startsWith('image/')) {
                preview.html('<img src="' + e.target.result + '" class="img-thumbnail" style="max-width:200px;">');
            } else {
                preview.html('<div class="alert alert-info"><i class="fas fa-file"></i> ' + file.name + '</div>');
            }
        };
        reader.readAsDataURL(file);
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../src/views/layouts/footer.php'; ?>

