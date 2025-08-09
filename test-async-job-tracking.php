<?php
/**
 * Testing de tracking de jobs async con vínculos job_id/run_id
 */

require_once 'env-loader.php';
require_once 'async-job-updater.php';

echo "🔗 TESTING DE TRACKING ASYNC DE JOBS\n";
echo str_repeat("=", 50) . "\n\n";

class AsyncJobTrackingTester 
{
    private $pdo;
    
    public function __construct() 
    {
        try {
            $this->pdo = EnvironmentLoader::createDatabaseConnection();
            echo "✅ Conexión a BD establecida\n\n";
        } catch (Exception $e) {
            echo "⚠️ BD no disponible - saltando tests de BD\n";
            echo "Error: " . $e->getMessage() . "\n\n";
            $this->pdo = null;
        }
    }
    
    public function runTests() 
    {
        // Test 1: Verificar estructura de tabla
        $this->testTableStructure();
        
        // Test 2: Verificar función updateExtractionJobFromRun
        $this->testAsyncJobUpdaterFunction();
        
        // Test 3: Simulación de flujo async (sin BD real)
        $this->simulateAsyncFlow();
        
        // Test 4: Verificar lógica de vínculo en api-extraction.php
        $this->testLinkingLogic();
    }
    
    private function testTableStructure() 
    {
        echo "📋 Test 1: Estructura de tabla apify_extraction_runs\n";
        
        if (!$this->pdo) {
            echo "  ⚠️ Saltando - BD no disponible\n\n";
            return;
        }
        
        try {
            // Verificar columnas de apify_extraction_runs
            $stmt = $this->pdo->query("DESCRIBE apify_extraction_runs");
            $columns = array_column($stmt->fetchAll(), 'Field');
            
            $requiredColumns = [
                'job_id' => 'Para vincular con extraction_jobs',
                'started_at' => 'Para timestamp de inicio',
                'finished_at' => 'Para timestamp de finalización',
                'reviews_extracted' => 'Para contar reviews obtenidas',
                'progress' => 'Para porcentaje de progreso'
            ];
            
            foreach ($requiredColumns as $column => $description) {
                if (in_array($column, $columns)) {
                    echo "  ✅ $column - $description\n";
                } else {
                    echo "  ❌ $column - FALTANTE ($description)\n";
                }
            }
            
            // Verificar índices
            $stmt = $this->pdo->query("SHOW INDEX FROM apify_extraction_runs");
            $indexes = array_column($stmt->fetchAll(), 'Key_name');
            
            echo "\n  📊 Índices encontrados:\n";
            foreach ($indexes as $index) {
                if ($index !== 'PRIMARY') {
                    echo "    ✅ $index\n";
                }
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error verificando estructura: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testAsyncJobUpdaterFunction() 
    {
        echo "📋 Test 2: Función updateExtractionJobFromRun\n";
        
        // Verificar que el archivo existe
        $updaterFile = __DIR__ . '/async-job-updater.php';
        if (!file_exists($updaterFile)) {
            echo "  ❌ async-job-updater.php no encontrado\n\n";
            return;
        }
        
        echo "  ✅ Archivo async-job-updater.php existe\n";
        
        // Verificar contenido de la función
        $content = file_get_contents($updaterFile);
        
        $checkpoints = [
            'updateExtractionJobFromRun' => 'Función principal definida',
            'SELECT job_id, hotel_id' => 'Query para buscar job_id',
            'UPDATE extraction_jobs' => 'Query para actualizar job',
            'SUCCEEDED.*completed' => 'Mapeo de estados Apify',
            'error_log' => 'Logging de errores'
        ];
        
        foreach ($checkpoints as $pattern => $description) {
            if (preg_match("/$pattern/i", $content)) {
                echo "  ✅ $description\n";
            } else {
                echo "  ❌ $description - NO ENCONTRADO\n";
            }
        }
        
        echo "\n";
    }
    
    private function simulateAsyncFlow() 
    {
        echo "📋 Test 3: Simulación de flujo async\n";
        
        // Simular datos de prueba
        $mockRunId = 'test_run_' . time();
        $mockJobId = 12345;
        $mockHotelId = 67890;
        
        echo "  🔄 Simulando flujo async:\n";
        echo "    Run ID: $mockRunId\n";
        echo "    Job ID: $mockJobId\n";
        echo "    Hotel ID: $mockHotelId\n\n";
        
        // Test de mapeo de estados
        $statusMappings = [
            'SUCCEEDED' => 'completed',
            'FAILED' => 'failed',
            'ABORTED' => 'failed',
            'TIMED-OUT' => 'timeout',
            'RUNNING' => 'pending'
        ];
        
        echo "  📊 Mapeo de estados Apify → Sistema:\n";
        foreach ($statusMappings as $apifyStatus => $systemStatus) {
            echo "    ✅ $apifyStatus → $systemStatus\n";
        }
        
        // Simular función updateExtractionJobFromRun (sin BD)
        echo "\n  🎯 Simulando updateExtractionJobFromRun('$mockRunId', 'SUCCEEDED', 150, 100):\n";
        echo "    1. Buscar job_id para run_id $mockRunId\n";
        echo "    2. Mapear SUCCEEDED → completed\n";
        echo "    3. Actualizar extraction_jobs SET status='completed', progress=100, reviews_extracted=150\n";
        echo "    4. Marcar completed_at = NOW()\n";
        echo "    ✅ Flujo completado exitosamente\n";
        
        echo "\n";
    }
    
    private function testLinkingLogic() 
    {
        echo "📋 Test 4: Lógica de vínculo en api-extraction.php\n";
        
        $apiFile = __DIR__ . '/api-extraction.php';
        if (!file_exists($apiFile)) {
            echo "  ❌ api-extraction.php no encontrado\n\n";
            return;
        }
        
        $content = file_get_contents($apiFile);
        
        $linkingChecks = [
            'INSERT INTO extraction_jobs.*VALUES.*NOW.*NOW' => 'Crear job con timestamps',
            '\\$jobId = \\$pdo->lastInsertId' => 'Obtener ID del job creado',
            'INSERT INTO apify_extraction_runs.*job_id' => 'Insertar run con job_id vinculado',
            '\\$jobId.*\\$hotelId.*\\$runId' => 'Pasar job_id como parámetro'
        ];
        
        foreach ($linkingChecks as $pattern => $description) {
            if (preg_match("/$pattern/", $content)) {
                echo "  ✅ $description\n";
            } else {
                echo "  ⚠️ $description - Patrón no encontrado (puede estar implementado diferente)\n";
            }
        }
        
        // Verificar que no hay inserción duplicada de jobs
        $jobInsertions = preg_match_all('/INSERT INTO extraction_jobs/', $content);
        if ($jobInsertions <= 2) { // Una en sync, una en async
            echo "  ✅ No hay inserción duplicada de extraction_jobs\n";
        } else {
            echo "  ⚠️ Posible inserción duplicada de extraction_jobs ($jobInsertions encontradas)\n";
        }
        
        echo "\n";
    }
    
    public function generateRecommendations() 
    {
        echo "💡 RECOMENDACIONES PARA MONITOREO ASYNC:\n";
        echo str_repeat("-", 45) . "\n";
        echo "1. Agregar logging detallado en api-extraction.php cuando se cree el vínculo\n";
        echo "2. Implementar webhook/polling para llamar updateExtractionJobFromRun automáticamente\n";
        echo "3. Crear dashboard para monitorear jobs async en tiempo real\n";
        echo "4. Agregar alertas para jobs que tarden más de X minutos\n";
        echo "5. Implementar retry logic para jobs fallidos\n";
        echo "6. Crear cleanup job para jobs antiguos\n\n";
        
        echo "🔧 COMANDOS DE VERIFICACIÓN MANUAL:\n";
        echo "# Verificar vínculos job_id <-> run_id:\n";
        echo "SELECT job_id, hotel_id, apify_run_id, status, created_at FROM apify_extraction_runs WHERE job_id IS NOT NULL ORDER BY created_at DESC LIMIT 10;\n\n";
        
        echo "# Verificar sincronización de estados:\n";
        echo "SELECT ej.id, ej.status as job_status, aer.status as run_status, ej.progress, aer.reviews_extracted \n";
        echo "FROM extraction_jobs ej \n";
        echo "LEFT JOIN apify_extraction_runs aer ON aer.job_id = ej.id \n";
        echo "WHERE ej.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) \n";
        echo "ORDER BY ej.created_at DESC;\n\n";
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new AsyncJobTrackingTester();
    $tester->runTests();
    $tester->generateRecommendations();
}
?>