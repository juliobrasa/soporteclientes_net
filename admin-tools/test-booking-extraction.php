<?php
/**
 * Script de prueba para el sistema de extracción de Booking
 */
require_once 'admin-config.php';

class BookingExtractionTester {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        if (!$this->pdo) {
            throw new Exception("Error conectando a la base de datos");
        }
    }
    
    public function runTests() {
        echo "=== PRUEBA DEL SISTEMA DE EXTRACCIÓN DE BOOKING ===\n\n";
        
        $this->testDatabaseStructure();
        $this->testHotelsWithBookingUrls();
        $this->testBookingAPI();
        
        echo "\n=== PRUEBAS COMPLETADAS ===\n";
    }
    
    private function testDatabaseStructure() {
        echo "1. 🔍 Verificando estructura de base de datos...\n";
        
        // Verificar tabla extraction_jobs
        $stmt = $this->pdo->query("SHOW COLUMNS FROM extraction_jobs LIKE 'platform'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Columna 'platform' existe en extraction_jobs\n";
        } else {
            echo "   ❌ Columna 'platform' NO existe en extraction_jobs\n";
        }
        
        // Verificar tabla hoteles
        $stmt = $this->pdo->query("SHOW COLUMNS FROM hoteles LIKE 'url_booking'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Columna 'url_booking' existe en hoteles\n";
        } else {
            echo "   ❌ Columna 'url_booking' NO existe en hoteles\n";
        }
        
        echo "\n";
    }
    
    private function testHotelsWithBookingUrls() {
        echo "2. 🏨 Verificando hoteles con URLs de Booking...\n";
        
        $stmt = $this->pdo->query("
            SELECT 
                id, 
                nombre_hotel, 
                url_booking,
                CASE WHEN url_booking IS NOT NULL AND url_booking != '' THEN 1 ELSE 0 END as has_url
            FROM hoteles 
            WHERE activo = 1
            ORDER BY id
        ");
        $hotels = $stmt->fetchAll();
        
        $hotelsWithUrls = 0;
        foreach ($hotels as $hotel) {
            if ($hotel['has_url']) {
                echo "   ✅ ID {$hotel['id']}: {$hotel['nombre_hotel']}\n";
                echo "      URL: " . parse_url($hotel['url_booking'], PHP_URL_HOST) . "\n";
                $hotelsWithUrls++;
            } else {
                echo "   ❌ ID {$hotel['id']}: {$hotel['nombre_hotel']} (Sin URL)\n";
            }
        }
        
        echo "\n   📊 Total: {$hotelsWithUrls} de " . count($hotels) . " hoteles con URLs\n\n";
        
        if ($hotelsWithUrls === 0) {
            echo "   ⚠️  ADVERTENCIA: No hay hoteles con URLs de Booking para probar\n\n";
        }
        
        return $hotelsWithUrls > 0;
    }
    
    private function testBookingAPI() {
        echo "3. 🔌 Probando API de extracción de Booking...\n";
        
        // Obtener un hotel para probar
        $stmt = $this->pdo->query("
            SELECT id, nombre_hotel, url_booking 
            FROM hoteles 
            WHERE activo = 1 AND url_booking IS NOT NULL AND url_booking != ''
            LIMIT 1
        ");
        $hotel = $stmt->fetch();
        
        if (!$hotel) {
            echo "   ❌ No hay hoteles disponibles para probar\n\n";
            return false;
        }
        
        echo "   🏨 Hotel de prueba: {$hotel['nombre_hotel']} (ID: {$hotel['id']})\n";
        echo "   📍 URL: " . parse_url($hotel['url_booking'], PHP_URL_HOST) . "\n\n";
        
        // Simular llamada a la API
        echo "   🧪 Simulando extracción (modo prueba)...\n";
        
        try {
            // Incluir la clase del BookingExtractor (versión de prueba)
            require_once 'booking-extractor-class.php';
            
            // Crear instancia
            $extractor = new BookingExtractor($this->pdo);
            echo "   ✅ BookingExtractor se puede instanciar correctamente\n";
            
            // Verificar configuración
            $config = $extractor->validateConfiguration();
            
            if ($config['hasToken']) {
                echo "   ✅ Token de Apify configurado\n";
            } else {
                echo "   ❌ Token de Apify NO configurado\n";
            }
            
            if ($config['hasDatabase']) {
                echo "   ✅ Conexión a base de datos OK\n";
            } else {
                echo "   ❌ Sin conexión a base de datos\n";
            }
            
            // Test de conectividad con Apify
            echo "   🌐 Probando conectividad con Apify...\n";
            $connectionTest = $extractor->testApifyConnection();
            
            if ($connectionTest['success']) {
                echo "   ✅ Conexión con Apify exitosa\n";
                echo "   👤 Usuario: {$connectionTest['username']}\n";
            } else {
                echo "   ⚠️  Error conectando con Apify: {$connectionTest['error']}\n";
            }
            
            // Simular extracción
            echo "   🧪 Simulando extracción de Booking...\n";
            $simulation = $extractor->simulateExtraction($hotel['id'], 10);
            
            if ($simulation['success']) {
                echo "   ✅ Simulación exitosa\n";
                echo "   💰 Costo estimado: $" . number_format($simulation['estimated_cost'], 3) . "\n";
                echo "   📋 " . $simulation['message'] . "\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Error en pruebas: " . $e->getMessage() . "\n";
            return false;
        }
        
        echo "\n";
        return true;
    }
    
    public function showInstructions() {
        echo "=== INSTRUCCIONES PARA USAR EL SISTEMA ===\n\n";
        
        echo "1. 🌐 Acceder al panel de administración:\n";
        echo "   - URL: https://soporteclientes.net/admin-extraction-portals.php\n\n";
        
        echo "2. 🏨 Seleccionar hoteles:\n";
        echo "   - Click en 'Nueva Extracción Booking'\n";
        echo "   - Seleccionar hoteles con URLs de Booking\n";
        echo "   - Configurar máximo de reseñas (recomendado: 10-50 para pruebas)\n\n";
        
        echo "3. 🚀 Iniciar extracción:\n";
        echo "   - Modo rápido (síncrono): Resultados en 2-5 minutos\n";
        echo "   - Modo avanzado (asíncrono): Para extracciones grandes\n\n";
        
        echo "4. 📊 Monitorear resultados:\n";
        echo "   - Ver tabla de trabajos de extracción\n";
        echo "   - Verificar reseñas en la tabla 'reviews'\n\n";
        
        echo "5. 💡 Tips:\n";
        echo "   - Empezar con 1 hotel y 10 reseñas para probar\n";
        echo "   - El actor funciona con proxies residenciales\n";
        echo "   - Costo aproximado: $0.002 por reseña\n\n";
    }
}

// Ejecutar pruebas solo si se llama directamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    try {
        $tester = new BookingExtractionTester();
        $tester->runTests();
        $tester->showInstructions();
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>