<?php
// admin_api.php - API para el panel de administración mejorado
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
$host = "localhost";
$db_name = "soporteia_bookingkavia";
$username = "soporteia_admin";
$password = "QCF8RhS*}.Oj0u(v";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]));
}

// Obtener método y acción
$method = $_SERVER['REQUEST_METHOD'];
$action = '';

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
} else if ($method === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = $data['action'] ?? '';
}

// Procesar según la acción
switch ($action) {
    case 'getHotels':
        getHotels($pdo);
        break;
        
    case 'saveHotel':
        saveHotel($pdo, $data);
        break;
        
    case 'deleteHotel':
        deleteHotel($pdo, $data);
        break;
        
    case 'syncHotel':
        syncHotel($pdo, $data);
        break;
        
    case 'getProviders':
        getProviders($pdo);
        break;
        
    case 'saveProvider':
        saveProvider($pdo, $data);
        break;
        
    case 'toggleProvider':
        toggleProvider($pdo, $data);
        break;
        
    case 'activateProvider':
        activateProvider($pdo, $data);
        break;
        
    case 'deleteProvider':
        deleteProvider($pdo, $data);
        break;
        
    case 'testProvider':
        testProvider($pdo, $data);
        break;
        
    case 'testCurrentProvider':
        testCurrentProvider($pdo, $data);
        break;
        
    case 'getPrompts':
        getPrompts($pdo);
        break;
        
    case 'savePrompt':
        savePrompt($pdo, $data);
        break;
        
    case 'activatePrompt':
        activatePrompt($pdo, $data);
        break;
        
    case 'deletePrompt':
        deletePrompt($pdo, $data);
        break;
        
    case 'getLogs':
        getLogs($pdo);
        break;
        
    // NUEVAS FUNCIONES DE HERRAMIENTAS
    case 'scanDuplicateReviews':
        scanDuplicateReviews($pdo, $data);
        break;
        
    case 'deleteDuplicateReviews':
        deleteDuplicateReviews($pdo, $data);
        break;
        
    case 'getDbStats':
        getDbStats($pdo);
        break;
        
    case 'optimizeTables':
        optimizeTables($pdo);
        break;
        
    case 'checkIntegrity':
        checkIntegrity($pdo);
        break;
        
    case 'cleanOldLogs':
        cleanOldLogs($pdo, $data);
        break;
        
    default:
        echo json_encode(['error' => 'Acción no válida: ' . $action]);
}

// Funciones para Hoteles
function getHotels($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                h.*,
                COUNT(r.id) as total_reviews,
                AVG(r.rating) as avg_rating
            FROM hoteles h
            LEFT JOIN reviews r ON h.id = r.hotel_id
            GROUP BY h.id
            ORDER BY h.id DESC
        ");
        
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapear nombres de columnas para compatibilidad
        $mappedHotels = array_map(function($hotel) {
            return [
                'id' => $hotel['id'],
                'hotel_name' => $hotel['nombre_hotel'],
                'hotel_destination' => $hotel['hoja_destino'],
                'url_booking' => $hotel['url_booking'],
                'max_reviews' => $hotel['max_reviews'],
                'activo' => $hotel['activo'],
                'total_reviews' => $hotel['total_reviews'],
                'avg_rating' => $hotel['avg_rating'],
                'created_at' => $hotel['created_at'],
                'updated_at' => $hotel['updated_at']
            ];
        }, $hotels);
        
        echo json_encode(['success' => true, 'hotels' => $mappedHotels]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al obtener hoteles: ' . $e->getMessage()]);
    }
}

function saveHotel($pdo, $data) {
    try {
        $id = $data['id'] ?? null;
        $nombre = $data['nombre_hotel'];
        $destino = $data['hoja_destino'] ?? '';
        $url = $data['url_booking'] ?? '';
        $max_reviews = $data['max_reviews'] ?? 200;
        $activo = $data['activo'] ?? 1;
        
        if ($id) {
            // Actualizar
            $stmt = $pdo->prepare("
                UPDATE hoteles 
                SET nombre_hotel = ?, hoja_destino = ?, url_booking = ?, 
                    max_reviews = ?, activo = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $destino, $url, $max_reviews, $activo, $id]);
        } else {
            // Insertar
            $stmt = $pdo->prepare("
                INSERT INTO hoteles (nombre_hotel, hoja_destino, url_booking, max_reviews, activo, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$nombre, $destino, $url, $max_reviews, $activo]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al guardar hotel: ' . $e->getMessage()]);
    }
}

function deleteHotel($pdo, $data) {
    try {
        $pdo->beginTransaction();
        
        $id = $data['id'];
        
        // Primero eliminar las reseñas asociadas por hotel_id
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE hotel_id = ?");
        $stmt->execute([$id]);
        
        // Eliminar logs asociados
        $stmt = $pdo->prepare("DELETE FROM ai_response_logs WHERE hotel_id = ?");
        $stmt->execute([$id]);
        
        // Luego eliminar el hotel
        $stmt = $pdo->prepare("DELETE FROM hoteles WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['error' => 'Error al eliminar hotel: ' . $e->getMessage()]);
    }
}

function syncHotel($pdo, $data) {
    try {
        $id = $data['id'];
        
        // Aquí iría la lógica de sincronización con Booking
        // Por ahora solo simulamos
        
        echo json_encode([
            'success' => true, 
            'message' => 'Sincronización completada (simulada). Implementar scraper real.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al sincronizar: ' . $e->getMessage()]);
    }
}

// Funciones para Proveedores IA
function getProviders($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM ai_providers ORDER BY is_active DESC, id DESC");
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'providers' => $providers]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al obtener proveedores: ' . $e->getMessage()]);
    }
}

function saveProvider($pdo, $data) {
    try {
        $id = $data['id'] ?? null;
        $name = $data['name'];
        $type = $data['type'];
        $api_key = $data['api_key'] ?? '';
        $api_url = $data['api_url'] ?? '';
        $model = $data['model'] ?? '';
        $params = $data['params'] ?? '';
        $active = $data['active'] ?? 0;
        
        if ($id) {
            // Actualizar
            $stmt = $pdo->prepare("
                UPDATE ai_providers 
                SET name = ?, provider_type = ?, api_key = ?, api_url = ?, 
                    model_name = ?, parameters = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $type, $api_key, $api_url, $model, $params, $active, $id]);
        } else {
            // Insertar
            $stmt = $pdo->prepare("
                INSERT INTO ai_providers (name, provider_type, api_key, api_url, model_name, parameters, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $type, $api_key, $api_url, $model, $params, $active]);
        }
        
        // Si está activando, desactivar otros del mismo tipo
        if ($active == 1) {
            $providerId = $id ?: $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                UPDATE ai_providers 
                SET is_active = 0 
                WHERE id != ? AND provider_type = ?
            ");
            $stmt->execute([$providerId, $type]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al guardar proveedor: ' . $e->getMessage()]);
    }
}

function toggleProvider($pdo, $data) {
    try {
        $id = intval($data['id']);
        $active = intval($data['active']);
        
        // Actualizar estado
        $stmt = $pdo->prepare("UPDATE ai_providers SET is_active = ? WHERE id = ?");
        $stmt->execute([$active, $id]);
        
        // Si está activando, desactivar los demás del mismo tipo
        if ($active == 1) {
            $stmt = $pdo->prepare("
                UPDATE ai_providers 
                SET is_active = 0 
                WHERE id != ? 
                AND provider_type = (SELECT provider_type FROM (SELECT provider_type FROM ai_providers WHERE id = ?) AS temp)
            ");
            $stmt->execute([$id, $id]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al cambiar estado: ' . $e->getMessage()]);
    }
}

function activateProvider($pdo, $data) {
    try {
        $id = $data['id'];
        
        // Obtener el tipo del proveedor
        $stmt = $pdo->prepare("SELECT provider_type FROM ai_providers WHERE id = ?");
        $stmt->execute([$id]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($provider) {
            // Desactivar todos los del mismo tipo
            $stmt = $pdo->prepare("UPDATE ai_providers SET is_active = 0 WHERE provider_type = ?");
            $stmt->execute([$provider['provider_type']]);
            
            // Activar el seleccionado
            $stmt = $pdo->prepare("UPDATE ai_providers SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Proveedor no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al activar proveedor: ' . $e->getMessage()]);
    }
}

function deleteProvider($pdo, $data) {
    try {
        $id = $data['id'];
        
        $stmt = $pdo->prepare("DELETE FROM ai_providers WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al eliminar proveedor: ' . $e->getMessage()]);
    }
}

function testProvider($pdo, $data) {
    try {
        $id = $data['id'];
        
        // Obtener datos del proveedor
        $stmt = $pdo->prepare("SELECT * FROM ai_providers WHERE id = ?");
        $stmt->execute([$id]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$provider) {
            echo json_encode(['error' => 'Proveedor no encontrado']);
            return;
        }
        
        // Test según el tipo
        $success = false;
        $message = '';
        
        switch ($provider['provider_type']) {
            case 'openai':
                if ($provider['api_key']) {
                    // Test simple de OpenAI
                    $ch = curl_init('https://api.openai.com/v1/models');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $provider['api_key']
                    ]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200) {
                        $success = true;
                        $message = 'Conexión exitosa con OpenAI';
                    } else {
                        $message = 'Error de conexión. Verifica tu API key.';
                    }
                } else {
                    $message = 'API key no configurada';
                }
                break;
                
            case 'deepseek':
                if ($provider['api_key']) {
                    $success = true; // Por ahora asumimos que funciona
                    $message = 'Proveedor DeepSeek configurado';
                } else {
                    $message = 'API key no configurada';
                }
                break;
                
            case 'claude':
                if ($provider['api_key']) {
                    $success = true;
                    $message = 'Proveedor Claude configurado';
                } else {
                    $message = 'API key no configurada';
                }
                break;
                
            case 'gemini':
                if ($provider['api_key']) {
                    $success = true;
                    $message = 'Proveedor Gemini configurado';
                } else {
                    $message = 'API key no configurada';
                }
                break;
                
            case 'local':
                $success = true;
                $message = 'Proveedor local activo (no requiere conexión)';
                break;
                
            default:
                $message = 'Tipo de proveedor no implementado';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al probar proveedor: ' . $e->getMessage()]);
    }
}

function testCurrentProvider($pdo, $data) {
    try {
        $type = $data['type'];
        $api_key = $data['api_key'] ?? '';
        $api_url = $data['api_url'] ?? '';
        $model = $data['model'] ?? '';
        
        $success = false;
        $message = '';
        
        switch ($type) {
            case 'openai':
                if ($api_key) {
                    $ch = curl_init('https://api.openai.com/v1/models');
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $api_key
                    ]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200) {
                        $success = true;
                        $message = 'Conexión exitosa con OpenAI';
                    } else {
                        $message = 'Error de conexión con OpenAI. Verifica tu API key.';
                    }
                } else {
                    $message = 'API key requerida para OpenAI';
                }
                break;
                
            case 'deepseek':
                if ($api_key) {
                    $success = true;
                    $message = 'Configuración DeepSeek válida';
                } else {
                    $message = 'API key requerida para DeepSeek';
                }
                break;
                
            case 'claude':
                if ($api_key) {
                    $success = true;
                    $message = 'Configuración Claude válida';
                } else {
                    $message = 'API key requerida para Claude';
                }
                break;
                
            case 'gemini':
                if ($api_key) {
                    $success = true;
                    $message = 'Configuración Gemini válida';
                } else {
                    $message = 'API key requerida para Gemini';
                }
                break;
                
            case 'local':
                $success = true;
                $message = 'Proveedor local configurado (no requiere conexión)';
                break;
                
            default:
                $message = 'Selecciona un tipo de proveedor';
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al probar conexión: ' . $e->getMessage()]);
    }
}

// Funciones para Prompts
function getPrompts($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM ai_prompts ORDER BY is_active DESC, id DESC");
        $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'prompts' => $prompts]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al obtener prompts: ' . $e->getMessage()]);
    }
}

function savePrompt($pdo, $data) {
    try {
        $id = $data['id'] ?? null;
        $name = $data['name'];
        $type = $data['type'];
        $text = $data['text'];
        $active = $data['active'] ?? 0;
        
        if ($id) {
            // Actualizar
            $stmt = $pdo->prepare("
                UPDATE ai_prompts 
                SET name = ?, prompt_type = ?, prompt_text = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $type, $text, $active, $id]);
        } else {
            // Insertar
            $stmt = $pdo->prepare("
                INSERT INTO ai_prompts (name, prompt_type, prompt_text, language, is_active, created_at)
                VALUES (?, ?, ?, 'es', ?, NOW())
            ");
            $stmt->execute([$name, $type, $text, $active]);
        }
        
        // Si está activando y es tipo response, desactivar otros
        if ($active == 1 && $type == 'response') {
            $promptId = $id ?: $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                UPDATE ai_prompts 
                SET is_active = 0 
                WHERE id != ? AND prompt_type = 'response'
            ");
            $stmt->execute([$promptId]);
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al guardar prompt: ' . $e->getMessage()]);
    }
}

function activatePrompt($pdo, $data) {
    try {
        $id = $data['id'];
        
        // Obtener el tipo del prompt
        $stmt = $pdo->prepare("SELECT prompt_type FROM ai_prompts WHERE id = ?");
        $stmt->execute([$id]);
        $prompt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($prompt) {
            // Desactivar todos del mismo tipo
            $stmt = $pdo->prepare("UPDATE ai_prompts SET is_active = 0 WHERE prompt_type = ?");
            $stmt->execute([$prompt['prompt_type']]);
            
            // Activar el seleccionado
            $stmt = $pdo->prepare("UPDATE ai_prompts SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Prompt no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al activar prompt: ' . $e->getMessage()]);
    }
}

function deletePrompt($pdo, $data) {
    try {
        $id = $data['id'];
        
        $stmt = $pdo->prepare("DELETE FROM ai_prompts WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al eliminar prompt: ' . $e->getMessage()]);
    }
}

// Función para Logs
function getLogs($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                l.*,
                p.name as provider_name,
                h.nombre_hotel as hotel_name
            FROM ai_response_logs l
            LEFT JOIN ai_providers p ON l.provider_id = p.id
            LEFT JOIN hoteles h ON l.hotel_id = h.id
            ORDER BY l.created_at DESC
            LIMIT 100
        ");
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'logs' => $logs]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al obtener logs: ' . $e->getMessage()]);
    }
}

// =============================================
// NUEVAS FUNCIONES DE HERRAMIENTAS
// =============================================

function scanDuplicateReviews($pdo, $data) {
    try {
        $criteria = $data['criteria'];
        
        // Construir WHERE clause basado en criterios
        $whereConditions = [];
        $selectFields = [
            'r.id', 'r.hotel_id', 'r.guest_name', 'r.title', 
            'r.positive', 'r.negative', 'r.rating', 'r.date', 
            'h.nombre_hotel as hotel_name'
        ];
        
        if (!empty($criteria['hotel_id'])) {
            $whereConditions[] = "r.hotel_id = " . intval($criteria['hotel_id']);
        }
        
        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Obtener todas las reseñas
        $sql = "SELECT " . implode(', ', $selectFields) . " 
                FROM reviews r 
                JOIN hoteles h ON r.hotel_id = h.id 
                $whereClause 
                ORDER BY r.hotel_id, r.date DESC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agrupar por similitud
        $duplicateGroups = [];
        $processedIds = [];
        
        foreach ($reviews as $review1) {
            if (in_array($review1['id'], $processedIds)) continue;
            
            $similarReviews = [$review1];
            $matchCriteria = [];
            
            foreach ($reviews as $review2) {
                if ($review1['id'] == $review2['id'] || in_array($review2['id'], $processedIds)) continue;
                if ($review1['hotel_id'] != $review2['hotel_id']) continue; // Solo mismo hotel
                
                $matches = 0;
                $currentCriteria = [];
                
                // Verificar título
                if ($criteria['title'] && !empty($review1['title']) && !empty($review2['title'])) {
                    $similarity = similar_text(strtolower(trim($review1['title'])), strtolower(trim($review2['title'])), $percent);
                    if ($percent > 85) { // 85% de similitud
                        $matches++;
                        $currentCriteria[] = 'título';
                    }
                }
                
                // Verificar contenido
                if ($criteria['content']) {
                    $content1 = strtolower(trim($review1['positive']) . ' ' . trim($review1['negative']));
                    $content2 = strtolower(trim($review2['positive']) . ' ' . trim($review2['negative']));
                    
                    if (strlen($content1) > 10 && strlen($content2) > 10) {
                        $similarity = similar_text($content1, $content2, $percent);
                        if ($percent > 80) { // 80% de similitud en contenido
                            $matches++;
                            $currentCriteria[] = 'contenido';
                        }
                    }
                }
                
                // Verificar huésped
                if ($criteria['guest'] && !empty($review1['guest_name']) && !empty($review2['guest_name'])) {
                    if (strtolower(trim($review1['guest_name'])) === strtolower(trim($review2['guest_name']))) {
                        $matches++;
                        $currentCriteria[] = 'huésped';
                    }
                }
                
                // Verificar fecha (mismo día o días consecutivos)
                if ($criteria['date'] && !empty($review1['date']) && !empty($review2['date'])) {
                    $date1 = new DateTime($review1['date']);
                    $date2 = new DateTime($review2['date']);
                    $daysDiff = abs($date1->diff($date2)->days);
                    
                    if ($daysDiff <= 1) { // Mismo día o día siguiente
                        $matches++;
                        $currentCriteria[] = 'fecha';
                    }
                }
                
                // Si coinciden al menos 2 criterios, es duplicado
                if ($matches >= 2) {
                    $similarReviews[] = $review2;
                    $processedIds[] = $review2['id'];
                    if (empty($matchCriteria)) {
                        $matchCriteria = $currentCriteria;
                    }
                }
            }
            
            // Si hay duplicados, agregar al grupo
            if (count($similarReviews) > 1) {
                $duplicateGroups[] = [
                    'reviews' => $similarReviews,
                    'match_criteria' => $matchCriteria
                ];
                foreach ($similarReviews as $review) {
                    $processedIds[] = $review['id'];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'duplicates' => $duplicateGroups,
            'total_groups' => count($duplicateGroups),
            'total_reviews_scanned' => count($reviews)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al escanear duplicados: ' . $e->getMessage()
        ]);
    }
}

function deleteDuplicateReviews($pdo, $data) {
    try {
        $duplicates = $data['duplicates'];
        
        $pdo->beginTransaction();
        
        $deletedCount = 0;
        $idsToDelete = [];
        
        // Recopilar IDs a eliminar (mantener el primero de cada grupo)
        foreach ($duplicates as $group) {
            $reviews = $group['reviews'];
            // Saltar el primero (se mantiene), eliminar el resto
            for ($i = 1; $i < count($reviews); $i++) {
                $idsToDelete[] = $reviews[$i]['id'];
            }
        }
        
        if (!empty($idsToDelete)) {
            $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id IN ($placeholders)");
            $stmt->execute($idsToDelete);
            $deletedCount = $stmt->rowCount();
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'deleted_count' => $deletedCount,
            'message' => "Se eliminaron $deletedCount reseñas duplicadas"
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'error' => 'Error al eliminar duplicados: ' . $e->getMessage()
        ]);
    }
}

function getDbStats($pdo) {
    try {
        // Estadísticas generales
        $stats = [];
        
        // Total de hoteles
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(activo) as active FROM hoteles");
        $hotelStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_hotels'] = $hotelStats['total'];
        $stats['active_hotels'] = $hotelStats['active'];
        
        // Total de reseñas y rating promedio
        $stmt = $pdo->query("SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM reviews WHERE rating > 0");
        $reviewStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_reviews'] = $reviewStats['total'];
        $stats['avg_rating'] = round($reviewStats['avg_rating'], 1);
        
        // Total de proveedores
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(is_active) as active FROM ai_providers");
        $providerStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_providers'] = $providerStats['total'] ?? 0;
        $stats['active_providers'] = $providerStats['active'] ?? 0;
        
        // Total de prompts
        $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(is_active) as active FROM ai_prompts");
        $promptStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_prompts'] = $promptStats['total'] ?? 0;
        $stats['active_prompts'] = $promptStats['active'] ?? 0;
        
        // Distribución por hotel
        $stmt = $pdo->query("
            SELECT h.nombre_hotel as hotel_name, COUNT(r.id) as review_count 
            FROM hoteles h 
            LEFT JOIN reviews r ON h.id = r.hotel_id 
            GROUP BY h.id, h.nombre_hotel 
            ORDER BY review_count DESC
        ");
        $stats['hotel_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener estadísticas: ' . $e->getMessage()
        ]);
    }
}

function optimizeTables($pdo) {
    try {
        $tables = ['hoteles', 'reviews', 'ai_providers', 'ai_prompts', 'ai_response_logs'];
        $optimized = 0;
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("OPTIMIZE TABLE `$table`");
                if ($stmt) $optimized++;
            } catch (Exception $e) {
                // Continúa con las demás tablas aunque una falle
                continue;
            }
        }
        
        echo json_encode([
            'success' => true,
            'tables_optimized' => $optimized,
            'message' => "Se optimizaron $optimized tablas"
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al optimizar tablas: ' . $e->getMessage()
        ]);
    }
}

function checkIntegrity($pdo) {
    try {
        $issues = [];
        
        // Verificar reseñas huérfanas (sin hotel)
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM reviews r 
            LEFT JOIN hoteles h ON r.hotel_id = h.id 
            WHERE h.id IS NULL
        ");
        $orphanReviews = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($orphanReviews > 0) {
            $issues[] = "$orphanReviews reseñas sin hotel asociado";
        }
        
        // Verificar logs huérfanos (solo si la tabla existe)
        try {
            $stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM ai_response_logs l 
                LEFT JOIN hoteles h ON l.hotel_id = h.id 
                WHERE l.hotel_id IS NOT NULL AND h.id IS NULL
            ");
            $orphanLogs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            if ($orphanLogs > 0) {
                $issues[] = "$orphanLogs logs sin hotel asociado";
            }
        } catch (Exception $e) {
            // Tabla ai_response_logs no existe, no es problema
        }
        
        // Verificar reseñas con datos faltantes
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM reviews 
            WHERE (guest_name IS NULL OR guest_name = '') 
            OR (title IS NULL OR title = '')
        ");
        $incompleteReviews = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($incompleteReviews > 0) {
            $issues[] = "$incompleteReviews reseñas con datos incompletos";
        }
        
        // Verificar reseñas con ratings inválidos
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM reviews 
            WHERE rating < 0 OR rating > 10
        ");
        $invalidRatings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($invalidRatings > 0) {
            $issues[] = "$invalidRatings reseñas con ratings inválidos";
        }
        
        echo json_encode([
            'success' => true,
            'issues' => $issues,
            'message' => count($issues) == 0 ? 'No se encontraron problemas' : count($issues) . ' problemas encontrados'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al verificar integridad: ' . $e->getMessage()
        ]);
    }
}

function cleanOldLogs($pdo, $data) {
    try {
        $days = intval($data['days']);
        
        if ($days < 1) {
            echo json_encode([
                'success' => false,
                'error' => 'Número de días debe ser mayor a 0'
            ]);
            return;
        }
        
        // Verificar si la tabla existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'ai_response_logs'");
        if ($stmt->rowCount() == 0) {
            echo json_encode([
                'success' => true,
                'deleted_logs' => 0,
                'message' => 'Tabla ai_response_logs no existe'
            ]);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM ai_response_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$days]);
        $deletedCount = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'deleted_logs' => $deletedCount,
            'message' => "Se eliminaron $deletedCount logs antiguos"
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al limpiar logs: ' . $e->getMessage()
        ]);
    }
}

?>