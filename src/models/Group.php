<?php
/**
 * Group Model
 * Handles all group-related database operations for PostgreSQL
 */

require_once __DIR__ . '/../../config/database.php';

class Group {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new group
     */
    public function create($data) {
        $slug = $this->generateSlug($data['name']);
        
        $sql = "INSERT INTO groups (name, slug, description, category, privacy_level, location, max_members, created_by, cover_image, tags, rules, meeting_frequency, website_url, social_links) 
                VALUES (:name, :slug, :description, :category, :privacy_level, :location, :max_members, :created_by, :cover_image, :tags, :rules, :meeting_frequency, :website_url, :social_links) 
                RETURNING id";
        
        $params = [
            ':name' => $data['name'],
            ':slug' => $slug,
            ':description' => $data['description'] ?? null,
            ':category' => $data['category'] ?? null,
            ':privacy_level' => $data['privacy_level'] ?? 'public',
            ':location' => $data['location'] ?? null,
            ':max_members' => $data['max_members'] ?? null,
            ':created_by' => $data['created_by'],
            ':cover_image' => $data['cover_image'] ?? null,
            ':tags' => $data['tags'] ? '{' . implode(',', $data['tags']) . '}' : null,
            ':rules' => $data['rules'] ?? null,
            ':meeting_frequency' => $data['meeting_frequency'] ?? null,
            ':website_url' => $data['website_url'] ?? null,
            ':social_links' => $data['social_links'] ? json_encode($data['social_links']) : null
        ];
        
        $result = $this->db->fetch($sql, $params);
        
        if ($result) {
            // Automatically join the creator as owner (not just creator)
            $this->joinGroup($result['id'], $data['created_by'], 'owner');
            
            // Log group creation
            $this->logActivity($result['id'], $data['created_by'], 'group_created', [
                'group_name' => $data['name']
            ]);
            
            return $result['id'];
        }
        
        return false;
    }
    
    /**
     * Get all groups with optional filtering
     */
    public function getAll($filters = []) {
        $sql = "SELECT g.*, gc.name as category_name, gc.icon as category_icon, gc.color as category_color,
                       u.name as creator_name,
                       COUNT(gm.id) as member_count
                FROM groups g 
                LEFT JOIN group_categories gc ON g.category = gc.name
                LEFT JOIN users u ON g.created_by = u.id
                LEFT JOIN group_memberships gm ON g.id = gm.group_id AND gm.status = 'active'
                WHERE g.status = 'active'";
        
        $params = [];
        
        // Add filters
        if (!empty($filters['category'])) {
            $sql .= " AND g.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['privacy'])) {
            $sql .= " AND g.privacy_level = :privacy";
            $params[':privacy'] = $filters['privacy'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (g.name ILIKE :search OR g.description ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['location'])) {
            $sql .= " AND g.location ILIKE :location";
            $params[':location'] = '%' . $filters['location'] . '%';
        }
        
        $sql .= " GROUP BY g.id, gc.name, gc.icon, gc.color, u.name
                  ORDER BY g.created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$filters['limit'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get group by ID
     */
    public function getById($id) {
        $sql = "SELECT g.*, gc.name as category_name, gc.icon as category_icon, gc.color as category_color,
                       u.name as creator_name, u.email as creator_email,
                       COUNT(gm.id) as member_count
                FROM groups g 
                LEFT JOIN group_categories gc ON g.category = gc.name
                LEFT JOIN users u ON g.created_by = u.id
                LEFT JOIN group_memberships gm ON g.id = gm.group_id AND gm.status = 'active'
                WHERE g.id = :id AND g.status = 'active'
                GROUP BY g.id, gc.name, gc.icon, gc.color, u.name, u.email";
        
        return $this->db->fetch($sql, [':id' => $id]);
    }
    
    /**
     * Get group by slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT g.*, gc.name as category_name, gc.icon as category_icon, gc.color as category_color,
                       u.name as creator_name, u.email as creator_email,
                       COUNT(gm.id) as member_count
                FROM groups g 
                LEFT JOIN group_categories gc ON g.category = gc.name
                LEFT JOIN users u ON g.created_by = u.id
                LEFT JOIN group_memberships gm ON g.id = gm.group_id AND gm.status = 'active'
                WHERE g.slug = :slug AND g.status = 'active'
                GROUP BY g.id, gc.name, gc.icon, gc.color, u.name, u.email";
        
        return $this->db->fetch($sql, [':slug' => $slug]);
    }
    
    /**
     * Join a group
     */
    public function joinGroup($groupId, $userId, $role = 'member') {
        // Check if already a member
        if ($this->isMember($groupId, $userId)) {
            return false;
        }
        
        // Check if group is private and needs approval
        $group = $this->getById($groupId);
        if ($group['privacy_level'] === 'private' && $role === 'member') {
            return $this->requestToJoin($groupId, $userId);
        }
        
        $sql = "INSERT INTO group_memberships (user_id, group_id, role, status) 
                VALUES (:user_id, :group_id, :role, 'active')
                ON CONFLICT (user_id, group_id) 
                DO UPDATE SET status = 'active', role = :role, joined_at = CURRENT_TIMESTAMP";
        
        $params = [
            ':user_id' => $userId,
            ':group_id' => $groupId,
            ':role' => $role
        ];
        
        return $this->db->query($sql, $params);
    }
    
    /**
     * Leave a group
     */
    public function leaveGroup($groupId, $userId) {
        $sql = "UPDATE group_memberships 
                SET status = 'inactive' 
                WHERE user_id = :user_id AND group_id = :group_id";
        
        return $this->db->query($sql, [
            ':user_id' => $userId,
            ':group_id' => $groupId
        ]);
    }
    
    /**
     * Check if user is a member of a group
     */
    public function isMember($groupId, $userId) {
        $sql = "SELECT id FROM group_memberships 
                WHERE user_id = :user_id AND group_id = :group_id AND status = 'active'";
        
        $result = $this->db->fetch($sql, [
            ':user_id' => $userId,
            ':group_id' => $groupId
        ]);
        
        return !empty($result);
    }
    
    /**
     * Get user's membership role in a group
     */
    public function getUserRole($groupId, $userId) {
        $sql = "SELECT role FROM group_memberships 
                WHERE user_id = :user_id AND group_id = :group_id AND status = 'active'";
        
        $result = $this->db->fetch($sql, [
            ':user_id' => $userId,
            ':group_id' => $groupId
        ]);
        
        return $result ? $result['role'] : null;
    }
    
    /**
     * Get groups user is a member of
     */
    public function getUserGroups($userId) {
        $sql = "SELECT g.*, gm.role, gm.joined_at,
                       COUNT(gm2.id) as member_count
                FROM groups g
                JOIN group_memberships gm ON g.id = gm.group_id
                LEFT JOIN group_memberships gm2 ON g.id = gm2.group_id AND gm2.status = 'active'
                WHERE gm.user_id = :user_id AND gm.status = 'active' AND g.status = 'active'
                GROUP BY g.id, gm.role, gm.joined_at
                ORDER BY gm.joined_at DESC";
        
        return $this->db->fetchAll($sql, [':user_id' => $userId]);
    }
    
    /**
     * Get group members
     */
    public function getMembers($groupId) {
        $sql = "SELECT u.id, u.name, u.email, gm.role, gm.joined_at
                FROM users u
                JOIN group_memberships gm ON u.id = gm.user_id
                WHERE gm.group_id = :group_id AND gm.status = 'active'
                ORDER BY 
                    CASE gm.role 
                        WHEN 'owner' THEN 1
                        WHEN 'co_host' THEN 2
                        WHEN 'moderator' THEN 3
                        ELSE 4
                    END,
                    gm.joined_at ASC";
        
        return $this->db->fetchAll($sql, [':group_id' => $groupId]);
    }
    
    /**
     * Get all categories
     */
    public function getCategories() {
        $sql = "SELECT * FROM group_categories WHERE is_active = true ORDER BY display_order, name";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Request to join a private group
     */
    public function requestToJoin($groupId, $userId, $message = '') {
        $sql = "INSERT INTO group_join_requests (user_id, group_id, message) 
                VALUES (:user_id, :group_id, :message)
                ON CONFLICT (user_id, group_id) 
                DO UPDATE SET message = :message, requested_at = CURRENT_TIMESTAMP, status = 'pending'";
        
        return $this->db->query($sql, [
            ':user_id' => $userId,
            ':group_id' => $groupId,
            ':message' => $message
        ]);
    }
    
    /**
     * Generate unique slug for group
     */
    private function generateSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
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
        $sql = "SELECT id FROM groups WHERE slug = :slug";
        $result = $this->db->fetch($sql, [':slug' => $slug]);
        return !empty($result);
    }
    
    /**
     * Promote user to a new role within the group
     */
    public function promoteUser($groupId, $targetUserId, $newRole, $promoterId) {
        // Validate that promoter has permission to promote to this role
        $promoterRole = $this->getUserRole($groupId, $promoterId);
        
        if (!$this->canPromoteToRole($promoterRole, $newRole)) {
            return false;
        }
        
        $sql = "UPDATE group_memberships 
                SET role = :role, promoted_by = :promoted_by, promoted_at = CURRENT_TIMESTAMP
                WHERE user_id = :user_id AND group_id = :group_id AND status = 'active'";
        
        $result = $this->db->query($sql, [
            ':role' => $newRole,
            ':promoted_by' => $promoterId,
            ':user_id' => $targetUserId,
            ':group_id' => $groupId
        ]);
        
        if ($result) {
            $this->logActivity($groupId, $promoterId, 'member_promoted', [
                'target_user_id' => $targetUserId,
                'new_role' => $newRole
            ]);
        }
        
        return $result;
    }
    
    /**
     * Check if user can promote to a specific role
     */
    private function canPromoteToRole($promoterRole, $targetRole) {
        $roleHierarchy = [
            'member' => 1,
            'moderator' => 2,
            'co_host' => 3,
            'owner' => 4
        ];
        
        $promoterLevel = $roleHierarchy[$promoterRole] ?? 0;
        $targetLevel = $roleHierarchy[$targetRole] ?? 0;
        
        // Owners can promote to any role except owner
        if ($promoterRole === 'owner' && $targetRole !== 'owner') {
            return true;
        }
        
        // Co-hosts can promote to moderator only
        if ($promoterRole === 'co_host' && $targetRole === 'moderator') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Transfer group ownership to another member
     */
    public function transferOwnership($groupId, $currentOwnerId, $newOwnerId) {
        // Verify current user is actually the owner
        $currentRole = $this->getUserRole($groupId, $currentOwnerId);
        if ($currentRole !== 'owner') {
            return false;
        }
        
        // Verify target user is a member
        if (!$this->isMember($groupId, $newOwnerId)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Demote current owner to co_host
            $this->db->query(
                "UPDATE group_memberships SET role = 'co_host' WHERE user_id = :current_owner AND group_id = :group_id",
                [':current_owner' => $currentOwnerId, ':group_id' => $groupId]
            );
            
            // Promote new owner
            $this->db->query(
                "UPDATE group_memberships SET role = 'owner', promoted_by = :promoted_by, promoted_at = CURRENT_TIMESTAMP WHERE user_id = :new_owner AND group_id = :group_id",
                [':new_owner' => $newOwnerId, ':promoted_by' => $currentOwnerId, ':group_id' => $groupId]
            );
            
            // Update group created_by field
            $this->db->query(
                "UPDATE groups SET created_by = :new_owner WHERE id = :group_id",
                [':new_owner' => $newOwnerId, ':group_id' => $groupId]
            );
            
            $this->db->commit();
            
            // Log the ownership transfer
            $this->logActivity($groupId, $currentOwnerId, 'ownership_transferred', [
                'new_owner_id' => $newOwnerId
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
    
    /**
     * Get group management permissions for a user
     */
    public function getUserPermissions($groupId, $userId) {
        $role = $this->getUserRole($groupId, $userId);
        
        if (!$role) {
            return [];
        }
        
        $sql = "SELECT permission FROM group_role_permissions WHERE role = :role";
        $permissions = $this->db->fetchAll($sql, [':role' => $role]);
        
        return array_column($permissions, 'permission');
    }
    
    /**
     * Check if user has specific permission in group
     */
    public function hasPermission($groupId, $userId, $permission) {
        $permissions = $this->getUserPermissions($groupId, $userId);
        return in_array($permission, $permissions);
    }
    
    /**
     * Check if user can create events in this group
     */
    public function canUserCreateEvents($userId, $groupId) {
        $role = $this->getUserRole($groupId, $userId);
        
        // Only owners, co-hosts, and moderators can create events
        return in_array($role, ['owner', 'co_host', 'moderator']);
    }
    
    /**
     * Get group activity log
     */
    public function getActivityLog($groupId, $limit = 20) {
        $sql = "SELECT gal.*, u.name as user_name
                FROM group_activity_log gal
                JOIN users u ON gal.user_id = u.id
                WHERE gal.group_id = :group_id
                ORDER BY gal.created_at DESC
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, [
            ':group_id' => $groupId,
            ':limit' => $limit
        ]);
    }
    
    /**
     * Log group activity
     */
    public function logActivity($groupId, $userId, $action, $details = null) {
        $sql = "INSERT INTO group_activity_log (group_id, user_id, action, details) 
                VALUES (:group_id, :user_id, :action, :details)";
        
        return $this->db->query($sql, [
            ':group_id' => $groupId,
            ':user_id' => $userId,
            ':action' => $action,
            ':details' => $details ? json_encode($details) : null
        ]);
    }
    
    /**
     * Get group co-hosts and moderators for management display
     */
    public function getGroupManagers($groupId) {
        $sql = "SELECT u.id, u.name, u.email, gm.role, gm.joined_at, gm.promoted_at,
                       promoter.name as promoted_by_name
                FROM group_memberships gm
                JOIN users u ON gm.user_id = u.id
                LEFT JOIN users promoter ON gm.promoted_by = promoter.id
                WHERE gm.group_id = :group_id 
                AND gm.status = 'active' 
                AND gm.role IN ('owner', 'co_host', 'moderator')
                ORDER BY 
                    CASE gm.role 
                        WHEN 'owner' THEN 1
                        WHEN 'co_host' THEN 2
                        WHEN 'moderator' THEN 3
                    END,
                    gm.promoted_at ASC";
        
        return $this->db->fetchAll($sql, [':group_id' => $groupId]);
    }
}