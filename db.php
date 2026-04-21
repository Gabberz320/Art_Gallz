<?php
session_start();

// read the .env file into an array
$env = parse_ini_file('.env');

// connect using the array values
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4", 
    $env['DB_USER'], 
    $env['DB_PASS']
);
?>