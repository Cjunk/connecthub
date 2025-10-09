<?php
/**
 * Add Images to Events
 * This script assigns random event images to all events in the database
 */

require_once '../config/constants.php';
require_once '../config/bootstrap.php';

echo "<h1>🖼️ Adding Images to Events</h1>";

try {
    $db = Database::getInstance();
    
    // Get all available event images
    $imageDir = '../public/uploads/events/';
    $images = array_diff(scandir($imageDir), array('.', '..', '.gitkeep', '.htaccess', 'README.md'));
    $images = array_values($images); // Reset array keys
    
    if (empty($images)) {
        echo "<p>❌ No images found in uploads/events directory</p>";
        exit;
    }
    
    echo "<h2>📸 Available Images:</h2>";
    echo "<ul>";
    foreach ($images as $image) {
        echo "<li>$image</li>";
    }
    echo "</ul>";
    
    // Get all events without images
    $events = $db->fetchAll("SELECT id, title, cover_image FROM events ORDER BY id");
    
    echo "<h2>🎯 Updating Events:</h2>";
    
    $updated = 0;
    foreach ($events as $event) {
        // Assign a random image or use existing if already has one
        if (empty($event['cover_image'])) {
            $randomImage = $images[array_rand($images)];
            $imagePath = 'uploads/events/' . $randomImage;
            
            $db->query(
                "UPDATE events SET cover_image = :cover_image WHERE id = :id",
                [':cover_image' => $imagePath, ':id' => $event['id']]
            );
            
            echo "<p>✅ Event '{$event['title']}' → $randomImage</p>";
            $updated++;
        } else {
            echo "<p>⏭️ Event '{$event['title']}' already has image: {$event['cover_image']}</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>📊 Summary:</h3>";
    echo "<ul>";
    echo "<li><strong>Total Events:</strong> " . count($events) . "</li>";
    echo "<li><strong>Images Available:</strong> " . count($images) . "</li>";
    echo "<li><strong>Events Updated:</strong> $updated</li>";
    echo "</ul>";
    
    echo "<p>✅ <strong>All events now have images!</strong></p>";
    echo "<p><a href='events.php' class='btn btn-primary'>View Events Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>