<?php
/**
 * User Model
 */

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new user
     */
    public function create($data) {
        $sql = "INSERT INTO users (name, email, password_hash, phone, bio, city, interests, role) 
                VALUES (:name, :email, :password_hash, :phone, :bio, :city, :interests, :role) 
                RETURNING id";
        
        $params = [
            ':name' => $data['name'],
            ':email' => $data['email'],
            ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':phone' => $data['phone'] ?? null,
            ':bio' => $data['bio'] ?? null,
            ':city' => $data['city'] ?? null,
            ':interests' => $data['interests'] ?? null,
            ':role' => $data['role'] ?? 'member'
        ];
        
        $result = $this->db->fetch($sql, $params);
        return $result['id'];
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id AND status = 'active'";
        return $this->db->fetch($sql, [':id' => $id]);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        return $this->db->fetch($sql, [':email' => $email]);
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user || $user['status'] !== 'active') {
            return false;
        }
        
        if (password_verify($password, $user['password_hash'])) {
            $this->updateLastLogin($user['id']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Update user profile
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'password') {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $this->db->query($sql, $params);
        return true;
    }
    
    /**
     * Change user password
     */
    public function changePassword($id, $newPassword) {
        $sql = "UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id";
        $params = [
            ':id' => $id,
            ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ];
        $this->db->query($sql, $params);
        return true;
    }
    
    /**
     * Check if membership is active
     */
    public function hasMembership($userId) {
        $user = $this->findById($userId);
        if (!$user) return false;

        // Organizers, admins, and super_admins don't need to pay
        if (in_array($user['role'], ['organizer', 'admin', 'super_admin'])) {
            return true;
        }

        // Check for membership_expires field (PostgreSQL style)
        if (empty($user['membership_expires'])) {
            return false; // No membership expiration date set
        }

        try {
            return new DateTime() < new DateTime($user['membership_expires']);
        } catch (Exception $e) {
            return false; // Invalid date format
        }
    }
    
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $this->db->query($sql, [':id' => $userId]);
    }

    /**
     * Get all dashboard data in one optimized query
     * Reduces multiple database calls for better performance
     */
    public function getDashboardData($userId) {
        // Get user membership status and basic stats in one query
        $sql = "
            SELECT
                u.membership_expires,
                u.role,
                COUNT(DISTINCT gm.group_id) as groups_joined,
                COUNT(DISTINCT ea.event_id) as events_attended,
                COALESCE(SUM(pt.points), 0) as total_points
            FROM users u
            LEFT JOIN group_members gm ON u.id = gm.user_id AND gm.status = 1
            LEFT JOIN event_attendees ea ON u.id = ea.user_id AND ea.status = 'attending'
            LEFT JOIN points_transactions pt ON u.id = pt.user_id
            WHERE u.id = :user_id AND u.status = 'active'
            GROUP BY u.id, u.membership_expires, u.role
        ";

        $result = $this->db->fetch($sql, [':user_id' => $userId]);

        if (!$result) {
            return [
                'has_membership' => false,
                'stats' => [
                    'groups_joined' => 0,
                    'events_attended' => 0,
                    'connections_made' => 0,
                    'reviews_given' => 0
                ]
            ];
        }

        // Check membership status
        $hasMembership = false;
        if (in_array($result['role'], ['organizer', 'admin', 'super_admin'])) {
            $hasMembership = true;
        } elseif ($result['membership_expires']) {
            try {
                $hasMembership = new DateTime() < new DateTime($result['membership_expires']);
            } catch (Exception $e) {
                $hasMembership = false;
            }
        }

        return [
            'has_membership' => $hasMembership,
            'stats' => [
                'groups_joined' => (int)$result['groups_joined'],
                'events_attended' => (int)$result['events_attended'],
                'connections_made' => (int)$result['events_attended'], // Using events attended as proxy for connections
                'reviews_given' => 0 // TODO: Implement when review system is added
            ]
        ];
    }
}