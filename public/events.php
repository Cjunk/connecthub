<?php
/**
 * Events Listing Page – instant filters + load more
 */

session_start();

// Core app bootstrap
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/bootstrap.php';

require_once __DIR__ . '/../src/models/Event.php';
require_once __DIR__ . '/../src/models/Group.php';
require_once __DIR__ . '/../src/helpers/functions.php';

$event = new Event();
$group = new Group();

/** ------------------------------
 * Inputs
 * ------------------------------ */
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$groupId      = isset($_GET['group_id']) ? trim($_GET['group_id']) : '';
$locationType = isset($_GET['location_type']) ? trim($_GET['location_type']) : '';
$dateFrom     = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$dateTo       = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$upcomingOnly = isset($_GET['upcoming_only']); // checkbox presence

// Pagination
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 24;
$limit = max(6, min($limit, 48)); // sane bounds
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page  = max(1, $page);
$offset = ($page - 1) * $limit;

// Build filters for the model
$filters = [
    'upcoming_only' => $upcomingOnly ? 1 : 0,
    'limit'         => $limit,
    'offset'        => $offset, // will be ignored if model doesn't support it
];

if ($search !== '')       { $filters['search']        = $search; }
if ($groupId !== '')      { $filters['group_id']      = (int)$groupId; }
if ($locationType !== '') { $filters['location_type'] = $locationType; }
if ($dateFrom !== '')     { $filters['date_from']     = $dateFrom; }
if ($dateTo !== '')       { $filters['date_to']       = $dateTo; }

// Default landing cap if no filters at all (fast 1st page)
if ($search === '' && $groupId === '' && $locationType === '' && $dateFrom === '' && $dateTo === '' && !$upcomingOnly) {
    $filters['limit']  = $limit;
    $filters['offset'] = $offset;
}

/** ------------------------------
 * Data
 * ------------------------------ */
$events    = $event->getAll($filters);
$allGroups = $group->getAll(['status' => 'active']);
$hasMore   = is_array($events) && count($events) === $limit; // heuristic without total count

/** ------------------------------
 * Partial response for AJAX (JSON)
 * ------------------------------ */
function render_event_card(array $e): string {
    $cover = !empty($e['cover_image'])
        ? htmlspecialchars($e['cover_image'])
        : '';
    $title = htmlspecialchars($e['title'] ?? 'Event');
    $slug  = htmlspecialchars($e['slug'] ?? '');
    $groupName = htmlspecialchars($e['group_name'] ?? 'Group');
    $groupSlug = htmlspecialchars($e['group_slug'] ?? '');
    $date = !empty($e['event_date']) ? strtotime($e['event_date']) : null;
    $start = !empty($e['start_time']) ? strtotime($e['start_time']) : null;
    $end   = !empty($e['end_time']) ? strtotime($e['end_time']) : null;
    $desc  = !empty($e['description']) ? htmlspecialchars(mb_strimwidth($e['description'], 0, 80, '…')) : '';
    $locType = $e['location_type'] ?? '';
    $venue = htmlspecialchars($e['venue_name'] ?? ($e['location'] ?? ''));
    $attending = (int)($e['attendee_count'] ?? 0);
    $priceHtml = (!empty($e['price']) && $e['price'] > 0)
        ? '<span class="text-primary fw-bold">$'.number_format((float)$e['price'], 2).'</span>'
        : '<span class="text-success fw-bold">Free</span>';

    ob_start(); ?>
    <div class="col-lg-3 col-md-6 mb-3">
      <div class="card h-100 event-card overflow-hidden">
        <div class="position-relative">
          <?php if ($cover): ?>
            <img src="<?= $cover ?>"
                 class="card-img-top"
                 alt="<?= $title ?>"
                 style="height: 120px; object-fit: cover;">
          <?php else: ?>
            <div class="card-img-top bg-gradient-primary d-flex align-items-center justify-content-center"
                 style="height: 120px;">
              <i class="fas fa-calendar-alt fa-lg text-white opacity-50"></i>
            </div>
          <?php endif; ?>

          <?php if ($date): ?>
            <div class="position-absolute top-0 start-0 m-2">
              <div class="event-date-badge text-center px-1 py-1 bg-white shadow-sm rounded">
                <div class="small text-primary fw-bold" style="font-size: .65rem;"><?= strtoupper(date('M', $date)) ?></div>
                <div class="fw-bold text-dark" style="font-size: .8rem;"><?= date('d', $date) ?></div>
              </div>
            </div>
          <?php endif; ?>

          <div class="position-absolute top-0 end-0 m-2">
            <?php if ($locType === 'online'): ?>
              <span class="badge bg-info"><i class="fas fa-laptop me-1"></i>Online</span>
            <?php elseif ($locType === 'hybrid'): ?>
              <span class="badge bg-warning"><i class="fas fa-globe me-1"></i>Hybrid</span>
            <?php else: ?>
              <span class="badge bg-success"><i class="fas fa-map-marker-alt me-1"></i>In Person</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-body d-flex flex-column p-2">
          <div class="mb-2">
            <h6 class="card-title mb-1 small">
              <a href="/event-detail.php?slug=<?= $slug ?>" class="text-decoration-none text-dark">
                <?= $title ?>
              </a>
            </h6>
            <p class="text-muted mb-0" style="font-size: .75rem;">
              <i class="fas fa-users me-1"></i>
              by <strong><?= $groupName ?></strong>
            </p>
          </div>

          <?php if ($desc): ?>
            <p class="card-text text-muted mb-2 flex-grow-1 small"><?= $desc ?></p>
          <?php endif; ?>

          <div class="small text-muted mb-2">
            <?php if ($date): ?>
            <div class="d-flex align-items-center mb-1">
              <i class="fas fa-calendar-day me-2 text-primary"></i>
              <span><?= date('M j, Y', $date) ?></span>
            </div>
            <?php endif; ?>
            <div class="d-flex align-items-center mb-1">
              <i class="fas fa-clock me-2 text-primary"></i>
              <span><?= $start ? date('g:i A', $start) : '' ?></span>
              <?php if ($end): ?>
                <span>&nbsp;–&nbsp;<?= date('g:i A', $end) ?></span>
              <?php endif; ?>
            </div>
            <?php if ($locType !== 'online' && $venue): ?>
              <div class="d-flex align-items-center">
                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                <span><?= $venue ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div class="d-flex align-items-center justify-content-between text-muted mb-2" style="font-size: .7rem;">
            <span><i class="fas fa-users me-1"></i><?= $attending ?> attending</span>
            <?= $priceHtml ?>
          </div>

          <div class="mt-auto">
            <div class="d-flex gap-1">
              <a href="/event-detail.php?slug=<?= $slug ?>"
                 class="btn btn-primary btn-xs flex-fill" style="font-size: .7rem;">
                <i class="fas fa-eye me-1"></i> View
              </a>
              <?php if ($groupSlug): ?>
              <a href="/group-detail.php?slug=<?= $groupSlug ?>"
                 class="btn btn-outline-secondary btn-xs" style="font-size: .7rem;">
                <i class="fas fa-users"></i>
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

function render_cards_html(array $events): string {
    $html = '';
    foreach ($events as $e) {
        $html .= render_event_card($e);
    }
    return $html;
}

// Partial JSON for fetch()
if (isset($_GET['partial'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'cards'    => render_cards_html($events),
        'has_more' => $hasMore,
    ]);
    exit;
}

/** ------------------------------
 * Full page render
 * ------------------------------ */
$pageTitle = "Events";
require_once __DIR__ . '/../src/views/layouts/header.php';
?>

<div class="container" id="events-page" data-limit="<?= (int)$limit ?>">
  <?php
  $title = "Events";
  $subtitle = "Discover and join interesting events in your community";
  $icon = "fas fa-calendar-alt";
  $buttonText = !empty($_SESSION['user_id']) ? '<i class="fas fa-users"></i> Browse Groups' : '';
  $buttonLink = "/groups.php";
  $buttonClass = "btn btn-primary";
  include __DIR__ . '/../src/views/components/page-header.php';
  ?>

  <!-- Filters -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <form method="GET" class="row g-3" id="filters-form">
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
                  <option value="<?= (int)$groupOption['id'] ?>"
                    <?= ($groupId !== '' && (int)$groupId === (int)$groupOption['id']) ? 'selected' : '' ?>>
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
                <option value="online"    <?= $locationType === 'online'    ? 'selected' : '' ?>>Online</option>
                <option value="hybrid"    <?= $locationType === 'hybrid'    ? 'selected' : '' ?>>Hybrid</option>
              </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
              <div class="d-flex gap-2 w-100" style="padding-top: 24px;">
                <button type="submit" class="btn btn-primary flex-grow-1">
                  <i class="fas fa-search"></i> Search
                </button>
                <a href="/events.php" class="btn btn-outline-secondary">
                  <i class="fas fa-times"></i> Clear
                </a>
              </div>
            </div>

            <div class="col-md-4 d-flex gap-2 align-items-end">
              <div class="flex-fill">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control form-control-lg" id="date_from" name="date_from"
                       value="<?= htmlspecialchars($dateFrom) ?>" style="font-size: 0.95rem; padding: 0.5rem 0.75rem;">
              </div>
              <div class="flex-fill">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control form-control-lg" id="date_to" name="date_to"
                       value="<?= htmlspecialchars($dateTo) ?>" style="font-size: 0.95rem; padding: 0.5rem 0.75rem;">
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

  <!-- Results -->
  <div class="position-relative">
    <div id="events-loading"
         class="position-absolute top-0 start-0 w-100 h-100 d-none"
         style="background:rgba(255,255,255,.6); z-index:2;">
      <div class="d-flex justify-content-center align-items-center h-100">
        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
      </div>
    </div>

    <div class="row" id="events-grid">
      <?php if (empty($events)): ?>
        <div class="col-12">
          <div class="text-center py-5">
            <div class="mb-4"><i class="fas fa-calendar-times fa-5x text-muted"></i></div>
            <h3 class="text-muted mb-3">No Events Found</h3>
            <p class="text-muted mb-4">
              <?php if ($search !== '' || $groupId !== '' || $locationType !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
                Try adjusting your search filters to find more events.
              <?php else: ?>
                There are no events available at the moment. Check back later!
              <?php endif; ?>
            </p>
            <?php if (!empty($_SESSION['user_id'])): ?>
              <a href="/groups.php" class="btn btn-primary">
                <i class="fas fa-users"></i> Join Groups to See Their Events
              </a>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <?= render_cards_html($events) ?>
      <?php endif; ?>
    </div>

    <!-- Load more -->
    <div class="text-center mt-3" id="load-more-wrap" <?= $hasMore ? '' : 'style="display:none;"' ?>>
      <button class="btn btn-outline-primary btn-sm" id="load-more-btn">
        <i class="fas fa-plus-circle me-1"></i> Load more
      </button>
    </div>
  </div>
</div>

<style>
.event-card { transition: all .3s ease; border: 1px solid rgba(0,0,0,.08); box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.event-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.1); border-color: rgba(0,0,0,.12); }
.event-date-badge { min-width: 32px; border: 1px solid rgba(0,0,0,.1); box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.card-img-top { transition: transform .3s ease; }
.event-card:hover .card-img-top { transform: scale(1.05); }
.bg-gradient-primary { background: linear-gradient(135deg, var(--bs-primary) 0%, #6f42c1 100%); }
.badge { font-size: .75rem; box-shadow: 0 2px 4px rgba(0,0,0,.1); }
.btn-sm { border-radius: 20px; font-weight: 500; }
.event-card .card-title a:hover { color: var(--bs-primary) !important; }
@media (max-width: 768px) {
  .event-card { margin-bottom: 1rem; }
  .event-date-badge { min-width: 28px; font-size: .7rem; }
  .card-img-top, .bg-gradient-primary { height: 100px !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('filters-form');
  const grid = document.getElementById('events-grid');
  const spinner = document.getElementById('events-loading');
  const loadMoreWrap = document.getElementById('load-more-wrap');
  const loadMoreBtn = document.getElementById('load-more-btn');
  const pageRoot = document.getElementById('events-page');
  const defaultLimit = Number(pageRoot?.dataset.limit || 24);

  let page = 1;
  let hasMore = <?= $hasMore ? 'true' : 'false' ?>;
  let currentController = null;

  function setLoading(on) {
    spinner.classList.toggle('d-none', !on);
  }

  function formParams(extra = {}) {
    const fd = new FormData(form);
    const params = new URLSearchParams();
    for (const [k, v] of fd.entries()) {
      if (k === 'upcoming_only') {
        // checkbox: include only when checked
        continue;
      }
      if (String(v).trim() !== '') params.set(k, v);
    }
    if (form.querySelector('#upcoming_only')?.checked) params.set('upcoming_only', '1');

    // pagination & partial
    params.set('limit', String(defaultLimit));
    if (extra.page) params.set('page', String(extra.page));
    params.set('partial', '1');

    return params;
  }

  function pushUrlState() {
    const fd = new FormData(form);
    const params = new URLSearchParams();
    for (const [k, v] of fd.entries()) {
      if (k === 'upcoming_only') continue;
      if (String(v).trim() !== '') params.set(k, v);
    }
    if (form.querySelector('#upcoming_only')?.checked) params.set('upcoming_only', '1');
    // do not persist page/partial in URL so links are clean
    const url = location.pathname + (params.toString() ? '?' + params.toString() : '');
    history.replaceState(null, '', url);
  }

  async function fetchResults({append = false} = {}) {
    try {
      if (currentController) currentController.abort();
      currentController = new AbortController();

      setLoading(true);
      const params = formParams({ page });
      const res = await fetch(location.pathname + '?' + params.toString(), {
        signal: currentController.signal,
        headers: { 'X-Requested-With': 'fetch' }
      });
      const data = await res.json();

      if (!append) grid.innerHTML = '';
      if (data.cards) grid.insertAdjacentHTML(append ? 'beforeend' : 'afterbegin', data.cards);

      hasMore = !!data.has_more;
      loadMoreWrap.style.display = hasMore ? '' : 'none';
      pushUrlState();
    } catch (e) {
      if (e.name !== 'AbortError') {
        console.warn('Fetch failed', e);
      }
    } finally {
      setLoading(false);
    }
  }

  // Debounce utilities
  let t = null;
  const debounce = (fn, ms=350) => {
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(null, args), ms);
    };
  };

  // Hook inputs
  const onFilterChange = debounce(() => {
    page = 1;
    fetchResults({ append: false });
  }, 350);

  // change events
  ['group_id','location_type','date_from','date_to','upcoming_only'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', onFilterChange);
  });
  // search keyup with debounce
  const searchEl = document.getElementById('search');
  if (searchEl) searchEl.addEventListener('keyup', onFilterChange);

  // Submit (press enter) still works
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    page = 1;
    fetchResults({ append: false });
  });

  // Load more
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', () => {
      if (!hasMore) return;
      page += 1;
      fetchResults({ append: true });
    });
  }
});
</script>

<?php require_once __DIR__ . '/../src/views/layouts/footer.php'; ?>

