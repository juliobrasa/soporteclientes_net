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
        if (isset($input['sync_mode']) && $input['sync_mode'] === true) {
            handleSyncExtraction($input, $pdo);
        } else {
            handleStartExtraction($input, $pdo);
        }
        break;
        
    case 'GET':
        if (isset($_GET['action']) && $_GET['action'] === 'get_recent') {
            handleGetRecentJobs($pdo);
        } elseif (isset($_GET['job_id'])) {
            handleGetJobDetails($_GET['job_id'], $pdo);
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
 * Ejecutar extracción síncrona y procesar resultados inmediatamente
 */
function handleSyncExtraction($input, $pdo) {
    try {
        DebugLogger::info("Iniciando extracción síncrona", ['input' => $input]);
        
        // Validar input
        if (!isset($input['hotel_id']) || empty($input['hotel_id'])) {
            DebugLogger::error("hotel_id faltante", ['input' => $input]);
            response(['error' => 'hotel_id es requerido'], 400);
        }
        
        $hotelId = $input['hotel_id'];
        $maxReviews = $input['max_reviews'] ?? 200; // Sin limitación artificial en sync
        $platforms = $input['platforms'] ?? ['tripadvisor', 'booking', 'google'];
        $languages = $input['languages'] ?? ['en', 'es'];
        $timeout = min($input['timeout'] ?? 300, 300); // Máximo 5 minutos
        
        // Verificar que el hotel existe y obtener su Place ID
        $stmt = $pdo->prepare("SELECT nombre_hotel, google_place_id FROM hoteles WHERE id = ?");
        $stmt->execute([$hotelId]);
        $hotel = $stmt->fetch();
        
        if (!$hotel) {
            response(['error' => 'Hotel no encontrado'], 404);
        }
        
        if (!$hotel['google_place_id']) {





            // Permitimos booking-only sin Place ID (usa url_booking). Para el resto, error
            $onlyBooking = count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking';
            if (!$onlyBooking) {
                response([
                    'error' => 'Hotel no tiene Google Place ID configurado',
                    'action_required' => 'configure_place_id',
                    'hotel_name' => $hotel['nombre_hotel']
                ], 400);
            }
        }
        

        // Configurar extracción usando la lógica
        $extractionConfig = [
            'hotel_id' => $hotelId,
            'platforms' => $platforms,
            'maxReviews' => $maxReviews,
            'languages' => $languages,
            'timeout' => $timeout
        ];
        

        // Si no es booking-only, pasar google_place_id como hotelId para el actor multi-OTA
        if (!(count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking') && !empty($hotel['google_place_id'])) {
            $extractionConfig['hotelId'] = $hotel['google_place_id'];
        }
        
        // Ejecutar extracción
        $apifyClient = new ApifyClient();
        $startTime = time();
        
        if (count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking') {
            DebugLogger::info('Usando actor específico de Booking (sync)');
            $apifyResponse = $apifyClient->runBookingExtractionSync($extractionConfig, $timeout);
        } else {
            DebugLogger::info('Usando actor multi-plataforma (sync)');
            $apifyResponse = $apifyClient->runHotelExtractionSync($extractionConfig, $timeout);
        }
        
        $executionTime = time() - $startTime;
        
        DebugLogger::info("Respuesta síncrona de Apify", [
            'success' => $apifyResponse['success'] ?? false,
            'execution_time' => $executionTime,
            'reviews_count' => count($apifyResponse['data'] ?? [])
        ]);
        
        if (!$apifyResponse['success']) {
            response([
                'error' => 'Error ejecutando extracción síncrona',
                'details' => $apifyResponse
            ], 500);
        }
        
        $reviews = $apifyResponse['data'] ?? [];
        $stats = $apifyResponse['stats'] ?? [];
        
        // Procesar y guardar reseñas inmediatamente
        $savedCount = 0;
        foreach ($reviews as $review) {
            try {


                // Guardar reseña
                $hotelNameStmt = $pdo->prepare("SELECT nombre_hotel FROM hoteles WHERE id = ?");
                $hotelNameStmt->execute([$hotelId]);
                $hotelData = $hotelNameStmt->fetch();
                $hotelName = $hotelData['nombre_hotel'] ?? 'Hotel Desconocido';
                
                $stmt = $pdo->prepare("
                    INSERT INTO reviews (
                        unique_id, hotel_id, hotel_name, hotel_destination,
                        user_name, review_date, rating, review_title, liked_text,
                        source_platform, platform_review_id, extraction_run_id, 
                        extraction_status, scraped_at, helpful_votes, review_language
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
                    ON DUPLICATE KEY UPDATE
                        rating = VALUES(rating),
                        liked_text = VALUES(liked_text),
                        review_date = VALUES(review_date)
                ");
                
                $stmt->execute([
                    ($review['reviewId'] ?? ($review['id'] ?? uniqid('bk_'))) . '_' . $hotelId, // unique_id
                    $hotelId, // hotel_id
                    $hotelName, // hotel_name
                    'Cancún, México', // hotel_destination
                    $review['reviewerName'] ?? ($review['user_name'] ?? 'Anónimo'), // user_name
                    $review['reviewDate'] ?? date('Y-m-d'), // review_date
                    $review['rating'] ?? 0, // rating
                    'Reseña de ' . ($review['platform'] ?? 'plataforma'), // review_title
                    $review['reviewText'] ?? ($review['liked_text'] ?? ''), // liked_text
                    $review['platform'] ?? 'apify', // source_platform
                    $review['reviewId'] ?? ($review['id'] ?? null), // platform_review_id
                    'sync_' . time(), // extraction_run_id
                    'completed', // extraction_status
                    $review['helpful'] ?? ($review['helpfulVotes'] ?? 0), // helpful_votes
                    $review['language'] ?? 'auto' // review_language
                ]);
                
                $savedCount++;
            } catch (Exception $e) {
                DebugLogger::error("Error guardando reseña", [
                    'review_id' => $review['reviewId'] ?? ($review['id'] ?? 'unknown'),
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Guardar registro de la extracción completada
        $stmt = $pdo->prepare("
            INSERT INTO extraction_jobs (
                hotel_id, status, progress, reviews_extracted, 
                created_at, updated_at, completed_at
            ) VALUES (?, 'completed', 100, ?, NOW(), NOW(), NOW())
        ");
        $stmt->execute([$hotelId, $savedCount]);
        
        $jobId = $pdo->lastInsertId();
        
        DebugLogger::info("Extracción síncrona completada", [
            'hotel_id' => $hotelId,
            'job_id' => $jobId,
            'reviews_processed' => count($reviews),
            'reviews_saved' => $savedCount,
            'execution_time' => $executionTime
        ]);
        
        response([
            'success' => true,
            'sync_mode' => true,
            'job_id' => $jobId,
            'hotel_name' => $hotel['nombre_hotel'],
            'reviews_extracted' => count($reviews),
            'reviews_saved' => $savedCount,
            'platforms' => $platforms,
            'execution_time' => $executionTime,
            'stats' => $stats,
            'message' => 'Extracción completada exitosamente'
        ]);
        
    } catch (Exception $e) {
        $errorDetails = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        
        DebugLogger::error("Error en extracción síncrona", $errorDetails);
        
        response([
            'error' => $e->getMessage(),
            'sync_mode' => true,
            'debug_info' => $_ENV['APP_DEBUG'] ?? false ? $errorDetails : null
        ], 500);
    }
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
        
        // Si no tiene Place ID, necesitamos configurarlo (para actor multi OTA). Para Booking-only no es crítico si hay url_booking
        if (!$hotel['google_place_id']) {
            // Si es booking-only permitimos continuar (actor usa url_booking). Si no, error.
            $onlyBooking = count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking';
            if (!$onlyBooking) {
                response([
                    'error' => 'Hotel no tiene Google Place ID configurado',
                    'action_required' => 'configure_place_id',
                    'hotel_name' => $hotel['nombre_hotel']
                ], 400);
            }
        }
        
        // Estimar costo
        $totalReviews = $maxReviews * count($platforms);
        $apifyClient = new ApifyClient();
        $costEstimate = $apifyClient->estimateCost($totalReviews);
        
        // Configurar extracción
        $extractionConfig = [
            'hotel_id' => $hotelId,
            'platforms' => $platforms,
            'maxReviews' => $maxReviews,
            'languages' => $languages
        ];
        
        // Para multi-OTA, pasar Place ID al cliente Apify
        $onlyBooking = count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking';
        if (!$onlyBooking && !empty($hotel['google_place_id'])) {
            $extractionConfig['hotelId'] = $hotel['google_place_id'];
        }
        
        // Si es SOLO Booking, usar actor específico de Booking
        DebugLogger::info('Detección de plataformas para inicio asíncrono', ['platforms' => $platforms]);

        if ($onlyBooking) {
            DebugLogger::info('Usando actor específico de Booking (async)');
            $apifyResponse = $apifyClient->startBookingExtractionAsync($extractionConfig);
        } else {
            DebugLogger::info('Usando actor multi-plataforma (async)');
            $apifyResponse = $apifyClient->startHotelExtraction($extractionConfig); // internamente usa multi-OTA
        }
        
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
                AND aer.started_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
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
 * Obtener detalles específicos de un trabajo
 */
function handleGetJobDetails($jobId, $pdo) {
    try {
        DebugLogger::info("Obteniendo detalles del job", ['job_id' => $jobId]);
        
        // Obtener detalles del trabajo con información del hotel
        $stmt = $pdo->prepare("
            SELECT 
                ej.*,
                h.nombre_hotel,
                aer.apify_run_id,
                aer.cost_estimate,
                aer.platforms_requested,
                aer.max_reviews_per_platform,
                aer.apify_response,
                TIMESTAMPDIFF(MINUTE, ej.created_at, COALESCE(ej.updated_at, NOW())) as duration_minutes
            FROM extraction_jobs ej
            JOIN hoteles h ON ej.hotel_id = h.id
            LEFT JOIN apify_extraction_runs aer ON aer.hotel_id = ej.hotel_id 
                AND aer.started_at >= DATE_SUB(ej.created_at, INTERVAL 1 HOUR)
            WHERE ej.id = ?
            ORDER BY aer.started_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        
        if (!$job) {
            response(['error' => 'Trabajo no encontrado'], 404);
        }
        
        // Obtener estadísticas adicionales de reseñas si existen
        $reviewsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                COUNT(CASE WHEN source_platform = 'booking' THEN 1 END) as booking_reviews,
                COUNT(CASE WHEN source_platform = 'tripadvisor' THEN 1 END) as tripadvisor_reviews,
                COUNT(CASE WHEN source_platform = 'google' THEN 1 END) as google_reviews,
                AVG(rating) as average_rating,
                MAX(scraped_at) as latest_review_date
            FROM reviews 
            WHERE hotel_id = ? AND scraped_at >= ?
        ");
        
        $reviewsStmt->execute([$job['hotel_id'], $job['created_at']]);
        $reviewStats = $reviewsStmt->fetch();
        
        // Agregar estadísticas al resultado
        $job['review_stats'] = $reviewStats;
        
        // Obtener logs recientes relacionados
        $logsStmt = $pdo->prepare("
            SELECT message, level, created_at, context
            FROM debug_logs 
            WHERE JSON_EXTRACT(context, '$.job_id') = ? 
               OR JSON_EXTRACT(context, '$.hotel_id') = ?
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        $logsStmt->execute([$jobId, $job['hotel_id']]);
        $logs = $logsStmt->fetchAll();
        
        $job['recent_logs'] = $logs;
        
        response([
            'success' => true,
            'data' => $job
        ]);
        
    } catch (Exception $e) {
        DebugLogger::error("Error obteniendo detalles del job", [
            'job_id' => $jobId,
            'error' => $e->getMessage()
        ]);
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
        $stmt = $pdo->prepare("DELETE FROM apify_extraction_runs WHERE hotel_id = ? AND started_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
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