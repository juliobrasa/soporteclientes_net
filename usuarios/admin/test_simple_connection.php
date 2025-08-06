<?php
// Test simple de la función de testing
require_once 'admin_api.php';

echo "🧪 Test directo de función testAiProviderConnection\n";
echo "==================================================\n";

// Test con sistema local (no requiere API key)
echo "1. Test Sistema Local:\n";
$result_local = testAiProviderConnection('local', '', '', 'local', 'Sistema Local');
echo "   Estado: " . ($result_local['success'] ? '✅ ÉXITO' : '❌ ERROR') . "\n";
echo "   Mensaje: " . ($result_local['test_message'] ?? $result_local['error']) . "\n\n";

// Test con OpenAI (usando API key falsa para ver validación)
echo "2. Test OpenAI (sin API key):\n";
$result_openai = testAiProviderConnection('openai', '', '', 'gpt-3.5-turbo', 'OpenAI Test');
echo "   Estado: " . ($result_openai['success'] ? '✅ ÉXITO' : '❌ ERROR') . "\n";
echo "   Mensaje: " . ($result_openai['test_message'] ?? $result_openai['error']) . "\n\n";

// Test con tipo no soportado
echo "3. Test tipo no soportado:\n";
$result_invalid = testAiProviderConnection('invalid_type', 'key', '', 'model', 'Invalid Test');
echo "   Estado: " . ($result_invalid['success'] ? '✅ ÉXITO' : '❌ ERROR') . "\n";
echo "   Mensaje: " . ($result_invalid['test_message'] ?? $result_invalid['error']) . "\n\n";

echo "✅ Tests de funciones completados\n";
?>