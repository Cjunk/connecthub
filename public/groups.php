<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

// Check membership
$currentUser = getCurrentUser();
$hasValidMembership = hasValidMembership($currentUser);

require_once '../src/models/Group.php';
$groupModel = new Group();

// Get filters
$filters = [];
if (!empty($_GET['category'])) {
    $filters['category'] = $_GET['category'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (!empty($_GET['location'])) {
    $filters['location'] = $_GET['location'];
}

// Get groups and categories
$groups = $groupModel->getAll($filters);
$categories = $groupModel->getCategories();

$pageTitle = 'Browse Groups';
?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2 mb-2">
                <i class="fas fa-users me-2 text-primary"></i>Browse Groups
            </h1>
            <p class="text-muted">Connect with like-minded people in your community</p>
        </div>
        <div class="col-md-4 text-end">
            <?php if (isOrganizer() && $hasValidMembership): ?>
                <a href="<?php echo BASE_URL; ?>/create-group.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Group
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Membership Alert -->
    <?php if (!$hasValidMembership): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>Membership Required
                        </h5>
                        <p class="mb-0">
                            To join groups and participate in events, please complete your annual membership payment.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="<?php echo BASE_URL; ?>/membership.php" class="btn btn-warning">
                            <i class="fas fa-credit-card me-2"></i>Pay Membership
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Groups</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Search by name or description..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['name']); ?>"
                                            <?php echo ($_GET['category'] ?? '') === $category['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   placeholder="City, State..." 
                                   value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (!empty($filters)): ?>
                    <div class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Groups Grid -->
    <div class="row">
        <?php if (empty($groups)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No groups found</h4>
                    <?php if (!empty($filters)): ?>
                        <p class="text-muted">Try adjusting your search criteria or <a href="<?php echo BASE_URL; ?>/groups.php">browse all groups</a>.</p>
                    <?php else: ?>
                        <p class="text-muted">Be the first to create a group in your community!</p>
                        <?php if (isOrganizer() && $hasValidMembership): ?>
                            <a href="<?php echo BASE_URL; ?>/create-group.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create First Group
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($groups as $group): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 group-card">
                        <?php if ($group['cover_image']): ?>
                            <img src="<?php echo htmlspecialchars($group['cover_image']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($group['name']); ?>" 
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-gradient-primary d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="<?php echo $group['category_icon'] ?: 'fas fa-users'; ?> fa-3x text-white"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <!-- Category Badge -->
                            <?php if ($group['category_name']): ?>
                                <div class="mb-2">
                                    <span class="badge" style="background-color: <?php echo $group['category_color'] ?: '#6c757d'; ?>">
                                        <i class="<?php echo $group['category_icon']; ?> me-1"></i>
                                        <?php echo htmlspecialchars($group['category_name']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Group Name -->
                            <h5 class="card-title">
                                <a href="<?php echo BASE_URL; ?>/group-detail.php?slug=<?php echo htmlspecialchars($group['slug']); ?>" 
                                   class="text-decoration-none">
                                    <?php echo htmlspecialchars($group['name']); ?>
                                </a>
                            </h5>
                            
                            <!-- Description -->
                            <p class="card-text text-muted flex-grow-1">
                                <?php echo htmlspecialchars(substr($group['description'] ?: 'No description available.', 0, 120)); ?>
                                <?php echo strlen($group['description'] ?: '') > 120 ? '...' : ''; ?>
                            </p>
                            
                            <!-- Group Info -->
                            <div class="group-info mb-3">
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo number_format($group['member_count']); ?> members
                                    </span>
                                    <?php if ($group['location']): ?>
                                        <span>
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?php echo htmlspecialchars($group['location']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($group['privacy_level'] !== 'public'): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-lock me-1"></i>
                                            <?php echo ucfirst($group['privacy_level']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Button -->
                            <div class="mt-auto">
                                <?php if ($hasValidMembership): ?>
                                    <?php 
                                    $userRole = $groupModel->getUserRole($group['id'], $currentUser['id']);
                                    $isMember = $groupModel->isMember($group['id'], $currentUser['id']);
                                    ?>
                                    
                                    <?php if ($isMember): ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>
                                                <?php echo ucfirst($userRole); ?>
                                            </span>
                                            <a href="<?php echo BASE_URL; ?>/group-detail.php?slug=<?php echo htmlspecialchars($group['slug']); ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                View Group
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>/group-detail.php?slug=<?php echo htmlspecialchars($group['slug']); ?>" 
                                           class="btn btn-primary w-100">
                                            <?php echo $group['privacy_level'] === 'private' ? 'Request to Join' : 'Join Group'; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>/membership.php" 
                                       class="btn btn-warning w-100">
                                        Membership Required
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.group-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.group-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
}
</style>

<?php include '../src/views/layouts/footer.php'; ?>