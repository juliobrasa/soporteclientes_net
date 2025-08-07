<?php
/**
 * Simple API test to verify the Hotels endpoint is working
 */

echo "🧪 TESTING API ENDPOINT\n";
echo "========================\n\n";

try {
    // Set up the environment like admin_api.php would
    $_GET['action'] = 'getHotels';
    
    // Capture output
    ob_start();
    include 'admin_api.php';
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "✅ API Response received:\n";
        echo substr($output, 0, 500) . "...\n\n";
        
        // Try to decode JSON
        $data = json_decode($output, true);
        if ($data) {
            echo "✅ JSON is valid\n";
            echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
            if (isset($data['hotels'])) {
                echo "Hotels count: " . count($data['hotels']) . "\n";
            }
            if (isset($data['error'])) {
                echo "Error: " . $data['error'] . "\n";
            }
        } else {
            echo "❌ Invalid JSON response\n";
        }
    } else {
        echo "❌ No API response received\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ API TEST COMPLETED\n";
?>