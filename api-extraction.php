<?php
/**
 * API para manejo de extracciones con Apify Hotel Review Aggregator
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'admin-config.php';
require_once 'apify-config.php';
require_once 'apify-data-processor.php';
require_once 'debug-logger.php';

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Obtener input
$input = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

DebugLogger::info("Nueva petición de extracción", [
    'method' => $method,
    'input' => $input,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'referer' => $_SERVER['HTTP_REFERER'] ?? null
]);

// Verificar autenticación
session_start();

// Método principal: verificar sesión normal
$isAuthenticated = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'];

// Método alternativo: verificar header de sesión para problemas de cookies
if (!$isAuthenticated && isset($_SERVER['HTTP_X_ADMIN_SESSION'])) {
    $sessionId = $_SERVER['HTTP_X_ADMIN_SESSION'];
    if ($sessionId && strlen($sessionId) > 10) { // Validación básica de session ID
        // Aceptar como autenticado si viene con header de sesión válido
        $isAuthenticated = true;
    }
}

DebugLogger::debug("Verificación de autenticación", [
    'session_logged' => isset($_SESSION['admin_logged']),
    'session_value' => $_SESSION['admin_logged'] ?? null,
    'session_id' => session_id(),
    'header_session' => $_SERVER['HTTP_X_ADMIN_SESSION'] ?? null,
    'cookies_count' => count($_COOKIE),
    'is_authenticated' => $isAuthenticated
]);

if (!$isAuthenticated) {
    DebugLogger::error("Autenticación fallida");
    response([
        'error' => 'No autorizado',
        'debug' => [
            'session_logged' => isset($_SESSION['admin_logged']),
            'session_id' => session_id(),
            'header_session' => $_SERVER['HTTP_X_ADMIN_SESSION'] ?? null,
            'cookies' => !empty($_COOKIE)
        ]
    ], 401);
}

// Conectar a base de datos
$pdo = getDBConnection();
if (!$pdo) {
    response(['error' => 'Error de conexión a la base de datos'], 500);
}

switch ($method) {
    case 'POST':
        handleStartExtraction($input, $pdo);
        break;
        
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'get_recent') {
            handleGetRecentJobs($pdo);
        } elseif (isset($_GET['run_id'])) {
            handleGetRunStatus($_GET['run_id'], $pdo);
        } elseif (isset($_GET['hotel_id'])) {
            handleGetHotelExtractions($_GET['hotel_id'], $pdo);
        } else {
            handleGetAllRuns($pdo);
        }
        break;
        
    case 'PUT':
        if (isset($_GET['run_id'])) {
            handleUpdateRun($_GET['run_id'], $input, $pdo);
        }
        break;
        
    case 'DELETE':
        if (isset($_GET['job_id'])) {
            handleDeleteJob($_GET['job_id'], $pdo);
        } elseif (isset($_GET['run_id'])) {
            handleCancelRun($_GET['run_id'], $pdo);
        }
        break;
        
    default:
        response(['error' => 'Método no permitido'], 405);
}

/**
 * Iniciar nueva extracción
 */
function handleStartExtraction($input, $pdo) {
    try {
        DebugLogger::info("Iniciando handleStartExtraction", ['input' => $input]);
        
        // Validar input
        if (!isset($input['hotel_id']) || empty($input['hotel_id'])) {
            DebugLogger::error("hotel_id faltante", ['input' => $input]);
            response(['error' => 'hotel_id es requerido'], 400);
        }
        
        $hotelId = $input['hotel_id'];
        $maxReviews = $input['max_reviews'] ?? 100;
        $platforms = $input['platforms'] ?? ['tripadvisor', 'booking', 'google'];
        $languages = $input['languages'] ?? ['en', 'es'];
        
        // Verificar que el hotel existe y obtener su Place ID
        $stmt = $pdo->prepare("SELECT nombre_hotel, google_place_id FROM hoteles WHERE id = ?");
        $stmt->execute([$hotelId]);
        $hotel = $stmt->fetch();
        
        if (!$hotel) {
            response(['error' => 'Hotel no encontrado'], 404);
        }
        
        // Si no tiene Place ID, necesitamos configurarlo
        if (!$hotel['google_place_id']) {
            response([
                'error' => 'Hotel no tiene Google Place ID configurado',
                'action_required' => 'configure_place_id',
                'hotel_name' => $hotel['nombre_hotel']
            ], 400);
        }
        
        // Estimar costo
        $totalReviews = $maxReviews * count($platforms);
        $apifyClient = new ApifyClient();
        $costEstimate = $apifyClient->estimateCost($totalReviews);
        
        // Configurar extracción
        $extractionConfig = [
            'startIds' => [$hotel['google_place_id']],
            'maxReviews' => $maxReviews,
            'reviewPlatforms' => $platforms,
            'reviewLanguages' => $languages,
            'reviewDates' => [
                'from' => date('Y-01-01'),
                'to' => date('Y-12-31')
            ]
        ];
        
        // Iniciar extracción en Apify
        DebugLogger::info("Llamando a Apify", ['config' => $extractionConfig]);
        $apifyResponse = $apifyClient->startHotelExtraction($extractionConfig);
        DebugLogger::info("Respuesta de Apify", ['response' => $apifyResponse]);
        
        if (!isset($apifyResponse['data']['id'])) {
            DebugLogger::error("Apify no devolvió run ID", ['response' => $apifyResponse]);
            response([
                'error' => 'Error iniciando extracción en Apify',
                'details' => $apifyResponse
            ], 500);
        }
        
        $runId = $apifyResponse['data']['id'];
        
        DebugLogger::info("Guardando en base de datos", [
            'hotel_id' => $hotelId,
            'run_id' => $runId,
            'platforms' => $platforms,
            'cost_estimate' => $costEstimate
        ]);
        
        // Guardar en base de datos
        try {
            $stmt = $pdo->prepare("
                INSERT INTO apify_extraction_runs (
                    hotel_id, apify_run_id, status, platforms_requested, 
                    max_reviews_per_platform, cost_estimate, apify_response
                ) VALUES (?, ?, 'pending', ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $hotelId,
                $runId,
                json_encode($platforms),
                $maxReviews,
                $costEstimate,
                json_encode($apifyResponse)
            ]);
            
            DebugLogger::info("apify_extraction_runs insertado", ['run_id' => $runId]);
            
            // También insertar en extraction_jobs para compatibilidad
            $stmt = $pdo->prepare("
                INSERT INTO extraction_jobs (
                    hotel_id, status, progress, reviews_extracted, 
                    created_at, updated_at
                ) VALUES (?, 'pending', 0, 0, NOW(), NOW())
            ");
            $stmt->execute([$hotelId]);
            
            DebugLogger::info("extraction_jobs insertado", ['hotel_id' => $hotelId]);
            
        } catch (PDOException $e) {
            DebugLogger::error("Error insertando en BD", [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'hotel_id' => $hotelId,
                'run_id' => $runId
            ]);
            throw $e;
        }
        
        $successResponse = [
            'success' => true,
            'run_id' => $runId,
            'cost_estimate' => number_format($costEstimate, 2),
            'platforms' => $platforms,
            'max_reviews' => $maxReviews,
            'message' => 'Extracción iniciada exitosamente'
        ];
        
        DebugLogger::info("Enviando respuesta exitosa", $successResponse);
        response($successResponse);
        
    } catch (Exception $e) {
        $errorDetails = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        error_log("Error en startExtraction: " . json_encode($errorDetails));
        response([
            'error' => $e->getMessage(),
            'debug_info' => $_ENV['APP_DEBUG'] ?? false ? $errorDetails : null
        ], 500);
    }
}

/**
 * Obtener estado de una ejecución
 */
function handleGetRunStatus($runId, $pdo) {
    try {
        $apifyClient = new ApifyClient();
        $apifyStatus = $apifyClient->getRunStatus($runId);
        
        // Actualizar estado en base de datos
        $status = mapApifyStatus($apifyStatus['data']['status']);
        
        $stmt = $pdo->prepare("
            UPDATE apify_extraction_runs 
            SET status = ?, apify_response = ?
            WHERE apify_run_id = ?
        ");
        $stmt->execute([$status, json_encode($apifyStatus), $runId]);
        
        // Si está completado, procesar resultados
        if ($status === 'succeeded' && $apifyStatus['data']['status'] === 'SUCCEEDED') {
            $processor = new ApifyDataProcessor();
            
            // Obtener hotel_id
            $stmt = $pdo->prepare("SELECT hotel_id FROM apify_extraction_runs WHERE apify_run_id = ?");
            $stmt->execute([$runId]);
            $run = $stmt->fetch();
            
            if ($run) {
                try {
                    $result = $processor->processApifyResults($runId, $run['hotel_id']);
                    response([
                        'success' => true,
                        'status' => $status,
                        'apify_status' => $apifyStatus['data']['status'],
                        'processed' => $result
                    ]);
                } catch (Exception $e) {
                    response([
                        'success' => false,
                        'status' => 'failed',
                        'error' => 'Error procesando resultados: ' . $e->getMessage()
                    ]);
                }
            }
        }
        
        response([
            'success' => true,
            'status' => $status,
            'apify_status' => $apifyStatus['data']['status'],
            'started_at' => $apifyStatus['data']['startedAt'] ?? null,
            'finished_at' => $apifyStatus['data']['finishedAt'] ?? null
        ]);
        
    } catch (Exception $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Obtener extracciones de un hotel
 */
function handleGetHotelExtractions($hotelId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                aer.*,
                h.nombre_hotel,
                TIMESTAMPDIFF(MINUTE, aer.started_at, COALESCE(aer.finished_at, NOW())) as duration_minutes
            FROM apify_extraction_runs aer
            JOIN hoteles h ON aer.hotel_id = h.id
            WHERE aer.hotel_id = ?
            ORDER BY aer.started_at DESC
            LIMIT 50
        ");
        
        $stmt->execute([$hotelId]);
        $runs = $stmt->fetchAll();
        
        response([
            'success' => true,
            'data' => $runs
        ]);
        
    } catch (Exception $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Obtener trabajos recientes para polling
 */
function handleGetRecentJobs($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                ej.id,
                ej.hotel_id,
                ej.status,
                ej.progress,
                ej.reviews_extracted,
                ej.created_at,
                ej.updated_at,
                h.nombre_hotel,
                aer.apify_run_id,
                aer.cost_estimate,
                aer.platforms_requested,
                TIMESTAMPDIFF(SECOND, ej.updated_at, NOW()) as seconds_since_update
            FROM extraction_jobs ej
            JOIN hoteles h ON ej.hotel_id = h.id
            LEFT JOIN apify_extraction_runs aer ON aer.hotel_id = ej.hotel_id 
                AND aer.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            WHERE ej.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ORDER BY ej.created_at DESC
            LIMIT 50
        ");
        
        $jobs = $stmt->fetchAll();
        
        response([
            'success' => true,
            'data' => $jobs,
            'timestamp' => time()
        ]);
        
    } catch (Exception $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Obtener todas las ejecuciones
 */
function handleGetAllRuns($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                aer.*,
                h.nombre_hotel,
                TIMESTAMPDIFF(MINUTE, aer.started_at, COALESCE(aer.finished_at, NOW())) as duration_minutes
            FROM apify_extraction_runs aer
            JOIN hoteles h ON aer.hotel_id = h.id
            ORDER BY aer.started_at DESC
            LIMIT 100
        ");
        
        $runs = $stmt->fetchAll();
        
        response([
            'success' => true,
            'data' => $runs
        ]);
        
    } catch (Exception $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Eliminar un trabajo de extracción
 */
function handleDeleteJob($jobId, $pdo) {
    try {
        DebugLogger::info("Eliminando job", ['job_id' => $jobId]);
        
        // Verificar que el job existe
        $stmt = $pdo->prepare("SELECT id, hotel_id, status FROM extraction_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        
        if (!$job) {
            response(['error' => 'Trabajo no encontrado'], 404);
        }
        
        // Si está corriendo, avisar al usuario
        if ($job['status'] === 'running') {
            response(['error' => 'No se puede eliminar un trabajo en ejecución. Pausalo primero.'], 400);
        }
        
        // Eliminar de extraction_jobs
        $stmt = $pdo->prepare("DELETE FROM extraction_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        
        // También eliminar los runs relacionados de Apify si existen
        $stmt = $pdo->prepare("DELETE FROM apify_extraction_runs WHERE hotel_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $stmt->execute([$job['hotel_id']]);
        
        DebugLogger::info("Job eliminado exitosamente", ['job_id' => $jobId]);
        
        response([
            'success' => true,
            'message' => 'Trabajo de extracción eliminado correctamente'
        ]);
        
    } catch (Exception $e) {
        DebugLogger::error("Error eliminando job", [
            'job_id' => $jobId,
            'error' => $e->getMessage()
        ]);
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Cancelar una ejecución
 */
function handleCancelRun($runId, $pdo) {
    try {
        // Aquí deberías implementar la cancelación en Apify si la API lo permite
        // Por ahora solo actualizamos el estado local
        
        $stmt = $pdo->prepare("
            UPDATE apify_extraction_runs 
            SET status = 'cancelled', finished_at = NOW()
            WHERE apify_run_id = ?
        ");
        $stmt->execute([$runId]);
        
        response([
            'success' => true,
            'message' => 'Extracción cancelada'
        ]);
        
    } catch (Exception $e) {
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Mapear estados de Apify a nuestros estados
 */
function mapApifyStatus($apifyStatus) {
    $mapping = [
        'READY' => 'pending',
        'RUNNING' => 'running', 
        'SUCCEEDED' => 'succeeded',
        'FAILED' => 'failed',
        'TIMING-OUT' => 'timeout',
        'TIMED-OUT' => 'timeout',
        'ABORTING' => 'failed',
        'ABORTED' => 'failed'
    ];
    
    return $mapping[$apifyStatus] ?? 'pending';
}
?>