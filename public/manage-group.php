<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

// Ensure User model is loaded for membership checking
require_once '../src/models/User.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

$currentUser = getCurrentUser();
$hasValidMembership = hasValidMembership($currentUser);

if (!$hasValidMembership) {
    setFlashMessage('error', 'You need a valid membership to manage groups.');
    redirect(BASE_URL . '/membership.php');
}

// Get group slug from URL parameter
$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    redirect(BASE_URL . '/groups.php');
}

require_once '../src/models/Group.php';
$groupModel = new Group();

// Get group details
$group = $groupModel->getBySlug($slug);
if (!$group) {
    setFlashMessage('error', 'Group not found.');
    redirect(BASE_URL . '/groups.php');
}

// Check user permissions - only owner and co-hosts can manage
$userRole = $groupModel->getUserRole($group['id'], $currentUser['id']);
if (!in_array($userRole, ['owner', 'co_host'])) {
    setFlashMessage('error', 'You do not have permission to manage this group.');
    redirect(BASE_URL . '/group-detail.php?slug=' . urlencode($slug));
}

// Handle role management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'promote':
                $targetUserId = (int)$_POST['user_id'];
                $newRole = $_POST['new_role'];
                
                if ($groupModel->promoteUser($group['id'], $targetUserId, $newRole, $currentUser['id'])) {
                    setFlashMessage('success', 'User role updated successfully!');
                } else {
                    setFlashMessage('error', 'Failed to update user role. Check your permissions.');
                }
                break;
                
            case 'remove':
                $targetUserId = (int)$_POST['user_id'];
                
                if ($groupModel->leaveGroup($group['id'], $targetUserId)) {
                    setFlashMessage('success', 'User removed from group.');
                } else {
                    setFlashMessage('error', 'Failed to remove user from group.');
                }
                break;
        }
        
        redirect(BASE_URL . '/manage-group.php?slug=' . urlencode($slug));
    }
}

// Get group members and managers
$members = $groupModel->getMembers($group['id']);
$managers = $groupModel->getGroupManagers($group['id']);

$pageTitle = 'Manage ' . $group['name'];
?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-2">
                <i class="fas fa-users-cog text-primary me-2"></i>Manage Group
            </h1>
            <p class="text-muted mb-0">
                <a href="<?php echo BASE_URL; ?>/group-detail.php?slug=<?php echo htmlspecialchars($slug); ?>" class="text-decoration-none">
                    <?php echo htmlspecialchars($group['name']); ?>
                </a>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?php echo BASE_URL; ?>/group-detail.php?slug=<?php echo htmlspecialchars($slug); ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Group
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Group Leadership -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-crown text-warning me-2"></i>Group Leadership
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($managers as $manager): ?>
                        <div class="d-flex align-items-center justify-content-between py-2 <?php echo $manager['role'] !== 'owner' ? 'border-bottom' : ''; ?>">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                     style="width: 40px; height: 40px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($manager['name']); ?></h6>
                                    <small class="text-muted">
                                        <?php if ($manager['role'] === 'owner'): ?>
                                            <i class="fas fa-crown text-warning me-1"></i>Owner
                                        <?php elseif ($manager['role'] === 'co_host'): ?>
                                            <i class="fas fa-star text-info me-1"></i>Co-Host
                                        <?php elseif ($manager['role'] === 'moderator'): ?>
                                            <i class="fas fa-shield text-success me-1"></i>Moderator
                                        <?php endif; ?>
                                        
                                        <?php if ($manager['promoted_at']): ?>
                                            <span class="text-muted">• Promoted <?php echo timeAgo($manager['promoted_at']); ?></span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            
                            <?php if ($userRole === 'owner' && $manager['role'] !== 'owner'): ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php if ($manager['role'] === 'co_host'): ?>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="promote">
                                                    <input type="hidden" name="user_id" value="<?php echo $manager['id']; ?>">
                                                    <input type="hidden" name="new_role" value="moderator">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-arrow-down me-2"></i>Demote to Moderator
                                                    </button>
                                                </form>
                                            </li>
                                        <?php elseif ($manager['role'] === 'moderator'): ?>
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="promote">
                                                    <input type="hidden" name="user_id" value="<?php echo $manager['id']; ?>">
                                                    <input type="hidden" name="new_role" value="co_host">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-arrow-up me-2"></i>Promote to Co-Host
                                                    </button>
                                                </form>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <li>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this person from leadership?');">
                                                <input type="hidden" name="action" value="promote">
                                                <input type="hidden" name="user_id" value="<?php echo $manager['id']; ?>">
                                                <input type="hidden" name="new_role" value="member">
                                                <button type="submit" class="dropdown-item text-warning">
                                                    <i class="fas fa-user me-2"></i>Demote to Member
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Group Members -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Group Members (<?php echo count($members); ?>)
                    </h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php 
                    $regularMembers = array_filter($members, function($member) {
                        return $member['role'] === 'member';
                    });
                    ?>
                    
                    <?php if (empty($regularMembers)): ?>
                        <p class="text-muted">No regular members yet. Only leadership team.</p>
                    <?php else: ?>
                        <?php foreach ($regularMembers as $member): ?>
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" 
                                         style="width: 35px; height: 35px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($member['name']); ?></h6>
                                        <small class="text-muted">Joined <?php echo timeAgo($member['joined_at']); ?></small>
                                    </div>
                                </div>
                                
                                <?php if ($userRole === 'owner' || ($userRole === 'co_host' && in_array('promote_moderators', $groupModel->getUserPermissions($group['id'], $currentUser['id'])))): ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($userRole === 'owner'): ?>
                                                <li>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="promote">
                                                        <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                        <input type="hidden" name="new_role" value="co_host">
                                                        <button type="submit" class="dropdown-item">
                                                            <i class="fas fa-star me-2"></i>Promote to Co-Host
                                                        </button>
                                                    </form>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <li>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="promote">
                                                    <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                    <input type="hidden" name="new_role" value="moderator">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-shield me-2"></i>Promote to Moderator
                                                    </button>
                                                </form>
                                            </li>
                                            
                                            <li><hr class="dropdown-divider"></li>
                                            
                                            <li>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this member from the group?');">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-user-times me-2"></i>Remove from Group
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Information -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Role Permissions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6 class="text-warning">
                                <i class="fas fa-crown me-1"></i>Owner
                            </h6>
                            <ul class="list-unstyled small text-muted">
                                <li>• Full group management</li>
                                <li>• Promote/demote co-hosts</li>
                                <li>• Transfer ownership</li>
                                <li>• Delete group</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-info">
                                <i class="fas fa-star me-1"></i>Co-Host
                            </h6>
                            <ul class="list-unstyled small text-muted">
                                <li>• Manage members</li>
                                <li>• Create events</li>
                                <li>• Promote moderators</li>
                                <li>• Moderate discussions</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-success">
                                <i class="fas fa-shield me-1"></i>Moderator
                            </h6>
                            <ul class="list-unstyled small text-muted">
                                <li>• Moderate discussions</li>
                                <li>• Help with events</li>
                                <li>• Basic member support</li>
                            </ul>
                        </div>
                        <div class="col-md-3">
                            <h6 class="text-primary">
                                <i class="fas fa-user me-1"></i>Member
                            </h6>
                            <ul class="list-unstyled small text-muted">
                                <li>• Participate in discussions</li>
                                <li>• Attend events</li>
                                <li>• Create discussion topics</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../src/views/layouts/footer.php'; ?>