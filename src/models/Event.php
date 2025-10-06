<?php
/**
 * Event Model
 */

class Event {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new event
     */
    public function create($data) {
        $sql = "INSERT INTO events (title, slug, description, image, group_id, organizer_id,
                venue_name, venue_address, latitude, longitude, start_datetime, end_datetime,
                max_attendees, registration_deadline, fee, is_online, online_link, tags, status) 
                VALUES (:title, :slug, :description, :image, :group_id, :organizer_id,
                :venue_name, :venue_address, :latitude, :longitude, :start_datetime, :end_datetime,
                :max_attendees, :registration_deadline, :fee, :is_online, :online_link, :tags, :status)";
        
        $params = [
            ':title' => $data['title'],
            ':slug' => $this->generateSlug($data['title']),
            ':description' => $data['description'],
            ':image' => $data['image'] ?? null,
            ':group_id' => $data['group_id'],
            ':organizer_id' => $data['organizer_id'],
            ':venue_name' => $data['venue_name'] ?? null,
            ':venue_address' => $data['venue_address'] ?? null,
            ':latitude' => $data['latitude'] ?? null,
            ':longitude' => $data['longitude'] ?? null,
            ':start_datetime' => $data['start_datetime'],
            ':end_datetime' => $data['end_datetime'],
            ':max_attendees' => $data['max_attendees'] ?? null,
            ':registration_deadline' => $data['registration_deadline'] ?? null,
            ':fee' => $data['fee'] ?? 0.00,
            ':is_online' => $data['is_online'] ?? false,
            ':online_link' => $data['online_link'] ?? null,
            ':tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
            ':status' => $data['status'] ?? EVENT_DRAFT
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Find event by ID
     */
    public function findById($id) {
        $sql = "SELECT e.*, g.name as group_name, g.slug as group_slug,
                       u.first_name, u.last_name, u.username as organizer_username
                FROM events e 
                LEFT JOIN groups g ON e.group_id = g.id 
                LEFT JOIN users u ON e.organizer_id = u.id 
                WHERE e.id = :id";
        return $this->db->fetch($sql, [':id' => $id]);
    }
    
    /**
     * Find event by slug
     */
    public function findBySlug($slug) {
        $sql = "SELECT e.*, g.name as group_name, g.slug as group_slug,
                       u.first_name, u.last_name, u.username as organizer_username
                FROM events e 
                LEFT JOIN groups g ON e.group_id = g.id 
                LEFT JOIN users u ON e.organizer_id = u.id 
                WHERE e.slug = :slug";
        return $this->db->fetch($sql, [':slug' => $slug]);
    }
    
    /**
     * Get upcoming events
     */
    public function getUpcoming($page = 1, $limit = EVENTS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        $whereConditions = ['e.status = :status', 'e.start_datetime > NOW()'];
        $params = [':status' => EVENT_PUBLISHED];
        
        // Apply filters
        if (!empty($filters['group_id'])) {
            $whereConditions[] = 'e.group_id = :group_id';
            $params[':group_id'] = $filters['group_id'];
        }
        
        if (!empty($filters['city'])) {
            $whereConditions[] = 'g.location_city LIKE :city';
            $params[':city'] = '%' . $filters['city'] . '%';
        }
        
        if (!empty($filters['search'])) {
            $whereConditions[] = '(e.title LIKE :search OR e.description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT e.*, g.name as group_name, g.slug as group_slug,
                       u.first_name, u.last_name
                FROM events e 
                LEFT JOIN groups g ON e.group_id = g.id 
                LEFT JOIN users u ON e.organizer_id = u.id 
                WHERE $whereClause 
                ORDER BY e.start_datetime ASC 
                LIMIT :limit OFFSET :offset";
        
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Add attendee to event
     */
    public function addAttendee($eventId, $userId, $status = 'attending') {
        $sql = "INSERT INTO event_attendees (event_id, user_id, status) 
                VALUES (:event_id, :user_id, :status) 
                ON DUPLICATE KEY UPDATE status = :status, registered_at = NOW()";
        
        $params = [
            ':event_id' => $eventId,
            ':user_id' => $userId,
            ':status' => $status
        ];
        
        $this->db->query($sql, $params);
        
        // Update attendee count
        $this->updateAttendeeCount($eventId);
        
        return true;
    }
    
    /**
     * Remove attendee from event
     */
    public function removeAttendee($eventId, $userId) {
        $sql = "DELETE FROM event_attendees WHERE event_id = :event_id AND user_id = :user_id";
        $this->db->query($sql, [':event_id' => $eventId, ':user_id' => $userId]);
        
        // Update attendee count
        $this->updateAttendeeCount($eventId);
        
        return true;
    }
    
    /**
     * Check if user is attending event
     */
    public function isAttending($eventId, $userId) {
        $sql = "SELECT status FROM event_attendees 
                WHERE event_id = :event_id AND user_id = :user_id";
        $result = $this->db->fetch($sql, [':event_id' => $eventId, ':user_id' => $userId]);
        return $result ? $result['status'] : false;
    }
    
    /**
     * Get event attendees
     */
    public function getAttendees($eventId, $page = 1, $limit = MEMBERS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_image,
                       ea.status, ea.registered_at, ea.checked_in
                FROM event_attendees ea
                JOIN users u ON ea.user_id = u.id
                WHERE ea.event_id = :event_id
                ORDER BY ea.registered_at ASC
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            ':event_id' => $eventId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }
    
    /**
     * Check in attendee
     */
    public function checkInAttendee($eventId, $userId) {
        $sql = "UPDATE event_attendees SET checked_in = TRUE 
                WHERE event_id = :event_id AND user_id = :user_id";
        $this->db->query($sql, [':event_id' => $eventId, ':user_id' => $userId]);
        return true;
    }
    
    /**
     * Update event
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                if ($key === 'tags' && is_array($value)) {
                    $value = json_encode($value);
                }
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE events SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $this->db->query($sql, $params);
        return true;
    }
    
    /**
     * Mark event as completed and award points to organizer
     */
    public function markCompleted($eventId) {
        $event = $this->findById($eventId);
        if (!$event) return false;
        
        $this->db->beginTransaction();
        
        try {
            // Update event status
            $this->update($eventId, ['status' => EVENT_COMPLETED]);
            
            // Award points to organizer
            $userModel = new User();
            $userModel->addPoints(
                $event['organizer_id'], 
                ORGANIZER_POINTS_PER_EVENT, 
                "Points earned for organizing event: " . $event['title'],
                $eventId
            );
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    private function generateSlug($title) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists and modify if necessary
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    private function slugExists($slug) {
        $sql = "SELECT id FROM events WHERE slug = :slug";
        $result = $this->db->fetch($sql, [':slug' => $slug]);
        return !empty($result);
    }
    
    private function updateAttendeeCount($eventId) {
        $sql = "UPDATE events SET current_attendees = (
                    SELECT COUNT(*) FROM event_attendees 
                    WHERE event_id = :event_id AND status = 'attending'
                ) WHERE id = :event_id";
        $this->db->query($sql, [':event_id' => $eventId]);
    }
}