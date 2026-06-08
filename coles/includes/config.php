<?php
/**
 * Database Configuration for Coles Preferences System
 * MySQL version for GoDaddy hosting
 */

class DatabaseConfig {
    // Database connection parameters - UPDATE THESE WITH YOUR GODADDY DETAILS
    const DB_HOST = 'localhost';           // Usually 'localhost' for GoDaddy
    const DB_NAME = 'colesholidayprefs';  // Your GoDaddy MySQL database name
    const DB_USER = 'cjunk';    // Your GoDaddy MySQL username  
    const DB_PASS = 'Quest35#Scrap35#Axiom35#';    // Your GoDaddy MySQL password
    const DB_PORT = '3306';                // MySQL standard port
    
    private static $connection = null;
    
    /**
     * Get PDO database connection
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $dsn = "mysql:host=" . self::DB_HOST . ";port=" . self::DB_PORT . ";dbname=" . self::DB_NAME . ";charset=utf8mb4";
                self::$connection = new PDO($dsn, self::DB_USER, self::DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }
    
    /**
     * Close database connection
     */
    public static function closeConnection() {
        self::$connection = null;
    }
}
?>
