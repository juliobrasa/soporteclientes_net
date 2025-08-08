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
                $stmt = $pdo->prepare("SELECT * FROM ai_prompts WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                $prompt = $stmt->fetch();
                if ($prompt) {
                    response(['success' => true, 'data' => $prompt]);
                } else {
                    response(['error' => 'Prompt no encontrado'], 404);
                }
            } else {
                $stmt = $pdo->query("SELECT * FROM ai_prompts ORDER BY name ASC");
                $prompts = $stmt->fetchAll();
                response(['success' => true, 'data' => $prompts]);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al obtener prompts: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        try {
            $required = ['name', 'prompt_text'];
            foreach ($required as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    response(['error' => "Campo requerido: $field"], 400);
                }
            }

            $stmt = $pdo->prepare("INSERT INTO ai_prompts (name, prompt_text, prompt_type, language, is_active, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $result = $stmt->execute([
                $input['name'],
                $input['prompt_text'],
                $input['prompt_type'] ?? 'response',
                $input['language'] ?? 'es',
                isset($input['active']) && $input['active'] ? 1 : 0
            ]);

            if ($result) {
                $id = $pdo->lastInsertId();
                response(['success' => true, 'message' => 'Prompt creado exitosamente', 'id' => $id], 201);
            } else {
                response(['error' => 'Error al crear prompt'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al crear prompt: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        try {
            if (!isset($_GET['id'])) {
                response(['error' => 'ID requerido para actualizar'], 400);
            }

            $id = $_GET['id'];
            $stmt = $pdo->prepare("UPDATE ai_prompts SET name = ?, prompt_text = ?, prompt_type = ?, language = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([
                $input['name'],
                $input['prompt_text'],
                $input['prompt_type'] ?? 'response',
                $input['language'] ?? 'es',
                isset($input['active']) && $input['active'] ? 1 : 0,
                $id
            ]);

            if ($result) {
                response(['success' => true, 'message' => 'Prompt actualizado exitosamente']);
            } else {
                response(['error' => 'Error al actualizar prompt'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al actualizar prompt: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        try {
            if (!isset($_GET['id'])) {
                response(['error' => 'ID requerido para eliminar'], 400);
            }

            $id = $_GET['id'];
            $stmt = $pdo->prepare("DELETE FROM ai_prompts WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                response(['success' => true, 'message' => 'Prompt eliminado exitosamente']);
            } else {
                response(['error' => 'Error al eliminar prompt'], 500);
            }
        } catch (PDOException $e) {
            response(['error' => 'Error al eliminar prompt: ' . $e->getMessage()], 500);
        }
        break;

    default:
        response(['error' => 'Método no permitido'], 405);
}
?>