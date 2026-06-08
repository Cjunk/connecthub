<?php
require __DIR__ . '/../../config/constants.php';
$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';port=' . (int)DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$groupSlugs = $pdo->query('SELECT slug FROM groups ORDER BY slug')->fetchAll(PDO::FETCH_COLUMN);
$eventSlugs = $pdo->query('SELECT slug FROM events ORDER BY slug')->fetchAll(PDO::FETCH_COLUMN);
echo 'PROD_GROUP_SLUGS|' . json_encode($groupSlugs) . PHP_EOL;
echo 'PROD_EVENT_SLUGS|' . json_encode($eventSlugs) . PHP_EOL;
