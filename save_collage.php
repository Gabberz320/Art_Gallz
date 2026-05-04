<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['upload_flash_message'] = 'Please log in before posting a collage.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: collage.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['image'])) {
    $_SESSION['upload_flash_message'] = 'No collage image was received.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: collage.php');
    exit;
}

$dataUrl = trim((string)$_POST['image']);

if (!preg_match('/^data:image\/png;base64,/', $dataUrl)) {
    $_SESSION['upload_flash_message'] = 'Invalid collage format. Please try again.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: collage.php');
    exit;
}

$base64Data = substr($dataUrl, strpos($dataUrl, ',') + 1);
$binary = base64_decode($base64Data, true);

if ($binary === false) {
    $_SESSION['upload_flash_message'] = 'Could not decode collage image data.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: collage.php');
    exit;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$filename = 'collage_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
$absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
$relativePath = 'uploads/' . $filename;

if (file_put_contents($absolutePath, $binary) === false) {
    $_SESSION['upload_flash_message'] = 'Failed to save collage image to disk.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: collage.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO Artworks (user_id, Title, Description, CreationDate, ImageURL) VALUES (?, ?, ?, CURDATE(), ?)'
    );
    $stmt->execute([
        (int)$_SESSION['user_id'],
        'Collage ' . date('Y-m-d H:i'),
        'Posted from collage studio',
        $relativePath,
    ]);

    $_SESSION['upload_flash_message'] = 'Your collage has been posted to the gallery!';
    $_SESSION['upload_flash_success'] = true;
    header('Location: index.php');
    exit;
} catch (PDOException $e) {
    if (file_exists($absolutePath)) {
        unlink($absolutePath);
    }

    $_SESSION['upload_flash_message'] = 'Database error while posting collage.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: collage.php');
    exit;
}
?>