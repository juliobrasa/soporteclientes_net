<?php
/**
 * Prueba real del sistema de extracción de Booking
 */
session_start();
$_SESSION['admin_logged'] = true; // Simular sesión admin para la prueba

require_once 'admin-config.php';

echo "=== PRUEBA REAL DE EXTRACCIÓN DE BOOKING ===\n\n";

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Error conectando a la base de datos");
    }
    
    // Seleccionar un hotel para la prueba
    $stmt = $pdo->query("
        SELECT id, nombre_hotel, url_booking 
        FROM hoteles 
        WHERE activo = 1 AND url_booking IS NOT NULL AND url_booking != ''
        ORDER BY id ASC
        LIMIT 1
    ");
    $hotel = $stmt->fetch();
    
    if (!$hotel) {
        throw new Exception("No hay hoteles disponibles para la prueba");
    }
    
    echo "🏨 Hotel seleccionado: {$hotel['nombre_hotel']} (ID: {$hotel['id']})\n";
    echo "📍 URL: {$hotel['url_booking']}\n";
    echo "📊 Parámetros: 5 reseñas máximo (modo síncrono)\n";
    echo "⏱️  Tiempo estimado: 2-5 minutos\n\n";
    
    echo "🚀 Iniciando extracción real...\n\n";
    
    // Preparar datos para la API
    $extractionData = [
        'hotel_id' => (int)$hotel['id'],
        'max_reviews' => 5, // Solo 5 reseñas para la prueba
        'sync_mode' => true,
        'timeout' => 300
    ];
    
    // Hacer la llamada POST a la API de Booking
    $url = 'http://localhost/booking-extraction-api.php';
    
    $postData = json_encode($extractionData);
    $headers = [
        'Content-Type: application/json',
        'X-Admin-Session: ' . session_id(),
        'X-Requested-With: XMLHttpRequest'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 360, // 6 minutos de timeout
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    
    echo "📤 Enviando petición a la API de Booking...\n";
    $startTime = time();
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $executionTime = time() - $startTime;
    
    if ($curlError) {
        throw new Exception("Error cURL: {$curlError}");
    }
    
    echo "📡 Respuesta recibida (HTTP {$httpCode}) en {$executionTime}s\n\n";
    
    if ($httpCode !== 200) {
        echo "❌ Error HTTP {$httpCode}\n";
        echo "Respuesta: " . substr($response, 0, 500) . "\n";
        exit(1);
    }
    
    $result = json_decode($response, true);
    if (!$result) {
        throw new Exception("Respuesta JSON inválida: " . substr($response, 0, 200));
    }
    
    echo "=== RESULTADOS ===\n\n";
    
    if ($result['success']) {
        echo "✅ ¡Extracción exitosa!\n\n";
        echo "📊 Estadísticas:\n";
        echo "   - Job ID: {$result['job_id']}\n";
        echo "   - Hotel: {$result['hotel_name']}\n";
        echo "   - Plataforma: {$result['platform']}\n";
        echo "   - Reseñas extraídas: {$result['reviews_extracted']}\n";
        echo "   - Reseñas guardadas: {$result['reviews_saved']}\n";
        echo "   - Tiempo de ejecución: {$result['execution_time']}s\n";
        echo "   - Mensaje: {$result['message']}\n\n";
        
        // Verificar las reseñas en la base de datos
        echo "🔍 Verificando reseñas guardadas en la base de datos...\n";
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM reviews 
            WHERE hotel_id = ? AND source_platform = 'booking' 
            AND scraped_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ");
        $stmt->execute([$hotel['id']]);
        $reviewCount = $stmt->fetch()['count'];
        
        echo "📈 Reseñas de Booking en BD: {$reviewCount}\n";
        
        if ($reviewCount > 0) {
            // Mostrar muestra de reseñas
            echo "\n📝 Muestra de reseñas extraídas:\n";
            $stmt = $pdo->prepare("
                SELECT user_name, rating, LEFT(liked_text, 100) as preview, review_date
                FROM reviews 
                WHERE hotel_id = ? AND source_platform = 'booking'
                AND scraped_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                ORDER BY scraped_at DESC
                LIMIT 3
            ");
            $stmt->execute([$hotel['id']]);
            $sampleReviews = $stmt->fetchAll();
            
            foreach ($sampleReviews as $i => $review) {
                echo "\n" . ($i + 1) . ". Usuario: {$review['user_name']}\n";
                echo "   Rating: {$review['rating']}/5\n";
                echo "   Fecha: {$review['review_date']}\n";
                echo "   Texto: " . ($review['preview'] ?: 'Sin texto') . "...\n";
            }
        }
        
        // Verificar el trabajo en extraction_jobs
        echo "\n📋 Verificando registro del trabajo...\n";
        $stmt = $pdo->prepare("
            SELECT * FROM extraction_jobs 
            WHERE id = ? AND platform = 'booking'
        ");
        $stmt->execute([$result['job_id']]);
        $job = $stmt->fetch();
        
        if ($job) {
            echo "✅ Trabajo registrado correctamente\n";
            echo "   - Estado: {$job['status']}\n";
            echo "   - Progreso: {$job['progress']}%\n";
            echo "   - Creado: {$job['created_at']}\n";
        } else {
            echo "⚠️  Trabajo no encontrado en la base de datos\n";
        }
        
    } else {
        echo "❌ Error en la extracción:\n";
        echo "   Mensaje: " . ($result['error'] ?? 'Error desconocido') . "\n";
        
        if (isset($result['debug_info'])) {
            echo "\n🔍 Información de debug:\n";
            print_r($result['debug_info']);
        }
    }
    
    echo "\n=== FIN DE LA PRUEBA ===\n";
    
} catch (Exception $e) {
    echo "❌ Error durante la prueba: " . $e->getMessage() . "\n";
}
?>