<?php
session_start();

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    die("Setup Error: The .env file is missing from " . __DIR__);
}

$env = parse_ini_file($envPath);

if ($env === false || empty($env)) {
    die("Setup Error: The .env file is empty or cannot be parsed. Please add your database variables.");
}

try {
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4", 
        $env['DB_USER'], 
        $env['DB_PASS']
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>