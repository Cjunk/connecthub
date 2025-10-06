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

// Check user membership status
$isMember = $groupModel->isMember($group['id'], $currentUser['id']);
$userRole = $groupModel->getUserRole($group['id'], $currentUser['id']);

// Handle join/leave actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $hasValidMembership) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'join':
                if (!$isMember) {
                    if ($group['privacy_level'] === 'private') {
                        $message = $_POST['message'] ?? '';
                        $result = $groupModel->requestToJoin($group['id'], $currentUser['id'], $message);
                        if ($result) {
                            setFlashMessage('success', 'Join request sent! The group admin will review your request.');
                        } else {
                            setFlashMessage('error', 'Failed to send join request. Please try again.');
                        }
                    } else {
                        $result = $groupModel->joinGroup($group['id'], $currentUser['id']);
                        if ($result) {
                            setFlashMessage('success', 'Successfully joined the group!');
                            $isMember = true;
                            $userRole = 'member';
                        } else {
                            setFlashMessage('error', 'Failed to join group. Please try again.');
                        }
                    }
                }
                break;
                
            case 'leave':
                if ($isMember && $userRole !== 'creator') {
                    $result = $groupModel->leaveGroup($group['id'], $currentUser['id']);
                    if ($result) {
                        setFlashMessage('success', 'You have left the group.');
                        $isMember = false;
                        $userRole = null;
                    } else {
                        setFlashMessage('error', 'Failed to leave group. Please try again.');
                    }
                }
                break;
        }
        
        // Refresh page to show updated status
        redirect(BASE_URL . '/group-detail.php?slug=' . urlencode($slug));
    }
}

// Get group members
$members = $groupModel->getMembers($group['id']);

$pageTitle = $group['name'];
?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container">
    <!-- Group Header -->
    <div class="row mb-4">
        <div class="col-12">
            <?php if ($group['cover_image']): ?>
                <div class="group-cover position-relative mb-4" style="height: 300px; background-image: url('<?php echo htmlspecialchars($group['cover_image']); ?>'); background-size: cover; background-position: center; border-radius: 10px;">
                    <div class="position-absolute bottom-0 start-0 end-0 bg-dark bg-opacity-75 text-white p-4" style="border-radius: 0 0 10px 10px;">
                        <h1 class="h2 mb-2"><?php echo htmlspecialchars($group['name']); ?></h1>
                        <p class="mb-0"><?php echo htmlspecialchars($group['location'] ?: 'Online'); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                         style="width: 120px; height: 120px;">
                        <i class="<?php echo $group['category_icon'] ?: 'fas fa-users'; ?> fa-3x"></i>
                    </div>
                    <h1 class="h2 mb-2"><?php echo htmlspecialchars($group['name']); ?></h1>
                    <p class="text-muted"><?php echo htmlspecialchars($group['location'] ?: 'Online'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Group Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-users text-primary me-2"></i>
                                <strong><?php echo number_format($group['member_count']); ?> members</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <?php if ($group['category_name']): ?>
                                <span class="badge" style="background-color: <?php echo $group['category_color'] ?: '#6c757d'; ?>">
                                    <i class="<?php echo $group['category_icon']; ?> me-1"></i>
                                    <?php echo htmlspecialchars($group['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($group['privacy_level'] !== 'public'): ?>
                                <span class="badge bg-warning text-dark ms-2">
                                    <i class="fas fa-lock me-1"></i>
                                    <?php echo ucfirst($group['privacy_level']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($group['description']): ?>
                        <h5>About This Group</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($group['description'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($group['rules']): ?>
                        <h5 class="mt-4">Group Rules</h5>
                        <div class="bg-light p-3 rounded">
                            <?php echo nl2br(htmlspecialchars($group['rules'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Members -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Members (<?php echo count($members); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($members)): ?>
                        <p class="text-muted">No members yet.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($members as $member): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($member['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php 
                                                $roleDisplay = ucfirst($member['role']);
                                                if ($member['role'] === 'owner') {
                                                    $roleDisplay .= ' <i class="fas fa-crown text-warning ms-1" title="Group Owner"></i>';
                                                } elseif ($member['role'] === 'co_host') {
                                                    $roleDisplay .= ' <i class="fas fa-star text-info ms-1" title="Co-Host"></i>';
                                                } elseif ($member['role'] === 'moderator') {
                                                    $roleDisplay .= ' <i class="fas fa-shield text-success ms-1" title="Moderator"></i>';
                                                }
                                                echo $roleDisplay;
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Join/Leave Actions -->
            <div class="card mb-4">
                <div class="card-body">
                    <?php if (!$hasValidMembership): ?>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">Membership Required</h6>
                            <p class="mb-0">You need an active membership to join groups.</p>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/membership.php" class="btn btn-warning w-100">
                            <i class="fas fa-credit-card me-2"></i>Get Membership
                        </a>
                    <?php elseif ($isMember): ?>
                        <div class="alert alert-success">
                            <h6 class="alert-heading">
                                <i class="fas fa-check me-2"></i>You're a <?php echo ucfirst($userRole); ?>
                            </h6>
                            <p class="mb-0">You have access to all group activities.</p>
                        </div>
                        
                        <?php if ($userRole !== 'owner'): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to leave this group?');">
                                <input type="hidden" name="action" value="leave">
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-sign-out-alt me-2"></i>Leave Group
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info mb-3">
                                <small><i class="fas fa-info-circle me-1"></i>As the group owner, you cannot leave. Transfer ownership first.</small>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="join">
                            
                            <?php if ($group['privacy_level'] === 'private'): ?>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message to Admin (Optional)</label>
                                    <textarea class="form-control" id="message" name="message" rows="3" 
                                              placeholder="Tell the admin why you'd like to join..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-paper-plane me-2"></i>Request to Join
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Join Group
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Group Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">Group Details</h6>
                </div>
                <div class="card-body">
                    <?php if ($group['meeting_frequency']): ?>
                        <div class="mb-2">
                            <i class="fas fa-calendar text-muted me-2"></i>
                            <strong>Meets:</strong> <?php echo htmlspecialchars($group['meeting_frequency']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-2">
                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                        <strong>Location:</strong> <?php echo htmlspecialchars($group['location'] ?: 'Online'); ?>
                    </div>
                    
                    <div class="mb-2">
                        <i class="fas fa-user text-muted me-2"></i>
                        <strong>Organizer:</strong> <?php echo htmlspecialchars($group['creator_name']); ?>
                    </div>
                    
                    <div class="mb-2">
                        <i class="fas fa-clock text-muted me-2"></i>
                        <strong>Created:</strong> <?php echo date('M j, Y', strtotime($group['created_at'])); ?>
                    </div>
                    
                    <?php if ($group['website_url']): ?>
                        <div class="mb-2">
                            <i class="fas fa-globe text-muted me-2"></i>
                            <a href="<?php echo htmlspecialchars($group['website_url']); ?>" target="_blank" rel="noopener">
                                Group Website
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Groups
                        </a>
                        
                        <?php if ($isMember): ?>
                            <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-outline-success">
                                <i class="fas fa-calendar me-2"></i>View Events
                            </a>
                            <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-outline-info">
                                <i class="fas fa-comments me-2"></i>Group Discussion
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($userRole === 'owner' || $userRole === 'co_host'): ?>
                            <hr>
                            <a href="<?php echo BASE_URL; ?>/manage-group.php?slug=<?php echo htmlspecialchars($slug); ?>" class="btn btn-warning">
                                <i class="fas fa-cog me-2"></i>Manage Group
                            </a>
                            <a href="<?php echo BASE_URL; ?>/under-construction.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Event
                            </a>
                            
                            <?php if ($userRole === 'owner'): ?>
                                <a href="<?php echo BASE_URL; ?>/manage-group.php?slug=<?php echo htmlspecialchars($slug); ?>" class="btn btn-info">
                                    <i class="fas fa-users-cog me-2"></i>Manage Roles
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../src/views/layouts/footer.php'; ?>