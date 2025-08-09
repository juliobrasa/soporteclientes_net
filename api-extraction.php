<?php
/**
 * API para manejo de extracciones con Apify Hotel Review Aggregator
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://soporteclientes.net');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Accept, Authorization, X-Admin-Session');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'admin-config.php';
require_once 'apify-config.php';
require_once 'apify-data-processor.php';
require_once 'admin-tools/debug-logger.php';

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

// Verificar autenticación - CORREGIDO: Sin bypass vulnerable
session_start();

// SOLO verificar sesión normal - NO hay fallback vulnerable
$isAuthenticated = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;

// Autenticación adicional con X-Admin-Session que debe coincidir con session_id
if (!$isAuthenticated && isset($_SERVER['HTTP_X_ADMIN_SESSION'])) {
    $headerSid = $_SERVER['HTTP_X_ADMIN_SESSION'];
    if ($headerSid === session_id()) {
        $isAuthenticated = true;
    }
}

// Log de intento de acceso
DebugLogger::info("Intento de acceso a API", [
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'session_id' => session_id(),
    'has_session' => isset($_SESSION['admin_logged']),
    'session_value' => $_SESSION['admin_logged'] ?? null,
    'header_session' => isset($_SERVER['HTTP_X_ADMIN_SESSION']) ? 'presente' : 'ausente'
]);

if (!$isAuthenticated) {
    DebugLogger::warning("Acceso no autorizado denegado", [
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'session_id' => session_id(),
        'attempted_endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown'
    ]);
    
    response([
        'error' => 'Acceso no autorizado',
        'message' => 'Sesión de administrador requerida'
    ], 401);
}

// Conectar a base de datos - Función de respaldo si no se carga desde admin-config
if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        try {
            // Usar EnvironmentLoader para obtener credenciales seguras
            return EnvironmentLoader::createDatabaseConnection();
        } catch (Exception $e) {
            error_log("Error de conexión BD (api-extraction): " . $e->getMessage());
            return null;
        }
    }
}

$pdo = getDBConnection();
if (!$pdo) {
    DebugLogger::error("Error de conexión a la base de datos", [
        'function_exists' => function_exists('getDBConnection'),
        'environment_loaded' => class_exists('EnvironmentLoader'),
        'included_files' => get_included_files()
    ]);
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
        response(['error' => 'Método no soportado'], 405);
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
        
        // Verificar que el hotel existe y obtener su Place ID y URL de Booking
        $stmt = $pdo->prepare("SELECT nombre_hotel, google_place_id, url_booking, hoja_destino FROM hoteles WHERE id = ?");
        $stmt->execute([$hotelId]);
        $hotel = $stmt->fetch();
        
        if (!$hotel) {
            response(['error' => 'Hotel no encontrado'], 404);
        }
        
        // CORRECCIÓN: Validación explícita para Booking-only (sync)
        $onlyBooking = count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking';
        
        if ($onlyBooking) {
            // Para Booking-only, verificar que tenga url_booking
            if (empty($hotel['url_booking'])) {
                response([
                    'error' => 'Hotel no tiene URL de Booking configurada',
                    'action_required' => 'configure_booking_url',
                    'hotel_name' => $hotel['nombre_hotel']
                ], 400);
            }
        } else {
            // Para multi-OTA, verificar que tenga Place ID
            if (!$hotel['google_place_id']) {
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
        
        // Ejecutar extracción - con debug detallado
        DebugLogger::info("Instanciando ApifyClient para sync extraction", [
            'class_exists_ApifyClient' => class_exists('ApifyClient'),
            'apify_config_included' => in_array('/root/soporteclientes_net/apify-config.php', get_included_files()),
            'extraction_config' => $extractionConfig
        ]);
        
        try {
            $apifyClient = new ApifyClient();
            DebugLogger::info("ApifyClient instanciado correctamente");
        } catch (Exception $e) {
            DebugLogger::error("Error instanciando ApifyClient", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        $startTime = time();
        
        DebugLogger::info("Iniciando extracción con configuración", [
            'platforms' => $platforms,
            'is_booking_only' => count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking',
            'timeout' => $timeout,
            'start_time' => $startTime
        ]);
        
        try {
            if (count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking') {
                DebugLogger::info('Llamando a runBookingExtractionSync');
                $apifyResponse = $apifyClient->runBookingExtractionSync($extractionConfig, $timeout);
            } else {
                DebugLogger::info('Llamando a runHotelExtractionSync');
                $apifyResponse = $apifyClient->runHotelExtractionSync($extractionConfig, $timeout);
            }
            
            DebugLogger::info("Extracción Apify completada", [
                'response_received' => !empty($apifyResponse),
                'response_keys' => array_keys($apifyResponse ?? [])
            ]);
            
        } catch (Exception $e) {
            DebugLogger::error("Error ejecutando extracción Apify", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        $executionTime = time() - $startTime;
        
        DebugLogger::info("Respuesta síncrona de Apify", [
            'success' => $apifyResponse['success'] ?? false,
            'execution_time' => $executionTime,
            'reviews_count' => count($apifyResponse['data'] ?? []),
            'full_response' => $apifyResponse
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
        DebugLogger::info("Procesando reseñas para guardar", [
            'total_reviews' => count($reviews),
            'hotel_id' => $hotelId
        ]);
        
        $savedCount = 0;
        foreach ($reviews as $reviewIndex => $review) {
            try {
                DebugLogger::info("Procesando reseña", [
                    'index' => $reviewIndex,
                    'review_id' => $review['reviewId'] ?? ($review['id'] ?? 'unknown'),
                    'has_reviewText' => !empty($review['reviewText']),
                    'rating' => $review['rating'] ?? null
                ]);

                // Guardar reseña - obtener nombre del hotel
                try {
                    $hotelNameStmt = $pdo->prepare("SELECT nombre_hotel FROM hoteles WHERE id = ?");
                    $hotelNameStmt->execute([$hotelId]);
                    $hotelData = $hotelNameStmt->fetch();
                    $hotelName = $hotelData['nombre_hotel'] ?? 'Hotel Desconocido';
                } catch (Exception $e) {
                    DebugLogger::error("Error obteniendo nombre del hotel", [
                        'hotel_id' => $hotelId,
                        'error' => $e->getMessage()
                    ]);
                    $hotelName = 'Hotel Desconocido';
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO reviews (
                        unique_id, hotel_id, hotel_name, hotel_destination,
                        user_name, review_date, rating, review_title, liked_text,
                        source_platform, platform_review_id, extraction_run_id, 
                        extraction_status, scraped_at, helpful_votes, review_language, original_rating
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        rating = VALUES(rating),
                        liked_text = VALUES(liked_text),
                        review_date = VALUES(review_date)
                ");
                
                // Normalizar datos de la reseña
                $reviewTitle = $review['reviewTitle'] ?? ($review['title'] ?? null);
                $hotelDestination = $hotel['hoja_destino'] ?? null; // Usar campo real de la BD
                
                // Determinar plataforma basado en configuración real
                $sourcePlatform = (count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking') 
                    ? 'booking' 
                    : ($review['platform'] ?? 'apify');
                    
                $normalizedRating = floatval($review['rating'] ?? 0);
                
                // Guardar rating original antes de normalizar
                $originalRating = $normalizedRating;
                
                // Normalizar rating de Booking (1-10) a escala 1-5
                if ($normalizedRating > 5) {
                    $normalizedRating = round(($normalizedRating / 10) * 5, 1);
                }
                
                $insertData = [
                    ($review['reviewId'] ?? ($review['id'] ?? uniqid('booking_'))) . '_' . $hotelId, // unique_id
                    $hotelId, // hotel_id
                    $hotelName, // hotel_name
                    $hotelDestination, // hotel_destination (de BD real)
                    $review['reviewerName'] ?? ($review['userName'] ?? 'Anónimo'), // user_name
                    $review['reviewDate'] ?? date('Y-m-d'), // review_date
                    $normalizedRating, // rating normalizado
                    $reviewTitle, // review_title (NULL si no existe, no hardcoded)
                    $review['reviewText'] ?? ($review['reviewTextParts']['Liked'] ?? ''), // liked_text
                    $sourcePlatform, // source_platform (determinado dinámicamente)
                    $review['reviewId'] ?? ($review['id'] ?? null), // platform_review_id
                    'sync_' . time(), // extraction_run_id
                    'completed', // extraction_status
                    $review['helpfulVotes'] ?? ($review['helpful'] ?? 0), // helpful_votes
                    $review['language'] ?? 'auto', // review_language
                    $originalRating // original_rating antes de normalizar
                ];
                
                DebugLogger::info("Ejecutando INSERT de reseña", [
                    'unique_id' => $insertData[0],
                    'hotel_id' => $insertData[1],
                    'hotel_name' => $insertData[2],
                    'rating' => $insertData[6],
                    'platform' => $insertData[9]
                ]);
                
                try {
                    $stmt->execute($insertData);
                    $savedCount++;
                    DebugLogger::info("Reseña guardada exitosamente", ['saved_count' => $savedCount]);
                } catch (Exception $e) {
                    DebugLogger::error("Error ejecutando INSERT de reseña", [
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'unique_id' => $insertData[0]
                    ]);
                    throw $e;
                }
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
        
        // Verificar que el hotel existe y obtener su Place ID y URL de Booking
        $stmt = $pdo->prepare("SELECT nombre_hotel, google_place_id, url_booking, hoja_destino FROM hoteles WHERE id = ?");
        $stmt->execute([$hotelId]);
        $hotel = $stmt->fetch();
        
        if (!$hotel) {
            response(['error' => 'Hotel no encontrado'], 404);
        }
        
        // CORRECCIÓN: Validación explícita para Booking-only
        $onlyBooking = count(array_unique(array_map('strtolower', $platforms))) === 1 && strtolower($platforms[0]) === 'booking';
        
        if ($onlyBooking) {
            // Para Booking-only, verificar que tenga url_booking
            if (empty($hotel['url_booking'])) {
                response([
                    'error' => 'Hotel no tiene URL de Booking configurada',
                    'action_required' => 'configure_booking_url',
                    'hotel_name' => $hotel['nombre_hotel']
                ], 400);
            }
        } else {
            // Para multi-OTA, verificar que tenga Place ID
            if (!$hotel['google_place_id']) {
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
        
        // Guardar en base de datos con vínculo job_id mejorado
        try {
            // MEJORA: Crear job primero para obtener job_id y vincularlo con run_id
            $jobStmt = $pdo->prepare("
                INSERT INTO extraction_jobs (
                    hotel_id, status, progress, platforms, created_at, started_at
                ) VALUES (?, 'running', 0, ?, NOW(), NOW())
            ");
            $jobStmt->execute([$hotelId, json_encode($platforms)]);
            $jobId = $pdo->lastInsertId();
            
            DebugLogger::info("Job creado para seguimiento", ['job_id' => $jobId]);
            
            // Insertar run con job_id vinculado
            $stmt = $pdo->prepare("
                INSERT INTO apify_extraction_runs (
                    job_id, hotel_id, apify_run_id, status, platforms_requested,
                    max_reviews_per_platform, cost_estimate, apify_response,
                    started_at, created_at
                ) VALUES (?, ?, ?, 'running', ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $jobId,            // job_id vinculado
                $hotelId,
                $runId,
                json_encode($platforms),
                $maxReviews,
                $costEstimate,
                json_encode($apifyResponse)
            ]);
            
            DebugLogger::info("apify_extraction_runs insertado con job_id", ['run_id' => $runId, 'job_id' => $jobId]);
            
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
        
        // Si es estado final, actualizar finished_at
        $finalStates = ['succeeded', 'failed', 'timeout'];
        if (in_array($status, $finalStates)) {
            $stmt = $pdo->prepare("
                UPDATE apify_extraction_runs 
                SET status = ?, apify_response = ?, finished_at = NOW()
                WHERE apify_run_id = ?
            ");
        } else {
            $stmt = $pdo->prepare("
                UPDATE apify_extraction_runs 
                SET status = ?, apify_response = ?
                WHERE apify_run_id = ?
            ");
        }
        $stmt->execute([$status, json_encode($apifyStatus), $runId]);
        
        // Sincronizar estado con extraction_jobs si es estado final
        if (in_array($status, $finalStates)) {
            $stmt = $pdo->prepare("SELECT job_id FROM apify_extraction_runs WHERE apify_run_id = ?");
            $stmt->execute([$runId]);
            $run = $stmt->fetch();
            
            if ($run && $run['job_id']) {
                $jobStatus = ($status === 'succeeded') ? 'completed' : (($status === 'failed') ? 'failed' : 'timeout');
                $stmt = $pdo->prepare("
                    UPDATE extraction_jobs 
                    SET status = ?, progress = 100, completed_at = NOW(), updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$jobStatus, $run['job_id']]);
                
                DebugLogger::info("Job status sincronizado", ['job_id' => $run['job_id'], 'status' => $jobStatus]);
            }
        }
        
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
        
        // Obtener logs recientes relacionados (compatible con diferentes versiones MySQL)
        $logsStmt = $pdo->prepare("
            SELECT message, level, created_at, context
            FROM debug_logs 
            WHERE (context LIKE CONCAT('%\"job_id\":\"', ?, '\"%') OR context LIKE CONCAT('%\"job_id\":', ?, '%'))
               OR (context LIKE CONCAT('%\"hotel_id\":\"', ?, '\"%') OR context LIKE CONCAT('%\"hotel_id\":', ?, '%'))
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        
        $logsStmt->execute([$jobId, $jobId, $job['hotel_id'], $job['hotel_id']]);
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
 * Actualizar estado de una ejecución (PUT endpoint)
 */
function handleUpdateRun($runId, $input, $pdo) {
    try {
        DebugLogger::info("Actualizando run", ['run_id' => $runId, 'input' => $input]);
        
        // Validar que el run existe
        $stmt = $pdo->prepare("SELECT id, hotel_id, status FROM apify_extraction_runs WHERE apify_run_id = ?");
        $stmt->execute([$runId]);
        $run = $stmt->fetch();
        
        if (!$run) {
            response(['error' => 'Run no encontrado'], 404);
        }
        
        // Campos actualizables
        $updateFields = [];
        $updateValues = [];
        
        // Status
        if (isset($input['status'])) {
            $newStatus = mapApifyStatus($input['status']);
            $updateFields[] = "status = ?";
            $updateValues[] = $newStatus;
            
            // Si es un estado final, marcar finished_at
            if (in_array($newStatus, ['succeeded', 'failed', 'timeout'])) {
                $updateFields[] = "finished_at = NOW()";
            }
        }
        
        // Progress
        if (isset($input['progress'])) {
            $progress = max(0, min(100, intval($input['progress'])));
            $updateFields[] = "progress = ?";
            $updateValues[] = $progress;
        }
        
        // Reviews extracted count
        if (isset($input['reviews_extracted'])) {
            $reviewsCount = max(0, intval($input['reviews_extracted']));
            $updateFields[] = "reviews_extracted = ?";
            $updateValues[] = $reviewsCount;
        }
        
        // Respuesta completa de Apify
        if (isset($input['apify_response'])) {
            $updateFields[] = "apify_response = ?";
            $updateValues[] = json_encode($input['apify_response']);
        }
        
        if (empty($updateFields)) {
            response(['error' => 'No hay campos para actualizar'], 400);
        }
        
        // Actualizar run
        $updateValues[] = $runId;
        $sql = "UPDATE apify_extraction_runs SET " . implode(', ', $updateFields) . " WHERE apify_run_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);
        
        // Si hay job_id vinculado, sincronizar extraction_jobs
        if ($run['job_id'] ?? null) {
            $jobUpdateFields = [];
            $jobUpdateValues = [];
            
            if (isset($input['status'])) {
                $jobStatus = mapApifyStatusToJobStatus($input['status']);
                $jobUpdateFields[] = "status = ?";
                $jobUpdateValues[] = $jobStatus;
                
                if (in_array($jobStatus, ['completed', 'failed', 'timeout'])) {
                    $jobUpdateFields[] = "completed_at = NOW()";
                }
            }
            
            if (isset($input['progress'])) {
                $jobUpdateFields[] = "progress = ?";
                $jobUpdateValues[] = $progress;
            }
            
            if (isset($input['reviews_extracted'])) {
                $jobUpdateFields[] = "reviews_extracted = ?";
                $jobUpdateValues[] = $reviewsCount;
            }
            
            if (!empty($jobUpdateFields)) {
                $jobUpdateFields[] = "updated_at = NOW()";
                $jobUpdateValues[] = $run['job_id'];
                
                $jobSql = "UPDATE extraction_jobs SET " . implode(', ', $jobUpdateFields) . " WHERE id = ?";
                $jobStmt = $pdo->prepare($jobSql);
                $jobStmt->execute($jobUpdateValues);
                
                DebugLogger::info("Job sincronizado", ['job_id' => $run['job_id']]);
            }
        }
        
        DebugLogger::info("Run actualizado exitosamente", ['run_id' => $runId]);
        
        response([
            'success' => true,
            'message' => 'Run actualizado correctamente',
            'run_id' => $runId,
            'updated_fields' => count($updateFields)
        ]);
        
    } catch (Exception $e) {
        DebugLogger::error("Error actualizando run", [
            'run_id' => $runId,
            'error' => $e->getMessage()
        ]);
        response(['error' => $e->getMessage()], 500);
    }
}

/**
 * Mapear estados de Apify específicamente para jobs
 */
function mapApifyStatusToJobStatus($apifyStatus) {
    $mapping = [
        'READY' => 'pending',
        'RUNNING' => 'running',
        'SUCCEEDED' => 'completed',
        'FAILED' => 'failed',
        'TIMING-OUT' => 'timeout',
        'TIMED-OUT' => 'timeout',
        'ABORTING' => 'failed',
        'ABORTED' => 'failed'
    ];
    
    return $mapping[$apifyStatus] ?? 'pending';
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