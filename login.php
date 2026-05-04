<?php
require 'db.php';

function normalizeLocalRedirect(string $candidate, string $fallback): string {
    $candidate = trim($candidate);
    if ($candidate === '') {
        return $fallback;
    }

    $parts = parse_url($candidate);
    if ($parts === false) {
        return $fallback;
    }

    // Allow only local redirects (no scheme/host).
    if (isset($parts['scheme']) || isset($parts['host']) || isset($parts['port']) || isset($parts['user']) || isset($parts['pass'])) {
        return $fallback;
    }

    $path = $parts['path'] ?? '';
    if ($path === '' || str_starts_with($path, '//') || str_contains($path, '..') || str_contains($path, "\0")) {
        return $fallback;
    }

    $target = $path;
    if (isset($parts['query']) && $parts['query'] !== '') {
        $target .= '?' . $parts['query'];
    }

    return $target;
}

function resolveRedirectTarget(string $fallback = 'index.php'): string {
    if (isset($_POST['redirect_to'])) {
        $fromPost = normalizeLocalRedirect((string)$_POST['redirect_to'], $fallback);
        if ($fromPost !== $fallback) {
            return $fromPost;
        }
    }

    if (isset($_SERVER['HTTP_REFERER'])) {
        $fromReferrer = normalizeLocalRedirect((string)$_SERVER['HTTP_REFERER'], $fallback);
        if ($fromReferrer !== $fallback) {
            return $fromReferrer;
        }
    }

    return $fallback;
}

$redirectTarget = resolveRedirectTarget('index.php');

// sent by our js function in index.php
if (isset($_POST['google_id'])) {
    $oauth_id = $_POST['google_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    try {
        // save or update the user using your 'oauthID' column
        $stmt = $pdo->prepare("INSERT INTO Users (oauthID, Email, Name) 
                               VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE Email = ?, Name = ?");
        $stmt->execute([$oauth_id, $email, $name, $email, $name]);

        // fetch user using your 'user_id' and 'oauthID' columns
        $stmt = $pdo->prepare("SELECT user_id, Name FROM Users WHERE oauthID = ?");
        $stmt->execute([$oauth_id]);
        $user = $stmt->fetch();

        // set session using column names
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['Name'];
        $_SESSION['google_id'] = $oauth_id;
        $_SESSION['user_email'] = $email;

        // send them back to the page where login started
        header('Location: ' . $redirectTarget);
        exit;
    } catch (Exception $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    header('Location: ' . $redirectTarget);
    exit;
}