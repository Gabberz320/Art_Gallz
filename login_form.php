<?php
require 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/jwt-decode/build/jwt-decode.min.js"></script>
</head>
<body data-google-client-id="<?php echo htmlspecialchars($env['GOOGLE_CLIENT_ID']); ?>">

<!-- Topbar (same as index) -->
<header class="topbar">
    <a href="index.php?filter=recent" class="topbar_logo">Web Gallz</a>
    <div class="topbar_actions">
        <a href="login_form.php" class="btn_upload topbar_auth_btn">Log in</a>
        <a href="register.php" class="btn_upload topbar_auth_btn">Register</a>
        <div class="g_id_signin"></div>
    </div>
</header>

<div class="layout">
    <main class="main">
        <div class="login_panel" style="max-width:520px;margin:40px auto;padding:24px;">
            <h2 style="font-family: 'Syne', sans-serif;">Log in</h2>
            <form method="POST" action="login.php" style="margin-top:12px;display:flex;flex-direction:column;gap:10px;">
                <input type="email" name="email" placeholder="Email" required style="padding:10px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);">
                <input type="password" name="password" placeholder="Password" required style="padding:10px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);">
                <div style="display:flex;gap:12px;align-items:center;">
                    <button type="submit" class="btn_upload" style="padding:10px 16px;">Log in</button>
                    <a href="register.php" style="color:var(--text-dim);">Create account</a>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="main.js"></script>
</body>
</html>
