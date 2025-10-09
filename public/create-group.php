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

// Check if user is organizer and has valid membership
if (!isOrganizer()) {
    setFlashMessage('error', 'Only organizers and admins can create groups.');
    redirect(BASE_URL . '/groups.php');
}

if (!hasValidMembership($currentUser)) {
    setFlashMessage('error', 'You need a valid membership to create groups.');
    redirect(BASE_URL . '/membership.php');
}

require_once '../src/models/Group.php';
$groupModel = new Group();

// Get categories for the form
$categories = $groupModel->getCategories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $privacy_level = $_POST['privacy_level'] ?? 'public';
    $location = trim($_POST['location'] ?? '');
    $max_members = !empty($_POST['max_members']) ? (int)$_POST['max_members'] : null;
    $meeting_frequency = trim($_POST['meeting_frequency'] ?? '');
    $website_url = trim($_POST['website_url'] ?? '');
    $rules = trim($_POST['rules'] ?? '');
    
    // Handle image upload
    $cover_image = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../public/uploads/groups/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $fileType = $_FILES['cover_image']['type'];
        $fileSize = $_FILES['cover_image']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = 'Cover image must be JPEG, PNG, or WebP format.';
        } elseif ($fileSize > $maxSize) {
            $errors[] = 'Cover image must be less than 5MB.';
        } else {
            $extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('group_') . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadPath)) {
                $cover_image = 'uploads/groups/' . $filename;
            } else {
                $errors[] = 'Failed to upload cover image.';
            }
        }
    }
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Group name is required.';
    } elseif (strlen($name) > 255) {
        $errors[] = 'Group name must be less than 255 characters.';
    }
    
    if (empty($description)) {
        $errors[] = 'Group description is required.';
    } elseif (strlen($description) < 20) {
        $errors[] = 'Group description must be at least 20 characters.';
    }
    
    if (empty($category)) {
        $errors[] = 'Please select a category.';
    }
    
    if (!in_array($privacy_level, ['public', 'private', 'secret'])) {
        $errors[] = 'Invalid privacy level.';
    }
    
    if ($max_members !== null && $max_members < 2) {
        $errors[] = 'Maximum members must be at least 2.';
    }
    
    if (!empty($website_url) && !filter_var($website_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid website URL.';
    }
    
    // If no errors, create the group
    if (empty($errors)) {
        $groupData = [
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'privacy_level' => $privacy_level,
            'location' => $location,
            'max_members' => $max_members,
            'meeting_frequency' => $meeting_frequency,
            'website_url' => $website_url,
            'rules' => $rules,
            'created_by' => $currentUser['id'],
            'cover_image' => $cover_image
        ];
        
        $groupId = $groupModel->create($groupData);
        
        if ($groupId) {
            setFlashMessage('success', 'Group created successfully! You are now the group admin.');
            
            // Get the created group to redirect to its page
            $newGroup = $groupModel->getById($groupId);
            redirect(BASE_URL . '/group-detail.php?slug=' . urlencode($newGroup['slug']));
        } else {
            $errors[] = 'Failed to create group. Please try again.';
        }
    }
    
    // If there are errors, they will be displayed in the form
}

$pageTitle = 'Create Group';
?>

<?php include '../src/views/layouts/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Page Header -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h1 class="h2 mb-2">
                        <i class="fas fa-plus-circle text-primary me-2"></i>Create New Group
                    </h1>
                    <p class="text-muted mb-0">Start a community around your interests and connect with like-minded people</p>
                </div>
            </div>

            <!-- Create Group Form -->
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">Please fix the following errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- Cover Image Upload -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="fas fa-image text-primary me-2"></i>Group Image
                                </h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="cover_image" class="form-label">Cover Image (Optional)</label>
                                <input type="file" class="form-control" id="cover_image" name="cover_image" 
                                       accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">
                                    Upload a cover image for your group. JPEG, PNG, or WebP format. Maximum 5MB.
                                    <br><small class="text-muted">Recommended size: 800x400 pixels for best display.</small>
                                </div>
                                
                                <!-- Image Preview -->
                                <div id="imagePreview" class="mt-3" style="display: none;">
                                    <div class="card" style="max-width: 300px;">
                                        <img id="previewImg" src="" class="card-img-top" alt="Preview" style="height: 150px; object-fit: cover;">
                                        <div class="card-body p-2">
                                            <small class="text-muted">Preview</small>
                                            <button type="button" class="btn btn-sm btn-outline-danger float-end" onclick="removePreview()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="fas fa-info-circle text-primary me-2"></i>Basic Information
                                </h5>
                            </div>
                            
                            <div class="col-md-8 mb-3">
                                <label for="name" class="form-label">Group Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                       placeholder="e.g., Phoenix Tech Enthusiasts" required>
                                <div class="form-text">Choose a clear, descriptive name for your group</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['name']); ?>"
                                                <?php echo ($_POST['category'] ?? '') === $cat['name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                          placeholder="Describe what your group is about, what activities you'll do, and who should join..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                <div class="form-text">Minimum 20 characters. Be specific about your group's purpose and activities.</div>
                            </div>
                        </div>

                        <!-- Settings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="fas fa-cog text-primary me-2"></i>Group Settings
                                </h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="privacy_level" class="form-label">Privacy Level</label>
                                <select class="form-select" id="privacy_level" name="privacy_level">
                                    <option value="public" <?php echo ($_POST['privacy_level'] ?? 'public') === 'public' ? 'selected' : ''; ?>>
                                        Public - Anyone can see and join
                                    </option>
                                    <option value="private" <?php echo ($_POST['privacy_level'] ?? '') === 'private' ? 'selected' : ''; ?>>
                                        Private - Visible but requires approval to join
                                    </option>
                                    <option value="secret" <?php echo ($_POST['privacy_level'] ?? '') === 'secret' ? 'selected' : ''; ?>>
                                        Secret - Invitation only, not visible in search
                                    </option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="max_members" class="form-label">Maximum Members (Optional)</label>
                                <input type="number" class="form-control" id="max_members" name="max_members" 
                                       value="<?php echo htmlspecialchars($_POST['max_members'] ?? ''); ?>" 
                                       placeholder="Leave blank for unlimited" min="2" max="10000">
                                <div class="form-text">Limit the size of your group (minimum 2 members)</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" 
                                       placeholder="e.g., Phoenix, AZ or Online">
                                <div class="form-text">Where will your group meet?</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="meeting_frequency" class="form-label">Meeting Frequency</label>
                                <select class="form-select" id="meeting_frequency" name="meeting_frequency">
                                    <option value="">Select frequency</option>
                                    <option value="Weekly" <?php echo ($_POST['meeting_frequency'] ?? '') === 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="Bi-weekly" <?php echo ($_POST['meeting_frequency'] ?? '') === 'Bi-weekly' ? 'selected' : ''; ?>>Bi-weekly</option>
                                    <option value="Monthly" <?php echo ($_POST['meeting_frequency'] ?? '') === 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="Quarterly" <?php echo ($_POST['meeting_frequency'] ?? '') === 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                                    <option value="As needed" <?php echo ($_POST['meeting_frequency'] ?? '') === 'As needed' ? 'selected' : ''; ?>>As needed</option>
                                    <option value="Irregular" <?php echo ($_POST['meeting_frequency'] ?? '') === 'Irregular' ? 'selected' : ''; ?>>Irregular</option>
                                </select>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="mb-3">
                                    <i class="fas fa-plus text-primary me-2"></i>Additional Information
                                </h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="website_url" class="form-label">Website URL (Optional)</label>
                                <input type="url" class="form-control" id="website_url" name="website_url" 
                                       value="<?php echo htmlspecialchars($_POST['website_url'] ?? ''); ?>" 
                                       placeholder="https://example.com">
                                <div class="form-text">Link to your group's website or social media</div>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="rules" class="form-label">Group Rules (Optional)</label>
                                <textarea class="form-control" id="rules" name="rules" rows="4" 
                                          placeholder="Set clear expectations for group members..."><?php echo htmlspecialchars($_POST['rules'] ?? ''); ?></textarea>
                                <div class="form-text">Guidelines and rules for group members to follow</div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo BASE_URL; ?>/groups.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create Group
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="fas fa-lightbulb text-warning me-2"></i>Tips for Creating a Successful Group
                    </h6>
                    <ul class="mb-0 text-muted">
                        <li>Be specific about your group's purpose and target audience</li>
                        <li>Choose a descriptive name that clearly indicates what your group is about</li>
                        <li>Set clear rules and expectations for members</li>
                        <li>Plan regular activities to keep members engaged</li>
                        <li>Start with a small, focused group and grow gradually</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview functionality
document.getElementById('cover_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (file) {
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a JPEG, PNG, or WebP image.');
            e.target.value = '';
            preview.style.display = 'none';
            return;
        }
        
        // Check file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Image must be less than 5MB.');
            e.target.value = '';
            preview.style.display = 'none';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

function removePreview() {
    document.getElementById('cover_image').value = '';
    document.getElementById('imagePreview').style.display = 'none';
}
</script>

<?php include '../src/views/layouts/footer.php'; ?>