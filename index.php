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

//filter for Browse
$filter = $_GET['filter'] ?? 'recent';

try {
    $order = match($filter) {
        'recent' => 'a.art_ID DESC',
        'liked'  => 'a.LikesCounter DESC',
        default  => 'a.LikesCounter DESC, a.art_ID DESC',
    };
    $stmt = $pdo->query(
        "SELECT a.art_ID, a.user_id, a.Title, a.Description, 
                a.CreationDate, a.ImageURL, a.LikesCounter, u.Name AS UserName
        FROM Artworks a
        INNER JOIN Users u ON a.user_id = u.user_id
        ORDER BY $order"
    );
    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $artworks = [];
}

//Get liked art IDs for current user
$likedIds = [];
if (isset($_SESSION['user_id'])) {
    try {
        $ls = $pdo->prepare("SELECT art_ID 
                            FROM Likes
                            WHERE user_id = ?");
        $ls->execute([$_SESSION['user_id']]);
        $likedIds = array_column($ls->fetchAll(PDO::FETCH_ASSOC), 'art_ID');
    } catch (PDOException $e){}
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
    <title>Web Gallz</title>
    <link href="styles.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/jwt-decode/build/jwt-decode.min.js"></script>
    <script>
        (function () {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.classList.add(savedTheme);
            document.documentElement.classList.add('no-transition');
        })();
    </script>
</head>
<body data-google-client-id="<?php echo htmlspecialchars($env['GOOGLE_CLIENT_ID']); ?>">

<!-- Navbar -->
<header class="topbar">
    <a href="index.php?filter=recent" class="topbar_logo">Web Gallz</a>
    <div class="topbar_actions">

        <!-- dark/Light toggle -->
        <button class="mode_toggle" id="modeToggle" title="Toggle theme">
            <span class="toggle_track">
                <span class="toggle_thumb"></span>
            </span>
        </button>

        <?php if (isset($_SESSION['user_id'])): ?>

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

<!-- Google Sign-In (programmatic initialization in main.js) -->
<div class="g_id_signin"></div>
<?php endif; ?>

</div>
</header>

<div class="layout">
    <!-- for the sidebar ( i don't think this's neccesary)-->
    <aside class="sidebar">
    <div>
        <div class="sidebar_section">Browse</div>
        <nav class="sidebar_nav">
            <a href="index.php" class="sidebar_link">Home</a>
            <a href="collage.php" class="sidebar_link">Collage</a>
            <a href="doodle.php" class="sidebar_link">Doodle</a>
            <a href="#" class="sidebar_link">My Uploads</a>
        </nav>
    </div>
    </aside>

<!-- main feed -->
<main class="main">

    <?php if($message): ?>
        <div id="upload_message" class="message<?php echo $isSuccessMessage ? ' success_popup' : ''; ?>" data-autohide="<?php echo $isSuccessMessage ? 'true' : 'false'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if(!isset($_SESSION['user_id'])): ?>
    <div class="login_panel">
    <p>Please log in to start sharing art.</p>
    </div>
    <?php endif; ?>

    <div class="feed_header">
        <div class="feed_title">What's poppin'</div>
        <div class="feed_tabs">
            <a href="?filter=recent" class="feed_tab <?php echo $filter === 'recent' ? 'active' : ''; ?>">Recent</a>
            <a href="?filter=liked"  class="feed_tab <?php echo $filter === 'liked'  ? 'active' : ''; ?>">Top Liked</a>
        </div>
    </div>

    <!--ask the user to upload post-->
    <?php if (empty($artworks)): ?>
        <div class="empty">
            <h3>No artworks yet</h3>
            <p>Be the first to upload something.</p>
        </div>
    <?php else: ?>
        <div class="gallery_grid">
            <?php foreach ($artworks as $art): ?>
            <?php $liked = in_array($art['art_ID'], $likedIds); ?>
            <article class="gallery_card">
        <div class="card_image_wrap">
            <img src="<?php echo htmlspecialchars($art['ImageURL']); ?>"
                 alt="<?php echo htmlspecialchars($art['Title']); ?>" >
        </div>

        <div class="card_body">
            <div class="card_user">
                <div class="card_avatar"><?php echo initials($art['UserName']); ?> </div>
                <span class="card_username"><?php echo htmlspecialchars($art['UserName']); ?></span>
            </div>

            <div class="card_title"><?php echo htmlspecialchars($art['Title']); ?></div>
            <?php if ($art['Description']): ?>
                <div class="card_desc"><?php echo htmlspecialchars($art['Description']); ?></div>
            <?php endif; ?>
        
            <div class="card_footer">
            <?php if (isset($_SESSION['user_id'])): ?>
                <button 
                    class="like_btn <?php echo $liked ? 'liked' : ''; ?>" 
                    data-id="<?php echo $art['art_ID']; ?>">
                    <svg class="heart_icon" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span class="like_count"><?php echo $art['LikesCounter']; ?></span>
                </button>
            <?php else: ?>
                <span class="like_btn">
                    <svg class="heart_icon" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <?php echo $art['LikesCounter']; ?>
                </span>
            <?php endif; ?>
                 <span class="card_date"><?php echo htmlspecialchars($art['CreationDate']); ?></span>
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === $art['user_id']): ?>
                <form method="POST" action="delete.php" onsubmit="return confirm('Delete this artwork?')">
                    <input type="hidden" name="art_id" value="<?php echo $art['art_ID']; ?>">
                    <button type="submit" class="delete_btn" title="Delete artwork" aria-label="Delete artwork">
                        <span class="material-icons">delete</span>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>
</div>

<script src="main.js"></script>
</body>
</html>


