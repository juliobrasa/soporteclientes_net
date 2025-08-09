<?php
/**
 * Minimal API test to isolate the 500 error
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://soporteclientes.net');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Admin-Session');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Test 1: Basic response
error_log("API-MINIMAL: Test 1 - Basic response");

// Test 2: Load env-loader
error_log("API-MINIMAL: Test 2 - Loading env-loader");
try {
    require_once 'env-loader.php';
    error_log("API-MINIMAL: env-loader loaded successfully");
} catch (Exception $e) {
    error_log("API-MINIMAL: Error loading env-loader: " . $e->getMessage());
    response(['error' => 'Error loading env-loader: ' . $e->getMessage()], 500);
}

// Test 3: Database connection
error_log("API-MINIMAL: Test 3 - Database connection");
try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    if ($pdo) {
        error_log("API-MINIMAL: Database connection successful");
    } else {
        error_log("API-MINIMAL: Database connection returned null");
        response(['error' => 'Database connection failed'], 500);
    }
} catch (Exception $e) {
    error_log("API-MINIMAL: Database error: " . $e->getMessage());
    response(['error' => 'Database error: ' . $e->getMessage()], 500);
}

// Test 4: Session check
error_log("API-MINIMAL: Test 4 - Session check");
session_start();

$isAuthenticated = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
error_log("API-MINIMAL: Authenticated: " . ($isAuthenticated ? 'true' : 'false'));

if (!$isAuthenticated) {
    response(['error' => 'Not authenticated', 'debug' => 'Session check failed'], 401);
}

// Test 5: Input parsing
error_log("API-MINIMAL: Test 5 - Input parsing");
$input = json_decode(file_get_contents('php://input'), true);
error_log("API-MINIMAL: Input received: " . json_encode($input));

// Test 6: Basic validation
if (!isset($input['hotel_id'])) {
    response(['error' => 'hotel_id required', 'debug' => 'Input validation failed'], 400);
}

// Success response
error_log("API-MINIMAL: All tests passed");
response([
    'success' => true,
    'message' => 'Minimal API test successful',
    'hotel_id' => $input['hotel_id'],
    'timestamp' => date('Y-m-d H:i:s'),
    'debug' => 'All validation checks passed'
]);
?>