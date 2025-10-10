<?php
/**
 * Comment Controller
 * Handles event comment operations
 */

require_once __DIR__ . '/../models/EventComment.php';
require_once __DIR__ . '/../models/CommentLike.php';
require_once __DIR__ . '/../models/EventMedia.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/User.php';

class CommentController {
    private $commentModel;
    private $likeModel;
    private $mediaModel;
    private $eventModel;
    private $userModel;

    public function __construct() {
        $this->commentModel = new EventComment();
        $this->likeModel = new CommentLike();
        $this->mediaModel = new EventMedia();
        $this->eventModel = new Event();
        $this->userModel = new User();
    }

    /**
     * Handle comment submission
     */
    public function submitComment() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Authentication required'], 401);
        }

        $userId = $_SESSION['user_id'];
        $eventId = (int)($_POST['event_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        // Validate input
        if (!$eventId || empty($comment)) {
            $this->jsonResponse(['error' => 'Event ID and comment are required'], 400);
        }

        if (strlen($comment) > 1000) {
            $this->jsonResponse(['error' => 'Comment too long (max 1000 characters)'], 400);
        }

        // Check if user can comment on this event
        if (!$this->commentModel->canCommentOnEvent($eventId, $userId)) {
            $this->jsonResponse(['error' => 'You must be attending this event to comment'], 403);
        }

        // Create comment
        $commentData = [
            'event_id' => $eventId,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'comment' => $comment,
            'status' => 'active'
        ];

        $commentId = $this->commentModel->create($commentData);

        if ($commentId) {
            // Get updated comments HTML
            $html = $this->renderComments($eventId);
            
            $this->jsonResponse([
                'success' => true,
                'comment_id' => $commentId,
                'message' => 'Comment posted successfully',
                'html' => $html
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to post comment'], 500);
        }
    }

    /**
     * Handle comment liking
     */
    public function toggleLike() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Authentication required'], 401);
        }

        $userId = $_SESSION['user_id'];
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $reactionType = $_POST['reaction_type'] ?? 'like';

        // Validate input
        if (!$commentId) {
            $this->jsonResponse(['error' => 'Comment ID is required'], 400);
        }

        // Check if comment exists and user can interact with it
        $comment = $this->commentModel->getById($commentId);
        if (!$comment) {
            $this->jsonResponse(['error' => 'Comment not found'], 404);
        }

        // Check if user can comment on this event (same permission as commenting)
        if (!$this->commentModel->canCommentOnEvent($comment['event_id'], $userId)) {
            $this->jsonResponse(['error' => 'You cannot interact with this comment'], 403);
        }

        // Toggle reaction
        $result = $this->likeModel->toggleReaction($commentId, $userId, $reactionType);

        if ($result) {
            // Get updated reaction counts
            $reactionCounts = $this->likeModel->getReactionCounts($commentId);
            $userReaction = $this->likeModel->getUserReaction($commentId, $userId);

            $this->jsonResponse([
                'success' => true,
                'reaction_counts' => $reactionCounts,
                'user_reaction' => $userReaction ? $userReaction['reaction_type'] : null,
                'total_likes' => array_sum($reactionCounts)
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update reaction'], 500);
        }
    }

    /**
     * Handle comment deletion
     */
    public function deleteComment() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Authentication required'], 401);
        }

        $userId = $_SESSION['user_id'];
        $commentId = (int)($_POST['comment_id'] ?? 0);

        // Validate input
        if (!$commentId) {
            $this->jsonResponse(['error' => 'Comment ID is required'], 400);
        }

        // Check if user can manage this comment
        if (!$this->commentModel->canManageComment($commentId, $userId)) {
            $this->jsonResponse(['error' => 'You cannot delete this comment'], 403);
        }

        // Delete comment (soft delete)
        $result = $this->commentModel->delete($commentId);

        if ($result) {
            // Get the event ID for re-rendering
            $comment = $this->commentModel->getById($commentId);
            $eventId = $comment ? $comment['event_id'] : 0;
            $html = $eventId ? $this->renderComments($eventId) : '';
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Comment deleted successfully',
                'html' => $html
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to delete comment'], 500);
        }
    }

    /**
     * Handle media upload
     */
    public function uploadMedia() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['error' => 'Authentication required'], 401);
        }

        $userId = $_SESSION['user_id'];
        $eventId = (int)($_POST['event_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        // Validate input
        if (!$eventId) {
            $this->jsonResponse(['error' => 'Event ID is required'], 400);
        }

        // Check if file was uploaded
        if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['error' => 'No file uploaded or upload error'], 400);
        }

        // Check if user can upload to this event
        if (!$this->mediaModel->canUploadToEvent($eventId, $userId)) {
            $this->jsonResponse(['error' => 'You cannot upload media to this event'], 403);
        }

        // Upload file
        $mediaId = $this->mediaModel->upload($eventId, $userId, $_FILES['media_file'], $description);

        if ($mediaId) {
            $this->jsonResponse([
                'success' => true,
                'media_id' => $mediaId,
                'message' => 'Media uploaded successfully'
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to upload media'], 500);
        }
    }

    /**
     * Get comments for an event (AJAX endpoint)
     */
    public function getComments() {
        $eventId = (int)($_GET['event_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? null;

        if (!$eventId) {
            $this->jsonResponse(['error' => 'Event ID is required'], 400);
        }

        // Check if user can view comments (must be able to comment to view)
        $canView = $userId ? $this->commentModel->canCommentOnEvent($eventId, $userId) : false;

        if (!$canView) {
            $this->jsonResponse(['error' => 'You must be attending this event to view comments'], 403);
        }

        $comments = $this->commentModel->getByEventId($eventId);
        $commentCount = $this->commentModel->getCommentCount($eventId);

        $this->jsonResponse([
            'success' => true,
            'comments' => $comments,
            'total_count' => $commentCount
        ]);
    }

    /**
     * Get media for an event (AJAX endpoint)
     */
    public function getMedia() {
        $eventId = (int)($_GET['event_id'] ?? 0);
        $fileType = $_GET['file_type'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;

        if (!$eventId) {
            $this->jsonResponse(['error' => 'Event ID is required'], 400);
        }

        // Check if user can view media (same as commenting permission)
        $canView = $userId ? $this->commentModel->canCommentOnEvent($eventId, $userId) : false;

        if (!$canView) {
            $this->jsonResponse(['error' => 'You must be attending this event to view media'], 403);
        }

        $media = $this->mediaModel->getByEventId($eventId, $fileType);
        $mediaCount = $this->mediaModel->getMediaCount($eventId);

        $this->jsonResponse([
            'success' => true,
            'media' => $media,
            'total_count' => $mediaCount
        ]);
    }

    /**
     * Helper method for JSON responses
     */
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Render comment form
     */
    public function renderCommentForm($eventId, $parentId = null) {
        $userId = $_SESSION['user_id'] ?? null;
        $canComment = $userId ? $this->commentModel->canCommentOnEvent($eventId, $userId) : false;

        if (!$canComment) {
            return '';
        }

        $placeholder = $parentId ? 'Write a reply...' : 'Share your thoughts about this event...';
        $submitText = $parentId ? 'Reply' : 'Comment';
        $formId = $parentId ? "reply-form-{$parentId}" : 'comment-form';

        ob_start();
        ?>
        <form id="<?= $formId ?>" class="comment-form mb-2" method="POST">
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <?php if ($parentId): ?>
                <input type="hidden" name="parent_id" value="<?= $parentId ?>">
            <?php endif; ?>

            <div class="d-flex">
                <div class="flex-grow-1 me-2">
                    <textarea name="comment" class="form-control form-control-sm" rows="2"
                              placeholder="<?= $placeholder ?>" maxlength="1000" required></textarea>
                </div>
                <div class="d-flex align-items-end">
                    <?php if ($parentId): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2 cancel-reply">
                            <i class="fas fa-times"></i>
                            <span class="d-none d-sm-inline ms-1">Cancel</span>
                        </button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        <span class="d-none d-sm-inline ms-1"><?= $submitText ?></span>
                    </button>
                </div>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Render comments section
     */
    public function renderComments($eventId) {
        $userId = $_SESSION['user_id'] ?? null;
        $canComment = $userId ? $this->commentModel->canCommentOnEvent($eventId, $userId) : false;
        $comments = $canComment ? $this->commentModel->getByEventId($eventId) : [];
        $commentCount = $this->commentModel->getCommentCount($eventId);

        ob_start();
        ?>
        <div class="comments-section mt-3" data-event-id="<?= $eventId ?>">
            <h5 class="mb-2">
                <i class="fas fa-comments text-primary"></i>
                Discussion (<?= $commentCount ?>)
            </h5>

            <?php if ($canComment): ?>
                <!-- Comment Form -->
                <div class="card mb-3">
                    <div class="card-body py-2">
                        <?= $this->renderCommentForm($eventId) ?>
                    </div>
                </div>
            <?php elseif ($userId): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i>
                    Commenting requires an active membership.
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-sign-in-alt me-1"></i>
                    <a href="/login.php" class="alert-link">Log in</a> and attend this event to join the discussion.
                </div>
            <?php endif; ?>

            <!-- Comments List -->
            <div id="comments-list">
                <?php if (!empty($comments)): ?>
                    <?= $this->renderCommentsList($comments, $canComment) ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p>No comments yet. Be the first to share your thoughts!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Render comments list recursively
     */
    private function renderCommentsList($comments, $canComment, $level = 0) {
        ob_start();

        foreach ($comments as $comment) {
            $isReply = $level > 0;
            $canManage = $this->commentModel->canManageComment($comment['id'], $_SESSION['user_id'] ?? null);
            ?>
            <div class="comment <?= $isReply ? 'reply ms-5 border-start border-secondary border-3' : 'mb-2' ?>" data-comment-id="<?= $comment['id'] ?>" style="<?= $isReply ? 'background-color: #f8f9fa; border-left-color: #6c757d !important;' : '' ?>">
                <div class="card border-0 bg-transparent">
                    <div class="card-body py-1">
                        <div class="d-flex align-items-start">
                            <!-- Avatar -->
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                 style="width:28px;height:28px;font-size:12px;">
                                <?= strtoupper(substr($comment['author_name'], 0, 1)) ?>
                            </div>

                            <div class="flex-grow-1">
                                <!-- Author and timestamp -->
                                <div class="d-flex align-items-center mb-1">
                                    <strong class="me-2" style="font-size: 0.9rem;"><?= htmlspecialchars($comment['author_name']) ?></strong>
                                    <?php if ($comment['author_role'] === 'organizer'): ?>
                                        <span class="badge bg-success" style="font-size: 0.75rem;">Organizer</span>
                                    <?php endif; ?>
                                    <small class="text-muted ms-auto" style="font-size: 0.8rem;">
                                        <?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?>
                                    </small>
                                </div>

                                <!-- Comment content -->
                                <div class="comment-content mb-1" style="font-size: 0.9rem; line-height: 1.4;">
                                    <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex align-items-center">
                                    <!-- Like button -->
                                    <button class="btn btn-sm btn-link like-btn p-0 me-2 <?= $comment['user_liked'] ? 'text-primary' : 'text-muted' ?>" style="font-size: 0.8rem;"
                                            data-comment-id="<?= $comment['id'] ?>">
                                        <i class="fas fa-thumbs-up me-1"></i>
                                        <span class="likes-count"><?= $comment['likes_count'] ?? 0 ?></span>
                                    </button>

                                    <!-- Reply button -->
                                    <?php if ($canComment && !$isReply): ?>
                                        <button class="btn btn-sm btn-link text-muted reply-btn p-0 me-2" style="font-size: 0.8rem;"
                                                data-comment-id="<?= $comment['id'] ?>">
                                            <i class="fas fa-reply me-1"></i> Reply
                                        </button>
                                    <?php endif; ?>

                                    <!-- Manage button -->
                                    <?php if ($canManage): ?>
                                        <button class="btn btn-sm btn-link text-danger delete-comment ms-1" style="font-size: 0.8rem;"
                                                data-comment-id="<?= $comment['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <!-- Reply form (hidden by default) -->
                                <?php if ($canComment && !$isReply): ?>
                                    <div class="reply-form mt-2" data-comment-id="<?= $comment['id'] ?>" style="display:none;">
                                        <?= $this->renderCommentForm($comment['event_id'], $comment['id']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Render replies -->
                <?php if (!empty($comment['replies'])): ?>
                    <div class="replies mt-1">
                        <?= $this->renderCommentsList($comment['replies'], $canComment, $level + 1) ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }

        return ob_get_clean();
    }
}
?>