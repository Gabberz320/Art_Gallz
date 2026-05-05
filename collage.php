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
            <a href="upload.php" class="btn_upload">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Upload </a>
            <div class="avatar_wrap">
                <div class="avatar" id="avatarBtn"><?php echo initials($_SESSION['user_name']); ?></div>
                <div class="avatar_dropdown" id="avatarDropdown">
                    <div class="dropdown_name"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                    <hr class="dropdown_divider">
                    <a href="logout.php" class="dropdown_item">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="login_form.php" class="btn_upload topbar_auth_btn">Log in</a>
            <a href="register.php" class="btn_upload topbar_auth_btn">Register</a>
        <?php endif; ?>
    </div>
</header>

<div class="layout">
    <aside class="sidebar" id="desktop-sidebar" style="overflow-x: hidden;">
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

        <div id="tools-container" style="margin-top: 2rem; width: 100%;">
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

            <div class="sidebar_section" style="margin-top: 20px;">APPEARANCE</div>
            <div class="appearance-group" style="padding: 0 15px;">
                <div>
                    <label style="font-size: 10px; color: var(--accent); font-weight: bold; display: block; margin-bottom: 5px;">COLOR</label>
                    <input type="color" id="colorPicker" value="#ffffff" style="width: 100%; height: 40px; border: none; background: none; cursor: pointer;">
                </div>
                <div>
                    <label class="transparency-label" style="font-size: 10px; color: var(--accent); font-weight: bold; display: block; margin-top: 20px; margin-bottom: 5px;">TRANSPARENCY</label>
                    <input type="range" min="0" max="100" value="100" id="opacityPicker" style="width: 100%; accent-color: var(--accent);">
                </div>
            </div>
        </div>
    </aside>

    <main class="main" style="padding: 0; background: var(--bg); display: flex; flex-direction: column;">
        <div class="feed_header" style="padding: 20px 40px;">
            <div class="feed_title">Collage Studio</div>
            <div class="feed_tabs">
                <button class="feed_tab" onclick="clearCanvas()">Clear Board</button>
                <button class="feed_tab active" id="postCollageBtn" onclick="postCollageToGallery()">Post Collage</button>
            </div>
        </div>

        <div id="canvas-area" class="canvas-container">
            <canvas id="mainCanvas" style="background: white; border-radius: 4px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);"></canvas>
        </div>

        <div id="mobile-tools-dest" style="padding: 0 20px 20px 20px;"></div>

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

<script>
    function relocateTools() {
        const tools = document.getElementById('tools-container');
        const destMobile = document.getElementById('mobile-tools-dest');
        const destDesktop = document.getElementById('desktop-sidebar');

        if (!tools || !destMobile || !destDesktop) return;

        // If screen is mobile-sized, move tools under the canvas
        if (window.innerWidth <= 900) {
            if (tools.parentElement !== destMobile) {
                tools.style.marginTop = '0';
                destMobile.appendChild(tools);
            }
        } else {
            // If screen is desktop-sized, put tools back in the sidebar
            if (tools.parentElement !== destDesktop) {
                tools.style.marginTop = '2rem';
                destDesktop.appendChild(tools);
            }
        }
    }
    window.addEventListener('resize', relocateTools);
    document.addEventListener('DOMContentLoaded', relocateTools);
</script>

<script src="main.js"></script>
<script src="collage.js"></script>
</body>
</html>