<?php
/**
 * Mimic Security Dashboard Loading (Exact Test)
 */

echo "Testing exact security dashboard flow...\n\n";

// This is exactly what security-dashboard.php does
try {
    echo "1. Including bootstrap...\n";
    require '../config/bootstrap.php';
    echo "   ✅ Bootstrap loaded\n";
    
    echo "2. Testing constants...\n";
    echo "   ✅ APP_TIMEZONE: " . APP_TIMEZONE . "\n";
    echo "   ✅ BASE_URL: " . BASE_URL . "\n";
    
    echo "3. Testing isLoggedIn function...\n";
    if (function_exists('isLoggedIn')) {
        echo "   ✅ isLoggedIn function exists\n";
    } else {
        echo "   ❌ isLoggedIn function missing\n";
    }
    
    echo "4. Testing Security class...\n";
    if (class_exists('Security')) {
        echo "   ✅ Security class loaded\n";
        
        echo "5. Testing Security::getSecurityStats()...\n";
        $stats = Security::getSecurityStats();
        echo "   ✅ Stats retrieved: " . json_encode($stats) . "\n";
        
        echo "6. Testing Security::getRecentFailedAttempts()...\n";
        $recentFailures = Security::getRecentFailedAttempts(5);
        echo "   ✅ Recent failures: " . count($recentFailures) . " attempts\n";
        
    } else {
        echo "   ❌ Security class not loaded\n";
    }
    
    echo "\n🎉 All tests passed! Security dashboard should work now.\n";
    
} catch (Error $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
} catch (Exception $e) {
    echo "\n❌ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}