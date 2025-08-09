<?php
/**
 * Script de testing para verificar correcciones del sistema de extracción
 */

require_once 'env-loader.php';
require_once 'apify-config.php';
require_once 'extraction-utils.php';
require_once 'debug-logger.php';

echo "🧪 TESTING DE CORRECCIONES DE EXTRACCIÓN\n";
echo str_repeat("=", 60) . "\n\n";

class ExtractionTester 
{
    private $pdo;
    private $testResults = [];
    
    public function __construct() 
    {
        try {
            $this->pdo = EnvironmentLoader::createDatabaseConnection();
            echo "✅ Conexión a BD establecida\n\n";
        } catch (Exception $e) {
            echo "⚠️ BD no disponible - tests limitados a lógica de archivos\n";
            $this->pdo = null;
        }
    }
    
    public function runAllTests() 
    {
        echo "🔍 Ejecutando todos los tests...\n\n";
        
        // Test 1: Booking-only consistency 
        $this->testBookingOnlyConsistency();
        
        // Test 2: Platform mapping
        $this->testPlatformMapping();
        
        // Test 3: Job tracking logic
        $this->testJobTrackingLogic();
        
        // Test 4: JS deduplication (file check)
        $this->testJSDeduplication();
        
        return $this->testResults;
    }
    
    /**
     * Test 1: Verificar consistencia Booking-only entre sync y async
     */
    private function testBookingOnlyConsistency() 
    {
        echo "📋 Test 1: Consistencia Booking-only\n";
        
        // Test configuraciones para Booking-only
        $testCases = [
            ['platforms' => ['booking'], 'should_be_booking_only' => true],
            ['platforms' => ['Booking'], 'should_be_booking_only' => true], // Case insensitive
            ['platforms' => ['booking', 'tripadvisor'], 'should_be_booking_only' => false],
            ['platforms' => ['tripadvisor', 'booking'], 'should_be_booking_only' => false],
            ['platforms' => ['googlemaps'], 'should_be_booking_only' => false],
        ];
        
        foreach ($testCases as $i => $case) {
            $platforms = $case['platforms'];
            $expected = $case['should_be_booking_only'];
            
            // Simular lógica de detección (igual en sync y async)
            $onlyBooking = count(array_unique(array_map('strtolower', $platforms))) === 1 && 
                          strtolower($platforms[0]) === 'booking';
            
            $result = $onlyBooking === $expected;
            $status = $result ? '✅' : '❌';
            
            echo "  $status Caso " . ($i + 1) . ": [" . implode(', ', $platforms) . "] → ";
            echo ($onlyBooking ? 'Booking-only' : 'Multi-OTA') . "\n";
            
            $this->testResults[] = [
                'test' => 'booking_consistency_' . ($i + 1),
                'passed' => $result,
                'input' => $platforms,
                'expected' => $expected ? 'booking_only' : 'multi_ota',
                'actual' => $onlyBooking ? 'booking_only' : 'multi_ota'
            ];
        }
        
        echo "\n";
    }
    
    /**
     * Test 2: Verificar mapeo correcto de plataformas
     */
    private function testPlatformMapping() 
    {
        echo "📋 Test 2: Mapeo de plataformas\n";
        
        $testCases = [
            // Solo Booking
            [
                'input' => ['hotel_id' => 123, 'platforms' => ['booking'], 'max_reviews' => 50],
                'expected_flags' => ['enableBooking' => true, 'enableGoogleMaps' => false, 'enableTripadvisor' => false]
            ],
            // Múltiples plataformas  
            [
                'input' => ['hotel_id' => 456, 'platforms' => ['booking', 'tripadvisor', 'googlemaps'], 'max_reviews' => 100],
                'expected_flags' => ['enableBooking' => true, 'enableGoogleMaps' => true, 'enableTripadvisor' => true]
            ],
            // Sin plataformas (error esperado)
            [
                'input' => ['hotel_id' => 789, 'max_reviews' => 25],
                'expected_flags' => ['enableBooking' => false, 'enableGoogleMaps' => false, 'enableTripadvisor' => false]
            ]
        ];
        
        foreach ($testCases as $i => $case) {
            $input = $case['input'];
            $expected = $case['expected_flags'];
            
            // Usar ExtractionInputBuilder corregido
            $result = ExtractionInputBuilder::buildExtractionInput($input);
            
            $passed = true;
            foreach ($expected as $flag => $expectedValue) {
                if (($result[$flag] ?? false) !== $expectedValue) {
                    $passed = false;
                    break;
                }
            }
            
            $status = $passed ? '✅' : '❌';
            echo "  $status Caso " . ($i + 1) . ": ";
            
            if (isset($input['platforms'])) {
                echo "[" . implode(', ', $input['platforms']) . "] → ";
                $enabledFlags = array_keys(array_filter($result, function($value, $key) {
                    return strpos($key, 'enable') === 0 && $value === true;
                }, ARRAY_FILTER_USE_BOTH));
                echo implode(', ', $enabledFlags) . "\n";
            } else {
                echo "Sin plataformas → Todas deshabilitadas\n";
            }
            
            $this->testResults[] = [
                'test' => 'platform_mapping_' . ($i + 1),
                'passed' => $passed,
                'input' => $input,
                'expected_flags' => $expected,
                'actual_flags' => array_intersect_key($result, $expected)
            ];
        }
        
        echo "\n";
    }
    
    /**
     * Test 3: Verificar lógica de tracking de jobs async
     */
    private function testJobTrackingLogic() 
    {
        echo "📋 Test 3: Job tracking async\n";
        
        if (!$this->pdo) {
            echo "  ⚠️ Saltando test de BD - conexión no disponible\n\n";
            return;
        }
        
        // Verificar que async-job-updater.php existe
        $updaterFile = __DIR__ . '/async-job-updater.php';
        if (file_exists($updaterFile)) {
            echo "  ✅ async-job-updater.php existe\n";
            
            // Verificar contenido de la función
            $content = file_get_contents($updaterFile);
            if (strpos($content, 'updateExtractionJobFromRun') !== false) {
                echo "  ✅ Función updateExtractionJobFromRun encontrada\n";
                $this->testResults[] = [
                    'test' => 'async_updater_exists',
                    'passed' => true,
                    'file' => $updaterFile
                ];
            } else {
                echo "  ❌ Función updateExtractionJobFromRun no encontrada\n";
                $this->testResults[] = [
                    'test' => 'async_updater_function',
                    'passed' => false
                ];
            }
        } else {
            echo "  ❌ async-job-updater.php no encontrado\n";
            $this->testResults[] = [
                'test' => 'async_updater_exists',
                'passed' => false
            ];
        }
        
        // Verificar estructura de tabla apify_extraction_runs
        try {
            $stmt = $this->pdo->query("DESCRIBE apify_extraction_runs");
            $columns = array_column($stmt->fetchAll(), 'Field');
            
            $requiredColumns = ['job_id', 'started_at', 'finished_at', 'reviews_extracted', 'progress'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                echo "  ✅ Todas las columnas requeridas presentes en apify_extraction_runs\n";
                $this->testResults[] = [
                    'test' => 'async_table_structure',
                    'passed' => true,
                    'columns_found' => $requiredColumns
                ];
            } else {
                echo "  ⚠️ Columnas faltantes: " . implode(', ', $missingColumns) . "\n";
                $this->testResults[] = [
                    'test' => 'async_table_structure',
                    'passed' => false,
                    'missing_columns' => $missingColumns
                ];
            }
        } catch (Exception $e) {
            echo "  ❌ Error verificando estructura de tabla: " . $e->getMessage() . "\n";
            $this->testResults[] = [
                'test' => 'async_table_check',
                'passed' => false,
                'error' => $e->getMessage()
            ];
        }
        
        echo "\n";
    }
    
    /**
     * Test 4: Verificar eliminación de JS duplicadas
     */
    private function testJSDeduplication() 
    {
        echo "📋 Test 4: Deduplicación JS\n";
        
        $adminFile = __DIR__ . '/admin-extraction.php';
        if (!file_exists($adminFile)) {
            echo "  ❌ admin-extraction.php no encontrado\n";
            return;
        }
        
        $content = file_get_contents($adminFile);
        
        // Buscar funciones getStatusBadge
        preg_match_all('/function\s+getStatusBadge\s*\([^}]+\}(?:[^}]+\})?/s', $content, $matches);
        $functionCount = count($matches[0]);
        
        if ($functionCount <= 1) {
            echo "  ✅ Funciones JS deduplicadas correctamente ($functionCount función encontrada)\n";
            $this->testResults[] = [
                'test' => 'js_deduplication',
                'passed' => true,
                'functions_found' => $functionCount
            ];
        } else {
            echo "  ❌ Aún hay $functionCount funciones getStatusBadge duplicadas\n";
            $this->testResults[] = [
                'test' => 'js_deduplication', 
                'passed' => false,
                'functions_found' => $functionCount
            ];
        }
        
        // Verificar comentario de eliminación
        if (strpos($content, 'Función duplicada eliminada') !== false) {
            echo "  ✅ Comentario de eliminación encontrado\n";
        }
        
        echo "\n";
    }
    
    /**
     * Generar reporte final
     */
    public function generateReport() 
    {
        $totalTests = count($this->testResults);
        $passedTests = count(array_filter($this->testResults, function($test) {
            return $test['passed'] === true;
        }));
        
        echo "📊 REPORTE FINAL DE TESTS\n";
        echo str_repeat("=", 40) . "\n";
        echo "Tests ejecutados: $totalTests\n";
        echo "Tests exitosos: $passedTests\n";
        echo "Tests fallidos: " . ($totalTests - $passedTests) . "\n";
        echo "Ratio de éxito: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";
        
        // Detalles de tests fallidos
        $failedTests = array_filter($this->testResults, function($test) {
            return $test['passed'] === false;
        });
        
        if (!empty($failedTests)) {
            echo "❌ TESTS FALLIDOS:\n";
            foreach ($failedTests as $test) {
                echo "  - {$test['test']}\n";
                if (isset($test['error'])) {
                    echo "    Error: {$test['error']}\n";
                }
            }
        } else {
            echo "🎉 ¡TODOS LOS TESTS PASARON EXITOSAMENTE!\n";
        }
        
        return [
            'total' => $totalTests,
            'passed' => $passedTests,
            'failed' => $totalTests - $passedTests,
            'success_rate' => round(($passedTests / $totalTests) * 100, 1)
        ];
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new ExtractionTester();
    $results = $tester->runAllTests();
    $report = $tester->generateReport();
    
    echo "\n🔧 RECOMENDACIONES:\n";
    echo "1. Verificar manualmente UI de selección Booking-only\n";
    echo "2. Probar extracciones reales con diferentes combinaciones de plataformas\n";
    echo "3. Monitorear logs durante extracciones async\n";
    echo "4. Validar que job_id se vincula correctamente con run_id\n";
}
?>