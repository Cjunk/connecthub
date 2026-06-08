<?php include __DIR__ . '/../layouts/header.php'; ?>
<link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/dashboard.css">

<div class="container dashboard-live-layout my-4">
    <!-- Mobile / tablet dashboard action menu -->
    <div class="dashboard-mobile-actions card shadow-sm mb-3">
        <div class="card-body d-flex align-items-center justify-content-between gap-2">
            <div>
                <strong>Dashboard</strong>
                <div class="small text-muted">Live updates and quick actions</div>
            </div>

            <div class="dropdown">
                <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dashboardActionMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bars me-1"></i> Menu
                </button>

                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dashboardActionMenu">
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL; ?>/events.php">
                            <i class="fas fa-calendar-alt me-2"></i> Browse Events
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL; ?>/groups.php">
                            <i class="fas fa-users me-2"></i> Browse Groups
                        </a>
                    </li>

                    <?php if (isOrganizer() && $hasMembership): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL; ?>/create-event.php">
                                <i class="fas fa-plus-circle me-2"></i> Create Event
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL; ?>/create-group.php">
                                <i class="fas fa-layer-group me-2"></i> Create Group
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if (!$hasMembership): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-warning fw-semibold" href="<?= BASE_URL; ?>/membership.php">
                                <i class="fas fa-crown me-2"></i> Get Membership
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">

        <!-- LEFT SIDE: profile / membership / actions -->
        <aside class="dashboard-sidebar dashboard-left">

            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <h5 class="mb-1">Welcome back</h5>
                    <div class="text-muted small mb-3">
                        <?= htmlspecialchars($currentUser['name'] ?? 'Member'); ?>
                    </div>

                    <?php if (!$hasMembership): ?>
                        <div class="alert alert-warning small mb-3">
                            <strong>Membership inactive.</strong><br>
                            Unlock full event access.
                        </div>
                        <a href="<?= BASE_URL; ?>/membership.php" class="btn btn-warning btn-sm w-100">
                            <i class="fas fa-crown me-1"></i> Get Membership
                        </a>
                    <?php else: ?>
                        <div class="alert alert-success small mb-0">
                            <i class="fas fa-check-circle me-1"></i> Membership active
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <strong><i class="fas fa-bolt me-1"></i> Quick Actions</strong>
                </div>
                <div class="card-body d-grid gap-2">
                    <a href="<?= BASE_URL; ?>/events.php" class="btn btn-outline-primary btn-sm">
                        Browse Events
                    </a>
                    <a href="<?= BASE_URL; ?>/groups.php" class="btn btn-outline-primary btn-sm">
                        Browse Groups
                    </a>
                    <?php if (isOrganizer() && $hasMembership): ?>
                        <a href="<?= BASE_URL; ?>/create-event.php" class="btn btn-primary btn-sm">
                            Create Event
                        </a>
                        <a href="<?= BASE_URL; ?>/create-group.php" class="btn btn-primary btn-sm">
                            Create Group
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <strong><i class="fas fa-users me-1"></i> Your Groups</strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($userGroups)): ?>
                        <?php foreach (array_slice($userGroups, 0, 5) as $group): ?>
                            <div class="border-bottom py-2">
                                <a href="<?= BASE_URL; ?>/group-detail.php?slug=<?= htmlspecialchars($group['slug'] ?? ''); ?>"
                                   class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars($group['name'] ?? 'Group'); ?>
                                </a>
                                <div class="small text-muted">
                                    <?= roleBadge($group['role'] ?? 'member'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No groups joined yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        </aside>

        <!-- MIDDLE: live feed -->
        <main class="dashboard-feed">

            <div class="card shadow-sm mb-3 live-feed-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong><i class="fas fa-satellite-dish me-1"></i> Live Feed</strong>
                        <div class="small text-muted">Events, public posts, photos and comments will appear here.</div>
                    </div>
                    <span class="badge bg-success">Ready</span>
                </div>

                <div class="card-body" id="dashboard-live-feed">

                    <div class="feed-item">
                        <div class="feed-icon bg-primary">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div>
                            <strong>Live activity feed placeholder</strong>
                            <div class="text-muted small">
                                Next step: connect this to real events, comments, public group photos and posts.
                            </div>
                        </div>
                    </div>

                    <div class="feed-item">
                        <div class="feed-icon bg-success">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <strong>Public group activity will go here</strong>
                            <div class="text-muted small">
                                Example: someone joins a group, posts a photo, or comments on an event.
                            </div>
                        </div>
                    </div>

                    <div class="feed-item">
                        <div class="feed-icon bg-warning">
                            <i class="fas fa-camera"></i>
                        </div>
                        <div>
                            <strong>Photo posts can appear here</strong>
                            <div class="text-muted small">
                                Later this can auto-refresh every 30 seconds.
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </main>

        <!-- RIGHT SIDE: upcoming events / photos / ad -->
        <aside class="dashboard-sidebar dashboard-right">

            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <strong><i class="fas fa-calendar-alt me-1"></i> Upcoming Events</strong>
                </div>
                <div class="card-body">
                    <?php if (!empty($visibleEvents)): ?>
                        <?php foreach (array_slice($visibleEvents, 0, 5) as $event): ?>
                            <div class="border-bottom py-2">
                                <a href="<?= BASE_URL; ?>/event-detail.php?slug=<?= htmlspecialchars($event['slug'] ?? ''); ?>"
                                   class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars($event['title'] ?? 'Event'); ?>
                                </a>
                                <div class="small text-muted">
                                    <?= !empty($event['event_date']) ? formatDate($event['event_date'], 'M d, Y') : 'Date TBA'; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted small mb-0">No upcoming events yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <strong><i class="fas fa-image me-1"></i> Adventure Photos</strong>
                </div>
                <div class="card-body">
                    <div class="photo-pile compact-photo-pile">
                        <?php foreach (array_slice($heroPhotos, 0, 4) as $i => $src): ?>
                            <a href="<?= htmlspecialchars($src); ?>"
                               class="polaroid p<?= $i + 1; ?>"
                               data-index="<?= $i; ?>">
                                <img src="<?= htmlspecialchars($src); ?>" alt="Adventure photo">
                                <span class="caption">Adventure <?= $i + 1; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if (function_exists('getAdCode')): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <?= getAdCode('sidebar'); ?>
                    </div>
                </div>
            <?php endif; ?>

        </aside>

    </div>
</div>

<!-- Lightbox kept for photo pile -->
<div id="ch-lightbox" class="ch-lightbox ch-hidden" aria-hidden="true" role="dialog" aria-modal="true">
  <button class="ch-close" aria-label="Close">&times;</button>
  <button class="ch-nav ch-prev" aria-label="Previous">&#10094;</button>
  <img id="ch-lightbox-img" class="ch-img" src="" alt="">
  <div class="ch-meta">
    <div id="ch-lightbox-caption"></div>
  </div>
  <button class="ch-nav ch-next" aria-label="Next">&#10095;</button>
</div>

<script>
window.dashboardHeroPhotos = <?= json_encode(array_slice($heroPhotos, 0, 4), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="<?= BASE_URL; ?>/assets/js/dashboard.js"></script>
<script src="<?= BASE_URL; ?>/assets/js/dashboard-feed.js"></script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>


