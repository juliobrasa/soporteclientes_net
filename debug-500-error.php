<?php
/**
 * Debug específico para el error 500 en api-extraction.php
 */

echo "🔥 DEBUG ERROR 500 EN API-EXTRACTION\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Verificar que los archivos dependientes existen
echo "📋 1. VERIFICANDO DEPENDENCIAS:\n";
$dependencies = [
    'admin-config.php',
    'apify-config.php', 
    'apify-data-processor.php',
    'admin-tools/debug-logger.php'
];

foreach ($dependencies as $file) {
    if (file_exists($file)) {
        echo "✅ $file - existe\n";
        
        // Verificar sintaxis
        $output = [];
        $returnCode = 0;
        exec("php -l $file 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "   ✅ Sintaxis PHP correcta\n";
        } else {
            echo "   ❌ ERROR DE SINTAXIS:\n";
            echo "      " . implode("\n      ", $output) . "\n";
        }
    } else {
        echo "❌ $file - NO EXISTE\n";
    }
}

echo "\n📋 2. VERIFICANDO FUNCIONES REQUERIDAS:\n";

// Verificar que las funciones críticas existen en api-extraction.php
$functionsToCheck = [
    'handleStartExtraction',
    'getDBConnection',
    'response'
];

$apiContent = file_get_contents('api-extraction.php');
foreach ($functionsToCheck as $func) {
    if (strpos($apiContent, "function $func") !== false) {
        echo "✅ Función $func - encontrada\n";
    } else {
        echo "❌ Función $func - NO ENCONTRADA\n";
    }
}

echo "\n📋 3. PROBANDO CONEXIÓN A BD:\n";

try {
    require_once 'admin-config.php';
    $pdo = getDBConnection();
    
    if ($pdo) {
        echo "✅ Conexión a BD exitosa\n";
        
        // Probar query del hotel específico
        $stmt = $pdo->prepare("SELECT id, nombre_hotel FROM hoteles WHERE id = ?");
        $stmt->execute([7]);
        $hotel = $stmt->fetch();
        
        if ($hotel) {
            echo "✅ Hotel ID 7 encontrado: " . $hotel['nombre_hotel'] . "\n";
        } else {
            echo "❌ Hotel ID 7 NO encontrado en BD\n";
        }
    } else {
        echo "❌ Error de conexión a BD\n";
    }
} catch (Exception $e) {
    echo "❌ Error probando BD: " . $e->getMessage() . "\n";
}

echo "\n📋 4. PROBANDO DEPENDENCIAS UNA POR UNA:\n";

foreach ($dependencies as $file) {
    echo "🔍 Probando incluir $file...\n";
    try {
        require_once $file;
        echo "✅ $file incluido exitosamente\n";
    } catch (Exception $e) {
        echo "❌ Error incluyendo $file: " . $e->getMessage() . "\n";
        echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    } catch (Error $e) {
        echo "💥 Error fatal incluyendo $file: " . $e->getMessage() . "\n";
        echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

echo "\n📋 5. SIMULANDO PETICIÓN INTERNA:\n";

session_start();
$_SESSION['admin_logged'] = true;

// Datos exactos que envía el admin-extraction.php
$postData = json_encode([
    'hotel_id' => 7,
    'max_reviews' => 200,
    'platforms' => ['booking'],
    'languages' => ['en', 'es'],
    'sentiment_analysis' => false,
    'generate_alerts' => false,
    'sync_mode' => false
]);

// Headers exactos
$headers = [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest',
    'Cookie: PHPSESSID=' . session_id()
];

echo "🔄 Haciendo petición POST interna a api-extraction.php...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://soporteclientes.net/api-extraction.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "📊 RESULTADO DE LA PETICIÓN:\n";
echo "HTTP Code: $httpCode\n";

if ($curlError) {
    echo "❌ cURL Error: $curlError\n";
}

if ($response) {
    $headerSize = strpos($response, "\r\n\r\n");
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize + 4);
    
    echo "\nHeaders de respuesta:\n";
    echo $responseHeaders . "\n";
    
    echo "\nBody de respuesta:\n";
    echo $responseBody . "\n";
    
    // Si es error 500, buscar pistas en el HTML de error
    if ($httpCode === 500) {
        echo "\n🔍 ANÁLISIS DEL ERROR 500:\n";
        
        if (strpos($responseBody, 'Parse error') !== false) {
            echo "❌ Error de sintaxis PHP detectado\n";
        } elseif (strpos($responseBody, 'Fatal error') !== false) {
            echo "💥 Error fatal PHP detectado\n";
        } elseif (strpos($responseBody, 'Warning') !== false) {
            echo "⚠️ Warning PHP detectado\n";
        } else {
            echo "🔍 Error 500 sin mensaje PHP visible\n";
        }
        
        // Extraer el error si está en HTML
        if (preg_match('/Fatal error:(.+?)in/', $responseBody, $matches)) {
            echo "💥 Error fatal: " . trim($matches[1]) . "\n";
        }
        if (preg_match('/Parse error:(.+?)in/', $responseBody, $matches)) {
            echo "❌ Error de sintaxis: " . trim($matches[1]) . "\n";
        }
    }
}

echo "\n💡 CONCLUSIÓN:\n";
if ($httpCode === 500) {
    echo "❌ Confirmado: Error 500 en api-extraction.php\n";
    echo "🔍 Revisa la salida anterior para encontrar la causa específica\n";
} else {
    echo "✅ No se reproduce error 500 en test directo\n";
    echo "🤔 El problema puede ser específico del contexto del navegador\n";
}
?>