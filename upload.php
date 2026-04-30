<?php
require 'db.php';

//redirect to home if the user is not logged in via Google OAuth
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';
$isSuccessMessage = false;

if (isset($_SESSION['upload_flash_message'])) {
    $message = $_SESSION['upload_flash_message'];
    $isSuccessMessage = isset($_SESSION['upload_flash_success']) && $_SESSION['upload_flash_success'] === true;
    unset($_SESSION['upload_flash_message'], $_SESSION['upload_flash_success']);
}

//form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? 'Untitled';
    $description = $_POST['description'] ?? '';
    $user_id = null;

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT user_id FROM Users WHERE user_id = ?");
        $stmt->execute([(int)$_SESSION['user_id']]);
        $existingUserId = $stmt->fetchColumn();

        if ($existingUserId) {
            $user_id = (int)$existingUserId;
        }
    }

    if ($user_id === null && !empty($_SESSION['google_id'])) {
        $stmt = $pdo->prepare("SELECT user_id, Name FROM Users WHERE oauthID = ?");
        $stmt->execute([$_SESSION['google_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user && !empty($_SESSION['user_email']) && !empty($_SESSION['user_name'])) {
            $stmt = $pdo->prepare("INSERT INTO Users (oauthID, Email, Name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Email = ?, Name = ?");
            $stmt->execute([
                $_SESSION['google_id'],
                $_SESSION['user_email'],
                $_SESSION['user_name'],
                $_SESSION['user_email'],
                $_SESSION['user_name']
            ]);

            $stmt = $pdo->prepare("SELECT user_id, Name FROM Users WHERE oauthID = ?");
            $stmt->execute([$_SESSION['google_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($user) {
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['user_name'] = $user['Name'];
            $user_id = (int)$user['user_id'];
        }
    }

    if ($user_id === null) {
        $_SESSION['upload_flash_message'] = 'Your login session is out of sync. Please sign out and sign in again.';
        $_SESSION['upload_flash_success'] = false;
        header('Location: upload.php');
        exit;
    }

    //check if file was uploaded without errors
    if (isset($_FILES['artwork']) && $_FILES['artwork']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['artwork']['tmp_name'];
        $fileName = $_FILES['artwork']['name'];
        $fileSize = $_FILES['artwork']['size'];
        
        //validate file extensions JPG JPEG PNG 
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        if (in_array($fileExtension, $allowedExtensions)) {
            //upload foler 
            $uploadFileDir = 'uploads/';
            
            //unique file names 
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            //move the file from the temp directory to your uploads folder
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                
                //insert the artwork details into the database
                try {
                    $stmt = $pdo->prepare("INSERT INTO Artworks (user_id, Title, Description, CreationDate, ImageURL) VALUES (?, ?, ?, CURDATE(), ?)");
                    $stmt->execute([$user_id, $title, $description, $dest_path]);
                    $message = "Your artwork has been added to the gallery!";
                    $isSuccessMessage = true;
                } catch (PDOException $e) {
                    $message = "Database error: " . $e->getMessage();
                }
            } else {
                $message = 'Error moving the file. Ensure the "uploads" directory has write permissions.';
            }
        } else {
            $message = 'Upload failed. Only JPG, JPEG, and PNG files are allowed.';
        }
    } else {
        $message = 'Please select a file to upload or check size limit.';
    }

    $_SESSION['upload_flash_message'] = $message;
    $_SESSION['upload_flash_success'] = $isSuccessMessage;
    header('Location: ' . ($isSuccessMessage ? 'index.php' : 'upload.php'));
    exit;
}

function initials($name){
    $parts = explode(' ', trim($name));
    $ini = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $ini .= strtoupper(substr(end($parts), 0, 1));
    return $ini;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Art - Web Gallz</title>
    <link href="styles.css" rel="stylesheet">
    <link href="upload.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<header class="topbar">
    <div class="topbar_logo">Web Gallz</div>
    <div class="topbar_actions">
        <?php if (isset($_SESSION['user_id'])): ?>

<!-- dark/Light toggle -->
<button class="mode_toggle" id="modeToggle" title="Toggle theme">
    <span class="toggle_track">
        <span class="toggle_thumb"></span>
    </span>
</button>

<!-- upload button -->
<a href="upload.php" class="btn_upload">
<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Upload </a>

<!--Initial/ profile dropdown-->
<div class="avatar_wrap">
    <div class="avatar" id="avatarBtn">
        <?php echo initials($_SESSION['user_name']); ?>
    </div>
    <div class="avatar_dropdown" id="avatarDropdown">
        <div class="dropdown_name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
        <hr class="dropdown_divider">
        <a href="logout.php" class="dropdown_item">Logout</a>
    </div>
</div>
<?php else: ?>

<!-- Google Sign-In: removed sign-in placeholders on this page (user should be logged in) -->
<?php endif; ?>

</div>
</header>

<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
    <div>
        <div class="sidebar_section">Browse</div>
        <nav class="sidebar_nav">
            <a href="index.php" class="sidebar_link">All Posts</a>
            <a href="index.php?filter=liked" class="sidebar_link">Trending</a>
        </nav>
    </div>

    <div>
        <div class="sidebar_section">Categories</div>
        <nav class="sidebar_nav">
            <a href="#" class="sidebar_link">Illustration</a>
            <a href="#" class="sidebar_link">Doodles</a>
            <a href="#" class="sidebar_link">Photography</a>
        </nav>
    </div>
    </aside>

<!-- main content -->
<main class="main">

    <?php if ($message): ?>
        <div id="upload-message" class="message<?php echo $isSuccessMessage ? ' success_popup' : ''; ?>" data-autohide="<?php echo $isSuccessMessage ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="feed_header">
        <div class="feed_title">Upload Your <span>Artwork</span></div>
    </div>

    <div style="max-width: 680px;">
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required placeholder="Name it.">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required placeholder="Describe it."></textarea>
            </div>

            <div class="form-group">
                <label>Select File</label>
                <label class="drop-zone">
                    <span class="drop-zone__prompt">Drag and drop it<br>or click to browse<br><small>(JPG, JPEG, PNG)</small></span>
                    <input id="artworkInput" type="file" name="artwork" class="drop-zone__input" accept=".jpg, .jpeg, .png" required>
                </label>
            </div>
            
            <button type="submit" class="submit-btn">Upload It</button>
        </form>
    </div>

</main>

</div>
<!-- scripts with forcing new copy of js files to be loaded -->
<script src="main.js?v=<?php echo time(); ?>"></script>
<script src="upload.js?v=<?php echo time(); ?>"></script>

</body>
</html>
