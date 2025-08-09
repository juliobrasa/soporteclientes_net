<?php
/**
 * Versión mínima para testear exactamente donde falla api-extraction.php
 */

header('Content-Type: application/json');
echo json_encode(['debug' => 'started', 'timestamp' => date('Y-m-d H:i:s')]) . "\n";

echo json_encode(['debug' => 'loading admin-config']) . "\n";
try {
    require_once 'admin-config.php';
    echo json_encode(['debug' => 'admin-config loaded successfully']) . "\n";
} catch (Exception $e) {
    echo json_encode(['debug' => 'error loading admin-config', 'error' => $e->getMessage()]) . "\n";
    exit(1);
}

echo json_encode(['debug' => 'checking if getDBConnection function exists']) . "\n";
if (function_exists('getDBConnection')) {
    echo json_encode(['debug' => 'getDBConnection function exists']) . "\n";
} else {
    echo json_encode(['debug' => 'ERROR: getDBConnection function NOT found']) . "\n";
    exit(1);
}

echo json_encode(['debug' => 'testing database connection']) . "\n";
try {
    $pdo = getDBConnection();
    if ($pdo) {
        echo json_encode(['debug' => 'database connection successful']) . "\n";
    } else {
        echo json_encode(['debug' => 'database connection returned null']) . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo json_encode(['debug' => 'database connection error', 'error' => $e->getMessage()]) . "\n";
    exit(1);
}

echo json_encode(['debug' => 'session start']) . "\n";
session_start();

echo json_encode(['debug' => 'checking authentication']) . "\n";
$isAuthenticated = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
echo json_encode(['debug' => 'authentication check', 'is_authenticated' => $isAuthenticated]) . "\n";

if (!$isAuthenticated) {
    echo json_encode(['debug' => 'not authenticated', 'session' => $_SESSION ?? []]) . "\n";
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']) . "\n";
    exit(1);
}

echo json_encode(['debug' => 'all checks passed successfully']) . "\n";
?>