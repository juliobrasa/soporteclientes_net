<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

include 'admin-config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    response(['error' => 'Error de conexión a la base de datos'], 500);
}

switch ($method) {
    case 'GET':
        try {
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM api_providers WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $api = $stmt->fetch();
                if ($api) {
                    response(['success' => true, 'data' => $api]);
                } else {
                    response(['error' => 'API no encontrada'], 404);
                }
            } else {
                $stmt = $pdo->query("SELECT * FROM api_providers ORDER BY name ASC");
                $apis = $stmt->fetchAll();
                response(['success' => true, 'data' => $apis]);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al obtener APIs: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        try {
            $required = ['name', 'provider_type'];
            foreach ($required as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    response(['error' => "Campo requerido: $field"], 400);
                }
            }

            $stmt = $pdo->prepare("INSERT INTO api_providers (name, provider_type, api_key, api_url, description, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $input['name'],
                $input['provider_type'],
                $input['api_key'] ?? null,
                $input['api_url'] ?? null,
                $input['description'] ?? null,
                isset($input['is_active']) && $input['is_active'] ? 1 : 0
            ]);

            if ($result) {
                $id = $pdo->lastInsertId();
                response(['success' => true, 'message' => 'API externa creada exitosamente', 'id' => $id], 201);
            } else {
                response(['error' => 'Error al crear API externa'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al crear API externa: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        try {
            if (!isset($_GET['id'])) {
                response(['error' => 'ID requerido para actualizar'], 400);
            }

            $id = $_GET['id'];
            $stmt = $pdo->prepare("UPDATE api_providers SET name = ?, provider_type = ?, api_key = ?, api_url = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                $input['name'],
                $input['provider_type'],
                $input['api_key'] ?? null,
                $input['api_url'] ?? null,
                $input['description'] ?? null,
                isset($input['is_active']) && $input['is_active'] ? 1 : 0,
                $id
            ]);

            if ($result) {
                response(['success' => true, 'message' => 'API externa actualizada exitosamente']);
            } else {
                response(['error' => 'Error al actualizar API externa'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al actualizar API externa: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        try {
            if (!isset($_GET['id'])) {
                response(['error' => 'ID requerido para eliminar'], 400);
            }

            $id = $_GET['id'];
            $stmt = $pdo->prepare("DELETE FROM api_providers WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                response(['success' => true, 'message' => 'API externa eliminada exitosamente']);
            } else {
                response(['error' => 'Error al eliminar API externa'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al eliminar API externa: ' . $e->getMessage()], 500);
        }
        break;

    default:
        response(['error' => 'Método no permitido'], 405);
}
?>