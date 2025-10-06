<?php
require_once '../config/constants.php';
require_once '../config/bootstrap.php';

session_destroy();
setFlashMessage('success', 'You have been logged out successfully.');
redirect(BASE_URL . '/index.php');