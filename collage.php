<?php
require 'db.php'; 

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
    <title>Collage Studio | Web Gallz</title>
    <link href="styles.css" rel="stylesheet">
    <link rel="stylesheet" href="collage.css">
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
<body data-google-client-id="<?php echo htmlspecialchars($env['GOOGLE_CLIENT_ID'] ?? ''); ?>">

<header class="topbar">
    <a href="index.php" class="topbar_logo">Web Gallz <small style="font-size: 12px; opacity: 0.5;">Studio</small></a>
    
    <div class="topbar_actions" style="margin-left: auto; display: flex; align-items: center; gap: 15px;">
        <button class="mode_toggle" id="modeToggle" title="Toggle theme">
            <span class="toggle_track"><span class="toggle_thumb"></span></span>
        </button>

        <?php if (isset($_SESSION['user_id'])): ?>
            <button class="btn_upload" onclick="postCollageToGallery()">Post to Gallery</button>
            <div class="avatar_wrap">
                <div class="avatar" id="avatarBtn"><?php echo initials($_SESSION['user_name']); ?></div>
                <div class="avatar_dropdown" id="avatarDropdown">
                    <div class="dropdown_name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                    <hr class="dropdown_divider">
                    <a href="logout.php" class="dropdown_item">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <div class="g_id_signin"></div>
        <?php endif; ?>
    </div>
</header>

<div class="layout">
    <aside class="sidebar" style="overflow-x: hidden;">
        <div>
            <div class="sidebar_section">Browse</div>
            <nav class="sidebar_nav">
                <a href="index.php" class="sidebar_link">Home</a>
                <a href="collage.php" class="sidebar_link active">Collage</a>
                <a href="doodle.php" class="sidebar_link">Doodle</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php?view=mine&filter=recent" class="sidebar_link">My Uploads</a>
                <?php endif; ?>
            </nav>
        </div>

        <div style="margin-top: 2rem;">
            <div class="sidebar_section">ELEMENTS</div>
            <nav class="sidebar_nav" style="display: flex; flex-direction: column; gap: 8px; padding: 0 15px;">
                <button class="collage-btn" onclick="addShape('rect')">Color Block</button>
                <button class="collage-btn" onclick="addShape('text')">Text</button>
                <button class="collage-btn" onclick="triggerImageUpload()">Upload Image</button>
            </nav>

            <div id="selection-panel" style="display:none; margin-top: 20px;">
                <div class="sidebar_section">EDIT SELECTION</div>
                <div id="text-group" style="padding: 0 15px;">
                    <label style="font-size: 10px; color: var(--accent); font-weight: bold; display: block; margin-bottom: 5px;">CONTENT</label>
                    <input type="text" id="text-edit" oninput="updateSelectedText()" style="width:100%; padding:10px; border-radius:4px; border:1px solid var(--border); background:var(--surface2); color:white;" placeholder="Edit text...">
                    
                    <label style="font-size: 10px; color: var(--accent); font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px;">FONT FAMILY</label>
                    <select id="fontFamily" onchange="updateFontFace(this.value)" style="width:100%; padding:8px; background:var(--surface2); color:white; border:1px solid var(--border); border-radius:4px;">
                        <option value="Arial">Arial</option>
                        <option value="Verdana">Verdana</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Courier New">Courier New</option>
                        <option value="Impact">Impact</option>
                    </select>

                    <label style="font-size: 10px; color: var(--accent); font-weight: bold; display: block; margin-top: 15px; margin-bottom: 5px;">FONT SIZE</label>
                    <input type="range" id="fontSizePicker" min="10" max="150" value="30" oninput="updateFontSize(this.value)" style="width:100%; accent-color: var(--accent);">
                </div>
                
                <div style="display:flex; gap:5px; padding: 10px 15px;">
                    <button class="collage-btn" style="flex:1; font-size:11px;" onclick="moveLayer('up')">Forward</button>
                    <button class="collage-btn" style="flex:1; font-size:11px;" onclick="moveLayer('down')">Backward</button>
                </div>
            </div>

            <div class="sidebar_section">APPEARANCE</div>
            <div style="padding: 0 15px;">
                <label style="font-size: 10px; color: var(--accent); font-weight: bold; display: block; margin-bottom: 5px;">COLOR</label>
                <input type="color" id="colorPicker" value="#ffffff" style="width: 100%; height: 40px; border: none; background: none; cursor: pointer;">
                
                <label style="font-size: 10px; color: var(--accent); font-weight: bold; display: block; margin-top: 20px; margin-bottom: 5px;">TRANSPARENCY</label>
                <input type="range" min="0" max="100" value="100" id="opacityPicker" style="width: 100%; accent-color: var(--accent);">
            </div>

            <div style="padding: 20px 15px;">
                <button class="collage-btn" style="background: rgba(255, 0, 0, 0.2); color: #ff4d4d; border: 1px solid #ff4d4d;" onclick="clearCanvas()">
                    <span class="material-icons" style="font-size: 16px; vertical-align: middle;">delete</span> Clear All
                </button>
            </div>
        </div>
    </aside>

    <main class="main" style="padding: 0; background: var(--bg);">
        <div class="feed_header" style="padding: 20px 40px;">
            <div class="feed_title">Collage Studio</div>
        </div>

        <div id="canvas-area" style="width: 100%; height: calc(100vh - 160px); display: flex; justify-content: center; align-items: center;">
            <canvas id="mainCanvas" style="background: white; border-radius: 4px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);"></canvas>
        </div>

        <form id="collageForm" method="POST" action="save_collage.php" style="display:none;">
            <input type="hidden" name="image" id="collageImageInput">
        </form>
    </main>
</div>

<style>
.collage-btn {
    background: var(--surface2);
    color: var(--text);
    border: 1px solid var(--border);
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
    text-align: left;
    width: 100%;
    font-weight: 500;
    transition: all 0.2s;
}
.collage-btn:hover {
    background: var(--accent);
    color: white;
}
</style>

<script src="main.js"></script>
<script src="collage.js"></script>
</body>
</html>