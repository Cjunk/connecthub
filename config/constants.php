<?php
/**
 * Configuration Constants
 * Central location for all application constants
 */

// Load configuration based on environment
$localConfigFile = __DIR__ . '/local_config.php';
$productionConfigFile = dirname(__DIR__) . '/production_config.php';

// Check for local development config first
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
} elseif (file_exists($productionConfigFile)) {
    require_once $productionConfigFile;
}

// Application Settings
define('APP_NAME', 'ConnectHub');
define('APP_VERSION', '1.0.0');
if (!defined('APP_ENV')) define('APP_ENV', 'development'); // Use config file value if available
if (!defined('APP_DEBUG')) define('APP_DEBUG', true); // Use config file value if available
define('APP_TIMEZONE', 'UTC');

// URL Settings - Use config file values if available, otherwise use environment variables or defaults
if (!defined('BASE_URL')) define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost');
if (!defined('SITE_URL')) define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost/connecthub');

// File Paths
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');

// Database Configuration - Use config file values if available, otherwise use environment variables or defaults
if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? 'connecthub');
if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? 'root');
if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// Security Settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Membership Settings
define('ANNUAL_MEMBERSHIP_FEE', 50.00);
define('CURRENCY', 'USD');
define('ORGANIZER_POINTS_PER_EVENT', 10);
define('POINTS_TO_CURRENCY_RATE', 0.10); // $0.10 per point

// Email Settings - Use config file values if available, otherwise use environment variables or defaults
if (!defined('SMTP_HOST')) define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
if (!defined('SMTP_PORT')) define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
if (!defined('FROM_EMAIL')) define('FROM_EMAIL', $_ENV['FROM_EMAIL'] ?? 'noreply@connecthub.com');
define('FROM_NAME', 'ConnectHub');

// Payment Settings - Use config file values if available, otherwise use environment variables
if (!defined('STRIPE_PUBLIC_KEY')) define('STRIPE_PUBLIC_KEY', $_ENV['STRIPE_PUBLIC_KEY'] ?? '');
if (!defined('STRIPE_SECRET_KEY')) define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');

// Security Settings - Use config file values if available, otherwise use environment variables
if (!defined('ENCRYPTION_KEY')) define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? '');
if (!defined('JWT_SECRET')) define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? '');

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);

// Pagination
define('EVENTS_PER_PAGE', 12);
define('GROUPS_PER_PAGE', 10);
define('MEMBERS_PER_PAGE', 20);

// Status Constants
define('STATUS_ACTIVE', 1);
define('STATUS_INACTIVE', 0);
define('STATUS_PENDING', 2);
define('STATUS_SUSPENDED', 3);

// User Roles
define('ROLE_MEMBER', 'member');
define('ROLE_ORGANIZER', 'organizer');
define('ROLE_ADMIN', 'admin');
define('ROLE_SUPER_ADMIN', 'super_admin');

// Event Status
define('EVENT_DRAFT', 'draft');
define('EVENT_PUBLISHED', 'published');
define('EVENT_CANCELLED', 'cancelled');
define('EVENT_COMPLETED', 'completed');

// Group Privacy Levels
define('GROUP_PUBLIC', 'public');
define('GROUP_PRIVATE', 'private');
define('GROUP_SECRET', 'secret');