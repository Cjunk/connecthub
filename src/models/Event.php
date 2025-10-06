<?php
/**
 * Event Model
 * Handles all event-related database operations
 */

require_once __DIR__ . '/../../config/database.php';

class Event {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new event
     */
    public function create($data) {
        $slug = $this->generateSlug($data['title']);
        
        $sql = "INSERT INTO events (title, slug, description, group_id, created_by, event_date, start_time, end_time, timezone, location_type, venue_name, venue_address, online_link, max_attendees, registration_deadline, price, currency, cover_image, tags, requirements, status) 
                VALUES (:title, :slug, :description, :group_id, :created_by, :event_date, :start_time, :end_time, :timezone, :location_type, :venue_name, :venue_address, :online_link, :max_attendees, :registration_deadline, :price, :currency, :cover_image, :tags, :requirements, :status)
                RETURNING id";
        
        $params = [
            ':title' => $data['title'],
            ':slug' => $slug,
            ':description' => $data['description'] ?? null,
            ':group_id' => $data['group_id'],
            ':created_by' => $data['created_by'],
            ':event_date' => $data['event_date'],
            ':start_time' => $data['start_time'],
            ':end_time' => $data['end_time'] ?? null,
            ':timezone' => $data['timezone'] ?? 'America/Phoenix',
            ':location_type' => $data['location_type'] ?? 'in_person',
            ':venue_name' => $data['venue_name'] ?? null,
            ':venue_address' => $data['venue_address'] ?? null,
            ':online_link' => $data['online_link'] ?? null,
            ':max_attendees' => $data['max_attendees'] ?? null,
            ':registration_deadline' => $data['registration_deadline'] ?? null,
            ':price' => $data['price'] ?? 0.00,
            ':currency' => $data['currency'] ?? 'USD',
            ':cover_image' => $data['cover_image'] ?? null,
            ':tags' => !empty($data['tags']) ? '{' . implode(',', $data['tags']) . '}' : null,
            ':requirements' => $data['requirements'] ?? null,
            ':status' => $data['status'] ?? 'draft'
        ];
        
        $result = $this->db->fetch($sql, $params);
        
        if ($result) {
            // Automatically register the creator as attending
            $this->rsvp($result['id'], $data['created_by'], 'going');
            return $result['id'];
        }
        
        return false;
    }
    
    /**
     * Get all events with optional filtering
     */
    public function getAll($filters = []) {
        $sql = "SELECT e.*, g.name as group_name, g.slug as group_slug,
                       u.name as organizer_name,
                       COUNT(ea.id) FILTER (WHERE ea.status = 'going') as attendee_count
                FROM events e 
                LEFT JOIN groups g ON e.group_id = g.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN event_attendees ea ON e.id = ea.event_id
                WHERE e.status = 'published'";
        
        $params = [];
        
        // Add filters
        if (!empty($filters['group_id'])) {
            $sql .= " AND e.group_id = :group_id";
            $params[':group_id'] = $filters['group_id'];
        }
        
        if (!empty($filters['location_type'])) {
            $sql .= " AND e.location_type = :location_type";
            $params[':location_type'] = $filters['location_type'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (e.title ILIKE :search OR e.description ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND e.event_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND e.event_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['upcoming_only'])) {
            $sql .= " AND e.event_date >= CURRENT_DATE";
        }
        
        $sql .= " GROUP BY e.id, g.name, g.slug, u.name
                  ORDER BY e.event_date ASC, e.start_time ASC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get event by ID
     */
    public function getById($id) {
        $sql = "SELECT e.*, g.name as group_name, g.slug as group_slug,
                       u.name as organizer_name, u.email as organizer_email,
                       COUNT(ea.id) FILTER (WHERE ea.status = 'going') as attendee_count,
                       COUNT(ea.id) FILTER (WHERE ea.status = 'maybe') as maybe_count
                FROM events e 
                LEFT JOIN groups g ON e.group_id = g.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN event_attendees ea ON e.id = ea.event_id
                WHERE e.id = :id
                GROUP BY e.id, e.title, e.description, e.event_date, e.start_time, e.end_time, 
                         e.location_type, e.venue_name, e.venue_address, e.online_link, e.max_attendees, 
                         e.cover_image, e.slug, e.status, e.group_id, e.created_by, e.created_at, e.updated_at,
                         g.name, g.slug, u.name, u.email";
        
        return $this->db->fetch($sql, [':id' => $id]);
    }
    
    /**
     * Get event by slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT e.*, g.name as group_name, g.slug as group_slug,
                       u.name as organizer_name, u.email as organizer_email,
                       COUNT(ea.id) FILTER (WHERE ea.status = 'going') as attendee_count,
                       COUNT(ea.id) FILTER (WHERE ea.status = 'maybe') as maybe_count
                FROM events e 
                LEFT JOIN groups g ON e.group_id = g.id
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN event_attendees ea ON e.id = ea.event_id
                WHERE e.slug = :slug
                GROUP BY e.id, e.title, e.description, e.event_date, e.start_time, e.end_time, 
                         e.location_type, e.venue_name, e.venue_address, e.online_link, e.max_attendees, 
                         e.cover_image, e.slug, e.status, e.group_id, e.created_by, e.created_at, e.updated_at,
                         g.name, g.slug, u.name, u.email";
        
        return $this->db->fetch($sql, [':slug' => $slug]);
    }
    
    /**
     * Get events for a specific group
     */
    public function getByGroupId($groupId, $includeAll = false) {
        $statusFilter = $includeAll ? "" : "AND e.status = 'published'";
        
        $sql = "SELECT e.*, u.name as organizer_name,
                       COUNT(ea.id) FILTER (WHERE ea.status = 'going') as attendee_count
                FROM events e 
                LEFT JOIN users u ON e.created_by = u.id
                LEFT JOIN event_attendees ea ON e.id = ea.event_id
                WHERE e.group_id = :group_id {$statusFilter}
                GROUP BY e.id, u.name
                ORDER BY e.event_date ASC, e.start_time ASC";
        
        return $this->db->fetchAll($sql, [':group_id' => $groupId]);
    }
    
    /**
     * Get upcoming events for a user's groups
     */
    public function getUpcomingForUser($userId, $limit = 5) {
        $sql = "SELECT e.*, g.name as group_name, g.slug as group_slug,
                       COUNT(ea.id) FILTER (WHERE ea.status = 'going') as attendee_count,
                       user_rsvp.status as user_rsvp_status
                FROM events e 
                JOIN groups g ON e.group_id = g.id
                JOIN group_memberships gm ON g.id = gm.group_id
                LEFT JOIN event_attendees ea ON e.id = ea.event_id
                LEFT JOIN event_attendees user_rsvp ON e.id = user_rsvp.event_id AND user_rsvp.user_id = :user_id
                WHERE gm.user_id = :user_id 
                AND gm.status = 'active'
                AND e.status = 'published'
                AND e.event_date >= CURRENT_DATE
                GROUP BY e.id, g.name, g.slug, user_rsvp.status
                ORDER BY e.event_date ASC, e.start_time ASC
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, [
            ':user_id' => $userId,
            ':limit' => $limit
        ]);
    }
    
    /**
     * RSVP to an event
     */
    public function rsvp($eventId, $userId, $status = 'going', $notes = '') {
        $sql = "INSERT INTO event_attendees (event_id, user_id, status, notes) 
                VALUES (:event_id, :user_id, :status, :notes)
                ON CONFLICT (event_id, user_id) 
                DO UPDATE SET status = :status, notes = :notes, registered_at = CURRENT_TIMESTAMP";
        
        return $this->db->query($sql, [
            ':event_id' => $eventId,
            ':user_id' => $userId,
            ':status' => $status,
            ':notes' => $notes
        ]);
    }
    
    /**
     * Get user's RSVP status for an event
     */
    public function getUserRSVP($eventId, $userId) {
        $sql = "SELECT * FROM event_attendees 
                WHERE event_id = :event_id AND user_id = :user_id";
        
        return $this->db->fetch($sql, [
            ':event_id' => $eventId,
            ':user_id' => $userId
        ]);
    }
    
    /**
     * Get event attendees
     */
    public function getAttendees($eventId, $status = null) {
        $sql = "SELECT u.id, u.name, u.email, ea.status, ea.registered_at, ea.notes
                FROM event_attendees ea
                JOIN users u ON ea.user_id = u.id
                WHERE ea.event_id = :event_id";
        
        $params = [':event_id' => $eventId];
        
        if ($status) {
            $sql .= " AND ea.status = :status";
            $params[':status'] = $status;
        }
        
        $sql .= " ORDER BY ea.registered_at ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Check if user can manage event (creator or group admin)
     */
    public function canManageEvent($eventId, $userId) {
        $sql = "SELECT e.created_by, gm.role
                FROM events e
                LEFT JOIN group_memberships gm ON e.group_id = gm.group_id AND gm.user_id = :user_id
                WHERE e.id = :event_id";
        
        $result = $this->db->fetch($sql, [
            ':event_id' => $eventId,
            ':user_id' => $userId
        ]);
        
        if (!$result) return false;
        
        // Creator can always manage
        if ($result['created_by'] == $userId) return true;
        
        // Group owners and co-hosts can manage events
        if (in_array($result['role'], ['owner', 'co_host'])) return true;
        
        return false;
    }
    
    /**
     * Get all event categories
     */
    public function getCategories() {
        $sql = "SELECT * FROM event_categories WHERE is_active = true ORDER BY display_order, name";
        return $this->db->fetchAll($sql);
    }
    
    
    /**
     * Update event
     */
    public function update($id, $data) {
        $allowedFields = ['title', 'description', 'event_date', 'start_time', 'end_time', 'timezone', 'location_type', 'venue_name', 'venue_address', 'online_link', 'max_attendees', 'registration_deadline', 'price', 'currency', 'cover_image', 'requirements', 'status'];
        $updateFields = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        // Handle tags separately
        if (isset($data['tags']) && is_array($data['tags'])) {
            $updateFields[] = "tags = :tags";
            $params[':tags'] = '{' . implode(',', $data['tags']) . '}';
        }
        
        $sql = "UPDATE events SET " . implode(', ', $updateFields) . " WHERE id = :id";
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Delete event (soft delete by changing status)
     */
    public function delete($id) {
        $sql = "UPDATE events SET status = 'cancelled' WHERE id = :id";
        return $this->db->query($sql, [':id' => $id]);
    }
    
    /**
     * Generate unique slug for event
     */
    private function generateSlug($title) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Check if slug exists
     */
    private function slugExists($slug) {
        $sql = "SELECT id FROM events WHERE slug = :slug";
        $result = $this->db->fetch($sql, [':slug' => $slug]);
        return !empty($result);
    }
}