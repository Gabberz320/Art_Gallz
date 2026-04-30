<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$art_id = isset($_POST['art_id']) ? (int) $_POST['art_id'] : 0;
$user_id = (int) $_SESSION['user_id'];

if ($art_id <= 0) {
    $_SESSION['upload_flash_message'] = 'Invalid artwork selected for deletion.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT user_id, ImageURL FROM Artworks WHERE art_ID = ?');
    $stmt->execute([$art_id]);
    $artwork = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artwork || (int) $artwork['user_id'] !== $user_id) {
        $_SESSION['upload_flash_message'] = 'You can only delete your own artwork.';
        $_SESSION['upload_flash_success'] = false;
        header('Location: index.php');
        exit;
    }

    $pdo->beginTransaction();

    try {
        $pdo->prepare('DELETE FROM Likes WHERE art_ID = ?')->execute([$art_id]);
    } catch (PDOException $e) {
        if ((int) $e->errorInfo[1] !== 1146) {
            throw $e;
        }
    }

    $deleteArtwork = $pdo->prepare('DELETE FROM Artworks WHERE art_ID = ?');
    $deleteArtwork->execute([$art_id]);

    if ($deleteArtwork->rowCount() === 0) {
        throw new RuntimeException('Artwork delete affected 0 rows.');
    }

    $pdo->commit();

    $imagePath = $artwork['ImageURL'];
    if ($imagePath) {
        $fullPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    $_SESSION['upload_flash_message'] = 'Artwork deleted successfully.';
    $_SESSION['upload_flash_success'] = true;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['upload_flash_message'] = 'Could not delete the artwork: ' . $e->getMessage();
    $_SESSION['upload_flash_success'] = false;
}

header('Location: index.php');
exit;
