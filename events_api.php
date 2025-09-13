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
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true)) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }
    // --- End Authentication Check ---

    // Include the database connection file.
    require '/home/jubileem/myfile/db_connect.php';

    // Include the class that handles all CRUD operations.
    require '/home/jubileem/myfile/events.php';

    // Instantiate the Events class, passing the PDO object to it.
    $eventsManager = new Events($pdo);

    // Get the HTTP request method to determine the action.
    $method = $_SERVER['REQUEST_METHOD'];

    // Define the cache file path and cache duration.
    $cacheDir = __DIR__ . '/tmp/';
    $cacheFile = $cacheDir . 'events_cache.json';
    $cacheTime = 3600; // Cache duration in seconds (1 hour).

    try {
        switch ($method) {
            case 'GET':
                // Check if a valid cache file exists.
                if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
                    // If a valid cache exists, read from the cache file.
                    $allEvents = json_decode(file_get_contents($cacheFile), true);
                
                } else {
                    // If cache is expired or does not exist, query the database.
                    $allEvents = $eventsManager->getEvents();
                    if (!is_dir($cacheDir)) {
                        mkdir($cacheDir, 0755, true);
                    }
                    file_put_contents($cacheFile, json_encode($allEvents));
                }

                if (!empty($allEvents)) {
                    $lastEvent = array_pop($allEvents);
                    array_unshift($allEvents, $lastEvent);
                }

                echo json_encode($allEvents);
                break;

            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);

                if ($eventsManager->createEvent($data)) {
                    // After a successful write operation, invalidate the cache.
                    @unlink($cacheFile);
                    echo json_encode(['success' => true, 'message' => 'Event created successfully.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to create event.']);
                }
                break;

            case 'PUT':
                $id = $_GET['id'] ?? null;
                $data = json_decode(file_get_contents('php://input'), true);

                if (!$data) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON data.']);
                    exit;
                }
                if ($id && $eventsManager->updateEvent($id, $data)) {
                    // After a successful write operation, invalidate the cache.
                    @unlink($cacheFile);
                    echo json_encode(['success' => true, 'message' => 'Event updated successfully.']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Failed to update event. Missing ID or data.']);
                }
                break;

            case 'DELETE':
                $id = $_GET['id'] ?? null;
                if ($id && $eventsManager->deleteEvent($id)) {
                    // After a successful write operation, invalidate the cache.
                    @unlink($cacheFile);
                    echo json_encode(['success' => true, 'message' => 'Event deleted successfully.']);
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Failed to delete event. Missing ID.']);
                }
                break;

            default:
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
                break;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
?>