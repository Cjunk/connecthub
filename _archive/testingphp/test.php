<!DOCTYPE html>
<html lang="en">

<head>
    <title>Testing Php</title>
</head>
<body>
    <h1>This page is for testing php to database script</h1>
<style>
   .therow{
       background-color:red;
   } 
   .night {
       height:80vh;
       background-color:yellow;
       width:100%;
   }
</style>   

<?php
$password2 = getenv('DB_PASS');
$username2 = getenv('DB_USER');
$DB_SECRETKEY2 = getenv('DB_SECRETKEY');

$servername = "localhost";
$username = "jericho_user";
$password = "password1password2";
$database = "testing";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, name, email FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        // echo "id: " . $row["id"] . " - Name: " . $row["name"] . " - Email: " . $row["email"] . "<br>";
        echo "<div class='therow'> Name: " . $row["name"] . " </div>";
    }
} else {
    echo "0 results";
}
echo "<div class='therow'> PASSWORD IS : " . $password2 . " </div>";
echo "<div class='therow'> USERNAME IS : " . $username2 . " </div>";
echo "<div class='therow'> SECRET KEY IS : " . $DB_SECRETKEY2 . " </div>";

$conn->close();
?>
<div class="night">
    
</div>

</body>
