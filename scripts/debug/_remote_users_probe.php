<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once "config/constants.php";
require_once "config/database.php";

if (method_exists("Database", "getInstance")) {
    $db = Database::getInstance();
} else {
    $db = new Database();
}
$conn = $db->getConnection();

echo "USERS_COLUMNS:";
$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = \"users\" AND TABLE_SCHEMA = DATABASE()";
$stmt = $conn->prepare($query);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo " " . $row["COLUMN_NAME"];
}
echo "\n";

$username = "probe_user_" . bin2hex(random_bytes(2));
$email = "probe_" . bin2hex(random_bytes(4)) . "@example.com";
$password = password_hash("password123", PASSWORD_DEFAULT);
$first_name = "Probe";
$last_name = "User";
$phone = "123456789";
$bio = "Probe Bio";
$role = "user";

try {
    $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, phone, bio, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtinsert = $conn->prepare($sql);
    $result = $stmtinsert->execute([$username, $email, $password, $first_name, $last_name, $phone, $bio, $role]);
    if ($result) {
        echo "DIRECT_INSERT_OK|" . $conn->lastInsertId() . "|" . $email . "\n";
    } else {
        echo "DIRECT_INSERT_ERR|" . implode(" ", $stmtinsert->errorInfo()) . "\n";
    }
} catch (Exception $e) {
    echo "DIRECT_INSERT_ERR|" . $e->getMessage() . "\n";
}
?>
