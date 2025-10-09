<?php
/**
 * Test Bootstrap Loading
 */

echo "Testing bootstrap loading...\n";

try {
    require_once 'config/bootstrap.php';
    echo "✅ Bootstrap loaded successfully!\n";
    echo "✅ APP_TIMEZONE: " . APP_TIMEZONE . "\n";
    echo "✅ APP_DEBUG: " . (APP_DEBUG ? 'true' : 'false') . "\n";
    echo "✅ BASE_URL: " . BASE_URL . "\n";
    echo "✅ APP_PATH: " . APP_PATH . "\n";
} catch (Exception $e) {
    echo "❌ Bootstrap loading failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}