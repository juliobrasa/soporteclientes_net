<?php
/**
 * ==========================================================================
 * VERIFICADOR DE CONFIGURACIÓN APIFY
 * Verifica el estado actual y valida la configuración
 * ==========================================================================
 */

require_once __DIR__ . '/apify-config.php';

class ApifySetupVerifier {
    private $apifyClient;
    
    public function __construct() {
        $this->apifyClient = new ApifyClient();
    }
    
    /**
     * Verificar configuración completa de Apify
     */
    public function verifySetup() {
        echo "=== VERIFICACIÓN DE CONFIGURACIÓN APIFY ===\n\n";
        
        // 1. Verificar información básica
        $debugInfo = $this->apifyClient->getDebugInfo();
        $this->printDebugInfo($debugInfo);
        
        // 2. Verificar token
        $tokenStatus = $this->verifyToken($debugInfo);
        
        // 3. Verificar actor si el token es real
        if (!$debugInfo['demo_mode']) {
            $actorStatus = $this->verifyActor();
        } else {
            $actorStatus = ['status' => 'demo', 'message' => 'Modo demo - actor no verificado'];
        }
        
        // 4. Verificar reseñas en BD
        $reviewsStatus = $this->verifyDatabaseReviews();
        
        // 5. Generar reporte
        $this->generateReport($tokenStatus, $actorStatus, $reviewsStatus);
        
        return [
            'token' => $tokenStatus,
            'actor' => $actorStatus,
            'reviews' => $reviewsStatus,
            'overall_status' => $this->getOverallStatus($tokenStatus, $actorStatus, $reviewsStatus)
        ];
    }
    
    /**
     * Mostrar información de debug
     */
    private function printDebugInfo($debugInfo) {
        echo "📊 INFORMACIÓN ACTUAL:\n";
        echo "- Modo Demo: " . ($debugInfo['demo_mode'] ? '✅ SÍ (usando datos simulados)' : '❌ NO (usando API real)') . "\n";
        echo "- Token API: " . $debugInfo['api_token'] . "\n";
        echo "- URL Base: " . $debugInfo['base_url'] . "\n";
        echo "- Actor ID: " . $debugInfo['actor_id'] . "\n\n";
    }
    
    /**
     * Verificar token de API
     */
    private function verifyToken($debugInfo) {
        echo "🔑 VERIFICANDO TOKEN:\n";
        
        if ($debugInfo['demo_mode']) {
            echo "❌ Token es de DEMO\n";
            echo "   - Archivo: .env\n";
            echo "   - Valor actual: demo_token_replace_with_real\n";
            echo "   - Necesario: Tu token real de Apify\n\n";
            
            return [
                'status' => 'demo',
                'message' => 'Token de demo - necesario token real',
                'action_required' => 'Configurar token real en .env'
            ];
        }
        
        echo "✅ Token configurado (no es demo)\n";
        echo "   - Longitud: " . strlen($debugInfo['api_token']) . " caracteres\n\n";
        
        return [
            'status' => 'configured',
            'message' => 'Token está configurado',
            'action_required' => 'Verificar validez del token'
        ];
    }
    
    /**
     * Verificar actor de Apify
     */
    private function verifyActor() {
        echo "🎭 VERIFICANDO ACTOR:\n";
        
        try {
            // Intentar una llamada simple al actor
            $testConfig = [
                'hotelId' => 'ChIJTest',
                'hotelName' => 'Test Hotel',
                'maxReviews' => 1,
                'reviewPlatforms' => ['booking']
            ];
            
            // Esto debería fallar o devolver error si el actor no existe
            $response = $this->apifyClient->startHotelExtraction($testConfig);
            
            if (isset($response['data'])) {
                echo "✅ Actor responde correctamente\n";
                echo "   - Actor ID: tri_angle~hotel-review-aggregator\n";
                echo "   - Estado: Disponible\n\n";
                
                return [
                    'status' => 'available',
                    'message' => 'Actor disponible y funcional'
                ];
            } else {
                echo "⚠️  Actor responde pero con formato inesperado\n";
                echo "   - Respuesta: " . json_encode($response) . "\n\n";
                
                return [
                    'status' => 'unknown',
                    'message' => 'Actor responde pero formato inesperado'
                ];
            }
            
        } catch (Exception $e) {
            echo "❌ Error verificando actor\n";
            echo "   - Error: " . $e->getMessage() . "\n";
            echo "   - Posibles causas:\n";
            echo "     * Actor no existe en tu cuenta\n";
            echo "     * Sin permisos para usar el actor\n";
            echo "     * Token inválido\n\n";
            
            return [
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
                'action_required' => 'Verificar actor en console.apify.com'
            ];
        }
    }
    
    /**
     * Verificar reseñas en base de datos
     */
    private function verifyDatabaseReviews() {
        echo "🗃️ VERIFICANDO RESEÑAS EN BD:\n";
        
        try {
            // Conexión directa a BD para evitar problemas
            $host = "soporteclientes.net";
            $dbname = "soporteia_bookingkavia";
            $username = "soporteia_admin";
            $password = "QCF8RhS*}.Oj0u(v";
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total_reviews,
                    COUNT(CASE WHEN platform_review_id LIKE 'demo_%' THEN 1 END) as demo_reviews,
                    COUNT(CASE WHEN platform_review_id NOT LIKE 'demo_%' THEN 1 END) as real_reviews,
                    MAX(scraped_at) as latest_extraction,
                    COUNT(DISTINCT hotel_id) as hotels_with_reviews,
                    COUNT(DISTINCT source_platform) as platforms_used
                FROM reviews
            ");
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "   - Total reseñas: " . number_format($stats['total_reviews']) . "\n";
            echo "   - Reseñas demo: " . number_format($stats['demo_reviews']) . " (" . round(($stats['demo_reviews']/$stats['total_reviews'])*100, 1) . "%)\n";
            echo "   - Reseñas reales: " . number_format($stats['real_reviews']) . " (" . round(($stats['real_reviews']/$stats['total_reviews'])*100, 1) . "%)\n";
            echo "   - Última extracción: " . ($stats['latest_extraction'] ?? 'Nunca') . "\n";
            echo "   - Hoteles con reseñas: " . $stats['hotels_with_reviews'] . "\n";
            echo "   - Plataformas usadas: " . $stats['platforms_used'] . "\n\n";
            
            return [
                'status' => 'ok',
                'stats' => $stats,
                'demo_percentage' => round(($stats['demo_reviews']/$stats['total_reviews'])*100, 1)
            ];
            
        } catch (Exception $e) {
            echo "❌ Error verificando BD: " . $e->getMessage() . "\n\n";
            
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generar reporte final
     */
    private function generateReport($tokenStatus, $actorStatus, $reviewsStatus) {
        echo "📋 REPORTE FINAL:\n";
        
        if ($tokenStatus['status'] === 'demo') {
            echo "🔴 CONFIGURACIÓN INCOMPLETA\n";
            echo "   - El sistema está en modo DEMO\n";
            echo "   - Las reseñas son simuladas\n";
            echo "   - No hay extracciones reales\n\n";
            
            echo "✅ PASOS PARA CONFIGURAR:\n";
            echo "   1. Obtén tu token de https://console.apify.com/\n";
            echo "   2. Edita .env y cambia APIFY_API_TOKEN\n";
            echo "   3. Ejecuta este script nuevamente\n";
            echo "   4. Prueba extracción real\n\n";
        } else {
            echo "🟡 TOKEN CONFIGURADO - Verificando funcionalidad...\n\n";
            
            if ($actorStatus['status'] === 'available') {
                echo "🟢 SISTEMA LISTO PARA EXTRACCIONES REALES\n";
                echo "   - Token configurado ✅\n";
                echo "   - Actor disponible ✅\n";
                echo "   - BD funcionando ✅\n\n";
            } else {
                echo "🔴 PROBLEMA CON EL ACTOR\n";
                echo "   - " . $actorStatus['message'] . "\n\n";
            }
        }
        
        if ($reviewsStatus['demo_percentage'] > 50) {
            echo "🧹 LIMPIEZA RECOMENDADA:\n";
            echo "   - Tienes " . $reviewsStatus['demo_percentage'] . "% de reseñas demo\n";
            echo "   - Considera limpiar datos de prueba\n";
            echo "   - Comando: php clean-demo-reviews.php\n\n";
        }
    }
    
    /**
     * Obtener estado general
     */
    private function getOverallStatus($tokenStatus, $actorStatus, $reviewsStatus) {
        if ($tokenStatus['status'] === 'demo') {
            return 'needs_token';
        }
        
        if ($actorStatus['status'] === 'error') {
            return 'actor_issue';
        }
        
        if ($actorStatus['status'] === 'available') {
            return 'ready';
        }
        
        return 'unknown';
    }
}

// Ejecutar verificación
if (php_sapi_name() === 'cli' || basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $verifier = new ApifySetupVerifier();
    $result = $verifier->verifySetup();
    
    // Salir con código de error si hay problemas
    exit($result['overall_status'] === 'ready' ? 0 : 1);
}
?>