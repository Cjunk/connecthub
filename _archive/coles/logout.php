<?php
/**
 * Logout functionality for Coles Preferences System
 */

require_once 'includes/auth.php';

// Logout the user
Auth::logout();

// Redirect to login page
header("Location: login");
exit;
?>
