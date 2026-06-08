<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../src/models/ActivityFeed.php';

header('Content-Type: application/json');

try {
    $feed = new ActivityFeed();

    $afterId = isset($_GET['after_id']) ? (int)$_GET['after_id'] : 0;
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;

    if ($afterId > 0) {
        $items = $feed->getAfterId($afterId, $limit);
    } else {
        $items = $feed->getRecentPublic($limit);
    }

    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Could not load activity feed'
    ]);
}