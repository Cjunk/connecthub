<?php
$_SERVER['REQUEST_METHOD']='GET';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/bootstrap.php';
$db = Database::getInstance()->getConnection();
$row = $db->query("SELECT MAX(id) AS id FROM users")->fetch(PDO::FETCH_ASSOC);
$uid = (int)($row['id'] ?? 5); if ($uid<=0) $uid=5;
$_SESSION['user_id']=$uid; $_SESSION['last_activity']=time();
try { ob_start(); include __DIR__ . '/dashboard.php'; ob_end_clean(); echo 'DASHBOARD_EXEC_OK|USER=' . $uid . PHP_EOL; }
catch (Throwable $e) { if (ob_get_level()>0) ob_end_clean(); echo 'DASHBOARD_EXEC_ERR|' . $e->getMessage() . '|FILE:' . $e->getFile() . '|LINE:' . $e->getLine() . PHP_EOL; }