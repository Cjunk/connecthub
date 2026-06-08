<?php
/**
 * PRODUCTION Configuration Template
 * Copy this to config/production_config.php when ready to go live
 * NEVER commit real keys to version control!
 */

// Environment Settings - PRODUCTION
define('APP_ENV', 'production');
define('APP_DEBUG', false); // NEVER show errors in production

// URL Settings - YOUR LIVE DOMAIN
define('BASE_URL', 'https://yourwebsite.com'); // Replace with your actual domain
define('SITE_URL', 'https://yourwebsite.com'); // Replace with your actual domain

// Database Configuration - PRODUCTION DATABASE
define('DB_HOST', 'your-production-db-host');
define('DB_NAME', 'your_production_db_name');
define('DB_USER', 'your_production_db_user');
define('DB_PASS', 'your_super_secure_password');
define('DB_PORT', '5432');

// Email Configuration - REAL EMAIL SERVICE
define('SMTP_HOST', 'your-smtp-server.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your@email.com');
define('SMTP_PASSWORD', 'your-email-password');
define('FROM_EMAIL', 'noreply@yourwebsite.com');

// Payment Configuration - LIVE STRIPE KEYS
// ⚠️  CRITICAL: These must be your LIVE keys from https://dashboard.stripe.com/apikeys
define('STRIPE_PUBLIC_KEY', 'pk_live_YOUR_REAL_LIVE_PUBLIC_KEY_HERE');
define('STRIPE_SECRET_KEY', 'sk_live_YOUR_REAL_LIVE_SECRET_KEY_HERE');

// Security Keys - GENERATE NEW SECURE KEYS FOR PRODUCTION
define('ENCRYPTION_KEY', 'GENERATE_32_CHARACTER_RANDOM_STRING_HERE'); 
define('JWT_SECRET', 'GENERATE_LONG_RANDOM_JWT_SECRET_FOR_PRODUCTION');

// Additional Production Settings
define('FORCE_HTTPS', true);
define('SESSION_SECURE', true);
define('SESSION_HTTPONLY', true);
?>

<!-- 
CHECKLIST BEFORE GOING LIVE:
☐ Complete Stripe account verification (identity, bank account)
☐ Replace with real LIVE Stripe keys (pk_live_, sk_live_)
☐ Set up production database
☐ Configure real SMTP email service
☐ Generate new secure encryption keys
☐ Set up SSL certificate (HTTPS)
☐ Test with small real payment first
☐ Set up monitoring and error logging
☐ Have backup and rollback plan ready
-->
