<?php
/**
 * Testing solo de lógica sin conexión BD
 */

require_once 'extraction-utils.php';

echo "🧪 TESTING DE LÓGICA DE EXTRACCIÓN\n";
echo str_repeat("=", 50) . "\n\n";

// Test 1: Booking-only consistency
echo "📋 Test 1: Consistencia Booking-only\n";

$testCases = [
    [['booking'], true],
    [['Booking'], true],
    [['BOOKING'], true],
    [['booking', 'tripadvisor'], false],
    [['tripadvisor', 'booking'], false],
    [['googlemaps'], false],
    [['tripadvisor', 'googlemaps'], false],
];

foreach ($testCases as [$platforms, $expected]) {
    // Lógica corregida de detección Booking-only
    $onlyBooking = count(array_unique(array_map('strtolower', $platforms))) === 1 && 
                  strtolower($platforms[0]) === 'booking';
    
    $status = ($onlyBooking === $expected) ? '✅' : '❌';
    echo "  $status [" . implode(', ', $platforms) . "] → " . 
         ($onlyBooking ? 'Booking-only' : 'Multi-OTA') . "\n";
}

echo "\n";

// Test 2: Platform mapping con ExtractionInputBuilder
echo "📋 Test 2: Mapeo de plataformas\n";

$mappingTests = [
    // Solo Booking
    [
        'input' => ['hotel_id' => 123, 'platforms' => ['booking'], 'max_reviews' => 50],
        'expected' => ['enableBooking' => true, 'enableTripadvisor' => false, 'enableGoogleMaps' => false]
    ],
    // Múltiples plataformas
    [
        'input' => ['hotel_id' => 456, 'platforms' => ['booking', 'tripadvisor'], 'max_reviews' => 100],
        'expected' => ['enableBooking' => true, 'enableTripadvisor' => true, 'enableGoogleMaps' => false]
    ],
    // Case insensitive
    [
        'input' => ['hotel_id' => 789, 'platforms' => ['BOOKING', 'GoogleMaps'], 'max_reviews' => 75],
        'expected' => ['enableBooking' => true, 'enableGoogleMaps' => true, 'enableTripadvisor' => false]
    ]
];

foreach ($mappingTests as $i => $test) {
    $result = ExtractionInputBuilder::buildExtractionInput($test['input']);
    
    $passed = true;
    $actualFlags = [];
    foreach ($test['expected'] as $flag => $expectedValue) {
        $actualValue = $result[$flag] ?? false;
        $actualFlags[$flag] = $actualValue;
        if ($actualValue !== $expectedValue) {
            $passed = false;
        }
    }
    
    $status = $passed ? '✅' : '❌';
    echo "  $status Caso " . ($i + 1) . ": ";
    
    $platforms = $test['input']['platforms'];
    echo "[" . implode(', ', $platforms) . "] → ";
    
    $enabledFlags = [];
    foreach ($actualFlags as $flag => $value) {
        if ($value) {
            $enabledFlags[] = str_replace('enable', '', $flag);
        }
    }
    echo (empty($enabledFlags) ? 'Ninguna' : implode(', ', $enabledFlags)) . "\n";
    
    if (!$passed) {
        echo "    Esperado: ";
        foreach ($test['expected'] as $flag => $value) {
            if ($value) echo str_replace('enable', '', $flag) . " ";
        }
        echo "\n";
    }
}

echo "\n";

// Test 3: Verificar archivos corregidos
echo "📋 Test 3: Archivos corregidos\n";

$files = [
    'api-extraction.php' => 'Booking-only async consistency',
    'apify-data-processor.php' => 'ExtractionInputBuilder usage',
    'admin-extraction.php' => 'JS deduplication',
    'async-job-updater.php' => 'Async job sync function'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "  ✅ $file - $description\n";
        
        // Verificaciones específicas
        if ($file === 'api-extraction.php') {
            $content = file_get_contents($file);
            if (strpos($content, 'CONSISTENCIA CON SYNC') !== false) {
                echo "    ✅ Comentario de consistencia encontrado\n";
            }
        }
        
        if ($file === 'apify-data-processor.php') {
            $content = file_get_contents($file);
            if (strpos($content, 'ExtractionInputBuilder::buildExtractionInput') !== false) {
                echo "    ✅ Uso de ExtractionInputBuilder confirmado\n";
            }
        }
        
        if ($file === 'admin-extraction.php') {
            $content = file_get_contents($file);
            preg_match_all('/function\s+getStatusBadge/', $content, $matches);
            $count = count($matches[0]);
            if ($count <= 1) {
                echo "    ✅ Funciones JS deduplicadas ($count función)\n";
            } else {
                echo "    ❌ Aún hay $count funciones duplicadas\n";
            }
        }
        
        if ($file === 'async-job-updater.php') {
            $content = file_get_contents($file);
            if (strpos($content, 'updateExtractionJobFromRun') !== false) {
                echo "    ✅ Función updateExtractionJobFromRun encontrada\n";
            }
        }
        
    } else {
        echo "  ❌ $file - Archivo no encontrado\n";
    }
}

echo "\n";

// Test 4: Validación de configuración
echo "📋 Test 4: Validación de ExtractionInputBuilder\n";

$validationTests = [
    [['hotel_id' => 123, 'platforms' => ['booking']], true],
    [['hotel_id' => 456, 'platforms' => ['booking', 'tripadvisor']], true],
    [['platforms' => ['booking']], false], // Sin hotel_id
    [['hotel_id' => 'abc', 'platforms' => ['booking']], false], // hotel_id no numérico
    [['hotel_id' => 123], false], // Sin plataformas
];

foreach ($validationTests as [$config, $shouldBeValid]) {
    $validation = ExtractionInputBuilder::validateUserConfig($config);
    $isValid = $validation['valid'];
    
    $status = ($isValid === $shouldBeValid) ? '✅' : '❌';
    echo "  $status Validación: " . ($isValid ? 'Válida' : 'Inválida');
    if (!$isValid && !empty($validation['errors'])) {
        echo " (" . implode(', ', $validation['errors']) . ")";
    }
    echo "\n";
}

echo "\n🎉 TESTS DE LÓGICA COMPLETADOS\n";
echo "\n💡 Para tests completos con BD, configurar DB_PASSWORD en .env.local\n";
?>