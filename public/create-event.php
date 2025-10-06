<?php
/**
 * Create Event Page
 * Allow group owners, co-hosts, and moderators to create events
 */

session_start();
require_once __DIR__ . '/../src/models/Group.php';
require_once __DIR__ . '/../src/models/Event.php';
require_once __DIR__ . '/../src/helpers/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /connecthub/public/login.php');
    exit;
}

$group = new Group();
$event = new Event();
$errors = [];
$success = false;

// Get group ID from URL
$groupId = $_GET['group_id'] ?? null;
if (!$groupId) {
    header('Location: /connecthub/public/groups.php');
    exit;
}

// Get group details
$groupData = $group->getById($groupId);
if (!$groupData) {
    header('Location: /connecthub/public/groups.php');
    exit;
}

// Check if user can create events in this group
if (!$group->canUserCreateEvents($_SESSION['user_id'], $groupId)) {
    $_SESSION['error'] = "You don't have permission to create events in this group.";
    header('Location: /connecthub/public/group-detail.php?slug=' . $groupData['slug']);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $locationType = $_POST['location_type'] ?? 'in_person';
    $venueName = trim($_POST['venue_name'] ?? '');
    $venueAddress = trim($_POST['venue_address'] ?? '');
    $onlineLink = trim($_POST['online_link'] ?? '');
    $maxAttendees = $_POST['max_attendees'] ?? '';
    $price = $_POST['price'] ?? '0.00';
    $requirements = trim($_POST['requirements'] ?? '');
    $tags = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
    $status = $_POST['status'] ?? 'draft';
    
    // Validation
    if (empty($title)) {
        $errors[] = "Event title is required.";
    }
    
    if (empty($eventDate)) {
        $errors[] = "Event date is required.";
    } elseif (strtotime($eventDate) < strtotime('today')) {
        $errors[] = "Event date cannot be in the past.";
    }
    
    if (empty($startTime)) {
        $errors[] = "Start time is required.";
    }
    
    if ($locationType === 'in_person' && empty($venueName)) {
        $errors[] = "Venue name is required for in-person events.";
    }
    
    if ($locationType === 'online' && empty($onlineLink)) {
        $errors[] = "Online meeting link is required for online events.";
    }
    
    if ($locationType === 'hybrid' && (empty($venueName) || empty($onlineLink))) {
        $errors[] = "Both venue and online link are required for hybrid events.";
    }
    
    if (!empty($maxAttendees) && (!is_numeric($maxAttendees) || $maxAttendees < 1)) {
        $errors[] = "Maximum attendees must be a positive number.";
    }
    
    if (!is_numeric($price) || $price < 0) {
        $errors[] = "Price must be a valid amount.";
    }
    
    // Handle image upload
    $coverImage = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/events/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileInfo = pathinfo($_FILES['cover_image']['name']);
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExt = strtolower($fileInfo['extension']);
        
        if (!in_array($fileExt, $allowedTypes)) {
            $errors[] = "Invalid image type. Please use JPG, PNG, or GIF.";
        } elseif ($_FILES['cover_image']['size'] > 5242880) { // 5MB
            $errors[] = "Image file is too large. Maximum size is 5MB.";
        } else {
            $fileName = 'event_' . uniqid() . '.' . $fileExt;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
                $coverImage = 'uploads/events/' . $fileName;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }
    
    if (empty($errors)) {
        $eventData = [
            'title' => $title,
            'description' => $description,
            'group_id' => $groupId,
            'created_by' => $_SESSION['user_id'],
            'event_date' => $eventDate,
            'start_time' => $startTime,
            'end_time' => !empty($endTime) ? $endTime : null,
            'location_type' => $locationType,
            'venue_name' => !empty($venueName) ? $venueName : null,
            'venue_address' => !empty($venueAddress) ? $venueAddress : null,
            'online_link' => !empty($onlineLink) ? $onlineLink : null,
            'max_attendees' => !empty($maxAttendees) ? (int)$maxAttendees : null,
            'price' => (float)$price,
            'requirements' => !empty($requirements) ? $requirements : null,
            'tags' => $tags,
            'status' => $status,
            'cover_image' => $coverImage
        ];
        
        $eventId = $event->create($eventData);
        
        if ($eventId) {
            $createdEvent = $event->getById($eventId);
            $_SESSION['success'] = "Event '{$title}' created successfully!";
            header('Location: http://localhost/event-detail.php?slug=' . $createdEvent['slug']);
            exit;
        } else {
            $errors[] = "Failed to create event. Please try again.";
        }
    }
}

// Get event categories for tags suggestions (with error handling)
$categories = [];
try {
    $categories = $event->getCategories();
} catch (Exception $e) {
    // Categories table might not exist yet, that's okay
    error_log("Categories not available: " . $e->getMessage());
}

$pageTitle = "Create Event - " . htmlspecialchars($groupData['name']);
require_once __DIR__ . '/../src/views/layouts/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Header -->
            <div class="d-flex align-items-center mb-4">
                <a href="/connecthub/public/group-detail.php?slug=<?= htmlspecialchars($groupData['slug']) ?>" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left"></i> Back to Group
                </a>
                <div>
                    <h2 class="mb-1">Create New Event</h2>
                    <p class="text-muted mb-0">in <?= htmlspecialchars($groupData['name']) ?></p>
                </div>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Event Creation Form -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" id="createEventForm" enctype="multipart/form-data">
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-info-circle"></i> Basic Information</h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Tell people what this event is about..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="cover_image" class="form-label">Event Cover Image</label>
                                <input type="file" class="form-control" id="cover_image" name="cover_image" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif">
                                <div class="form-text">Optional. Recommended size: 1200x630px. Max 5MB. (JPG, PNG, GIF)</div>
                            </div>
                        </div>

                        <!-- Date and Time -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-calendar"></i> Date & Time</h5>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="event_date" class="form-label">Event Date *</label>
                                <input type="date" class="form-control" id="event_date" name="event_date" 
                                       value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" 
                                       value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" 
                                       value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-map-marker-alt"></i> Location</h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="location_type" class="form-label">Event Type *</label>
                                <select class="form-select" id="location_type" name="location_type" required>
                                    <option value="in_person" <?= ($_POST['location_type'] ?? 'in_person') === 'in_person' ? 'selected' : '' ?>>In Person</option>
                                    <option value="online" <?= ($_POST['location_type'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
                                    <option value="hybrid" <?= ($_POST['location_type'] ?? '') === 'hybrid' ? 'selected' : '' ?>>Hybrid (In Person + Online)</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="venue_name_group">
                                <label for="venue_name" class="form-label">Venue Name</label>
                                <input type="text" class="form-control" id="venue_name" name="venue_name" 
                                       value="<?= htmlspecialchars($_POST['venue_name'] ?? '') ?>" 
                                       placeholder="e.g., Phoenix Tech Hub">
                            </div>
                            
                            <div class="col-md-6 mb-3" id="venue_address_group">
                                <label for="venue_address" class="form-label">Venue Address</label>
                                <input type="text" class="form-control" id="venue_address" name="venue_address" 
                                       value="<?= htmlspecialchars($_POST['venue_address'] ?? '') ?>" 
                                       placeholder="123 Main St, Phoenix, AZ 85001">
                            </div>
                            
                            <div class="col-12 mb-3" id="online_link_group" style="display: none;">
                                <label for="online_link" class="form-label">Online Meeting Link</label>
                                <input type="url" class="form-control" id="online_link" name="online_link" 
                                       value="<?= htmlspecialchars($_POST['online_link'] ?? '') ?>" 
                                       placeholder="https://zoom.us/j/...">
                                <div class="form-text">Zoom, Google Meet, Teams, etc.</div>
                            </div>
                        </div>

                        <!-- Event Details -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-cogs"></i> Event Details</h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="max_attendees" class="form-label">Maximum Attendees</label>
                                <input type="number" class="form-control" id="max_attendees" name="max_attendees" 
                                       value="<?= htmlspecialchars($_POST['max_attendees'] ?? '') ?>" min="1" 
                                       placeholder="Leave empty for unlimited">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (USD)</label>
                                <input type="number" class="form-control" id="price" name="price" 
                                       value="<?= htmlspecialchars($_POST['price'] ?? '0.00') ?>" 
                                       min="0" step="0.01" placeholder="0.00">
                                <div class="form-text">Set to 0.00 for free events</div>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags" 
                                       value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>" 
                                       placeholder="networking, tech, workshop (comma-separated)">
                                <div class="form-text">Add tags to help people find your event</div>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="requirements" class="form-label">Requirements/What to Bring</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="3" 
                                          placeholder="Any requirements, what attendees should bring, prerequisites, etc."><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Publish Options -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3"><i class="fas fa-eye"></i> Visibility</h5>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="status" id="status_draft" 
                                           value="draft" <?= ($_POST['status'] ?? 'draft') === 'draft' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="status_draft">
                                        <strong>Save as Draft</strong> - You can edit and publish later
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="status" id="status_published" 
                                           value="published" <?= ($_POST['status'] ?? '') === 'published' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="status_published">
                                        <strong>Publish Immediately</strong> - Event will be visible to group members
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="http://localhost/group-detail.php?slug=<?= htmlspecialchars($groupData['slug']) ?>" 
                               class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-calendar-plus"></i> Create Event
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dynamic form behavior based on location type
document.getElementById('location_type').addEventListener('change', function() {
    const locationType = this.value;
    const venueNameGroup = document.getElementById('venue_name_group');
    const venueAddressGroup = document.getElementById('venue_address_group');
    const onlineLinkGroup = document.getElementById('online_link_group');
    const venueName = document.getElementById('venue_name');
    const onlineLink = document.getElementById('online_link');
    
    // Reset required attributes
    venueName.removeAttribute('required');
    onlineLink.removeAttribute('required');
    
    if (locationType === 'in_person') {
        venueNameGroup.style.display = 'block';
        venueAddressGroup.style.display = 'block';
        onlineLinkGroup.style.display = 'none';
        venueName.setAttribute('required', 'required');
    } else if (locationType === 'online') {
        venueNameGroup.style.display = 'none';
        venueAddressGroup.style.display = 'none';
        onlineLinkGroup.style.display = 'block';
        onlineLink.setAttribute('required', 'required');
    } else if (locationType === 'hybrid') {
        venueNameGroup.style.display = 'block';
        venueAddressGroup.style.display = 'block';
        onlineLinkGroup.style.display = 'block';
        venueName.setAttribute('required', 'required');
        onlineLink.setAttribute('required', 'required');
    }
});

// Set minimum date to today
document.getElementById('event_date').min = new Date().toISOString().split('T')[0];

// Initialize location type visibility on page load
document.getElementById('location_type').dispatchEvent(new Event('change'));
</script>

<?php require_once __DIR__ . '/../src/views/layouts/footer.php'; ?>