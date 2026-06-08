<?php
require_once "includes/config.php";
try {
    $db = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "DRIVER: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
    $tables = ["users", "payments", "login_attempts", "group_memberships", "events", "event_attendees", "groups"];
    foreach ($tables as $table) {
        echo "\nTABLE: $table\n";
        $stmt = $db->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "COLUMN: {$row['Field']} ({$row['Type']})\n";
        }
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "COUNT: $count\n";
    }
    echo "\nLAST_PAYMENT_KEYS:\n";
    $stmt = $db->query("SELECT * FROM payments ORDER BY payment_id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        foreach ($row as $k => $v) echo "KEY: $k\n";
    } else {
        echo "No records in payments\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}