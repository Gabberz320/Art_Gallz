<?php
require 'db.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if ($name === '') $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $password2) $errors[] = 'Password confirmation does not match.';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT user_id FROM Users WHERE Email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO Users (Name, Email, PasswordHash) VALUES (?, ?, ?)');
                $ins->execute([$name, $email, $hash]);

                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;

                $success = true;
                header('Location: index.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body data-google-client-id="<?php echo htmlspecialchars($env['GOOGLE_CLIENT_ID']); ?>">

<header class="topbar">
    <a href="index.php?filter=recent" class="topbar_logo">Web Gallz</a>
    <div class="topbar_actions">
        <a href="index.php" class="btn_upload">Home</a>
        <a href="register.php" class="btn_upload" style="padding:6px 12px; font-size:14px;">Register</a>
        <a href="login_form.php" class="btn_upload" style="padding:6px 12px; font-size:14px;">Log in</a>
        <div class="g_id_signin"></div>
    </div>
</header>

<div class="layout">
    <main class="main">
        <div class="login_panel" style="max-width:520px;margin:40px auto;padding:24px;">
            <h2 style="font-family: 'Syne', sans-serif;">Create account</h2>

            <?php if (!empty($errors)): ?>
                <div class="message" style="margin-top:12px;color:var(--danger);">
                    <ul style="margin:0;padding-left:18px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" style="margin-top:12px;display:flex;flex-direction:column;gap:10px;">
                <input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required style="padding:10px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);">
                <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required style="padding:10px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);">
                <input type="password" name="password" placeholder="Password" required style="padding:10px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);">
                <input type="password" name="password2" placeholder="Confirm password" required style="padding:10px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text);">

                <div style="display:flex;gap:12px;align-items:center;">
                    <button type="submit" class="btn_upload" style="padding:10px 16px;">Register</button>
                    <a href="login_form.php" style="color:var(--text-dim);">Already have an account?</a>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="main.js"></script>
</body>
</html>
