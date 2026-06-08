<?php
include 'WEBSITE_STATS_conn.php';  // Include your database config file
include 'website_ids.php';
function getUserIP() {
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }

    return $ip;
}

function logVisit($conn, $websiteName) {
    global $websiteIDs;  // Access the global array defined in website_ids.php
    if (!isset($websiteIDs[$websiteName])) {
        echo "Error: '$websiteName' is not a valid website identifier.";
        return;  // Exit the function if the website name is not found in the array
    }

    $websiteID = $websiteIDs[$websiteName];  // Get the corresponding website ID
    $visitorIP = getUserIP();
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'No referrer';

    $sql = "INSERT INTO WebsiteVisits (WebsiteID, VisitorIP, VisitorReferrer, VisitDuration, PagesViewed) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('MySQL prepare error: ' . $conn->error);
    }

    $stmt->bind_param('issii', $websiteID, $visitorIP, $referrer, $visitDuration, $pagesViewed);
    $stmt->execute();

    if ($stmt->error) {
        die('Execute error on MySQL statement: ' . $stmt->error);
    } 

    $stmt->close();
}


?>
