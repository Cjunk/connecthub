<?php
/**
 * Test Security Dashboard Loading
 */

echo "Testing security dashboard dependencies...\n";

try {
    // Test bootstrap loading (this should now work)
    require_once '../config/bootstrap.php';
    echo "✅ Bootstrap loaded successfully!\n";
    
    // Test constants
    echo "✅ APP_TIMEZONE: " . APP_TIMEZONE . "\n";
    echo "✅ BASE_URL: " . BASE_URL . "\n";
    
    // Test Security class
    if (class_exists('Security')) {
        echo "✅ Security class available\n";
        
        // Test a simple method
        $stats = Security::getSecurityStats();
        echo "✅ Security stats: " . json_encode($stats) . "\n";
    } else {
        echo "❌ Security class not available\n";
    }
    
    echo "\n🎉 Security dashboard should now work!\n";
    
} catch (Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}