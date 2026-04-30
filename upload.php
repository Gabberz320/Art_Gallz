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
    $user_id = $_SESSION['user_id'];

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Art - Web Gallz</title>

    <!--separate upload css file for now -->
    <link href="upload.css" type="text/css" rel="stylesheet">
</head>
<body>
    <!-- upload form -->
    <div class="upload-container">
        <h2>Upload Your Artwork</h2>
        <p><a href="index.php">Back to Gallery</a></p>

        <?php if ($message): ?>
            <div id="upload-message" class="message<?php echo $isSuccessMessage ? ' success-popup' : ''; ?>" data-autohide="<?php echo $isSuccessMessage ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required placeholder="Name it.">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" required placeholder="Describe it."></textarea>
            </div>

            <div class="form-group">
                <label>Select File</label>
                <div class="drop-zone">
                    <span class="drop-zone__prompt">Drag and drop it<br>or click to browse<br><small>(JPG, JPEG, PNG)</small></span>
                    <input type="file" name="artwork" class="drop-zone__input" accept=".jpg, .jpeg, .png" required>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Upload It</button>
        </form>
    </div>

<script src="upload.js"></script>
</body>
</html>
