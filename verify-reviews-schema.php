<?php
/**
 * Script de Verificación de Esquema - Tabla Reviews
 * 
 * Verifica la estructura actual de la tabla reviews
 * e identifica discrepancias entre los diferentes esquemas usados
 */

require_once 'env-loader.php';

try {
    // Configuración de base de datos
    $host = $_ENV['DB_HOST'] ?? 'soporteclientes.net';
    $dbname = $_ENV['DB_NAME'] ?? 'soporteia_bookingkavia';
    $username = $_ENV['DB_USER'] ?? 'soporteia_admin';
    $password = $_ENV['DB_PASS'] ?? 'QCF8RhS*}.Oj0u(v';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "🔍 VERIFICACIÓN DE ESQUEMA - TABLA REVIEWS\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // Verificar si existe la tabla reviews
    $stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
    $tableExists = $stmt->fetch() !== false;
    
    if (!$tableExists) {
        echo "❌ ERROR: La tabla 'reviews' no existe en la base de datos\n";
        echo "📋 Acción requerida: Crear tabla reviews con esquema unificado\n";
        exit(1);
    }
    
    echo "✅ Tabla 'reviews' existe\n\n";
    
    // Obtener estructura de la tabla
    $stmt = $pdo->query("DESCRIBE reviews");
    $columns = $stmt->fetchAll();
    
    echo "📊 COLUMNAS ACTUALES:\n";
    echo str_repeat("-", 40) . "\n";
    
    $existingColumns = [];
    foreach ($columns as $column) {
        $existingColumns[] = $column['Field'];
        printf("%-25s %-15s %s\n", 
            $column['Field'], 
            $column['Type'],
            $column['Null'] === 'YES' ? 'NULLABLE' : 'NOT NULL'
        );
    }
    
    echo "\n";
    
    // Definir columnas requeridas por cada esquema
    $schemaA_columns = [
        'source_platform' => 'API Reviews actual',
        'property_response' => 'API Reviews actual',
        'liked_text' => 'API Reviews actual', 
        'disliked_text' => 'API Reviews actual',
        'user_name' => 'API Reviews actual',
        'user_location' => 'API Reviews actual',
        'review_title' => 'API Reviews actual',
        'traveler_type_spanish' => 'API Reviews actual',
        'review_language' => 'API Reviews actual',
        'helpful_votes' => 'API Reviews actual'
    ];
    
    $schemaB_columns = [
        'platform' => 'Apify Data Processor',
        'review_text' => 'Apify Data Processor',
        'reviewer_name' => 'Apify Data Processor',
        'normalized_rating' => 'Apify Data Processor', 
        'response_from_owner' => 'Apify Data Processor'
    ];
    
    // Verificar compatibilidad Schema A (API Reviews)
    echo "🔍 VERIFICACIÓN SCHEMA A (API REVIEWS):\n";
    echo str_repeat("-", 40) . "\n";
    
    $schemaA_missing = [];
    foreach ($schemaA_columns as $column => $source) {
        $exists = in_array($column, $existingColumns);
        echo sprintf("%-25s [%s] %s\n", 
            $column, 
            $exists ? "✅" : "❌", 
            $exists ? "EXISTS" : "MISSING"
        );
        
        if (!$exists) {
            $schemaA_missing[] = $column;
        }
    }
    
    echo "\n";
    
    // Verificar compatibilidad Schema B (Apify)
    echo "🔍 VERIFICACIÓN SCHEMA B (APIFY):\n";
    echo str_repeat("-", 40) . "\n";
    
    $schemaB_missing = [];
    foreach ($schemaB_columns as $column => $source) {
        $exists = in_array($column, $existingColumns);
        echo sprintf("%-25s [%s] %s\n", 
            $column, 
            $exists ? "✅" : "❌", 
            $exists ? "EXISTS" : "MISSING"
        );
        
        if (!$exists) {
            $schemaB_missing[] = $column;
        }
    }
    
    echo "\n";
    
    // Análisis de compatibilidad
    echo "📋 ANÁLISIS DE COMPATIBILIDAD:\n";
    echo str_repeat("-", 40) . "\n";
    
    if (empty($schemaA_missing) && empty($schemaB_missing)) {
        echo "✅ PERFECTO: Ambos esquemas son completamente compatibles\n";
        echo "🚀 No se requiere migración\n";
    } else {
        echo "⚠️  INCONSISTENCIAS DETECTADAS:\n\n";
        
        if (!empty($schemaA_missing)) {
            echo "❌ Schema A (API Reviews) - Columnas faltantes:\n";
            foreach ($schemaA_missing as $column) {
                echo "   - $column\n";
            }
            echo "\n";
        }
        
        if (!empty($schemaB_missing)) {
            echo "❌ Schema B (Apify) - Columnas faltantes:\n"; 
            foreach ($schemaB_missing as $column) {
                echo "   - $column\n";
            }
            echo "\n";
        }
        
        echo "🔧 ACCIÓN REQUERIDA: Ejecutar migración de unificación\n";
        echo "📂 Ver: REVIEWS_SCHEMA_UNIFICATION.md para el plan detallado\n";
    }
    
    // Verificar datos existentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $totalReviews = $stmt->fetch()['total'];
    
    echo "\n📊 ESTADÍSTICAS DE DATOS:\n";
    echo str_repeat("-", 40) . "\n";
    echo "Total de reviews: " . number_format($totalReviews) . "\n";
    
    if ($totalReviews > 0) {
        // Verificar distribución por plataformas
        $platformQuery = "SELECT ";
        if (in_array('source_platform', $existingColumns)) {
            $platformQuery .= "source_platform as platform";
        } elseif (in_array('platform', $existingColumns)) {
            $platformQuery .= "platform";
        } else {
            $platformQuery .= "'unknown' as platform";
        }
        $platformQuery .= ", COUNT(*) as count FROM reviews GROUP BY platform ORDER BY count DESC LIMIT 5";
        
        $stmt = $pdo->query($platformQuery);
        $platforms = $stmt->fetchAll();
        
        echo "\nDistribución por plataformas:\n";
        foreach ($platforms as $platform) {
            printf("  %-15s: %s reviews\n", 
                $platform['platform'] ?? 'N/A', 
                number_format($platform['count'])
            );
        }
    }
    
    // Verificar registros recientes
    if (in_array('scraped_at', $existingColumns)) {
        $stmt = $pdo->query("
            SELECT DATE(scraped_at) as date, COUNT(*) as count 
            FROM reviews 
            WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(scraped_at) 
            ORDER BY date DESC
        ");
        $recentActivity = $stmt->fetchAll();
        
        if (!empty($recentActivity)) {
            echo "\nActividad reciente (últimos 7 días):\n";
            foreach ($recentActivity as $day) {
                printf("  %s: %s reviews\n", $day['date'], number_format($day['count']));
            }
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ Verificación completada\n";
    
    if (!empty($schemaA_missing) || !empty($schemaB_missing)) {
        echo "\n🚨 SIGUIENTE PASO: Ejecutar migración de unificación\n";
        echo "   Comando: php unify-reviews-schema.php\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR DE BASE DE DATOS: " . $e->getMessage() . "\n";
    echo "🔧 Verificar configuración de conexión en .env\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERROR GENERAL: " . $e->getMessage() . "\n";
    exit(1);
}
?>