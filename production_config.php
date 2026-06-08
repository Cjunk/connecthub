<?php
/**
 * PRODUCTION Configuration Template
 * Copy this to config/production_config.php when ready to go live
 * NEVER commit real keys to version control!
 */

// Environment Settings - PRODUCTION
if (!defined('APP_ENV')) define('APP_ENV', 'production');
if (!defined('APP_DEBUG')) define('APP_DEBUG', false); // NEVER show errors in production

// URL Settings - YOUR LIVE DOMAIN
define('BASE_URL', 'https://www.phat-fitness.com'); // Updated for root domain
define('SITE_URL', 'https://www.phat-fitness.com'); // Updated for root domain

// Database Configuration - PRODUCTION DATABASE
define('DB_HOST', 'localhost');
define('DB_NAME', 'uhura_dbase'); // If GoDaddy prefixes DB names, verify actual name in phpMyAdmin
define('DB_USER', 'jerichosharman'); // Your actual MySQL username
define('DB_PASS', 'Quest35#Scrap35#Axiom35#'); // Your actual MySQL password
define('DB_PORT', 3306); // MySQL default port

// Email Configuration - REAL EMAIL SERVICE
define('SMTP_HOST', 'relay-hosting.secureserver.net');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'noreply@yourdomain.com');
define('SMTP_PASSWORD', 'your_email_password');
define('FROM_EMAIL', 'noreply@yourdomain.com');

// Payment Configuration - TEST STRIPE KEYS (FOR TESTING ONLY)
// ⚠️  WARNING: These are TEST keys - replace with LIVE keys for production!
define('STRIPE_PUBLIC_KEY', 'pk_test_51SF5M57txnFEAm8djTMYmh9SD65FZyKxd2udIx119rNjJXnDV1AoUwXwpXacWvOhom25j8KaAF9cmXBm85PYF06a00HGJuxmVf');
define('STRIPE_SECRET_KEY', 'sk_test_51SF5M57txnFEAm8d2zRBFPceXB6bJU83HdAYHuOmGimgJr0u9iYpvXHjXWR0UQVds7w0ePE9nYxitbzFwxnlG4jx00umT3turj');

// Security Keys - GENERATE NEW SECURE KEYS FOR PRODUCTION
define('ENCRYPTION_KEY', 'da35f8fc2a55a493c0aee8c91fe58736'); 
define('JWT_SECRET', '0071b3471c063166cd74486ae45726298d2386f4a86c5b76f8575c0c8aeee38b');


// Additional Production Settings
define('FORCE_HTTPS', true);
define('SESSION_SECURE', true);
define('SESSION_HTTPONLY', true);

