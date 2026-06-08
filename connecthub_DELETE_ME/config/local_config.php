<?php
/**
 * Local Development Configuration
 * This file is used when developing locally
 */

// Environment Settings - DEVELOPMENT SETTINGS
define('APP_ENV', 'development');
define('APP_DEBUG', true); // Show errors during development

// URL Settings - LOCAL DEVELOPMENT URLs
define('BASE_URL', ''); // Local development - use relative URLs
define('SITE_URL', 'http://localhost'); // Local development site URL

// Database Configuration - LOCAL DEVELOPMENT DATABASE (PostgreSQL)
define('DB_HOST', 'localhost');
define('DB_NAME', 'connecthub'); // Local database name
define('DB_USER', 'connecthub_admin'); // PostgreSQL user
define('DB_PASS', 'Quest35#'); // PostgreSQL password
define('DB_PORT', '5432'); // PostgreSQL default port

// Email Configuration - DEVELOPMENT SETTINGS (can be dummy values)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'dev@localhost');
define('SMTP_PASSWORD', 'password');
define('FROM_EMAIL', 'noreply@localhost');

// Payment Configuration - DEVELOPMENT SETTINGS (Stripe test keys)
// Note: Replace with your actual Stripe test keys from https://dashboard.stripe.com/test/apikeys
define('STRIPE_PUBLIC_KEY', 'pk_test_51SF5M57txnFEAm8djTMYmh9SD65FZyKxd2udIx119rNjJXnDV1AoUwXwpXacWvOhom25j8KaAF9cmXBm85PYF06a00HGJuxmVf'); // Replace with your test publishable key
define('STRIPE_SECRET_KEY', 'sk_test_51SF5M57txnFEAm8d2zRBFPceXB6bJU83HdAYHuOmGimgJr0u9iYpvXHjXWR0UQVds7w0ePE9nYxitbzFwxnlG4jx00umT3turj'); // Replace with your test secret key

// Security Keys - DEVELOPMENT KEYS (not for production use)
define('ENCRYPTION_KEY', 'dev1234567890abcdef1234567890abcd'); // 32 characters for development
define('JWT_SECRET', 'development-jwt-secret-key-not-for-production-use');
