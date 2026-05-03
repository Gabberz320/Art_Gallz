<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['image'])) {
    $data = $_POST['image'];

    $uri =  substr($data, strpos($data, ",") + 1);
    
    // Create a unique filename
    $filename = "doodle_" . time() . ".png";
    $filepath = "uploads/" . $filename;

    // Save file to the uploads folder
    file_put_contents($filepath, base64_decode($uri));

    // After saving, redirect the user back to the feed
    header("Location: index.php?success=1");
    exit;
}
?>