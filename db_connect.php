<?php

$configPath = '/home/jubileem/myfile/config.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    error_log("DB CONNECT ERROR: Config file not found at $configPath");
    echo json_encode(['success' => false, 'message' => 'Internal server error: Config file not found.']);
    exit;
}

$config = require $configPath;

define('DB_HOST', 'localhost');
define('DB_USER', $config['db_user']);
define('DB_PASS', $config['db_password']);
define('DB_NAME', 'jubileem_jmc'); 

try {
    error_log("Attempting to connect to database: " . DB_NAME . " with user: " . DB_USER);
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connection successful.");
} catch (PDOException $e) {
    http_response_code(500);
    $errorMessage = 'connect db fail: ' . $e->getMessage();
    error_log("Database connection failed: " . $errorMessage);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
    exit;
}
