<?php
/**
 * Database Configuration
 */

require_once __DIR__ . '/constants.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $isProduction = defined('APP_ENV') && APP_ENV === 'production';
            $hasMysql = extension_loaded('pdo_mysql');
            $hasPgsql = extension_loaded('pdo_pgsql');
            $port = defined('DB_PORT') ? (int) DB_PORT : 3306;
            $driverHint = strtolower(trim((string)($_ENV['DB_DRIVER'] ?? getenv('DB_DRIVER') ?? '')));

            // Default to MySQL unless pgsql is explicitly requested/configured.
            $usePgsql = false;
            if ($driverHint === 'pgsql' || $driverHint === 'postgres' || $driverHint === 'postgresql') {
                $usePgsql = true;
            } elseif (!$isProduction && $port === 5432 && $hasPgsql && !$hasMysql) {
                $usePgsql = true;
            }

            if (!$usePgsql) {
                // MySQL/MariaDB for production
                if (!$hasMysql) {
                    $msg = 'pdo_mysql extension is not enabled';
                    error_log('Database connection failed: ' . $msg);
                    throw new Exception(APP_DEBUG ? ('Database connection failed: ' . $msg) : 'Database connection failed');
                }
                $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
                $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . $charset;
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            } else {
                // PostgreSQL for local development
                if (!$hasPgsql) {
                    $msg = 'pdo_pgsql extension is not enabled';
                    error_log('Database connection failed: ' . $msg);
                    throw new Exception(APP_DEBUG ? ('Database connection failed: ' . $msg) : 'Database connection failed');
                }
                $port = $port > 0 ? $port : 5432;
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME;
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception(APP_DEBUG ? ("Database connection failed: " . $e->getMessage()) : "Database connection failed");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            $sqlState = (string) $e->getCode();
            if ($sqlState === '') {
                $sqlState = 'UNKNOWN';
            }
            throw new Exception("Database query failed (SQLSTATE: " . $sqlState . ")");
        }
    }
    
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
}
