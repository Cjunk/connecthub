<?php
/**
 * User Model
 */

class User {
    private $db;
    private ?array $userColumns = null;
    private ?string $passwordColumn = null;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a new user
     */
    public function create($data) {
        $columns = $this->getUserColumns();
        $name = trim((string)($data['name'] ?? ''));
        [$firstName, $lastName] = $this->splitName($name);

        $insert = [];

        if (in_array('name', $columns, true)) {
            $insert['name'] = $name;
        }
        if (in_array('username', $columns, true)) {
            $insert['username'] = $this->generateUsername($data['email'] ?? '', $columns, $name);
        }
        if (in_array('first_name', $columns, true)) {
            $insert['first_name'] = $firstName;
        }
        if (in_array('last_name', $columns, true)) {
            $insert['last_name'] = $lastName;
        }

        $insert['email'] = $data['email'];

        $passwordColumn = $this->getPasswordColumn();
        if ($passwordColumn !== null) {
            $insert[$passwordColumn] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (in_array('phone', $columns, true)) {
            $insert['phone'] = $data['phone'] ?? null;
        }
        if (in_array('bio', $columns, true)) {
            $insert['bio'] = $data['bio'] ?? null;
        }
        if (in_array('city', $columns, true)) {
            $insert['city'] = $data['city'] ?? null;
        }
        if (in_array('interests', $columns, true)) {
            $insert['interests'] = $data['interests'] ?? null;
        }
        if (in_array('role', $columns, true)) {
            $insert['role'] = $data['role'] ?? 'member';
        }
        if (in_array('status', $columns, true)) {
            $insert['status'] = 'active';
        }

        $colNames = array_keys($insert);
        $placeholders = array_map(fn($c) => ':' . $c, $colNames);
        $params = [];
        foreach ($insert as $col => $value) {
            $params[':' . $col] = $value;
        }

        $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = "INSERT INTO users (" . implode(', ', $colNames) . ") VALUES (" . implode(', ', $placeholders) . ")";

        if ($driver === 'pgsql') {
            $result = $this->db->fetch($sql . " RETURNING id", $params);
            return (int)$result['id'];
        }

        $this->db->query($sql, $params);
        return (int)$this->db->lastInsertId();
    }

    private function getUserColumns(): array {
        if ($this->userColumns !== null) {
            return $this->userColumns;
        }

        $driver = $this->db->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'users'";
        } else {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users'";
        }

        $rows = $this->db->fetchAll($sql);
        $this->userColumns = array_map(static fn($r) => $r['column_name'], $rows);
        return $this->userColumns;
    }

    private function splitName(string $name): array {
        $name = trim($name);
        if ($name === '') {
            return ['User', ''];
        }

        $parts = preg_split('/\s+/', $name);
        $first = $parts[0] ?? 'User';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
        return [$first, $last];
    }

    private function generateUsername(string $email, array $columns, string $name): string {
        $base = '';
        if ($email !== '' && str_contains($email, '@')) {
            $base = explode('@', $email)[0];
        }
        if ($base === '') {
            $base = preg_replace('/\s+/', '', strtolower($name));
        }
        if ($base === '') {
            $base = 'user';
        }
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base);
        if ($base === '') {
            $base = 'user';
        }

        $username = substr($base, 0, 24);

        if (in_array('username', $columns, true)) {
            $existing = $this->db->fetch("SELECT id FROM users WHERE username = :username LIMIT 1", [':username' => $username]);
            if ($existing) {
                $username = substr($username, 0, 18) . mt_rand(100000, 999999);
            }
        }

        return $username;
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
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
     * Find user by username
     */
    public function findByUsername($username) {
        if (!$this->hasUserColumn('username')) {
            return false;
        }

        $sql = "SELECT * FROM users WHERE LOWER(username) = LOWER(:username) LIMIT 1";
        return $this->db->fetch($sql, [':username' => $username]);
    }
    
    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user || !$this->isActiveStatus($user['status'] ?? null)) {
            return false;
        }

        $storedHash = $this->getStoredPasswordHash($user);
        if ($storedHash === null || $storedHash === '') {
            return false;
        }
        
        if (password_verify($password, $storedHash)) {
            $this->updateLastLogin($user['id']);
            return $user;
        }
        
        return false;
    }

    /**
     * Authenticate user by username
     */
    public function authenticateByUsername($username, $password) {
        $user = $this->findByUsername($username);

        if (!$user || !$this->isActiveStatus($user['status'] ?? null)) {
            return false;
        }

        $storedHash = $this->getStoredPasswordHash($user);
        if ($storedHash === null || $storedHash === '') {
            return false;
        }

        if (password_verify($password, $storedHash)) {
            $this->updateLastLogin($user['id']);
            return $user;
        }

        return false;
    }

    private function isActiveStatus($status): bool {
        if ($status === null || $status === '') {
            return true;
        }

        $normalized = strtolower(trim((string)$status));
        return $normalized === 'active' || $normalized === '1';
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
        $passwordColumn = $this->getPasswordColumn();
        if ($passwordColumn === null) {
            return false;
        }

        $sql = "UPDATE users SET {$passwordColumn} = :password_hash, updated_at = NOW() WHERE id = :id";
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

        // Support both legacy/local and production column names.
        $membershipExpires = $user['membership_expires'] ?? $user['membership_expires_at'] ?? null;
        if (empty($membershipExpires)) {
            return false; // No membership expiration date set
        }

        try {
            return new DateTime() < new DateTime($membershipExpires);
        } catch (Exception $e) {
            return false; // Invalid date format
        }
    }
    
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $this->db->query($sql, [':id' => $userId]);
    }

    private function hasUserColumn(string $column): bool {
        return in_array($column, $this->getUserColumns(), true);
    }

    private function getPasswordColumn(): ?string {
        if ($this->passwordColumn !== null) {
            return $this->passwordColumn;
        }

        $columns = $this->getUserColumns();
        if (in_array('password_hash', $columns, true)) {
            $this->passwordColumn = 'password_hash';
            return $this->passwordColumn;
        }
        if (in_array('password', $columns, true)) {
            $this->passwordColumn = 'password';
            return $this->passwordColumn;
        }

        return null;
    }

    private function getStoredPasswordHash(array $user): ?string {
        $passwordColumn = $this->getPasswordColumn();
        if ($passwordColumn !== null && array_key_exists($passwordColumn, $user)) {
            return $user[$passwordColumn];
        }

        if (array_key_exists('password_hash', $user)) {
            return $user['password_hash'];
        }
        if (array_key_exists('password', $user)) {
            return $user['password'];
        }

        return null;
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
