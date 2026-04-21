<?php
require 'db.php';

// sent by our js function in index.php
if (isset($_POST['google_id'])) {
    $oauth_id = $_POST['google_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    try {
        // save or update the user using your 'oauthID' column
        $stmt = $pdo->prepare("INSERT INTO Users (oauthID, Email, Name) 
                               VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE Name = ?");
        $stmt->execute([$oauth_id, $email, $name, $name]);

        // fetch user using your 'user_id' and 'oauthID' columns
        $stmt = $pdo->prepare("SELECT user_id, Name FROM Users WHERE oauthID = ?");
        $stmt->execute([$oauth_id]);
        $user = $stmt->fetch();

        // set session using column names
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['Name'];

        // send them back to the main page
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}