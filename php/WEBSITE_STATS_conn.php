<?php
$servername = "localhost";  // or your server IP/hostname
$username = "jericho";
$password = "Quest35#";
$dbname = "WEBSITE_STATS";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
