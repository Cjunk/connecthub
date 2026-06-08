<?php
require __DIR__ . '/../../config/constants.php';
$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';port=' . (int)DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$tables = [
    'users','groups','group_memberships','events','event_attendees','event_comments','comment_likes','event_media',
    'event_categories','group_categories','group_join_requests','group_role_permissions','group_activity_log'
];
$out = [];
foreach ($tables as $t) {
    $exists = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '" . $t . "'")->fetchColumn();
    if ($exists > 0) {
        $out[$t] = (int)$pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
    } else {
        $out[$t] = null;
    }
}
echo json_encode($out) . PHP_EOL;
