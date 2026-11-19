<?php

require_once __DIR__ . '/app.php';

$host = 'localhost';
$dbname = 'bicycle_budgeting';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $error) {
    error_log('Database connection error: ' . $error->getMessage());
    http_response_code(500);
    exit('Database connection failed. Start MySQL in XAMPP and check config/database.php.');
}

return $pdo;
