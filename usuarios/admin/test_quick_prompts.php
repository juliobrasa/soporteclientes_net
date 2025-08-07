<?php
/**
 * Quick test of prompts functionality
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up test environment
$_POST['action'] = 'getPrompts';
$_POST['page'] = '1';
$_POST['limit'] = '10';
$_REQUEST = $_POST;

echo "Testing prompts API endpoint...\n";

// Capture API response
ob_start();
include_once 'admin_api.php';
$api_response = ob_get_clean();

echo "API Response:\n";
echo $api_response . "\n";

// Test decode
$decoded = json_decode($api_response, true);
if ($decoded && isset($decoded['success'])) {
    if ($decoded['success']) {
        echo "✅ API working correctly\n";
        echo "Total prompts: " . ($decoded['data']['total'] ?? 0) . "\n";
    } else {
        echo "❌ API returned error: " . ($decoded['error'] ?? 'Unknown') . "\n";
    }
} else {
    echo "❌ Invalid API response format\n";
}

// Test stats endpoint
echo "\n--- Testing stats endpoint ---\n";
$_POST['action'] = 'getPromptsStats';
$_REQUEST = $_POST;

ob_start();
include_once 'admin_api.php';
$stats_response = ob_get_clean();

echo "Stats Response:\n";
echo $stats_response . "\n";

$stats_decoded = json_decode($stats_response, true);
if ($stats_decoded && isset($stats_decoded['success'])) {
    if ($stats_decoded['success']) {
        echo "✅ Stats API working correctly\n";
    } else {
        echo "❌ Stats API error: " . ($stats_decoded['error'] ?? 'Unknown') . "\n";
    }
}
?>