<?php
/**
 * Group Model
 * Handles all group-related database operations for PostgreSQL
 */

require_once __DIR__ . '/../../config/database.php';

class Group {
    private $db;
    private $groupColumns = null;
    private $userColumns = null;
    
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
        $sql = "SELECT g.*, NULL as category_name, NULL as category_icon, NULL as category_color,
                       " . $this->creatorNameExpression() . " as creator_name,
                       (
                           SELECT COUNT(gm.id)
                           FROM group_memberships gm
                           WHERE gm.group_id = g.id AND " . $this->activeStatusCondition('gm.status') . "
                       ) as member_count
                FROM groups g
                LEFT JOIN users u ON g.created_by = u.id
                WHERE " . $this->activeStatusCondition('g.status');
        
        $params = [];
        
        // Add filters
        if (!empty($filters['category'])) {
            if ($this->hasGroupColumn('category_id') && isset($filters['category_id'])) {
                $sql .= " AND g.category_id = :category_id";
                $params[':category_id'] = $filters['category_id'];
            } elseif ($this->hasGroupColumn('category')) {
                $sql .= " AND g.category = :category";
                $params[':category'] = $filters['category'];
            } else {
                $sql .= " AND g.category_id = :category";
                $params[':category'] = $filters['category'];
            }
        }
        
        if (!empty($filters['privacy'])) {
            if ($this->hasGroupColumn('privacy')) {
                $sql .= " AND g.privacy = :privacy";
            } else {
                $sql .= " AND g.privacy_level = :privacy";
            }
            $params[':privacy'] = $filters['privacy'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (LOWER(g.name) LIKE LOWER(:search) OR LOWER(g.description) LIKE LOWER(:search))";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['location'])) {
            if ($this->hasGroupColumn('location')) {
                $sql .= " AND LOWER(COALESCE(g.location, '')) LIKE LOWER(:location)";
            } else {
                $sql .= " AND (LOWER(COALESCE(g.location_city, '')) LIKE LOWER(:location)
                            OR LOWER(COALESCE(g.location_state, '')) LIKE LOWER(:location)
                            OR LOWER(COALESCE(g.location_country, '')) LIKE LOWER(:location))";
            }
            $params[':location'] = '%' . $filters['location'] . '%';
        }
        
        $sql .= " ORDER BY g.created_at DESC";
        
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
                 $sql = "SELECT g.*, NULL as category_name, NULL as category_icon, NULL as category_color,
                                    " . $this->creatorNameExpression() . " as creator_name, u.email as creator_email,
                                    (
                                            SELECT COUNT(gm.id)
                                            FROM group_memberships gm
                                            WHERE gm.group_id = g.id AND " . $this->activeStatusCondition('gm.status') . "
                                    ) as member_count
                                FROM groups g
                                LEFT JOIN users u ON g.created_by = u.id
                                WHERE g.id = :id AND " . $this->activeStatusCondition('g.status');
        
        return $this->db->fetch($sql, [':id' => $id]);
    }
    
    /**
     * Get group by slug
     */
    public function getBySlug($slug) {
         $sql = "SELECT g.*, NULL as category_name, NULL as category_icon, NULL as category_color,
                  " . $this->creatorNameExpression() . " as creator_name, u.email as creator_email,
                  (
                      SELECT COUNT(gm.id)
                      FROM group_memberships gm
                      WHERE gm.group_id = g.id AND " . $this->activeStatusCondition('gm.status') . "
                  ) as member_count
                FROM groups g
                LEFT JOIN users u ON g.created_by = u.id
                WHERE g.slug = :slug AND " . $this->activeStatusCondition('g.status');
        
        return $this->db->fetch($sql, [':slug' => $slug]);
    }

    private function activeStatusCondition($column) {
        $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $castType = $driver === 'pgsql' ? 'TEXT' : 'CHAR';
        return "LOWER(CAST(" . $column . " AS " . $castType . ")) IN ('active', '1')";
    }

    private function creatorNameExpression() {
        if ($this->hasUserColumn('first_name') || $this->hasUserColumn('last_name')) {
            if ($this->hasUserColumn('username')) {
                return "COALESCE(NULLIF(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')), ' '), u.username, u.email)";
            }
            return "COALESCE(NULLIF(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')), ' '), u.email)";
        }

        if ($this->hasUserColumn('name')) {
            return "COALESCE(NULLIF(u.name, ''), u.email)";
        }

        if ($this->hasUserColumn('username')) {
            return "COALESCE(NULLIF(u.username, ''), u.email)";
        }

        return "u.email";
    }

    private function hasGroupColumn($columnName) {
        return in_array($columnName, $this->getGroupColumns(), true);
    }

    private function hasUserColumn($columnName) {
        return in_array($columnName, $this->getUserColumns(), true);
    }

    private function getGroupColumns() {
        if ($this->groupColumns !== null) {
            return $this->groupColumns;
        }

        $this->groupColumns = $this->readTableColumns('groups');
        return $this->groupColumns;
    }

    private function getUserColumns() {
        if ($this->userColumns !== null) {
            return $this->userColumns;
        }

        $this->userColumns = $this->readTableColumns('users');
        return $this->userColumns;
    }

    private function readTableColumns($tableName) {
        $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :table_name";
        } else {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name";
        }

        $rows = $this->db->fetchAll($sql, [':table_name' => $tableName]);
        return array_map(static function ($row) {
            return $row['column_name'];
        }, $rows);
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
        
        $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $sql = "INSERT INTO group_memberships (user_id, group_id, role, status) 
                VALUES (:user_id, :group_id, :role, 'active')
                ON CONFLICT (user_id, group_id) 
                DO UPDATE SET status = 'active', role = :role, joined_at = CURRENT_TIMESTAMP";
        } else {
            $sql = "INSERT INTO group_memberships (user_id, group_id, role, status)
                VALUES (:user_id, :group_id, :role, 'active')
                ON DUPLICATE KEY UPDATE status = 'active', role = VALUES(role), joined_at = CURRENT_TIMESTAMP";
        }
        
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
                WHERE gm.user_id = :user_id AND gm.status = 'active' AND " . $this->activeStatusCondition('g.status') . "
                GROUP BY g.id, gm.role, gm.joined_at
                ORDER BY gm.joined_at DESC";
        
        return $this->db->fetchAll($sql, [':user_id' => $userId]);
    }
    
    /**
     * Get count of groups user has joined
     */
    public function getUserGroupCount($userId) {
        $sql = "SELECT COUNT(*) as count
                FROM group_memberships gm
                JOIN groups g ON gm.group_id = g.id
                WHERE gm.user_id = :user_id AND gm.status = 'active' AND " . $this->activeStatusCondition('g.status');
        
        $result = $this->db->fetch($sql, [':user_id' => $userId]);
        return $result ? $result['count'] : 0;
    }
    
    /**
     * Get group members
     */
    public function getMembers($groupId) {
        $sql = "SELECT u.id, " . $this->userDisplayExpression('u') . " as name, u.email, gm.role, gm.joined_at
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
        // Some production schemas don't have group_categories yet.
        // Return empty categories instead of failing the page.
        return [];
    }
    
    /**
     * Request to join a private group
     */
    public function requestToJoin($groupId, $userId, $message = '') {
        $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $sql = "INSERT INTO group_join_requests (user_id, group_id, message) 
                    VALUES (:user_id, :group_id, :message)
                    ON CONFLICT (user_id, group_id) 
                    DO UPDATE SET message = :message, requested_at = CURRENT_TIMESTAMP, status = 'pending'";
        } else {
            $sql = "INSERT INTO group_join_requests (user_id, group_id, message)
                    VALUES (:user_id, :group_id, :message)
                    ON DUPLICATE KEY UPDATE message = VALUES(message), requested_at = CURRENT_TIMESTAMP, status = 'pending'";
        }
        
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
        $sql = "SELECT gal.*, " . $this->userDisplayExpression('u') . " as user_name
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
        $sql = "SELECT u.id, " . $this->userDisplayExpression('u') . " as name, u.email, gm.role, gm.joined_at, gm.promoted_at,
                       " . $this->userDisplayExpression('promoter') . " as promoted_by_name
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

    private function userDisplayExpression($alias) {
        if ($this->hasUserColumn('first_name') || $this->hasUserColumn('last_name')) {
            if ($this->hasUserColumn('username')) {
                return "COALESCE(NULLIF(CONCAT(COALESCE(" . $alias . ".first_name, ''), ' ', COALESCE(" . $alias . ".last_name, '')), ' '), " . $alias . ".username, " . $alias . ".email)";
            }
            return "COALESCE(NULLIF(CONCAT(COALESCE(" . $alias . ".first_name, ''), ' ', COALESCE(" . $alias . ".last_name, '')), ' '), " . $alias . ".email)";
        }

        if ($this->hasUserColumn('name')) {
            return "COALESCE(NULLIF(" . $alias . ".name, ''), " . $alias . ".email)";
        }

        if ($this->hasUserColumn('username')) {
            return "COALESCE(NULLIF(" . $alias . ".username, ''), " . $alias . ".email)";
        }

        return $alias . ".email";
    }
}
