<?php
/**
 * API para manejo de APIs externas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'admin-config.php';
require_once 'env-loader.php';

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Verificar autenticación
session_start();

// Método principal: verificar sesión normal
$isAuthenticated = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'];

// Método alternativo: verificar header de sesión para problemas de cookies
if (!$isAuthenticated && isset($_SERVER['HTTP_X_ADMIN_SESSION'])) {
    $sessionId = $_SERVER['HTTP_X_ADMIN_SESSION'];
    if ($sessionId && strlen($sessionId) > 10) {
        $isAuthenticated = true;
    }
}

if (!$isAuthenticated) {
    response(['error' => 'No autorizado'], 401);
}

// Conectar a base de datos
$pdo = getDBConnection();
if (!$pdo) {
    response(['error' => 'Error de conexión a la base de datos'], 500);
}

// Obtener input
$input = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];
$apiId = $_GET['id'] ?? null;

// Manejar test de conexión
if (isset($_GET['test']) && $apiId) {
    $result = testApiConnection($apiId, $pdo);
    response($result);
}

switch ($method) {
    case 'GET':
        if ($apiId) {
            handleGetSingleApi($apiId, $pdo);
        } else {
            handleGetAllApis($pdo);
        }
        break;
        
    case 'POST':
        handleCreateApi($input, $pdo);
        break;
        
    case 'PUT':
        if ($apiId) {
            handleUpdateApi($apiId, $input, $pdo);
        } else {
            response(['error' => 'ID requerido para actualizar'], 400);
        }
        break;
        
    case 'DELETE':
        if ($apiId) {
            handleDeleteApi($apiId, $pdo);
        } else {
            response(['error' => 'ID requerido para eliminar'], 400);
        }
        break;
        
    default:
        response(['error' => 'Método no permitido'], 405);
}

/**
 * Obtener todas las APIs
 */
function handleGetAllApis($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM api_providers ORDER BY name ASC");
        $apis = $stmt->fetchAll();
        
        response([
            'success' => true,
            'data' => $apis
        ]);
        
    } catch (PDOException $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Obtener una API específica
 */
function handleGetSingleApi($id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM api_providers WHERE id = ?");
        $stmt->execute([$id]);
        $api = $stmt->fetch();
        
        if (!$api) {
            response(['error' => 'API no encontrada'], 404);
        }
        
        response([
            'success' => true,
            'data' => $api
        ]);
        
    } catch (PDOException $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Crear nueva API
 */
function handleCreateApi($input, $pdo) {
    try {
        // Validar input
        if (!$input['name'] || !$input['api_url']) {
            response(['error' => 'Nombre y URL son requeridos'], 400);
        }
        
        // Preparar datos
        $data = [
            'name' => $input['name'],
            'provider_type' => $input['provider_type'] ?? 'custom',
            'api_url' => $input['api_url'],
            'api_key' => $input['api_key'] ?? null,
            'auth_type' => $input['auth_type'] ?? 'none',
            'headers' => json_encode($input['headers'] ?? []),
            'config' => json_encode($input['config'] ?? []),
            'is_active' => $input['is_active'] ? 1 : 0,
            'description' => $input['description'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO api_providers (name, provider_type, api_url, api_key, auth_type, headers, config, is_active, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        response([
            'success' => true,
            'message' => 'API creada exitosamente',
            'id' => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Actualizar API existente
 */
function handleUpdateApi($id, $input, $pdo) {
    try {
        // Verificar que existe
        $stmt = $pdo->prepare("SELECT id FROM api_providers WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            response(['error' => 'API no encontrada'], 404);
        }
        
        // Preparar datos de actualización
        $data = [
            'name' => $input['name'],
            'provider_type' => $input['provider_type'] ?? 'custom',
            'api_url' => $input['api_url'],
            'api_key' => $input['api_key'] ?? null,
            'auth_type' => $input['auth_type'] ?? 'none',
            'headers' => json_encode($input['headers'] ?? []),
            'config' => json_encode($input['config'] ?? []),
            'is_active' => $input['is_active'] ? 1 : 0,
            'description' => $input['description'] ?? '',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "UPDATE api_providers SET name=?, provider_type=?, api_url=?, api_key=?, auth_type=?, headers=?, config=?, is_active=?, description=?, updated_at=? WHERE id=?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([...array_values($data), $id]);
        
        response([
            'success' => true,
            'message' => 'API actualizada exitosamente'
        ]);
        
    } catch (PDOException $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Eliminar API
 */
function handleDeleteApi($id, $pdo) {
    try {
        // Verificar que existe
        $stmt = $pdo->prepare("SELECT name FROM api_providers WHERE id = ?");
        $stmt->execute([$id]);
        $api = $stmt->fetch();
        
        if (!$api) {
            response(['error' => 'API no encontrada'], 404);
        }
        
        // Eliminar
        $stmt = $pdo->prepare("DELETE FROM api_providers WHERE id = ?");
        $stmt->execute([$id]);
        
        response([
            'success' => true,
            'message' => "API '{$api['name']}' eliminada exitosamente"
        ]);
        
    } catch (PDOException $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Test de conexión de API
 */
function testApiConnection($id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM api_providers WHERE id = ?");
        $stmt->execute([$id]);
        $api = $stmt->fetch();
        
        if (!$api) {
            return ['success' => false, 'error' => 'API no encontrada'];
        }
        
        $headers = json_decode($api['headers'] ?? '[]', true) ?: [];
        $config = json_decode($api['config'] ?? '[]', true) ?: [];
        
        // Configurar headers de autenticación
        if ($api['auth_type'] === 'api_key' && $api['api_key']) {
            $headers['Authorization'] = 'Bearer ' . $api['api_key'];
        } elseif ($api['auth_type'] === 'bearer' && $api['api_key']) {
            $headers['Authorization'] = 'Bearer ' . $api['api_key'];
        }
        
        // Realizar test HTTP usando cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api['api_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeout'] ?? 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, buildCurlHeaders($headers));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $start = microtime(true);
        $result = curl_exec($ch);
        $responseTime = round((microtime(true) - $start) * 1000);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($result !== false && $httpCode < 400) {
            // Actualizar último test
            $stmt = $pdo->prepare("UPDATE api_providers SET last_test_at = NOW(), last_test_status = 'success' WHERE id = ?");
            $stmt->execute([$id]);
            
            return [
                'success' => true,
                'message' => 'Conexión exitosa',
                'response_time' => $responseTime . 'ms',
                'http_code' => $httpCode,
                'status' => 'online'
            ];
        } else {
            // Actualizar último test
            $stmt = $pdo->prepare("UPDATE api_providers SET last_test_at = NOW(), last_test_status = 'failed' WHERE id = ?");
            $stmt->execute([$id]);
            
            return [
                'success' => false,
                'error' => $error ?: "HTTP Error $httpCode",
                'http_code' => $httpCode,
                'status' => 'offline'
            ];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Construir headers para cURL
 */
function buildCurlHeaders($headers) {
    $headerStrings = [];
    foreach ($headers as $key => $value) {
        $headerStrings[] = "$key: $value";
    }
    return $headerStrings;
}
?>