<?php
/**
 * EventMedia Model
 * Handles media attachments for events
 */

require_once __DIR__ . '/../../config/database.php';

class EventMedia {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Upload media file for an event
     */
    public function upload($eventId, $userId, $file, $description = '') {
        // Validate file
        if (!$this->validateFile($file)) {
            return false;
        }

        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('event_' . $eventId . '_') . '.' . $extension;

        // Determine file type
        $fileType = $this->getFileType($file['type']);

        // Upload directory
        $uploadDir = __DIR__ . '/../../public/uploads/event-media/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filePath = 'uploads/event-media/' . $filename;
        $fullPath = __DIR__ . '/../../public/' . $filePath;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return false;
        }

        // Save to database
        $sql = "INSERT INTO event_media (event_id, user_id, filename, original_filename, file_path, file_type, file_size, mime_type, description)
                VALUES (:event_id, :user_id, :filename, :original_filename, :file_path, :file_type, :file_size, :mime_type, :description)
                RETURNING id";

        $params = [
            ':event_id' => $eventId,
            ':user_id' => $userId,
            ':filename' => $filename,
            ':original_filename' => $file['name'],
            ':file_path' => $filePath,
            ':file_type' => $fileType,
            ':file_size' => $file['size'],
            ':mime_type' => $file['type'],
            ':description' => $description
        ];

        $result = $this->db->fetch($sql, $params);
        return $result ? $result['id'] : false;
    }

    /**
     * Get media files for an event
     */
    public function getByEventId($eventId, $fileType = null) {
        $sql = "SELECT em.*, u.name as uploader_name
                FROM event_media em
                JOIN users u ON em.user_id = u.id
                WHERE em.event_id = :event_id AND em.status = 'active'";

        $params = [':event_id' => $eventId];

        if ($fileType) {
            $sql .= " AND em.file_type = :file_type";
            $params[':file_type'] = $fileType;
        }

        $sql .= " ORDER BY em.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get media file by ID
     */
    public function getById($id) {
        $sql = "SELECT em.*, u.name as uploader_name, e.title as event_title
                FROM event_media em
                JOIN users u ON em.user_id = u.id
                JOIN events e ON em.event_id = e.id
                WHERE em.id = :id";

        return $this->db->fetch($sql, [':id' => $id]);
    }

    /**
     * Delete media file
     */
    public function delete($id, $userId = null) {
        // Get file info first
        $media = $this->getById($id);
        if (!$media) return false;

        // Check permissions if userId provided
        if ($userId && $media['user_id'] != $userId) {
            // Check if user can manage the event
            $eventModel = new Event();
            if (!$eventModel->canManageEvent($media['event_id'], $userId)) {
                return false;
            }
        }

        // Soft delete from database
        $sql = "UPDATE event_media SET status = 'deleted' WHERE id = :id";
        $result = $this->db->query($sql, [':id' => $id]);

        if ($result) {
            // Optionally delete physical file
            $fullPath = __DIR__ . '/../../public/' . $media['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        return $result;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return false;
        }

        // Check file type
        $allowedTypes = [
            // Images
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            // Videos
            'video/mp4', 'video/avi', 'video/mov', 'video/wmv',
            // Documents
            'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            // Audio
            'audio/mpeg', 'audio/wav', 'audio/mp3'
        ];

        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }

        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'avi', 'mov', 'wmv', 'pdf', 'doc', 'docx', 'mp3', 'wav', 'mpeg'];

        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        return true;
    }

    /**
     * Determine file type from MIME type
     */
    private function getFileType($mimeType) {
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'video';
        } elseif (strpos($mimeType, 'audio/') === 0) {
            return 'audio';
        } else {
            return 'document';
        }
    }

    /**
     * Get file type icon
     */
    public static function getFileIcon($fileType) {
        return match($fileType) {
            'image' => 'fas fa-image',
            'video' => 'fas fa-video',
            'audio' => 'fas fa-music',
            'document' => 'fas fa-file',
            default => 'fas fa-file'
        };
    }

    /**
     * Get file type color
     */
    public static function getFileColor($fileType) {
        return match($fileType) {
            'image' => 'primary',
            'video' => 'danger',
            'audio' => 'warning',
            'document' => 'success',
            default => 'secondary'
        };
    }

    /**
     * Check if user can upload media to event
     */
    public function canUploadToEvent($eventId, $userId) {
        // Must be logged in
        if (!$userId) return false;

        // Check if user is attending the event or is the organizer
        $sql = "SELECT e.created_by, ea.user_id
                FROM events e
                LEFT JOIN event_attendees ea ON e.id = ea.event_id AND ea.user_id = :user_id AND ea.status = 'going'
                WHERE e.id = :event_id";

        $result = $this->db->fetch($sql, [
            ':event_id' => $eventId,
            ':user_id' => $userId
        ]);

        if (!$result) return false;

        // Event organizer can always upload
        if ($result['created_by'] == $userId) return true;

        // Attendees can upload media
        if ($result['user_id'] == $userId) return true;

        return false;
    }

    /**
     * Get media count for event
     */
    public function getMediaCount($eventId, $fileType = null) {
        $sql = "SELECT COUNT(*) as count FROM event_media WHERE event_id = :event_id AND status = 'active'";
        $params = [':event_id' => $eventId];

        if ($fileType) {
            $sql .= " AND file_type = :file_type";
            $params[':file_type'] = $fileType;
        }

        $result = $this->db->fetch($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }
}