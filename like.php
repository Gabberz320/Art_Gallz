<?php
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['art_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$art_id = (int)$_POST['art_id'];
$user_id = $_SESSION['user_id'];

try {
    $check = $pdo->prepare("SELECT 1 FROM Likes WHERE user_id = ? AND art_ID = ?");
    $check->execute([$user_id, $art_id]);

    if ($check->fetch()) {
        // Unlike
        $pdo->prepare("DELETE FROM Likes WHERE user_id = ? AND art_ID = ?")->execute([$user_id, $art_id]);
        $pdo->prepare("UPDATE Artworks SET LikesCounter = GREATEST(0, LikesCounter - 1) WHERE art_ID = ?")->execute([$art_id]);
        $liked = false;
    } else {
        // Like
        $pdo->prepare("INSERT INTO Likes (user_id, art_ID) VALUES (?, ?)")->execute([$user_id, $art_id]);
        $pdo->prepare("UPDATE Artworks SET LikesCounter = LikesCounter + 1 WHERE art_ID = ?")->execute([$art_id]);
        $liked = true;
    }

    $stmt = $pdo->prepare("SELECT LikesCounter FROM Artworks WHERE art_ID = ?");
    $stmt->execute([$art_id]);
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count]);
} catch (PDOException $e) {
    echo json_encode(['success' => false]);
}
