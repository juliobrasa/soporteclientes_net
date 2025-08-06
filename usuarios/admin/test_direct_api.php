<?php
// Test directo del endpoint getHotels
$_REQUEST['action'] = 'getHotels';

// Capturar la salida
ob_start();
try {
    include 'admin_api.php';
    $output = ob_get_clean();
    
    echo "🔍 DIRECT API TEST - getHotels\n";
    echo "==============================\n\n";
    echo "📊 Raw Output:\n";
    echo $output . "\n\n";
    
    // Verificar si es JSON válido
    $json = json_decode($output, true);
    if ($json !== null) {
        echo "✅ Valid JSON Response\n";
        echo "📋 Structure Analysis:\n";
        echo "- success: " . ($json['success'] ? 'true' : 'false') . "\n";
        
        if (isset($json['hotels'])) {
            echo "- hotels: ✅ Present (" . count($json['hotels']) . " items)\n";
        } else {
            echo "- hotels: ❌ MISSING\n";
        }
        
        if (isset($json['data'])) {
            echo "- data: ⚠️ Present (should be removed)\n";
        } else {
            echo "- data: ✅ Correctly absent\n";
        }
        
        echo "- total: " . ($json['total'] ?? 'MISSING') . "\n";
        
        if (isset($json['error'])) {
            echo "❌ Error: " . $json['error'] . "\n";
        }
        
    } else {
        echo "❌ INVALID JSON Response\n";
        echo "Raw response: " . substr($output, 0, 200) . "...\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "💥 Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    ob_end_clean(); 
    echo "💥 Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>