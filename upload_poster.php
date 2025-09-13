<?php
// Start the session to check for authentication.
session_start();

// Set CORS headers to allow cross-origin requests.
header("Access-Control-Allow-Origin: https://jubileemulticulturalchurch.com");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

// Handle preflight requests (OPTIONS method).
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- Authentication Check ---
// All requests except GET should require authentication.
// GET requests are public for viewing events, but POST, PUT, DELETE are for admins only.
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// Set the directory where uploaded files will be stored.
// We use a relative path from the current script to the public_html directory.
// Assuming this script is in /home/jubileem/myfile/, the path to public_html is ../../public_html.
$baseDirectory = dirname(__DIR__, 2); // Go up two directories from the current script
$targetDirectory = $baseDirectory . '/public_html/file/images/';

// Create the uploads directory if it doesn't exist.
if (!is_dir($targetDirectory)) {
    mkdir($targetDirectory, 0755, true);
}

try {
    // Check if a file was uploaded.
    if (!isset($_FILES['posterFile']) || $_FILES['posterFile']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No file uploaded or an upload error occurred.");
    }

    // Get the uploaded file details.
    $file = $_FILES['posterFile'];
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Generate a unique filename to prevent overwriting.
    $uniqueFileName = uniqid('poster_', true) . '.' . $fileType;
    $targetFile = $targetDirectory . $uniqueFileName;

    // Validate the file type.
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.");
    }

    // Move the file from the temporary directory to the target directory.
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Return a JSON response with the success status and the file's new URL.
        // The URL needs to be relative to the web root.
        $posterUrl = '/file/images/' . $uniqueFileName;
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'posterUrl' => $posterUrl
        ]);
    } else {
        throw new Exception("Failed to move the uploaded file.");
    }
} catch (Exception $e) {
    // Handle any exceptions and return a JSON error response.
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
?>
