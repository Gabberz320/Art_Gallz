<?php
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['upload_flash_message'] = 'Please log in before posting a doodle.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: doodle.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['image'])) {
    $_SESSION['upload_flash_message'] = 'No doodle image was received.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: doodle.php');
    exit;
}

$dataUrl = trim((string)$_POST['image']);

if (!preg_match('/^data:image\/png;base64,/', $dataUrl)) {
    $_SESSION['upload_flash_message'] = 'Invalid doodle format. Please try again.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: doodle.php');
    exit;
}

$base64Data = substr($dataUrl, strpos($dataUrl, ',') + 1);
$binary = base64_decode($base64Data, true);

if ($binary === false) {
    $_SESSION['upload_flash_message'] = 'Could not decode doodle image data.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: doodle.php');
    exit;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$filename = 'doodle_' . time() . '_' . bin2hex(random_bytes(4)) . '.png';
$absolutePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
$relativePath = 'uploads/' . $filename;

if (file_put_contents($absolutePath, $binary) === false) {
    $_SESSION['upload_flash_message'] = 'Failed to save doodle image to disk.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: doodle.php');
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO Artworks (user_id, Title, Description, CreationDate, ImageURL) VALUES (?, ?, ?, CURDATE(), ?)'
    );
    $stmt->execute([
        (int)$_SESSION['user_id'],
        'Doodle ' . date('Y-m-d H:i'),
        'Posted from doodle board',
        $relativePath,
    ]);

    $_SESSION['upload_flash_message'] = 'Your doodle has been posted to the gallery!';
    $_SESSION['upload_flash_success'] = true;
    header('Location: index.php');
    exit;
} catch (PDOException $e) {
    if (file_exists($absolutePath)) {
        unlink($absolutePath);
    }

    $_SESSION['upload_flash_message'] = 'Database error while posting doodle.';
    $_SESSION['upload_flash_success'] = false;
    header('Location: doodle.php');
    exit;
}
?>