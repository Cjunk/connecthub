<?php
/**
 * Production Configuration for GoDaddy Hosting
 * Update these values with your actual hosting details
 */

// Database Configuration - UPDATE WITH YOUR GODADDY DATABASE INFO
define('DB_HOST', 'localhost'); // Usually localhost on GoDaddy
define('DB_NAME', 'your_database_name'); // Your GoDaddy database name
define('DB_USER', 'your_database_user'); // Your GoDaddy database username
define('DB_PASS', 'your_database_password'); // Your GoDaddy database password

// Environment Settings - PRODUCTION SETTINGS
define('APP_ENV', 'production');
define('APP_DEBUG', false); // NEVER set to true in production

// URL Settings - UPDATE WITH YOUR DOMAIN
define('BASE_URL', 'https://phat-fitness.com/connecthub/public'); // Your actual current URL
define('SITE_URL', 'https://phat-fitness.com/connecthub'); // Your actual current site URL

// Email Configuration - UPDATE WITH YOUR EMAIL SETTINGS
define('SMTP_HOST', 'relay-hosting.secureserver.net'); // GoDaddy SMTP
define('SMTP_PORT', 25); // GoDaddy SMTP port (or 587 for TLS)
define('SMTP_USERNAME', 'your-email@yourdomain.com');
define('SMTP_PASSWORD', 'your-email-password');
define('FROM_EMAIL', 'noreply@yourdomain.com');

// Payment Configuration - UPDATE WITH YOUR STRIPE LIVE KEYS
define('STRIPE_PUBLIC_KEY', 'pk_live_your_stripe_public_key');
define('STRIPE_SECRET_KEY', 'sk_live_your_stripe_secret_key');

// Security Keys - GENERATE NEW SECURE KEYS
define('ENCRYPTION_KEY', 'generate-a-secure-32-character-key'); // Use: bin2hex(random_bytes(16))
define('JWT_SECRET', 'generate-a-secure-jwt-secret-key'); // Use: bin2hex(random_bytes(32))