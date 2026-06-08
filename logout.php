<?php
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/bootstrap.php';

session_destroy();
setFlashMessage('success', 'You have been logged out successfully.');
redirect(BASE_URL . '/index.php');
