<?php
/**
 * Verificar estado de un run específico de Apify
 */

require_once 'env-loader.php';
require_once 'apify-data-processor.php';

if (!isset($argv[1])) {
    echo "Uso: php check-apify-run.php <run_id>\n";
    echo "Ejemplo: php check-apify-run.php act_ABC123\n";
    exit(1);
}

$runId = $argv[1];

echo "🔍 VERIFICANDO RUN DE APIFY: $runId\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $apifyClient = new ApifyClient();
    
    echo "📋 1. OBTENIENDO ESTADO DEL RUN...\n";
    $statusResponse = $apifyClient->getRunStatus($runId);
    
    if (!$statusResponse['success']) {
        echo "❌ Error obteniendo estado: " . $statusResponse['error'] . "\n";
        exit(1);
    }
    
    $runData = $statusResponse['data'];
    
    echo "✅ Estado: " . $runData['status'] . "\n";
    echo "✅ Iniciado: " . ($runData['startedAt'] ?? 'N/A') . "\n";
    echo "✅ Finalizado: " . ($runData['finishedAt'] ?? 'Aún corriendo') . "\n";
    echo "✅ Duración: " . (isset($runData['startedAt'], $runData['finishedAt']) 
        ? (strtotime($runData['finishedAt']) - strtotime($runData['startedAt'])) . " segundos" 
        : 'N/A') . "\n";
    
    echo "\n📋 2. INPUT ENVIADO:\n";
    if (isset($runData['input'])) {
        echo json_encode($runData['input'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ No se pudo obtener el input\n";
    }
    
    echo "\n📋 3. ESTADÍSTICAS:\n";
    if (isset($runData['stats'])) {
        echo "📊 Items de salida: " . ($runData['stats']['outputItems'] ?? 0) . "\n";
        echo "📊 Items de dataset: " . ($runData['stats']['datasetItems'] ?? 0) . "\n";
        echo "📊 Requests fallidos: " . ($runData['stats']['requestsFailed'] ?? 0) . "\n";
        echo "📊 Requests exitosos: " . ($runData['stats']['requestsFinished'] ?? 0) . "\n";
    }
    
    echo "\n📋 4. LOGS (ÚLTIMOS 10):\n";
    if (isset($runData['log'])) {
        $logs = array_slice($runData['log'], -10);
        foreach ($logs as $log) {
            echo "📝 " . ($log['timestamp'] ?? '') . ": " . ($log['data'] ?? $log['message'] ?? '') . "\n";
        }
    } else {
        echo "ℹ️ Logs no disponibles en esta respuesta\n";
        echo "💡 Revisa los logs en: https://console.apify.com/actors/runs/{$runId}\n";
    }
    
    echo "\n📋 5. ACTOR UTILIZADO:\n";
    echo "🎭 Actor ID: " . ($runData['actorId'] ?? 'N/A') . "\n";
    echo "🎭 Actor Task: " . ($runData['actorTaskId'] ?? 'N/A') . "\n";
    
    echo "\n💡 DIAGNÓSTICO:\n";
    
    $status = $runData['status'];
    $outputItems = $runData['stats']['outputItems'] ?? 0;
    $datasetItems = $runData['stats']['datasetItems'] ?? 0;
    
    if ($status === 'SUCCEEDED') {
        if ($outputItems > 0 || $datasetItems > 0) {
            echo "✅ Run exitoso con datos extraídos ($outputItems items)\n";
        } else {
            echo "⚠️ Run exitoso pero SIN datos - revisa input/configuración\n";
        }
    } elseif ($status === 'FAILED') {
        echo "❌ Run falló - revisa logs para ver error específico\n";
    } elseif ($status === 'RUNNING') {
        echo "🔄 Run aún ejecutándose - espera más tiempo\n";
    } else {
        echo "🤔 Estado desconocido: $status\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error verificando run: " . $e->getMessage() . "\n";
    echo "💡 Verifica que el run_id sea correcto y que tengas permisos\n";
}
?>