<?php

header("Access-Control-Allow-Origin: https://jubileemulticulturalchurch.com");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function logMessage($message) {
    // Log file is now named with the current date, e.g., 'mailer_log-2025-08-04.txt'
    $logFile = '/home/jubileem/logs/mailer_log-' . date('Y-m-d') . '.txt';
    $timestamp = date('[Y-m-d H:i:s]');
    $logEntry = $timestamp . ' ' . $message . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Only POST allowed']);
    exit;
}

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strpos($contentType, 'application/json') !== false) {
    $rawPostData = file_get_contents("php://input");
    $jsonData = json_decode($rawPostData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON payload.']);
        exit;
    }
    
    if (is_array($jsonData)) {
        $_POST = array_merge($_POST, $jsonData);
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$configPath = '/home/jubileem/myfile/config.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: Config file not found.']);
    exit;
}
$config = require $configPath;

$name    = htmlspecialchars($_POST['name'] ?? '');
$email   = htmlspecialchars($_POST['email'] ?? '');
$message = htmlspecialchars($_POST['message'] ?? '');

logMessage("Received data - Name: {$name}, Email: {$email}, Message: {$message}");

if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All form fields are required.']);
    exit;
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = 'tls';
    $mail->Port       = $config['smtp_port'];

    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['from_email']);
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'New message from contact form';
    $mail->Body    = "
        <h3>New Contact Message</h3>
        <p><strong>Name:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Message:</strong><br>{$message}</p>
    ";

    $mail->send();

    echo json_encode(['success' => true, 'message' => 'Email sent successfully!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.', 'error_details' => $mail->ErrorInfo]);
}

