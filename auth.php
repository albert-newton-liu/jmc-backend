<?php
session_start();
header("Content-Type: application/json");

$configPath = '/home/jubileem/myfile/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Config file not found.']);
    exit;
}
$config = require $configPath;

$validUsername = $config['mng_user'] ?? '';
$validPassword = $config['mng_password'] ?? '';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!empty($_SESSION['authenticated'])) {
        echo json_encode(['success' => true, 'message' => 'Authenticated.']);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    }
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request body.']);
        exit;
    }

    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    if ($username === $validUsername && $password === $validPassword) {
        $_SESSION['authenticated'] = true;
        echo json_encode(['success' => true, 'message' => 'Login successful.']);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
