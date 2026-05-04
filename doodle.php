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
    <!-- Sidebar with Drawing Tools -->
    <aside class="sidebar">
        <div>
            <div class="sidebar_section">Browse</div>
            <nav class="sidebar_nav">
                <a href="index.php" class="sidebar_link">Home</a>
                <a href="collage.php" class="sidebar_link">Collage</a>
                <a href="doodle.php" class="sidebar_link active">Doodle</a>
            </nav>
        </div>

        <div style="margin-top: 2rem;">
            <div class="sidebar_section">Doodle Tools</div>
            <nav class="sidebar_nav">
                <button type="button" class="sidebar_link active" id="brushBtn">
                    <span class="material-icons" style="font-size: 18px; vertical-align: middle; margin-right: 8px;">edit</span> Pencil
                </button>
                <button type="button" class="sidebar_link" id="eraserBtn">
                    <span class="material-icons" style="font-size: 18px; vertical-align: middle; margin-right: 8px;">auto_fix_normal</span> Eraser
                </button>
            </nav>
            
            <!-- Tool Settings -->
            <div style="padding: 15px 20px;">
                <label class="sidebar_section" style="font-size: 11px; display: block; margin-bottom: 8px;">Size</label>
                <input type="range" id="sizePicker" min="1" max="50" value="5" style="width: 100%; accent-color: var(--accent);">
                
                <label class="sidebar_section" style="font-size: 11px; display: block; margin-top: 20px; margin-bottom: 8px;">Color</label>
                <input type="color" id="colorPicker" value="#ff00cc" style="width: 100%; height: 35px; border: 1px solid var(--border); background: var(--surface2); border-radius: 4px; cursor: pointer;">
            </div>
        </div>
    </aside>

    <!-- Main Drawing Area -->
    <main class="main">
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

        <!-- Notebook -->
        <div class="notebook_paper">
            <canvas id="doodleCanvas"></canvas>
        </div>

        <!-- Submit form -->
        <form id="doodleForm" method="POST" action="save_doodle.php" style="display:none;">
            <input type="hidden" name="image" id="imageInput">
        </form>
    </main>
</div>

<script src="main.js"></script> 
<script>
    const canvas = document.getElementById('doodleCanvas');
    const ctx = canvas.getContext('2d');
    const colorPicker = document.getElementById('colorPicker');
    const sizePicker = document.getElementById('sizePicker');
    const brushBtn = document.getElementById('brushBtn');
    const eraserBtn = document.getElementById('eraserBtn');

    // Resize canvas to fill the notebook container
    function initCanvas() {
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
    }
    window.addEventListener('load', initCanvas);
    window.addEventListener('resize', initCanvas);

    let drawing = false;
    let mode = 'brush';

    function getMousePos(e) {
        const rect = canvas.getBoundingClientRect();
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    }

    canvas.addEventListener('mousedown', (e) => {
        drawing = true;
        const pos = getMousePos(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
    });

    canvas.addEventListener('mousemove', (e) => {
        if (!drawing) return;
        const pos = getMousePos(e);
        ctx.lineWidth = sizePicker.value;
        if (mode === 'eraser') {
            ctx.globalCompositeOperation = 'destination-out';
        } else {
            ctx.globalCompositeOperation = 'source-over';
            ctx.strokeStyle = colorPicker.value;
        }
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
    });

    window.addEventListener('mouseup', () => drawing = false);

    // Switch Tools
    brushBtn.onclick = () => {
        mode = 'brush';
        brushBtn.classList.add('active');
        eraserBtn.classList.remove('active');
    };
    eraserBtn.onclick = () => {
        mode = 'eraser';
        eraserBtn.classList.add('active');
        brushBtn.classList.remove('active');
    };

    function clearCanvas() { ctx.clearRect(0, 0, canvas.width, canvas.height); }

    // Prepare image for upload
    document.getElementById('saveBtn').onclick = function() {
        if (!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
            alert('Please login to post!');
            return;
        }

        const tempCanvas = document.createElement('canvas');
        const tCtx = tempCanvas.getContext('2d');
        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;

        // Draw notebook background for the final file
        tCtx.fillStyle = "#f1f1f1";
        tCtx.fillRect(0,0, tempCanvas.width, tempCanvas.height);
        tCtx.strokeStyle = "#ffb4b8";
        tCtx.lineWidth = 2;
        tCtx.beginPath();
        tCtx.moveTo(51, 0); tCtx.lineTo(51, tempCanvas.height);
        tCtx.stroke();

        tCtx.drawImage(canvas, 0, 0);
        document.getElementById('imageInput').value = tempCanvas.toDataURL('image/png');
        document.getElementById('doodleForm').submit();
    };
</script>
</body>
</html>