<?php require 'db.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Art Gallz</title>
    <link href="styles.css" type="text/css" rel="stylesheet">
    
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    
    <script src="https://cdn.jsdelivr.net/npm/jwt-decode/build/jwt-decode.min.js"></script>
</head>
<body>
    <h1>Welcome to Art Gallz</h1>

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
        <a href="logout.php">Logout</a>
    <?php endif; ?>

    <script>
    function handleCredentialResponse(response) {
        const responsePayload = jwt_decode(response.credential);

        if (!responsePayload) {
            console.error("Payload was empty. Aborting login.");
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