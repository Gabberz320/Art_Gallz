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
    <title>Doodle - Web Gallz</title>
    <link href="styles.css" rel="stylesheet">
    <link href="doodle.css" rel="stylesheet">
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
    <a href="index.php?filter=recent" class="topbar_logo">Web Gallz</a>
    <div class="topbar_actions">
        <button class="mode_toggle" id="modeToggle" title="Toggle theme">
            <span class="toggle_track">
                <span class="toggle_thumb"></span>
            </span>
        </button>

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Upload button -->
            <a href="upload.php" class="btn_upload">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg> 
                Upload 
            </a>
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
            <!-- Google Sign-In -->
            <div class="g_id_signin"></div>
        <?php endif; ?>

    </div>
</header>

<div class="layout">
    <aside class="sidebar" id="desktop-sidebar">
        <div>
            <div class="sidebar_section">Browse</div>
            <nav class="sidebar_nav">
                <a href="index.php" class="sidebar_link">Home</a>
                <a href="collage.php" class="sidebar_link">Collage</a>
                <a href="doodle.php" class="sidebar_link active">Doodle</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php?view=mine&filter=recent" class="sidebar_link">My Uploads</a>
                <?php endif; ?>
            </nav>
        </div>

        <div id="doodle-tools-container" style="margin-top: 2rem; width: 100%;">
            <div class="sidebar_section">Doodle Tools</div>
            <nav class="sidebar_nav" style="display: flex; flex-direction: row; gap: 10px; padding: 0 15px;">
                <button type="button" class="sidebar_link active doodle-btn" id="brushBtn">
                    <span class="material-icons" style="font-size: 18px; vertical-align: middle; margin-right: 5px;">edit</span> Pencil
                </button>
                <button type="button" class="sidebar_link doodle-btn" id="eraserBtn">
                    <span class="material-icons" style="font-size: 18px; vertical-align: middle; margin-right: 5px;">auto_fix_normal</span> Eraser
                </button>
            </nav>
            
            <div class="tool-settings-group" style="padding: 15px 15px;">
                <div style="flex: 1;">
                    <label class="sidebar_section" style="font-size: 11px; display: block; margin-bottom: 8px;">Size</label>
                    <input type="range" id="sizePicker" min="1" max="50" value="5" style="width: 100%; accent-color: var(--accent);">
                </div>
                <div style="flex: 1;">
                    <label class="sidebar_section margin-adjust" style="font-size: 11px; display: block; margin-top: 20px; margin-bottom: 8px;">Color</label>
                    <input type="color" id="colorPicker" value="#ff00cc" style="width: 100%; height: 35px; border: 1px solid var(--border); background: var(--surface2); border-radius: 4px; cursor: pointer;">
                </div>
            </div>
        </div>
    </aside>

    <main class="main" style="display: flex; flex-direction: column;">
        <div class="feed_header">
            <div class="feed_title">Notebook Doodle</div>
            <div class="feed_tabs">
                <button class="feed_tab" onclick="clearCanvas()">Clear Board</button>
                <button class="feed_tab active" id="saveBtn">Post Doodle</button>
            </div>
        </div>

        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="login_panel">
                <p>Please log in to save and post your doodles.</p>
            </div>
        <?php endif; ?>

        <div class="notebook_paper">
            <canvas id="doodleCanvas"></canvas>
        </div>

        <div id="mobile-tools-dest" style="padding: 20px 0;"></div>

        <form id="doodleForm" method="POST" action="save_doodle.php" style="display:none;">
            <input type="hidden" name="image" id="imageInput">
        </form>
    </main>
</div>

<script>
    function relocateDoodleTools() {
        const tools = document.getElementById('doodle-tools-container');
        const destMobile = document.getElementById('mobile-tools-dest');
        const destDesktop = document.getElementById('desktop-sidebar');

        if (!tools || !destMobile || !destDesktop) return;

        // If screen is mobile-sized, move tools under the notebook
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
    window.addEventListener('resize', relocateDoodleTools);
    document.addEventListener('DOMContentLoaded', relocateDoodleTools);
</script>

<script src="main.js"></script> 
<script src="doodle.js"></script>
</body>
</html>