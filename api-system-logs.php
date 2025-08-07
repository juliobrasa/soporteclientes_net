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
                $stmt = $pdo->prepare("SELECT sl.*, h.nombre_hotel FROM system_logs sl LEFT JOIN hoteles h ON sl.hotel_id = h.id WHERE sl.id = ?");
                $stmt->execute([$_GET['id']]);
                $log = $stmt->fetch();
                if ($log) {
                    response(['success' => true, 'data' => $log]);
                } else {
                    response(['error' => 'Log no encontrado'], 404);
                }
            } else {
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
                $level = isset($_GET['level']) ? $_GET['level'] : null;
                
                $query = "SELECT sl.*, h.nombre_hotel FROM system_logs sl LEFT JOIN hoteles h ON sl.hotel_id = h.id";
                $params = [];
                
                if ($level) {
                    $query .= " WHERE sl.level = ?";
                    $params[] = $level;
                }
                
                $query .= " ORDER BY sl.created_at DESC LIMIT ? OFFSET ?";
                $params[] = $limit;
                $params[] = $offset;
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $logs = $stmt->fetchAll();
                response(['success' => true, 'data' => $logs]);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al obtener logs: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        try {
            $required = ['action', 'level'];
            foreach ($required as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    response(['error' => "Campo requerido: $field"], 400);
                }
            }

            $stmt = $pdo->prepare("INSERT INTO system_logs (hotel_id, action, details, level, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $input['hotel_id'] ?? null,
                $input['action'],
                $input['details'] ?? null,
                $input['level'],
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

            if ($result) {
                $id = $pdo->lastInsertId();
                response(['success' => true, 'message' => 'Log creado exitosamente', 'id' => $id], 201);
            } else {
                response(['error' => 'Error al crear log'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al crear log: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        try {
            if (isset($_GET['clear_all']) && $_GET['clear_all'] === 'true') {
                // Clear all logs older than specified days
                $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
                $stmt = $pdo->prepare("DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $result = $stmt->execute([$days]);
                $affected = $stmt->rowCount();
                
                if ($result) {
                    response(['success' => true, 'message' => "Eliminados $affected logs de más de $days días"]);
                } else {
                    response(['error' => 'Error al limpiar logs'], 500);
                }
            } elseif (isset($_GET['id'])) {
                $id = $_GET['id'];
                $stmt = $pdo->prepare("DELETE FROM system_logs WHERE id = ?");
                $result = $stmt->execute([$id]);

                if ($result) {
                    response(['success' => true, 'message' => 'Log eliminado exitosamente']);
                } else {
                    response(['error' => 'Error al eliminar log'], 500);
                }
            } else {
                response(['error' => 'ID requerido para eliminar'], 400);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al eliminar log: ' . $e->getMessage()], 500);
        }
        break;

    default:
        response(['error' => 'Método no permitido'], 405);
}
?>