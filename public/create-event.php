<?php
/**
 * ConnectHub - Create Event (Outdoor Adventure Edition)
 * - Top banner image preview (hidden until a file is selected)
 * - Compact numeric inputs with spinners
 * - AUD pricing
 * - Aussie-flavoured placeholders (Katoomba / Blue Mountains)
 */

session_start();
require_once __DIR__ . '/../src/models/Group.php';
require_once __DIR__ . '/../src/models/Event.php';
require_once __DIR__ . '/../src/helpers/functions.php';

if (empty($_SESSION['user_id'])) { header('Location: /login.php'); exit; }

$groupId = $_GET['group_id'] ?? null;
if (!$groupId) { header('Location: /groups.php'); exit; }

$group = new Group();
$event = new Event();
$errors = [];

$groupData = $group->getById($groupId);
if (!$groupData) { header('Location: /groups.php'); exit; }

if (!$group->canUserCreateEvents($_SESSION['user_id'], $groupId)) {
    $_SESSION['error'] = "You don't have permission to create events in this group.";
    header('Location: /group-detail.php?slug=' . $groupData['slug']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title'          => trim($_POST['title'] ?? ''),
        'description'    => trim($_POST['description'] ?? ''),
        'event_date'     => $_POST['event_date'] ?? '',
        'start_time'     => $_POST['start_time'] ?? '',
        'end_time'       => $_POST['end_time'] ?? null,
        'location_type'  => $_POST['location_type'] ?? 'in_person',
        'venue_name'     => trim($_POST['venue_name'] ?? ''),
        'venue_address'  => trim($_POST['venue_address'] ?? ''),
        'online_link'    => trim($_POST['online_link'] ?? ''),
        'max_attendees'  => $_POST['max_attendees'] ?? null,
        'price'          => $_POST['price'] ?? '0.00',
        'requirements'   => trim($_POST['requirements'] ?? ''),
        'tags'           => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))),
        'status'         => $_POST['status'] ?? 'draft',
    ];

    // Validation
    if (!$data['title']) $errors[] = "Event title is required.";
    if (!$data['event_date']) $errors[] = "Event date is required.";
    elseif (strtotime($data['event_date']) < strtotime('today')) $errors[] = "Event date cannot be in the past.";
    if (!$data['start_time']) $errors[] = "Start time is required.";

    if ($data['location_type'] === 'in_person' && !$data['venue_name']) $errors[] = "Venue name is required for in-person events.";
    if ($data['location_type'] === 'online' && !$data['online_link']) $errors[] = "Online meeting link is required for online events.";
    if ($data['location_type'] === 'hybrid' && (!$data['venue_name'] || !$data['online_link'])) $errors[] = "Both venue and online link are required for hybrid events.";

    if ($data['max_attendees'] && (!is_numeric($data['max_attendees']) || $data['max_attendees'] < 1)) $errors[] = "Maximum attendees must be a positive number.";
    if (!is_numeric($data['price']) || $data['price'] < 0) $errors[] = "Price must be a valid amount.";

    // Image upload
    $coverImage = null;
    if (!empty($_FILES['cover_image']['name']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/uploads/events/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($ext, $allowed)) $errors[] = "Invalid image type. JPG, PNG or GIF only.";
        elseif ($_FILES['cover_image']['size'] > 5 * 1024 * 1024) $errors[] = "Image too large (max 5MB).";
        else {
            $filename = 'event_' . uniqid() . '.' . $ext;
            $path = $dir . $filename;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $path)) {
                $coverImage = 'uploads/events/' . $filename;
            } else {
                $errors[] = "Image upload failed.";
            }
        }
    }

    if (!$errors) {
        $payload = array_merge($data, [
            'group_id'      => $groupId,
            'created_by'    => $_SESSION['user_id'],
            'cover_image'   => $coverImage,
            'price'         => (float)$data['price'],
            'max_attendees' => $data['max_attendees'] ? (int)$data['max_attendees'] : null,
            'venue_name'    => $data['venue_name'] ?: null,
            'venue_address' => $data['venue_address'] ?: null,
            'online_link'   => $data['online_link'] ?: null,
            'end_time'      => $data['end_time'] ?: null,
            'requirements'  => $data['requirements'] ?: null,
        ]);

        $eventId = $event->create($payload);
        if ($eventId) {
            $created = $event->getById($eventId);
            $_SESSION['success'] = "Event '{$data['title']}' created successfully!";
            header('Location: /event-detail.php?slug=' . $created['slug']);
            exit;
        } else {
            $errors[] = "Failed to create event. Please try again.";
        }
    }
}

$pageTitle = "Create Event - " . htmlspecialchars($groupData['name']);
require_once __DIR__ . '/../src/views/layouts/header.php';
?>

<div class="container mt-4">
  <div class="row"><div class="col-md-8 mx-auto">
    <!-- Heading -->
    <div class="d-flex align-items-center mb-4">
      <a href="/group-detail.php?slug=<?= htmlspecialchars($groupData['slug']) ?>" class="btn btn-outline-secondary me-3">
        <i class="fas fa-arrow-left"></i> Back
      </a>
      <div>
        <h2 class="mb-1 fw-semibold text-forest">Create Event</h2>
        <p class="text-muted mb-0">in <?= htmlspecialchars($groupData['name']) ?></p>
      </div>
    </div>

    <!-- Alerts -->
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
    <?php elseif (!empty($_SESSION['success'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <!-- Card -->
    <div class="card shadow-sm border-0">
      <!-- Top banner preview (hidden by default) -->
      <div id="bannerWrap" class="banner-wrap" style="display:none;">
        <img id="bannerImg" alt="Event banner preview" class="banner-img">
      </div>

      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="eventForm" novalidate>
          <!-- Basic Info -->
          <h5 class="section-title"><i class="fas fa-info-circle me-2"></i>Basic Info</h5>

          <div class="mb-3">
            <label for="title" class="form-label">Event Title *</label>
            <input id="title" name="title" type="text" class="form-control" required
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                   placeholder="e.g., Katoomba Night Hike & Campfire">
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="3" class="form-control"
                      placeholder="Outline the plan, difficulty, transport, what to expect in the Blue Mountains..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          </div>

          <div class="mb-4">
            <label for="cover_image" class="form-label">Cover Image (banner)</label>
            <input id="cover_image" name="cover_image" type="file" class="form-control" accept="image/jpeg,image/png,image/gif">
            <div class="form-text">Optional. Recommended 1600×600px. Max 5MB.</div>
          </div>

          <!-- Date & Time -->
          <h5 class="section-title"><i class="fas fa-calendar me-2"></i>Date & Time</h5>
          <div class="row mb-4">
            <div class="col-md-4 mb-3">
              <label for="event_date" class="form-label">Event Date *</label>
              <input id="event_date" name="event_date" type="date" class="form-control" required
                     value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label for="start_time" class="form-label">Start Time *</label>
              <input id="start_time" name="start_time" type="time" class="form-control" required
                     value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label for="end_time" class="form-label">End Time</label>
              <input id="end_time" name="end_time" type="time" class="form-control"
                     value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>">
            </div>
          </div>

          <!-- Location -->
          <h5 class="section-title"><i class="fas fa-map-marker-alt me-2"></i>Location</h5>
          <div class="mb-3">
            <label for="locType" class="form-label">Event Type *</label>
            <?php $lt = $_POST['location_type'] ?? 'in_person'; ?>
            <select id="locType" name="location_type" class="form-select" required>
              <option value="in_person" <?= $lt==='in_person'?'selected':'' ?>>In Person</option>
              <?php // blue mountains vibe examples appear in placeholders below ?>
              <option value="online"    <?= $lt==='online'?'selected':'' ?>>Online</option>
              <option value="hybrid"    <?= $lt==='hybrid'?'selected':'' ?>>Hybrid (In Person + Online)</option>
            </select>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3" id="venue_name_group">
              <label for="venue_name" class="form-label">Venue Name</label>
              <input id="venue_name" name="venue_name" type="text" class="form-control"
                     value="<?= htmlspecialchars($_POST['venue_name'] ?? '') ?>" placeholder="Katoomba Community Hall">
            </div>
            <div class="col-md-6 mb-3" id="venue_address_group">
              <label for="venue_address" class="form-label">Venue Address</label>
              <input id="venue_address" name="venue_address" type="text" class="form-control"
                     value="<?= htmlspecialchars($_POST['venue_address'] ?? '') ?>" placeholder="Echo Point Rd, Katoomba NSW">
            </div>
          </div>

          <div class="mb-4" id="online_link_group">
            <label for="online_link" class="form-label">Online Meeting Link</label>
            <input id="online_link" name="online_link" type="url" class="form-control"
                   value="<?= htmlspecialchars($_POST['online_link'] ?? '') ?>" placeholder="https://meet.google.com/...">
            <div class="form-text">Zoom, Google Meet, Teams, etc.</div>
          </div>

          <!-- Details -->
          <h5 class="section-title"><i class="fas fa-cogs me-2"></i>Details</h5>
          <div class="row g-3 align-items-end mb-3">
            <div class="col-sm-6 col-md-4">
              <label for="max_attendees" class="form-label">Max Attendees</label>
              <input id="max_attendees" name="max_attendees" type="number" min="1"
                     class="form-control form-control-sm"
                     value="<?= htmlspecialchars($_POST['max_attendees'] ?? '') ?>" placeholder="e.g., 20">
            </div>
            <div class="col-sm-6 col-md-4">
              <label for="price" class="form-label">Price (AUD)</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input id="price" name="price" type="number" min="0" step="0.01"
                       class="form-control"
                       value="<?= htmlspecialchars($_POST['price'] ?? '0.00') ?>" placeholder="0.00">
              </div>
              <div class="form-text">Set to 0.00 for free events</div>
            </div>
            <div class="col-md-4">
              <label for="tags" class="form-label">Tags</label>
              <input id="tags" name="tags" type="text" class="form-control form-control-sm"
                     value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>" placeholder="bushwalk, lookout, sunset">
            </div>
          </div>

          <div class="mb-4">
            <label for="requirements" class="form-label">Requirements / What to Bring</label>
            <textarea id="requirements" name="requirements" rows="2" class="form-control"
                      placeholder="Water, headlamp, warm layers for Blue Mountains evenings, Opal card, etc."><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
          </div>

          <!-- Visibility -->
          <h5 class="section-title"><i class="fas fa-eye me-2"></i>Visibility</h5>
          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="status" id="status_draft" value="draft" <?= ($_POST['status'] ?? 'draft')==='draft'?'checked':'' ?>>
            <label class="form-check-label" for="status_draft"><strong>Save as Draft</strong> — edit and publish later</label>
          </div>
          <div class="form-check mb-4">
            <input class="form-check-input" type="radio" name="status" id="status_published" value="published" <?= ($_POST['status'] ?? '')==='published'?'checked':'' ?>>
            <label class="form-check-label" for="status_published"><strong>Publish Immediately</strong> — visible to group members</label>
          </div>

          <!-- Actions -->
          <div class="d-flex justify-content-between">
            <a href="/group-detail.php?slug=<?= htmlspecialchars($groupData['slug']) ?>" class="btn btn-outline-secondary">
              <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-forest" id="submitBtn">
              <i class="fas fa-hiking me-1" id="submitIcon"></i> Create Event
            </button>
          </div>
        </form>
      </div>
    </div>
  </div></div>
</div>

<style>
/* Outdoor adventure palette (subtle, professional) */
.text-forest { color: #2f6d3a; }
.btn-forest { background-color: #2f6d3a; border-color: #2f6d3a; color: #fff; }
.btn-forest:hover { background-color: #285d32; border-color: #285d32; color: #fff; }
.section-title { color: #2f6d3a; margin: 0 0 0.75rem; font-weight: 600; }

/* Banner preview inside card */
.banner-wrap {
  width: 100%;
  background: #f6f3ed; /* sandstone tint */
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
  overflow: hidden;
}
.banner-img {
  width: 100%;
  max-height: 400px;
  object-fit: cover;
  object-position: center center;
}



/* Keep numeric spinners visible (Chrome/Edge) – default is fine, but ensure no reset kills it */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button { opacity: 1; }

/* Submit spinner */
#submitIcon.spin { animation: spin 0.8s linear infinite; }
@keyframes spin { from { transform: rotate(0); } to { transform: rotate(360deg); } }
</style>

<script>
(function () {
  const $ = id => document.getElementById(id);

  // Set min date to today (Sydney users — HTML date is local; server-side validates anyway)
  const dateEl = $('event_date');
  if (dateEl) dateEl.min = new Date().toISOString().split('T')[0];

  // Location dynamic fields
  const locType = $('locType'), vNameG = $('venue_name_group'), vAddrG = $('venue_address_group'), oLinkG = $('online_link_group');
  const vName = $('venue_name'), oLink = $('online_link');

  function updateVisibility() {
    const v = locType.value;
    if (vName) vName.removeAttribute('required');
    if (oLink) oLink.removeAttribute('required');

    if (v === 'in_person') {
      vNameG.style.display = vAddrG.style.display = 'block';
      oLinkG.style.display = 'none';
      if (vName) vName.setAttribute('required','required');
    } else if (v === 'online') {
      vNameG.style.display = vAddrG.style.display = 'none';
      oLinkG.style.display = 'block';
      if (oLink) oLink.setAttribute('required','required');
    } else {
      vNameG.style.display = vAddrG.style.display = oLinkG.style.display = 'block';
      if (vName) vName.setAttribute('required','required');
      if (oLink) oLink.setAttribute('required','required');
    }
  }
  if (locType) {
    locType.addEventListener('change', updateVisibility);
    updateVisibility();
  }

  // Top-of-card banner preview (hidden until file chosen)
  const fileInput = $('cover_image');
  const bannerWrap = $('bannerWrap');
  const bannerImg = $('bannerImg');
  let objectUrl = null;

  function showPreview(file) {
    if (!file) { bannerWrap.style.display = 'none'; return; }
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = URL.createObjectURL(file);
    bannerImg.src = objectUrl;
    bannerWrap.style.display = 'block';
  }
  if (fileInput && bannerWrap && bannerImg) {
    fileInput.addEventListener('change', e => showPreview(e.target.files[0]));
  }

  // Submit feedback
  const form = $('eventForm');
  const submitBtn = $('submitBtn');
  const submitIcon = $('submitIcon');
  if (form && submitBtn && submitIcon) {
    form.addEventListener('submit', function () {
      submitBtn.disabled = true;
      submitIcon.classList.add('spin');
      submitBtn.innerHTML = '<i class="fas fa-sync-alt me-1 spin"></i> Creating...';
    });
  }
})();
</script>

<?php require_once __DIR__ . '/../src/views/layouts/footer.php'; ?>
