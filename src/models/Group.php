<?php
/**
 * Group Model
 */

class Group {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new group
     */
    public function create($data) {
        $sql = "INSERT INTO groups (name, slug, description, image, privacy, category_id, 
                location_city, location_state, location_country, latitude, longitude, organizer_id) 
                VALUES (:name, :slug, :description, :image, :privacy, :category_id, 
                :location_city, :location_state, :location_country, :latitude, :longitude, :organizer_id)";
        
        $params = [
            ':name' => $data['name'],
            ':slug' => $this->generateSlug($data['name']),
            ':description' => $data['description'],
            ':image' => $data['image'] ?? null,
            ':privacy' => $data['privacy'] ?? GROUP_PUBLIC,
            ':category_id' => $data['category_id'],
            ':location_city' => $data['location_city'] ?? null,
            ':location_state' => $data['location_state'] ?? null,
            ':location_country' => $data['location_country'] ?? null,
            ':latitude' => $data['latitude'] ?? null,
            ':longitude' => $data['longitude'] ?? null,
            ':organizer_id' => $data['organizer_id']
        ];
        
        $this->db->query($sql, $params);
        $groupId = $this->db->lastInsertId();
        
        // Add organizer as a member
        $this->addMember($groupId, $data['organizer_id'], 'organizer');
        
        return $groupId;
    }
    
    /**
     * Find group by ID
     */
    public function findById($id) {
        $sql = "SELECT g.*, c.name as category_name, c.color as category_color,
                       u.first_name, u.last_name, u.username as organizer_username
                FROM groups g 
                LEFT JOIN categories c ON g.category_id = c.id 
                LEFT JOIN users u ON g.organizer_id = u.id 
                WHERE g.id = :id AND g.status = :status";
        return $this->db->fetch($sql, [':id' => $id, ':status' => STATUS_ACTIVE]);
    }
    
    /**
     * Find group by slug
     */
    public function findBySlug($slug) {
        $sql = "SELECT g.*, c.name as category_name, c.color as category_color,
                       u.first_name, u.last_name, u.username as organizer_username
                FROM groups g 
                LEFT JOIN categories c ON g.category_id = c.id 
                LEFT JOIN users u ON g.organizer_id = u.id 
                WHERE g.slug = :slug AND g.status = :status";
        return $this->db->fetch($sql, [':slug' => $slug, ':status' => STATUS_ACTIVE]);
    }
    
    /**
     * Get all groups with pagination
     */
    public function getAll($page = 1, $limit = GROUPS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        $whereConditions = ['g.status = :status'];
        $params = [':status' => STATUS_ACTIVE];
        
        // Apply filters
        if (!empty($filters['category'])) {
            $whereConditions[] = 'g.category_id = :category_id';
            $params[':category_id'] = $filters['category'];
        }
        
        if (!empty($filters['city'])) {
            $whereConditions[] = 'g.location_city LIKE :city';
            $params[':city'] = '%' . $filters['city'] . '%';
        }
        
        if (!empty($filters['search'])) {
            $whereConditions[] = '(g.name LIKE :search OR g.description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT g.*, c.name as category_name, c.color as category_color,
                       u.first_name, u.last_name
                FROM groups g 
                LEFT JOIN categories c ON g.category_id = c.id 
                LEFT JOIN users u ON g.organizer_id = u.id 
                WHERE $whereClause 
                ORDER BY g.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Add member to group
     */
    public function addMember($groupId, $userId, $role = 'member') {
        $sql = "INSERT INTO group_members (group_id, user_id, role) 
                VALUES (:group_id, :user_id, :role) 
                ON DUPLICATE KEY UPDATE status = 1, role = :role";
        
        $params = [
            ':group_id' => $groupId,
            ':user_id' => $userId,
            ':role' => $role
        ];
        
        $this->db->query($sql, $params);
        
        // Update member count
        $this->updateMemberCount($groupId);
        
        return true;
    }
    
    /**
     * Remove member from group
     */
    public function removeMember($groupId, $userId) {
        $sql = "DELETE FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
        $this->db->query($sql, [':group_id' => $groupId, ':user_id' => $userId]);
        
        // Update member count
        $this->updateMemberCount($groupId);
        
        return true;
    }
    
    /**
     * Check if user is member of group
     */
    public function isMember($groupId, $userId) {
        $sql = "SELECT id FROM group_members 
                WHERE group_id = :group_id AND user_id = :user_id AND status = 1";
        $result = $this->db->fetch($sql, [':group_id' => $groupId, ':user_id' => $userId]);
        return !empty($result);
    }
    
    /**
     * Get group members
     */
    public function getMembers($groupId, $page = 1, $limit = MEMBERS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_image,
                       gm.role, gm.joined_at
                FROM group_members gm
                JOIN users u ON gm.user_id = u.id
                WHERE gm.group_id = :group_id AND gm.status = 1
                ORDER BY gm.role = 'organizer' DESC, gm.role = 'co_organizer' DESC, gm.joined_at ASC
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            ':group_id' => $groupId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }
    
    /**
     * Update group
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE groups SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $this->db->query($sql, $params);
        return true;
    }
    
    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
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
        $sql = "SELECT id FROM groups WHERE slug = :slug";
        $result = $this->db->fetch($sql, [':slug' => $slug]);
        return !empty($result);
    }
    
    private function updateMemberCount($groupId) {
        $sql = "UPDATE groups SET member_count = (
                    SELECT COUNT(*) FROM group_members 
                    WHERE group_id = :group_id AND status = 1
                ) WHERE id = :group_id";
        $this->db->query($sql, [':group_id' => $groupId]);
    }
}