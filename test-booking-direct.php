<?php
/**
 * Prueba directa con token real de Apify para Booking
 */

echo "=== PRUEBA DIRECTA DE BOOKING CON TOKEN REAL ===\n\n";

$apiToken = 'your_token_here'; // Reemplazar con token real
$bookingActorId = 'PbMHke3jW25J6hSOA'; // voyager/booking-reviews-scraper
$hotelBookingUrl = 'https://www.booking.com/hotel/mx/ambiance-suites-cancun.html';

echo "🔧 Configuración:\n";
echo "   - Actor: {$bookingActorId}\n";
echo "   - URL Hotel: {$hotelBookingUrl}\n";
echo "   - Token: " . substr($apiToken, 0, 20) . "...\n\n";

// Configuración para el actor de Booking
$input = [
    'startUrls' => [
        ['url' => $hotelBookingUrl]
    ],
    'maxItems' => 5,
    'includeReviewText' => true,
    'includeReviewerInfo' => true,
    'proxyConfiguration' => [
        'useApifyProxy' => true,
        'apifyProxyGroups' => ['RESIDENTIAL']
    ]
];

$queryParams = http_build_query([
    'timeout' => 120,
    'memory' => 2048,
    'format' => 'json'
]);

$url = "https://api.apify.com/v2/acts/{$bookingActorId}/run-sync-get-dataset-items?{$queryParams}";

echo "📤 Enviando petición a Apify...\n";
echo "   URL: {$url}\n";
echo "   Input: " . json_encode($input, JSON_PRETTY_PRINT) . "\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiToken}",
        'Content-Type: application/json'
    ],
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($input),
    CURLOPT_TIMEOUT => 150,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT => 'Hotel Review System/1.0'
]);

$startTime = time();
echo "⏱️  Iniciando extracción...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$executionTime = time() - $startTime;

echo "\n📊 RESULTADOS:\n";
echo "   - Tiempo ejecución: {$executionTime}s\n";
echo "   - Código HTTP: {$httpCode}\n";

if ($error) {
    echo "   - Error cURL: {$error}\n";
} else {
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        if (is_array($data)) {
            echo "   - ✅ Éxito: Respuesta recibida\n";
            echo "   - Reseñas extraídas: " . count($data) . "\n";
            
            if (count($data) > 0) {
                echo "\n📝 MUESTRA DE PRIMERA RESEÑA:\n";
                $firstReview = $data[0];
                if (is_array($firstReview)) {
                    foreach (array_slice($firstReview, 0, 10) as $field => $value) {
                        $displayValue = is_string($value) ? 
                            substr($value, 0, 100) . (strlen($value) > 100 ? '...' : '') : 
                            json_encode($value);
                        echo "   {$field}: {$displayValue}\n";
                    }
                } else {
                    echo "   " . json_encode($firstReview) . "\n";
                }
                
                echo "\n✅ EXTRACCIÓN EXITOSA!\n";
                echo "🎉 El sistema de Booking ahora funciona correctamente\n";
            } else {
                echo "\n⚠️  No se obtuvieron reseñas, pero la conexión funcionó\n";
                echo "   Posibles causas:\n";
                echo "   - Hotel sin reseñas recientes\n";
                echo "   - Filtros muy restrictivos\n";
                echo "   - Problemas temporales del sitio\n";
            }
        } else {
            echo "   - ⚠️  Respuesta no válida\n";
            echo "   - Respuesta: " . substr($response, 0, 500) . "...\n";
        }
    } else {
        echo "   - ❌ Error HTTP: {$httpCode}\n";
        echo "   - Respuesta: " . substr($response, 0, 500) . "...\n";
    }
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>