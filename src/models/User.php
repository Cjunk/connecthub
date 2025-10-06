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
        
        return $user['membership_expires'] && 
               new DateTime() < new DateTime($user['membership_expires']);
    }
    
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $this->db->query($sql, [':id' => $userId]);
    }
}