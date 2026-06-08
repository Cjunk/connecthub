<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/bootstrap.php';

// Backward-compatible endpoint: keep POST support for old forms/bookmarks.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
	redirect(BASE_URL . '/register.php');
}

require_once __DIR__ . '/../register.php';
