<h1> HI THERE </h1>

<?php
include 'log_website_visit.php';

$websiteName  = "NEZBALLOONS";  // Set the website ID based on your application logic
logVisit($conn, $websiteName );

$conn->close();
?>
<h1>It is done</h1>
