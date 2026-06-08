<?php
// Define the target directory for file uploads
$targetDir = "uploads/";
if (!is_dir($targetDir)) {
  mkdir($targetDir, 0777, true);
}

// Get the uploaded file information
$targetFile = $targetDir . basename($_FILES["file"]["name"]);
$uploadOk = 1;
$fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

// Check if the file is a CSV file
if ($fileType != "csv") {
  echo "Sorry, only CSV files are allowed.";
  $uploadOk = 0;
}

// Check if file upload is okay
if ($uploadOk == 0) {
  echo "Sorry, your file was not uploaded.";
} else {
  if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
    echo "The file " . htmlspecialchars(basename($_FILES["file"]["name"])) . " has been uploaded.";
  } else {
    echo "Sorry, there was an error uploading your file.";
  }
}
