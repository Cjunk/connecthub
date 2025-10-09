<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';
require_once '../src/models/User.php';
require_once '../src/models/Group.php';
require_once '../src/models/Event.php';

if (!isLoggedIn()) redirect(BASE_URL . '/login.php');
$currentUser = getCurrentUser();
$hasValidMembership = hasValidMembership($currentUser);

$slug = $_GET['slug'] ?? '';
if (!$slug) redirect(BASE_URL . '/groups.php');

$groupModel = new Group();
$eventModel = new Event();
$group = $groupModel->getBySlug($slug);
if (!$group) {
    setFlashMessage('error', 'Group not found.');
    redirect(BASE_URL . '/groups.php');
}

$isMember = $groupModel->isMember($group['id'], $currentUser['id']);
$userRole = $groupModel->getUserRole($group['id'], $currentUser['id']);

// Join / Leave
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasValidMembership) {
    $action = $_POST['action'] ?? '';
    if ($action === 'join' && !$isMember) {
        if ($group['privacy_level'] === 'private') {
            $msg = $_POST['message'] ?? '';
            $ok = $groupModel->requestToJoin($group['id'], $currentUser['id'], $msg);
            setFlashMessage($ok ? 'success' : 'error', $ok ? 'Join request sent!' : 'Failed to send request.');
        } else {
            $ok = $groupModel->joinGroup($group['id'], $currentUser['id']);
            setFlashMessage($ok ? 'success' : 'error', $ok ? 'Successfully joined!' : 'Failed to join.');
        }
        redirect(BASE_URL . '/group-detail.php?slug=' . urlencode($slug));
    }

    if ($action === 'leave' && $isMember && $userRole !== 'owner') {
        $ok = $groupModel->leaveGroup($group['id'], $currentUser['id']);
        setFlashMessage($ok ? 'success' : 'error', $ok ? 'You have left the group.' : 'Failed to leave.');
        redirect(BASE_URL . '/group-detail.php?slug=' . urlencode($slug));
    }
}

$members = $groupModel->getMembers($group['id']);
$pageTitle = $group['name'];
include '../src/views/layouts/header.php';
?>

<div class="container mt-4 group-detail">
  <!-- Header -->
  <div class="d-flex align-items-center mb-4 flex-wrap">
    <a href="<?= BASE_URL ?>/groups.php" class="btn btn-outline-secondary me-3 mb-2">
      <i class="fas fa-arrow-left"></i> Back to Groups
    </a>
    <div>
      <h2 class="mb-1 fw-semibold text-forest"><?= htmlspecialchars($group['name']) ?></h2>
      <p class="text-muted mb-0"><?= htmlspecialchars($group['location'] ?: 'Online') ?></p>
    </div>
  </div>

  <!-- Banner -->
  <div class="rounded shadow-sm mb-4 overflow-hidden" style="height:300px;">
    <?php if ($group['cover_image']): ?>
      <img src="<?= htmlspecialchars($group['cover_image']) ?>" alt="Group cover"
           style="width:100%; height:100%; object-fit:cover; object-position:center;">
    <?php else: ?>
      <div class="placeholder-banner d-flex align-items-center justify-content-center h-100 text-muted">
        <i class="fas fa-mountain me-2"></i> Group image coming soon
      </div>
    <?php endif; ?>
  </div>

  <!-- Two Column Layout -->
  <div class="row">
    <!-- LEFT: Group Details -->
    <div class="col-lg-8">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
        <div class="d-flex flex-wrap align-items-center mb-3">
            <div class="me-4">
            <i class="fas fa-users text-primary me-1"></i>
            <strong><?= number_format($group['member_count']) ?> members</strong>
            </div>
            <?php if ($group['category_name']): ?>
            <span class="badge me-2" style="background-color:<?= $group['category_color'] ?: '#6c757d' ?>">
                <i class="<?= $group['category_icon'] ?> me-1"></i>
                <?= htmlspecialchars($group['category_name']) ?>
            </span>
            <?php endif; ?>
            <?php if ($group['privacy_level'] !== 'public'): ?>
            <span class="badge bg-warning text-dark">
                <i class="fas fa-lock me-1"></i><?= ucfirst($group['privacy_level']) ?>
            </span>
            <?php endif; ?>
        </div>

        <?php if ($group['description']): ?>
            <h5 class="text-forest">About This Group</h5>
            <p class="text-muted"><?= nl2br(htmlspecialchars($group['description'])) ?></p>
        <?php endif; ?>

        <?php if ($group['rules']): ?>
            <h5 class="text-forest mt-4">Group Rules</h5>
            <div class="bg-light p-3 rounded small"><?= nl2br(htmlspecialchars($group['rules'])) ?></div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Events Tabs -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-sandstone d-flex justify-content-between align-items-center">
          <ul class="nav nav-tabs card-header-tabs" id="eventTabs" role="tablist">
            <li class="nav-item">
              <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab"
                      data-bs-target="#upcoming" type="button" role="tab">Upcoming</button>
            </li>
            <li class="nav-item">
              <button class="nav-link" id="past-tab" data-bs-toggle="tab"
                      data-bs-target="#past" type="button" role="tab">Past</button>
            </li>
          </ul>
          <?php if (in_array($userRole, ['owner','co_host','moderator'])): ?>
            <a href="<?= BASE_URL ?>/create-event.php?group_id=<?= $group['id'] ?>"
               class="btn btn-sm btn-forest ms-2">
               <i class="fas fa-plus"></i> Create
            </a>
          <?php endif; ?>
        </div>
        <div class="card-body tab-content">
        <!-- Upcoming -->
         
        <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
            <?php
            $allEvents = $eventModel->getByGroupId($group['id'], true);
            $upcomingEvents = array_filter($allEvents, function($e) {
                return strtotime($e['event_date'] . ' ' . $e['start_time']) > time();
            });
            if (empty($upcomingEvents)):
            ?>
            <p class="text-muted mb-0">No upcoming adventures yet.</p>
            <?php else: ?>
            <?php foreach ($upcomingEvents as $e): ?>
                <div class="event-card d-flex align-items-center mb-3 p-2 border rounded hover-shadow">
                <div class="event-date text-center bg-forest text-white rounded me-3 px-3 py-2">
                    <div class="fw-bold"><?= date('M', strtotime($e['event_date'])) ?></div>
                    <div class="fs-5"><?= date('d', strtotime($e['event_date'])) ?></div>
                </div>
                <div class="flex-grow-1">
                    <a href="<?= BASE_URL ?>/event-detail.php?slug=<?= htmlspecialchars($e['slug']) ?>&from=group"
                    class="fw-semibold text-decoration-none text-dark">
                    <?= htmlspecialchars($e['title']) ?>
                    </a>
                    <div class="small text-muted">
                    <?= date('l, g:i A', strtotime($e['event_date'] . ' ' . $e['start_time'])) ?> – 
                    <?= htmlspecialchars($e['venue_name'] ?: 'Online') ?>
                    </div>
                </div>
                </div>
            <?php endforeach; ?>
            
            <?php endif; ?>
        </div>

        <!-- Past -->
        <div class="tab-pane fade" id="past" role="tabpanel">
            <?php
            $pastEvents = array_filter($allEvents, function($e) {
                return strtotime($e['event_date'] . ' ' . $e['start_time']) < time();
            });
            if (empty($pastEvents)):
            ?>
            <p class="text-muted mb-0">No past events yet.</p>
            <?php else: ?>
            <?php foreach ($pastEvents as $e): ?>
                <div class="event-card d-flex align-items-center mb-3 p-2 border rounded hover-shadow">
                <div class="event-date text-center bg-secondary text-white rounded me-3 px-3 py-2">
                    <div class="fw-bold"><?= date('M', strtotime($e['event_date'])) ?></div>
                    <div class="fs-5"><?= date('d', strtotime($e['event_date'])) ?></div>
                </div>
                <div class="flex-grow-1">
                    <a href="<?= BASE_URL ?>/event-detail.php?slug=<?= htmlspecialchars($e['slug']) ?>&from=group"
                    class="fw-semibold text-decoration-none text-dark">
                    <?= htmlspecialchars($e['title']) ?>
                    </a>
                    <div class="small text-muted">
                    <?= date('l, g:i A', strtotime($e['event_date'] . ' ' . $e['start_time'])) ?> – 
                    <?= htmlspecialchars($e['venue_name'] ?: 'Online') ?>
                    </div>
                </div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        </div>
    </div>
    </div>


    <!-- RIGHT: Quick Actions + Members -->
    <div class="col-lg-4">
      <!-- Ownership Alert -->
      <?php if ($isMember): ?>
        <div class="alert alert-success small py-2 px-3 mb-3 text-center">
          <i class="fas fa-check me-1"></i>You're a <?= ucfirst($userRole) ?>
        </div>
      <?php endif; ?>

      <!-- Quick Actions -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-sandstone"><h6 class="mb-0 text-forest">Quick Actions</h6></div>
        <div class="card-body d-grid gap-2">
          <?php if (!$hasValidMembership): ?>
            <div class="alert alert-warning small mb-2">
              <i class="fas fa-info-circle me-1"></i> You need a membership to join.
            </div>
            <a href="<?= BASE_URL ?>/membership.php" class="btn btn-warning w-100">
              <i class="fas fa-credit-card me-1"></i>Get Membership
            </a>
          <?php elseif ($isMember): ?>
            <?php if ($userRole !== 'owner'): ?>
              <form method="POST" onsubmit="return confirm('Leave this group?');">
                <input type="hidden" name="action" value="leave">
                <button type="submit" class="btn btn-outline-danger">
                  <i class="fas fa-sign-out-alt me-1"></i>Leave Group
                </button>
              </form>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/under-construction.php" class="btn btn-outline-success">
              <i class="fas fa-calendar me-1"></i>View Events
            </a>
            <a href="<?= BASE_URL ?>/under-construction.php" class="btn btn-outline-info">
              <i class="fas fa-comments me-1"></i>Group Discussion
            </a>
          <?php else: ?>
            <form method="POST">
              <input type="hidden" name="action" value="join">
              <?php if ($group['privacy_level'] === 'private'): ?>
                <textarea name="message" class="form-control mb-2" rows="2"
                          placeholder="Message to admin (optional)"></textarea>
                <button class="btn btn-primary w-100">
                  <i class="fas fa-paper-plane me-1"></i>Request to Join
                </button>
              <?php else: ?>
                <button class="btn btn-primary w-100">
                  <i class="fas fa-plus me-1"></i>Join Group
                </button>
              <?php endif; ?>
            </form>
          <?php endif; ?>

          <?php if (in_array($userRole, ['owner','co_host'])): ?>
            <hr>
            <a href="<?= BASE_URL ?>/manage-group.php?slug=<?= htmlspecialchars($slug) ?>" class="btn btn-warning">
              <i class="fas fa-cog me-1"></i>Manage Group
            </a>
          <?php endif; ?>

          <?php if (in_array($userRole, ['owner','co_host','moderator'])): ?>
            <a href="<?= BASE_URL ?>/create-event.php?group_id=<?= $group['id'] ?>" class="btn btn-forest">
              <i class="fas fa-plus me-1"></i>Create Event
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Members -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-sandstone d-flex justify-content-between align-items-center">
          <h6 class="mb-0 text-forest"><i class="fas fa-users me-1"></i>Members (<?= count($members) ?>)</h6>
        </div>
        <div class="card-body p-3" style="max-height:300px; overflow-y:auto;">
          <?php if (!$members): ?>
            <p class="text-muted small mb-0">No members yet.</p>
          <?php else: ?>
            <?php foreach ($members as $m): ?>
              <div class="d-flex align-items-center mb-2">
                <div class="rounded-circle bg-forest text-white d-flex align-items-center justify-content-center me-2"
                     style="width:36px; height:36px;"><i class="fas fa-user"></i></div>
                <div>
                  <div class="small fw-semibold"><?= htmlspecialchars($m['name']) ?></div>
                  <small class="text-muted">
                    <?= ucfirst($m['role']) ?>
                    <?php if ($m['role']==='owner'): ?><i class="fas fa-crown text-warning ms-1"></i><?php endif; ?>
                    <?php if ($m['role']==='co_host'): ?><i class="fas fa-star text-info ms-1"></i><?php endif; ?>
                    <?php if ($m['role']==='moderator'): ?><i class="fas fa-shield text-success ms-1"></i><?php endif; ?>
                  </small>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Group Details -->
      <div class="card shadow-sm border-0">
        <div class="card-header bg-sandstone"><h6 class="mb-0 text-forest">Group Details</h6></div>
        <div class="card-body small text-muted">
          <div><i class="fas fa-user text-muted me-1"></i>Organizer: <?= htmlspecialchars($group['creator_name']) ?></div>
          <div><i class="fas fa-clock text-muted me-1"></i>Created: <?= date('M j, Y', strtotime($group['created_at'])) ?></div>
          <?php if ($group['website_url']): ?>
            <div><i class="fas fa-globe text-muted me-1"></i>
              <a href="<?= htmlspecialchars($group['website_url']) ?>" target="_blank">Website</a></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.text-forest { color:#2f6d3a; }
.bg-sandstone { background:#f6f3ed; }
.bg-forest { background:#2f6d3a; }
.btn-forest { background:#2f6d3a; color:#fff; border-color:#2f6d3a; }
.btn-forest:hover { background:#285d32; color:#fff; border-color:#285d32; }
.placeholder-banner {
  background: linear-gradient(135deg,#e9e4d8,#f6f3ed);
  font-weight:500;
  border:2px dashed #ccc;
}
.group-detail .card { border-radius:10px; }

/* Enhanced Tab Styling */
.nav-tabs .nav-link.active {
  background-color: #2f6d3a !important;
  color: white !important;
  border-color: #2f6d3a #2f6d3a #f6f3ed !important;
}
.nav-tabs .nav-link {
  color: #2f6d3a;
  font-weight: 500;
}

/* Event Card Polish */
.event-card:hover {
  background: #f9f9f4;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  transition: 0.2s ease;
}
</style>

<?php include '../src/views/layouts/footer.php'; ?>

