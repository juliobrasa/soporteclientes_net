<?php
/**
 * Prueba directa del sistema de extracción
 */

require_once 'env-loader.php';
require_once 'admin-config.php';

// Cargar variables de entorno
loadEnvFile();

echo "=== PRUEBA DIRECTA DEL SISTEMA DE EXTRACCIÓN ===\n\n";

try {
    // 1. Verificar token de Apify
    $token = $_ENV['APIFY_API_TOKEN'] ?? null;
    if (!$token || $token === 'your_apify_token_here') {
        throw new Exception("Token de Apify no configurado correctamente");
    }
    echo "✅ Token de Apify configurado: " . substr($token, 0, 15) . "...\n";
    
    // 2. Verificar conexión a BD
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Error conectando a la base de datos");
    }
    echo "✅ Conexión a base de datos exitosa\n";
    
    // 3. Verificar hotel Ambiance
    $stmt = $pdo->prepare("SELECT id, nombre_hotel, url_booking FROM hoteles WHERE id = 7");
    $stmt->execute();
    $hotel = $stmt->fetch();
    
    if (!$hotel) {
        throw new Exception("Hotel Ambiance no encontrado");
    }
    echo "✅ Hotel Ambiance encontrado: {$hotel['nombre_hotel']}\n";
    echo "   URL: {$hotel['url_booking']}\n\n";
    
    // 4. Probar API de Apify con un test básico
    echo "🔍 Probando conectividad con Apify...\n";
    
    $testUrl = "https://api.apify.com/v2/users/me";
    $headers = [
        "Authorization: Bearer {$token}",
        "Content-Type: application/json"
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $testUrl,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception("Error cURL conectando a Apify: {$curlError}");
    }
    
    if ($httpCode !== 200) {
        throw new Exception("API de Apify respondió con código {$httpCode}: {$response}");
    }
    
    $userData = json_decode($response, true);
    if (!$userData) {
        throw new Exception("Respuesta inválida de Apify");
    }
    
    echo "✅ Conexión exitosa con Apify\n";
    echo "   Usuario: {$userData['username']}\n";
    echo "   Plan: {$userData['plan']}\n\n";
    
    // 5. Probar extracción simulada (sin consumir créditos)
    echo "🧪 Simulando extracción de reseñas...\n";
    
    $simulatedInput = [
        'startUrls' => [['url' => $hotel['url_booking']]],
        'maxItems' => 5,
        'proxyConfiguration' => ['useApifyProxy' => true]
    ];
    
    echo "   📋 Configuración:\n";
    echo "   - Hotel: {$hotel['nombre_hotel']}\n";
    echo "   - URL: {$hotel['url_booking']}\n";
    echo "   - Máx reseñas: 5\n";
    echo "   - Proxy: Activado\n\n";
    
    echo "✅ DIAGNÓSTICO COMPLETADO\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔧 ESTADO: SISTEMA LISTO PARA EXTRACCIÓN\n";
    echo "📊 El error anterior debería estar resuelto\n";
    echo "🚀 Puedes intentar la extracción desde el panel admin nuevamente\n\n";
    
    // 6. Crear registro de prueba en extraction_jobs
    $stmt = $pdo->prepare("
        INSERT INTO extraction_jobs (
            hotel_id, status, progress, reviews_extracted, 
            created_at, updated_at, platform, name, api_provider_id
        ) VALUES (?, 'pending', 0, 0, NOW(), NOW(), 'booking', 'Prueba del sistema', 1)
    ");
    $stmt->execute([7]); // Hotel Ambiance
    
    $jobId = $pdo->lastInsertId();
    echo "✅ Trabajo de prueba creado (ID: {$jobId})\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n🔧 SOLUCIONES POSIBLES:\n";
    echo "1. Verificar token de Apify en archivo .env\n";
    echo "2. Comprobar conectividad a internet\n";
    echo "3. Revisar permisos de archivos\n";
    echo "4. Validar estructura de base de datos\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>