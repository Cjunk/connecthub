<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';
require_once '../src/models/User.php';
require_once '../src/models/Group.php';
require_once '../src/models/Event.php';

if (!isLoggedIn()) redirect(BASE_URL . '/login.php');
$currentUser = getCurrentUser();
$hasValidMembership = hasValidMembership($currentUser);

if (!$hasValidMembership) {
    setFlashMessage('error', 'You need a valid membership to manage groups.');
    redirect(BASE_URL . '/membership.php');
}

$slug = $_GET['slug'] ?? '';
if (empty($slug)) redirect(BASE_URL . '/groups.php');

$groupModel = new Group();
$group = $groupModel->getBySlug($slug);
if (!$group) {
    setFlashMessage('error', 'Group not found.');
    redirect(BASE_URL . '/groups.php');
}

$userRole = $groupModel->getUserRole($group['id'], $currentUser['id']);
if (!in_array($userRole, ['owner', 'co_host'])) {
    setFlashMessage('error', 'You do not have permission to manage this group.');
    redirect(BASE_URL . '/group-detail.php?slug=' . urlencode($slug));
}

// POST actions (promote, remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $targetUserId = (int)($_POST['user_id'] ?? 0);

    switch ($action) {
        case 'promote':
            $newRole = $_POST['new_role'] ?? '';
            $ok = $groupModel->promoteUser($group['id'], $targetUserId, $newRole, $currentUser['id']);
            setFlashMessage($ok ? 'success' : 'error', $ok ? 'Role updated.' : 'Failed to update role.');
            break;
        case 'remove':
            $ok = $groupModel->leaveGroup($group['id'], $targetUserId);
            setFlashMessage($ok ? 'success' : 'error', $ok ? 'Member removed.' : 'Failed to remove.');
            break;
    }
    redirect(BASE_URL . '/manage-group.php?slug=' . urlencode($slug));
}

$members = $groupModel->getMembers($group['id']);
$managers = $groupModel->getGroupManagers($group['id']);
$pageTitle = 'Manage ' . $group['name'];
include '../src/views/layouts/header.php';
?>

<div class="container mt-4 group-manage">
  <!-- Group Header -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="banner-wrapper position-relative" style="height:280px; overflow:hidden;">
      <?php if ($group['cover_image']): ?>
        <img src="<?= htmlspecialchars($group['cover_image']) ?>" alt="Group Banner"
             style="width:100%; height:100%; object-fit:cover; object-position:center;">
      <?php else: ?>
        <div class="placeholder-banner d-flex align-items-center justify-content-center h-100 text-muted">
          <i class="fas fa-image me-2"></i> No group image uploaded yet
        </div>
      <?php endif; ?>
      <div class="banner-overlay position-absolute top-0 start-0 w-100 h-100"
           style="background:linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.6));"></div>
      <div class="banner-text position-absolute bottom-0 start-0 text-white p-4">
        <h2 class="fw-semibold mb-1"><?= htmlspecialchars($group['name']) ?></h2>
        <p class="mb-0 small"><?= htmlspecialchars($group['location'] ?: 'Online') ?></p>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="row">
    <!-- LEFT: Leadership & Members -->
    <div class="col-lg-8">
      <!-- Leadership -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-sandstone d-flex align-items-center justify-content-between">
          <h5 class="text-forest mb-0"><i class="fas fa-crown text-warning me-2"></i>Group Leadership</h5>
          <small class="text-muted"><?= count($managers) ?> leader<?= count($managers) !== 1 ? 's' : '' ?></small>
        </div>
        <div class="card-body">
          <?php foreach ($managers as $m): ?>
            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
              <div class="d-flex align-items-center">
                <div class="rounded-circle bg-forest text-white d-flex align-items-center justify-content-center me-3"
                     style="width:42px; height:42px;"><i class="fas fa-user"></i></div>
                <div>
                  <div class="fw-semibold"><?= htmlspecialchars($m['name']) ?></div>
                  <small class="text-muted">
                    <?php if ($m['role']==='owner'): ?><i class="fas fa-crown text-warning me-1"></i>Owner<?php endif; ?>
                    <?php if ($m['role']==='co_host'): ?><i class="fas fa-star text-info me-1"></i>Co-Host<?php endif; ?>
                    <?php if ($m['role']==='moderator'): ?><i class="fas fa-shield text-success me-1"></i>Moderator<?php endif; ?>
                    <?php if ($m['promoted_at']): ?> • Promoted <?= timeAgo($m['promoted_at']) ?><?php endif; ?>
                  </small>
                </div>
              </div>
              <?php if ($userRole === 'owner' && $m['role'] !== 'owner'): ?>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-cog"></i></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <?php if ($m['role']==='co_host'): ?>
                      <li><form method="POST"><input type="hidden" name="action" value="promote"><input type="hidden" name="user_id" value="<?= $m['id'] ?>"><input type="hidden" name="new_role" value="moderator"><button class="dropdown-item"><i class="fas fa-arrow-down me-2"></i>Demote to Moderator</button></form></li>
                    <?php elseif ($m['role']==='moderator'): ?>
                      <li><form method="POST"><input type="hidden" name="action" value="promote"><input type="hidden" name="user_id" value="<?= $m['id'] ?>"><input type="hidden" name="new_role" value="co_host"><button class="dropdown-item"><i class="fas fa-arrow-up me-2"></i>Promote to Co-Host</button></form></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><form method="POST" onsubmit="return confirm('Remove from leadership?');"><input type="hidden" name="action" value="promote"><input type="hidden" name="user_id" value="<?= $m['id'] ?>"><input type="hidden" name="new_role" value="member"><button class="dropdown-item text-warning"><i class="fas fa-user me-2"></i>Demote to Member</button></form></li>
                  </ul>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Members -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-sandstone">
          <h5 class="text-forest mb-0"><i class="fas fa-users me-2"></i>Group Members (<?= count($members) ?>)</h5>
        </div>
        <div class="card-body" style="max-height:420px; overflow-y:auto;">
          <?php
          $regularMembers = array_filter($members, fn($m) => $m['role'] === 'member');
          ?>
          <?php if (empty($regularMembers)): ?>
            <p class="text-muted mb-0">No regular members yet.</p>
          <?php else: ?>
            <?php foreach ($regularMembers as $m): ?>
              <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                <div class="d-flex align-items-center">
                  <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3"
                       style="width:36px; height:36px;"><i class="fas fa-user"></i></div>
                  <div>
                    <div class="fw-semibold"><?= htmlspecialchars($m['name']) ?></div>
                    <small class="text-muted">Joined <?= timeAgo($m['joined_at']) ?></small>
                  </div>
                </div>
                <?php if ($userRole === 'owner' || $userRole === 'co_host'): ?>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-cog"></i></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <?php if ($userRole==='owner'): ?>
                        <li><form method="POST"><input type="hidden" name="action" value="promote"><input type="hidden" name="user_id" value="<?= $m['id'] ?>"><input type="hidden" name="new_role" value="co_host"><button class="dropdown-item"><i class="fas fa-star me-2"></i>Promote to Co-Host</button></form></li>
                      <?php endif; ?>
                      <li><form method="POST"><input type="hidden" name="action" value="promote"><input type="hidden" name="user_id" value="<?= $m['id'] ?>"><input type="hidden" name="new_role" value="moderator"><button class="dropdown-item"><i class="fas fa-shield me-2"></i>Promote to Moderator</button></form></li>
                      <li><hr class="dropdown-divider"></li>
                      <li><form method="POST" onsubmit="return confirm('Remove this member?');"><input type="hidden" name="action" value="remove"><input type="hidden" name="user_id" value="<?= $m['id'] ?>"><button class="dropdown-item text-danger"><i class="fas fa-user-times me-2"></i>Remove from Group</button></form></li>
                    </ul>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- RIGHT: Role Info + Placeholders -->
    <div class="col-lg-4">
      <!-- Role Info -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-sandstone"><h6 class="mb-0 text-forest"><i class="fas fa-info-circle me-1"></i>Role Permissions</h6></div>
        <div class="card-body small text-muted">
          <ul class="list-unstyled mb-2">
            <li><i class="fas fa-crown text-warning me-1"></i><strong>Owner:</strong> full control, can promote/demote all.</li>
            <li><i class="fas fa-star text-info me-1"></i><strong>Co-Host:</strong> manage members, create events, moderate.</li>
            <li><i class="fas fa-shield text-success me-1"></i><strong>Moderator:</strong> assist events, help manage chats.</li>
            <li><i class="fas fa-user text-primary me-1"></i><strong>Member:</strong> attend, chat, participate.</li>
          </ul>
        </div>
      </div>

      <!-- Placeholder: Announcements -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-sandstone"><h6 class="mb-0 text-forest"><i class="fas fa-bullhorn me-1"></i>Group Announcements</h6></div>
        <div class="card-body text-muted small">
          <p>No announcements yet.</p>
          <?php if (in_array($userRole, ['owner','co_host'])): ?>
            <a href="#" class="btn btn-sm btn-forest"><i class="fas fa-plus me-1"></i>Create Announcement</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Placeholder: Group Settings -->
      <div class="card shadow-sm border-0">
        <div class="card-header bg-sandstone"><h6 class="mb-0 text-forest"><i class="fas fa-cog me-1"></i>Group Settings</h6></div>
        <div class="card-body text-muted small">
          <p>Future section for group privacy, image updates, and ownership transfer.</p>
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
.btn-forest:hover { background:#285d32; color:#fff; }
.placeholder-banner { background:linear-gradient(135deg,#e9e4d8,#f6f3ed); border:2px dashed #ccc; }
.card { border-radius:10px; }
</style>

<?php include '../src/views/layouts/footer.php'; ?>
