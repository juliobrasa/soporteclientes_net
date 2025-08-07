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
                $stmt = $pdo->prepare("SELECT * FROM hoteles WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $hotel = $stmt->fetch();
                if ($hotel) {
                    response(['success' => true, 'data' => $hotel]);
                } else {
                    response(['error' => 'Hotel no encontrado'], 404);
                }
            } else {
                $stmt = $pdo->query("SELECT * FROM hoteles ORDER BY nombre_hotel ASC");
                $hotels = $stmt->fetchAll();
                response(['success' => true, 'data' => $hotels]);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al obtener hoteles: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        try {
            $required = ['nombre_hotel'];
            foreach ($required as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    response(['error' => "Campo requerido: $field"], 400);
                }
            }

            $stmt = $pdo->prepare("INSERT INTO hoteles (nombre_hotel, url_booking, hoja_destino, max_reviews, activo, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $input['nombre_hotel'],
                $input['url_booking'] ?? null,
                $input['hoja_destino'] ?? null,
                $input['max_reviews'] ?? 200,
                isset($input['activo']) ? ($input['activo'] ? 1 : 0) : 1
            ]);

            if ($result) {
                $id = $pdo->lastInsertId();
                response(['success' => true, 'message' => 'Hotel creado exitosamente', 'id' => $id], 201);
            } else {
                response(['error' => 'Error al crear hotel'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al crear hotel: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        try {
            if (!isset($_GET['id'])) {
                response(['error' => 'ID requerido para actualizar'], 400);
            }

            $id = $_GET['id'];
            $stmt = $pdo->prepare("UPDATE hoteles SET nombre_hotel = ?, url_booking = ?, hoja_destino = ?, max_reviews = ?, activo = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                $input['nombre_hotel'],
                $input['url_booking'] ?? null,
                $input['hoja_destino'] ?? null,
                $input['max_reviews'] ?? 200,
                isset($input['activo']) ? ($input['activo'] ? 1 : 0) : 1,
                $id
            ]);

            if ($result) {
                response(['success' => true, 'message' => 'Hotel actualizado exitosamente']);
            } else {
                response(['error' => 'Error al actualizar hotel'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al actualizar hotel: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        try {
            if (!isset($_GET['id'])) {
                response(['error' => 'ID requerido para eliminar'], 400);
            }

            $id = $_GET['id'];
            $stmt = $pdo->prepare("DELETE FROM hoteles WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                response(['success' => true, 'message' => 'Hotel eliminado exitosamente']);
            } else {
                response(['error' => 'Error al eliminar hotel'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al eliminar hotel: ' . $e->getMessage()], 500);
        }
        break;

    default:
        response(['error' => 'Método no permitido'], 405);
}
?>