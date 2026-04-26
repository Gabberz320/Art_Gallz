<?php
require 'db.php';

$artworks = [];
$message = '';
$isSuccessMessage = false;

if (isset($_SESSION['upload_flash_message'])) {
    $message = $_SESSION['upload_flash_message'];
    $isSuccessMessage = isset($_SESSION['upload_flash_success']) && $_SESSION['upload_flash_success'] === true;
    unset($_SESSION['upload_flash_message'], $_SESSION['upload_flash_success']);
}

try {
    $stmt = $pdo->query(
        "SELECT a.Title, a.Description, a.CreationDate, a.ImageURL, u.Name AS UserName
         FROM Artworks a
         INNER JOIN Users u ON a.user_id = u.user_id
         ORDER BY a.art_ID DESC"
    );
    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $artworks = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Web Gallz</title>
    <link href="styles.css" type="text/css" rel="stylesheet">
    
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <script src="https://cdn.jsdelivr.net/npm/jwt-decode/build/jwt-decode.min.js"></script>
</head>
<body>
    <h1>Welcome to Web Gallz</h1>
    <!-- show login or user info based on session -->
    <?php if (!isset($_SESSION['user_id'])): ?>
        <p>Please log in to start sharing art.</p>
        <div id="g_id_onload"
             data-client_id="<?php echo $env['GOOGLE_CLIENT_ID']; ?>"
             data-callback="handleCredentialResponse"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin" data-type="standard"></div>
    <?php else: ?>
        <p>
            Success! You are logged in as: <?php echo htmlspecialchars($_SESSION['user_name']); ?>
        </p>
        <p><a href="upload.php">Upload New Artwork</a></p>
        <a href="logout.php">Logout</a>
    <?php endif; ?>

    <?php if ($message): ?>
        <div id="upload-message" class="message<?php echo $isSuccessMessage ? ' success-popup' : ''; ?>" data-autohide="<?php echo $isSuccessMessage ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <hr>
    <h2>Gallery</h2>

    <?php if (empty($artworks)): ?>
        <p>No artworks have been added yet.</p>
    <?php else: ?>
        <div class="gallery-list">
            <?php foreach ($artworks as $artwork): ?>
                <article class="gallery-item">
                    <img
                        src="<?php echo htmlspecialchars($artwork['ImageURL']); ?>"
                        alt="<?php echo htmlspecialchars($artwork['Title']); ?>"
                        class="gallery-image"
                    >
                    <h3><?php echo htmlspecialchars($artwork['Title']); ?></h3>
                    <p><strong>By:</strong> <?php echo htmlspecialchars($artwork['UserName']); ?></p>
                    <p><?php echo nl2br(htmlspecialchars($artwork['Description'])); ?></p>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($artwork['CreationDate']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <script>
    function handleCredentialResponse(response) {
        const responsePayload = jwt_decode(response.credential);

        if (!responsePayload) {
            console.error("Login error.");
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'login.php';

        const fields = {
            'google_id': responsePayload.sub,
            'name': responsePayload.name,
            'email': responsePayload.email
        };

        for (const key in fields) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    }
    </script>
    
    <script src="main.js"></script>
</body>
</html>