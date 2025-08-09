<?php
/**
 * Testing avanzado de mapeo de plataformas - casos edge
 */

require_once 'extraction-utils.php';

echo "🔬 TESTING AVANZADO DE MAPEO DE PLATAFORMAS\n";
echo str_repeat("=", 55) . "\n\n";

// Edge cases específicos
$edgeCases = [
    // Casos de nombre de plataforma
    'Nombres variados' => [
        ['hotel_id' => 1, 'platforms' => ['booking.com'], 'expected' => ['booking']],
        ['hotel_id' => 2, 'platforms' => ['Booking.com'], 'expected' => ['booking']],
        ['hotel_id' => 3, 'platforms' => ['hotels.com'], 'expected' => ['hotelscom']],
        ['hotel_id' => 4, 'platforms' => ['hotelscom'], 'expected' => ['hotelscom']],
        ['hotel_id' => 5, 'platforms' => ['google'], 'expected' => []], // No mapeado
        ['hotel_id' => 6, 'platforms' => ['googlemaps'], 'expected' => ['googlemaps']],
        ['hotel_id' => 7, 'platforms' => ['GoogleMaps'], 'expected' => ['googlemaps']],
    ],
    
    // Combinaciones múltiples
    'Combinaciones complejas' => [
        ['hotel_id' => 10, 'platforms' => ['booking', 'tripadvisor', 'googlemaps'], 'expected' => ['booking', 'tripadvisor', 'googlemaps']],
        ['hotel_id' => 11, 'platforms' => ['BOOKING', 'TripAdvisor', 'hotels.com'], 'expected' => ['booking', 'tripadvisor', 'hotelscom']],
        ['hotel_id' => 12, 'platforms' => ['expedia', 'agoda', 'booking'], 'expected' => ['expedia', 'agoda', 'booking']],
    ],
    
    // Casos límite
    'Casos límite' => [
        ['hotel_id' => 20, 'platforms' => [], 'expected' => []],
        ['hotel_id' => 21, 'platforms' => ['platform_unknown'], 'expected' => []],
        ['hotel_id' => 22, 'platforms' => ['booking', 'platform_unknown', 'tripadvisor'], 'expected' => ['booking', 'tripadvisor']],
    ],
    
    // Configuraciones de parámetros
    'Parámetros de extracción' => [
        ['hotel_id' => 30, 'platforms' => ['booking'], 'max_reviews' => 500, 'include_photos' => true],
        ['hotel_id' => 31, 'platforms' => ['tripadvisor'], 'max_reviews' => 50, 'language' => 'en'],
    ]
];

foreach ($edgeCases as $categoryName => $cases) {
    echo "📂 $categoryName\n";
    
    foreach ($cases as $i => $case) {
        $input = array_diff_key($case, ['expected' => '']);
        $expected = $case['expected'] ?? null;
        
        echo "  Test " . ($i + 1) . ": ";
        
        // Ejecutar buildExtractionInput
        $result = ExtractionInputBuilder::buildExtractionInput($input);
        
        if ($expected !== null) {
            // Verificar que las plataformas esperadas estén habilitadas
            $enabledPlatforms = [];
            
            $platformMapping = [
                'booking' => 'enableBooking',
                'googlemaps' => 'enableGoogleMaps',
                'tripadvisor' => 'enableTripadvisor',
                'expedia' => 'enableExpedia',
                'agoda' => 'enableAgoda',
                'hotelscom' => 'enableHotelsCom',
            ];
            
            foreach ($platformMapping as $platform => $flag) {
                if (!empty($result[$flag])) {
                    $enabledPlatforms[] = $platform;
                }
            }
            
            sort($expected);
            sort($enabledPlatforms);
            
            $passed = $expected === $enabledPlatforms;
            $status = $passed ? '✅' : '❌';
            
            echo "$status [" . implode(', ', $input['platforms'] ?? []) . '] → [' . implode(', ', $enabledPlatforms) . ']';
            
            if (!$passed) {
                echo " (esperado: [" . implode(', ', $expected) . '])';
            }
            
        } else {
            // Solo mostrar resultado
            $enabledFlags = array_keys(array_filter($result, function($value, $key) {
                return strpos($key, 'enable') === 0 && $value === true;
            }, ARRAY_FILTER_USE_BOTH));
            
            echo "✅ [" . implode(', ', $input['platforms'] ?? []) . '] → ' . count($enabledFlags) . ' flags habilitados';
        }
        
        echo "\n";
    }
    
    echo "\n";
}

// Test estimación de costos
echo "📊 Testing estimación de costos\n";

$costTests = [
    ['hotel_id' => 100, 'platforms' => ['booking'], 'max_reviews' => 100],
    ['hotel_id' => 101, 'platforms' => ['booking', 'tripadvisor'], 'max_reviews' => 200],
    ['hotel_id' => 102, 'platforms' => ['booking', 'tripadvisor', 'googlemaps'], 'max_reviews' => 500],
];

foreach ($costTests as $i => $test) {
    $result = ExtractionInputBuilder::buildExtractionInput($test);
    $cost = ExtractionInputBuilder::estimateCost($result);
    
    echo "  Caso " . ($i + 1) . ": " . count($test['platforms']) . " plataformas, ";
    echo $test['max_reviews'] . " reviews → \$" . $cost['estimated_cost'] . "\n";
    echo "    Desglose: \$" . $cost['extraction_cost'] . " extracción + \$" . $cost['setup_cost'] . " setup\n";
}

echo "\n";

// Test validación avanzada
echo "🔍 Testing validación avanzada\n";

$validationTests = [
    ['hotel_id' => 'string', 'platforms' => ['booking']], // hotel_id inválido
    ['hotel_id' => 123, 'platforms' => 'booking'], // platforms no es array
    ['hotel_id' => 123, 'platforms' => ['booking'], 'max_reviews' => -10], // max_reviews negativo
    ['hotel_id' => 123, 'platforms' => ['booking'], 'max_reviews' => 'abc'], // max_reviews no numérico
];

foreach ($validationTests as $i => $config) {
    $validation = ExtractionInputBuilder::validateUserConfig($config);
    echo "  Error " . ($i + 1) . ": ";
    
    if (!$validation['valid']) {
        echo "✅ " . implode(', ', $validation['errors']) . "\n";
    } else {
        echo "❌ Debería ser inválido pero pasó validación\n";
    }
}

echo "\n🎯 TESTING COMPLETADO\n";
echo "Todos los casos edge verificados para mapeo de plataformas\n";
?>