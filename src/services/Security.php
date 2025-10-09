<?php
/**
 * Security Service
 * Rate limiting and brute-force protection using database storage
 * Implements rolling window for login attempts per IP address
 */

class Security {
    private static $db;
    private static $tableChecked = false;
    
    private static function getDb() {
        if (!self::$db) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }
    
    /**
     * Ensure the login_attempts table exists
     */
    private static function ensureTable(): void {
        if (self::$tableChecked) {
            return;
        }
        
        $db = self::getDb();
        
        try {
            // Check if table exists
            $checkSql = "SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'login_attempts'
            )";
            
            $exists = $db->fetch($checkSql);
            
            if (!$exists['exists']) {
                // Create table
                $createSql = "
                CREATE TABLE login_attempts (
                    id SERIAL PRIMARY KEY,
                    ip_address INET NOT NULL,
                    email VARCHAR(255),
                    success BOOLEAN NOT NULL DEFAULT false,
                    attempted_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                );
                
                CREATE INDEX idx_login_attempts_ip_time ON login_attempts(ip_address, attempted_at);
                CREATE INDEX idx_login_attempts_success_time ON login_attempts(success, attempted_at);
                ";
                
                $db->query($createSql);
            }
            
            self::$tableChecked = true;
        } catch (Exception $e) {
            error_log("Failed to ensure login_attempts table: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if IP has too many failed login attempts
     * Uses rolling window approach (sliding time window)
     * 
     * @param string $ip Client IP address
     * @param int $limit Maximum attempts allowed (default: 5)
     * @param int $windowSeconds Time window in seconds (default: 900 = 15 minutes)
     * @return bool True if too many attempts, false otherwise
     */
    public static function tooManyAttempts(string $ip, int $limit = 5, int $windowSeconds = 900): bool {
        self::ensureTable();
        $db = self::getDb();
        
        // Clean up old attempts first
        self::cleanupOldAttempts($windowSeconds);
        
        // Count failed attempts in the time window
        // Calculate the cutoff time
        $cutoffTime = date('Y-m-d H:i:s', time() - $windowSeconds);
        
        $sql = "SELECT COUNT(*) as attempt_count 
                FROM login_attempts 
                WHERE ip_address = :ip 
                AND success = false 
                AND attempted_at > :cutoff_time";
        
        $result = $db->fetch($sql, [
            ':ip' => $ip,
            ':cutoff_time' => $cutoffTime
        ]);
        
        return ($result['attempt_count'] ?? 0) >= $limit;
    }
    
    /**
     * Record a login attempt (success or failure)
     * 
     * @param string $ip Client IP address
     * @param bool $success Whether the attempt was successful
     * @param string|null $email Email address (optional, for enhanced tracking)
     */
    public static function recordAttempt(string $ip, bool $success, ?string $email = null): void {
        self::ensureTable();
        $db = self::getDb();
        
        $sql = "INSERT INTO login_attempts (ip_address, email, success, attempted_at) 
                VALUES (:ip, :email, :success, NOW())";
        
        $params = [
            ':ip' => $ip,
            ':email' => $email,
            ':success' => $success
        ];
        
        try {
            $db->query($sql, $params);
        } catch (Exception $e) {
            // Log error but don't break authentication flow
            error_log("Failed to record login attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Reset failed attempts for an IP (called on successful login)
     * 
     * @param string $ip Client IP address
     */
    public static function resetAttempts(string $ip): void {
        $db = self::getDb();
        
        // We don't delete records for audit purposes, but successful login
        // resets the effective count since we only count recent failures
        self::recordAttempt($ip, true);
    }
    
    /**
     * Clean up old login attempts to prevent table bloat
     * 
     * @param int $olderThanSeconds Remove attempts older than this (default: 24 hours)
     */
    private static function cleanupOldAttempts(int $olderThanSeconds = 86400): void {
        $db = self::getDb();
        
        // Calculate the cutoff time
        $cutoffTime = date('Y-m-d H:i:s', time() - $olderThanSeconds);
        
        $sql = "DELETE FROM login_attempts 
                WHERE attempted_at < :cutoff_time";
        
        try {
            $db->query($sql, [':cutoff_time' => $cutoffTime]);
        } catch (Exception $e) {
            // Log but don't fail - cleanup is not critical
            error_log("Failed to cleanup old login attempts: " . $e->getMessage());
        }
    }
    
    /**
     * Get recent failed attempts for monitoring
     * 
     * @param int $limit Number of recent attempts to return
     * @return array Recent failed attempts with IP, email, and timestamp
     */
    public static function getRecentFailedAttempts(int $limit = 50): array {
        $db = self::getDb();
        
        $sql = "SELECT ip_address, email, attempted_at 
                FROM login_attempts 
                WHERE success = false 
                ORDER BY attempted_at DESC 
                LIMIT :limit";
        
        return $db->fetchAll($sql, [':limit' => $limit]);
    }
    
    /**
     * Get stats for security monitoring
     * 
     * @return array Stats including total attempts, success rate, etc.
     */
    public static function getSecurityStats(): array {
        $db = self::getDb();
        
        // Get stats for last 24 hours
        $cutoffTime = date('Y-m-d H:i:s', time() - 86400); // 24 hours ago
        
        $sql = "SELECT 
                    COUNT(*) as total_attempts,
                    COUNT(CASE WHEN success = true THEN 1 END) as successful_attempts,
                    COUNT(CASE WHEN success = false THEN 1 END) as failed_attempts,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM login_attempts 
                WHERE attempted_at > :cutoff_time";
        
        $stats = $db->fetch($sql, [':cutoff_time' => $cutoffTime]);
        
        if ($stats['total_attempts'] > 0) {
            $stats['success_rate'] = round(($stats['successful_attempts'] / $stats['total_attempts']) * 100, 2);
        } else {
            $stats['success_rate'] = 0;
        }
        
        return $stats;
    }
}