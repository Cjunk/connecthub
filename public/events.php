<?php
/**
 * Events Listing Page
 * Browse and search for events across all groups
 */

session_start();
require_once __DIR__ . '/../src/models/Event.php';
require_once __DIR__ . '/../src/models/Group.php';
require_once __DIR__ . '/../src/helpers/functions.php';

$event = new Event();
$group = new Group();

// Get search/filter parameters
$search = $_GET['search'] ?? '';
$groupId = $_GET['group_id'] ?? '';
$locationType = $_GET['location_type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$upcomingOnly = isset($_GET['upcoming_only']) ? true : false;

// Build filters
$filters = [
    'upcoming_only' => $upcomingOnly,
    'limit' => 20
];

if (!empty($search)) {
    $filters['search'] = $search;
}

if (!empty($groupId)) {
    $filters['group_id'] = $groupId;
}

if (!empty($locationType)) {
    $filters['location_type'] = $locationType;
}

if (!empty($dateFrom)) {
    $filters['date_from'] = $dateFrom;
}

if (!empty($dateTo)) {
    $filters['date_to'] = $dateTo;
}

// Get events
$events = $event->getAll($filters);

// Get all groups for filter dropdown
$allGroups = $group->getAll(['status' => 'active']);

$pageTitle = "Events";
require_once __DIR__ . '/../src/views/layouts/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1">Events</h1>
                    <p class="text-muted mb-0">Discover and join interesting events in your community</p>
                </div>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/connecthub/public/groups.php" class="btn btn-primary">
                        <i class="fas fa-users"></i> Browse Groups
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Events</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Search by title, description...">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="group_id" class="form-label">Group</label>
                            <select class="form-select" id="group_id" name="group_id">
                                <option value="">All Groups</option>
                                <?php foreach ($allGroups as $groupOption): ?>
                                    <option value="<?= $groupOption['id'] ?>" 
                                            <?= $groupId == $groupOption['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($groupOption['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="location_type" class="form-label">Type</label>
                            <select class="form-select" id="location_type" name="location_type">
                                <option value="">All Types</option>
                                <option value="in_person" <?= $locationType === 'in_person' ? 'selected' : '' ?>>In Person</option>
                                <option value="online" <?= $locationType === 'online' ? 'selected' : '' ?>>Online</option>
                                <option value="hybrid" <?= $locationType === 'hybrid' ? 'selected' : '' ?>>Hybrid</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="/connecthub/public/events.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="upcoming_only" name="upcoming_only" 
                                       <?= $upcomingOnly ? 'checked' : '' ?>>
                                <label class="form-check-label" for="upcoming_only">
                                    Show only upcoming events
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Events List -->
    <div class="row">
        <?php if (empty($events)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-calendar-times fa-5x text-muted"></i>
                    </div>
                    <h3 class="text-muted mb-3">No Events Found</h3>
                    <p class="text-muted mb-4">
                        <?php if (!empty($search) || !empty($groupId) || !empty($locationType)): ?>
                            Try adjusting your search filters to find more events.
                        <?php else: ?>
                            There are no events available at the moment. Check back later!
                        <?php endif; ?>
                    </p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/connecthub/public/groups.php" class="btn btn-primary">
                            <i class="fas fa-users"></i> Join Groups to See Their Events
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($events as $eventItem): ?>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100 shadow-sm event-card">
                        <div class="card-body d-flex flex-column">
                            <!-- Event Date Badge -->
                            <div class="d-flex align-items-start mb-3">
                                <div class="event-date-badge text-center p-2 bg-primary text-white rounded me-3 flex-shrink-0">
                                    <div class="small"><?= date('M', strtotime($eventItem['event_date'])) ?></div>
                                    <div class="fw-bold"><?= date('d', strtotime($eventItem['event_date'])) ?></div>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">
                                        <a href="/connecthub/public/event-detail.php?slug=<?= htmlspecialchars($eventItem['slug']) ?>" 
                                           class="text-decoration-none">
                                            <?= htmlspecialchars($eventItem['title']) ?>
                                        </a>
                                    </h5>
                                    <p class="text-muted small mb-1">
                                        by <strong><?= htmlspecialchars($eventItem['group_name']) ?></strong>
                                    </p>
                                </div>
                            </div>

                            <!-- Event Description -->
                            <?php if ($eventItem['description']): ?>
                                <p class="card-text text-muted mb-3">
                                    <?= htmlspecialchars(substr($eventItem['description'], 0, 120)) ?>
                                    <?= strlen($eventItem['description']) > 120 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>

                            <!-- Event Details -->
                            <div class="small text-muted mb-3">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-clock me-2"></i>
                                    <?= date('l, M j, Y • g:i A', strtotime($eventItem['event_date'] . ' ' . $eventItem['start_time'])) ?>
                                </div>
                                
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php if ($eventItem['location_type'] === 'online'): ?>
                                        <span class="badge bg-info me-1">Online</span>
                                    <?php elseif ($eventItem['location_type'] === 'hybrid'): ?>
                                        <span class="badge bg-warning me-1">Hybrid</span>
                                        <?= htmlspecialchars($eventItem['venue_name']) ?>
                                    <?php else: ?>
                                        <span class="badge bg-success me-1">In Person</span>
                                        <?= htmlspecialchars($eventItem['venue_name']) ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users me-2"></i>
                                    <?= $eventItem['attendee_count'] ?> going
                                    <?php if ($eventItem['price'] > 0): ?>
                                        <span class="ms-2">• $<?= number_format($eventItem['price'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="ms-2 text-success">• Free</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="/connecthub/public/event-detail.php?slug=<?= htmlspecialchars($eventItem['slug']) ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    
                                    <a href="/connecthub/public/group-detail.php?slug=<?= htmlspecialchars($eventItem['group_slug']) ?>" 
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-users"></i> View Group
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Load More Button (for future pagination) -->
    <?php if (count($events) >= 20): ?>
        <div class="row mt-4">
            <div class="col-12 text-center">
                <p class="text-muted">Showing first 20 events. Use filters to narrow your search.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.event-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.event-date-badge {
    min-width: 50px;
}
</style>

<?php require_once __DIR__ . '/../src/views/layouts/footer.php'; ?>