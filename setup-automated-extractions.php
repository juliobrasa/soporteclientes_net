<?php
/**
 * ==========================================================================
 * CONFIGURACIÓN DE EXTRACCIONES AUTOMÁTICAS CON CRON
 * Sistema completo de extracciones programadas
 * ==========================================================================
 */

echo "=== CONFIGURACIÓN DE EXTRACCIONES AUTOMÁTICAS ===\n\n";

try {
    // Conectar a base de datos
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔄 CONFIGURANDO SISTEMA AUTOMATIZADO...\n\n";
    
    // 1. Crear tabla para logs de extracciones automáticas
    echo "📊 Creando tabla de logs de extracciones...\n";
    
    $createLogTable = "
    CREATE TABLE IF NOT EXISTS extraction_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        execution_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        hotel_id INT,
        platform VARCHAR(50),
        reviews_extracted INT DEFAULT 0,
        status ENUM('success', 'error', 'partial') DEFAULT 'success',
        execution_time INT DEFAULT 0,
        error_message TEXT NULL,
        cost_estimate DECIMAL(10,6) DEFAULT 0,
        run_id VARCHAR(100) NULL,
        INDEX idx_date (execution_date),
        INDEX idx_hotel_platform (hotel_id, platform),
        INDEX idx_status (status)
    )";
    
    $pdo->exec($createLogTable);
    echo "   ✅ Tabla extraction_logs creada\n\n";
    
    // 2. Crear tabla para configuración de extracciones
    echo "⚙️  Creando tabla de configuración...\n";
    
    $createConfigTable = "
    CREATE TABLE IF NOT EXISTS extraction_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hotel_id INT,
        platform VARCHAR(50),
        enabled BOOLEAN DEFAULT TRUE,
        max_reviews_per_run INT DEFAULT 20,
        frequency_hours INT DEFAULT 24,
        last_extraction DATETIME NULL,
        next_extraction DATETIME NULL,
        priority INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_next_extraction (next_extraction),
        INDEX idx_enabled (enabled),
        UNIQUE KEY unique_hotel_platform (hotel_id, platform)
    )";
    
    $pdo->exec($createConfigTable);
    echo "   ✅ Tabla extraction_config creada\n\n";
    
    // 3. Insertar configuración inicial para todos los hoteles
    echo "🏨 Configurando extracciones para todos los hoteles...\n";
    
    $hotels = $pdo->query("SELECT id, nombre_hotel FROM hoteles WHERE activo = 1")->fetchAll(PDO::FETCH_ASSOC);
    $platforms = ['booking', 'google_maps', 'tripadvisor'];
    
    $configInsert = $pdo->prepare("
        INSERT IGNORE INTO extraction_config 
        (hotel_id, platform, max_reviews_per_run, frequency_hours, next_extraction, priority) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $configCount = 0;
    foreach ($hotels as $hotel) {
        foreach ($platforms as $i => $platform) {
            // Configurar frecuencias y prioridades diferentes
            $frequency = match($platform) {
                'booking' => 12,      // Cada 12 horas (más frecuente)
                'google_maps' => 24,  // Cada 24 horas
                'tripadvisor' => 48   // Cada 48 horas
            };
            
            $maxReviews = match($platform) {
                'booking' => 15,      // Booking suele tener más reseñas
                'google_maps' => 10,  // Google Maps moderado
                'tripadvisor' => 8    // TripAdvisor menos frecuente
            };
            
            $priority = $i + 1; // booking=1, google_maps=2, tripadvisor=3
            
            // Próxima extracción escalonada para evitar sobrecarga
            $nextExtraction = date('Y-m-d H:i:s', strtotime("+{$i} hours"));
            
            $configInsert->execute([
                $hotel['id'],
                $platform,
                $maxReviews,
                $frequency,
                $nextExtraction,
                $priority
            ]);
            
            $configCount++;
        }
        
        echo "   ✅ {$hotel['nombre_hotel']}: 3 plataformas configuradas\n";
    }
    
    echo "\n📊 Total configuraciones: {$configCount}\n\n";
    
    // 4. Crear script principal de extracción automática
    echo "🤖 Creando script principal de extracción...\n";
    
    $cronScript = '<?php
/**
 * SCRIPT PRINCIPAL DE EXTRACCIONES AUTOMÁTICAS
 * Ejecutado por cron cada hora
 */

set_time_limit(0); // Sin límite de tiempo
ini_set("memory_limit", "512M");

require_once __DIR__ . "/multi-platform-scraper.php";
require_once __DIR__ . "/env-loader.php";

echo "[" . date("Y-m-d H:i:s") . "] === INICIANDO EXTRACCIONES AUTOMÁTICAS ===\n";

try {
    // Conectar a BD
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar extracciones pendientes
    $pendingStmt = $pdo->prepare("
        SELECT ec.*, h.nombre_hotel, h.google_place_id
        FROM extraction_config ec
        JOIN hoteles h ON ec.hotel_id = h.id
        WHERE ec.enabled = 1 
        AND ec.next_extraction <= NOW()
        AND h.activo = 1
        ORDER BY ec.priority ASC, ec.next_extraction ASC
        LIMIT 5
    ");
    
    $pendingStmt->execute();
    $pendingExtractions = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pendingExtractions)) {
        echo "[" . date("Y-m-d H:i:s") . "] No hay extracciones pendientes\n";
        exit(0);
    }
    
    echo "[" . date("Y-m-d H:i:s") . "] Encontradas " . count($pendingExtractions) . " extracciones pendientes\n";
    
    $multiScraper = new MultiPlatformScraper();
    
    foreach ($pendingExtractions as $extraction) {
        $startTime = time();
        
        echo "[" . date("Y-m-d H:i:s") . "] Extrayendo {$extraction[\"platform\"]} para {$extraction[\"nombre_hotel\"]}\n";
        
        try {
            // Configurar extracción específica
            $hotelData = [
                "id" => $extraction["hotel_id"],
                "nombre_hotel" => $extraction["nombre_hotel"],
                "google_place_id" => $extraction["google_place_id"]
            ];
            
            $options = [
                "max_reviews_per_platform" => $extraction["max_reviews_per_run"],
                "platforms" => [$extraction["platform"]],
                "language" => "es",
                "date_from" => date("Y-m-d", strtotime("-30 days"))
            ];
            
            $result = $multiScraper->extractAllPlatforms($hotelData, $options);
            
            $executionTime = time() - $startTime;
            $reviewsCount = $result["total_reviews"] ?? 0;
            $estimatedCost = $result["estimated_cost"] ?? 0;
            
            if ($reviewsCount > 0) {
                // Guardar reseñas en BD
                $saveResult = $multiScraper->saveReviewsToDatabase($result["all_reviews"], $extraction["hotel_id"]);
                
                if ($saveResult["success"]) {
                    echo "[" . date("Y-m-d H:i:s") . "] ✅ {$reviewsCount} reseñas extraídas y guardadas\n";
                    $status = "success";
                    $errorMsg = null;
                } else {
                    echo "[" . date("Y-m-d H:i:s") . "] ⚠️  Reseñas extraídas pero error al guardar: {$saveResult[\"error\"]}\n";
                    $status = "partial";
                    $errorMsg = $saveResult["error"];
                }
            } else {
                echo "[" . date("Y-m-d H:i:s") . "] ℹ️  Sin nuevas reseñas encontradas\n";
                $status = "success";
                $errorMsg = "Sin nuevas reseñas";
            }
            
        } catch (Exception $e) {
            $executionTime = time() - $startTime;
            $reviewsCount = 0;
            $estimatedCost = 0;
            $status = "error";
            $errorMsg = $e->getMessage();
            
            echo "[" . date("Y-m-d H:i:s") . "] ❌ Error: {$errorMsg}\n";
        }
        
        // Registrar log de ejecución
        $logStmt = $pdo->prepare("
            INSERT INTO extraction_logs 
            (hotel_id, platform, reviews_extracted, status, execution_time, error_message, cost_estimate) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $logStmt->execute([
            $extraction["hotel_id"],
            $extraction["platform"],
            $reviewsCount,
            $status,
            $executionTime,
            $errorMsg,
            $estimatedCost
        ]);
        
        // Actualizar próxima extracción
        $nextExtraction = date("Y-m-d H:i:s", strtotime("+{$extraction[\"frequency_hours\"]} hours"));
        
        $updateStmt = $pdo->prepare("
            UPDATE extraction_config 
            SET last_extraction = NOW(), next_extraction = ? 
            WHERE id = ?
        ");
        
        $updateStmt->execute([$nextExtraction, $extraction["id"]]);
        
        // Pausa entre extracciones para no sobrecargar APIs
        sleep(10);
    }
    
    echo "[" . date("Y-m-d H:i:s") . "] === EXTRACCIONES COMPLETADAS ===\n";
    
} catch (Exception $e) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR CRÍTICO: " . $e->getMessage() . "\n";
    exit(1);
}
?>';
    
    file_put_contents('/root/soporteclientes_net/automated-extraction.php', $cronScript);
    chmod('/root/soporteclientes_net/automated-extraction.php', 0755);
    echo "   ✅ automated-extraction.php creado\n\n";
    
    // 5. Crear script de monitoreo y estadísticas
    echo "📊 Creando script de monitoreo...\n";
    
    $monitorScript = '<?php
/**
 * SCRIPT DE MONITOREO DE EXTRACCIONES
 */

require_once __DIR__ . "/env-loader.php";

echo "=== MONITOREO DE EXTRACCIONES ===\n\n";

try {
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Estadísticas de las últimas 24 horas
    echo "📊 ESTADÍSTICAS ÚLTIMAS 24 HORAS:\n";
    
    $statsStmt = $pdo->query("
        SELECT 
            platform,
            COUNT(*) as total_runs,
            SUM(reviews_extracted) as total_reviews,
            AVG(execution_time) as avg_time,
            SUM(cost_estimate) as total_cost,
            SUM(CASE WHEN status = \"success\" THEN 1 ELSE 0 END) as success_count,
            SUM(CASE WHEN status = \"error\" THEN 1 ELSE 0 END) as error_count
        FROM extraction_logs 
        WHERE execution_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY platform
        ORDER BY total_reviews DESC
    ");
    
    $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stats as $stat) {
        echo "   {$stat[\"platform\"]}:\n";
        echo "     - Ejecuciones: {$stat[\"total_runs\"]}\n";
        echo "     - Reseñas extraídas: {$stat[\"total_reviews\"]}\n";
        echo "     - Tiempo promedio: " . round($stat[\"avg_time\"]) . "s\n";
        echo "     - Costo: $" . number_format($stat[\"total_cost\"], 4) . "\n";
        echo "     - Éxito: {$stat[\"success_count\"]} | Errores: {$stat[\"error_count\"]}\n\n";
    }
    
    // Próximas extracciones programadas
    echo "⏰ PRÓXIMAS EXTRACCIONES:\n";
    
    $nextStmt = $pdo->query("
        SELECT ec.platform, h.nombre_hotel, ec.next_extraction, ec.max_reviews_per_run
        FROM extraction_config ec
        JOIN hoteles h ON ec.hotel_id = h.id
        WHERE ec.enabled = 1
        ORDER BY ec.next_extraction ASC
        LIMIT 10
    ");
    
    $nextExtractions = $nextStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($nextExtractions as $next) {
        echo "   - {$next[\"next_extraction\"]}: {$next[\"platform\"]} para {$next[\"nombre_hotel\"]} (max {$next[\"max_reviews_per_run\"]} reseñas)\n";
    }
    
    // Estado de salud del sistema
    echo "\n🏥 ESTADO DEL SISTEMA:\n";
    
    $healthStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_configs,
            SUM(enabled) as active_configs,
            COUNT(DISTINCT hotel_id) as hotels_configured
        FROM extraction_config
    ");
    
    $health = $healthStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   - Configuraciones totales: {$health[\"total_configs\"]}\n";
    echo "   - Configuraciones activas: {$health[\"active_configs\"]}\n";
    echo "   - Hoteles configurados: {$health[\"hotels_configured\"]}\n";
    
    // Últimos errores
    $errorStmt = $pdo->query("
        SELECT platform, error_message, execution_date
        FROM extraction_logs 
        WHERE status = \"error\" 
        AND execution_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY execution_date DESC
        LIMIT 5
    ");
    
    $errors = $errorStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($errors)) {
        echo "\n⚠️  ERRORES RECIENTES:\n";
        foreach ($errors as $error) {
            echo "   - {$error[\"execution_date\"]}: {$error[\"platform\"]} - {$error[\"error_message\"]}\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN MONITOREO ===\n";
?>';
    
    file_put_contents('/root/soporteclientes_net/monitor-extractions.php', $monitorScript);
    chmod('/root/soporteclientes_net/monitor-extractions.php', 0755);
    echo "   ✅ monitor-extractions.php creado\n\n";
    
    // 6. Configurar trabajos cron
    echo "⏰ CONFIGURANDO TRABAJOS CRON...\n";
    
    $cronJobs = [
        '# Extracciones automáticas cada hora',
        '0 * * * * cd /root/soporteclientes_net && /usr/bin/php automated-extraction.php >> /var/log/hotel-extractions.log 2>&1',
        '',
        '# Monitoreo y limpieza cada 6 horas',
        '0 */6 * * * cd /root/soporteclientes_net && /usr/bin/php monitor-extractions.php >> /var/log/hotel-monitor.log 2>&1',
        '',
        '# Backup de logs semanalmente',
        '0 2 * * 0 cd /root/soporteclientes_net && /usr/bin/php backup-extraction-logs.php >> /var/log/hotel-backup.log 2>&1'
    ];
    
    $cronFile = '/tmp/hotel-extraction-crons';
    file_put_contents($cronFile, implode("\n", $cronJobs) . "\n");
    
    // Instalar crontab
    $currentCrontab = shell_exec('crontab -l 2>/dev/null') ?: '';
    $newCrontab = $currentCrontab . "\n" . implode("\n", $cronJobs) . "\n";
    
    file_put_contents($cronFile, $newCrontab);
    shell_exec("crontab {$cronFile}");
    unlink($cronFile);
    
    echo "   ✅ Trabajos cron instalados\n";
    echo "   📅 Extracciones: Cada hora\n";
    echo "   📊 Monitoreo: Cada 6 horas\n";
    echo "   💾 Backup: Semanalmente\n\n";
    
    // 7. Crear script de backup y mantenimiento
    echo "💾 Creando script de backup...\n";
    
    $backupScript = '<?php
/**
 * BACKUP Y MANTENIMIENTO DE LOGS
 */

try {
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    echo "[" . date("Y-m-d H:i:s") . "] Iniciando mantenimiento de logs\n";
    
    // Limpiar logs antiguos (más de 30 días)
    $cleanStmt = $pdo->prepare("
        DELETE FROM extraction_logs 
        WHERE execution_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $cleanStmt->execute();
    $cleaned = $cleanStmt->rowCount();
    
    echo "[" . date("Y-m-d H:i:s") . "] Logs antiguos limpiados: {$cleaned}\n";
    
    // Optimizar tablas
    $pdo->exec("OPTIMIZE TABLE extraction_logs");
    $pdo->exec("OPTIMIZE TABLE extraction_config");
    
    echo "[" . date("Y-m-d H:i:s") . "] Tablas optimizadas\n";
    
    // Reporte de estadísticas mensuales
    $monthlyStmt = $pdo->query("
        SELECT 
            DATE_FORMAT(execution_date, \"%Y-%m\") as month,
            platform,
            COUNT(*) as executions,
            SUM(reviews_extracted) as total_reviews,
            SUM(cost_estimate) as total_cost
        FROM extraction_logs
        WHERE execution_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY month, platform
        ORDER BY month DESC, platform
    ");
    
    $monthlyStats = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "[" . date("Y-m-d H:i:s") . "] === ESTADÍSTICAS MENSUALES ===\n";
    foreach ($monthlyStats as $stat) {
        echo "   {$stat[\"month\"]} - {$stat[\"platform\"]}: {$stat[\"total_reviews\"]} reseñas, ${$stat[\"total_cost\"]}\n";
    }
    
} catch (Exception $e) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR: " . $e->getMessage() . "\n";
}
?>';
    
    file_put_contents('/root/soporteclientes_net/backup-extraction-logs.php', $backupScript);
    chmod('/root/soporteclientes_net/backup-extraction-logs.php', 0755);
    echo "   ✅ backup-extraction-logs.php creado\n\n";
    
    // 8. Inicializar primera ejecución
    echo "🚀 INICIANDO PRIMERA EXTRACCIÓN DE PRUEBA...\n";
    
    // Ejecutar una extracción inmediata para verificar que todo funciona
    echo "   Ejecutando extracción de prueba...\n";
    
    shell_exec("cd /root/soporteclientes_net && php automated-extraction.php > /tmp/first-extraction.log 2>&1 &");
    
    echo "   ✅ Primera extracción iniciada en segundo plano\n";
    echo "   📄 Log disponible en: /tmp/first-extraction.log\n\n";
    
    // 9. Mostrar resumen final
    echo "🎉 SISTEMA DE EXTRACCIONES AUTOMÁTICAS CONFIGURADO!\n\n";
    
    echo "📊 RESUMEN DE LA CONFIGURACIÓN:\n";
    echo "   - Hoteles configurados: " . count($hotels) . "\n";
    echo "   - Plataformas por hotel: 3 (Booking, Google Maps, TripAdvisor)\n";
    echo "   - Configuraciones totales: {$configCount}\n";
    echo "   - Frecuencia: Cada hora (automático)\n";
    echo "   - Backup: Semanal\n";
    echo "   - Monitoreo: Cada 6 horas\n\n";
    
    echo "📅 PROGRAMACIÓN DE EXTRACCIONES:\n";
    echo "   - Booking: Cada 12 horas, máximo 15 reseñas\n";
    echo "   - Google Maps: Cada 24 horas, máximo 10 reseñas\n";
    echo "   - TripAdvisor: Cada 48 horas, máximo 8 reseñas\n\n";
    
    echo "🛠️  COMANDOS ÚTILES:\n";
    echo "   - Ver estado: php monitor-extractions.php\n";
    echo "   - Ejecutar manual: php automated-extraction.php\n";
    echo "   - Ver logs: tail -f /var/log/hotel-extractions.log\n";
    echo "   - Ver cron jobs: crontab -l\n\n";
    
    echo "📊 LOGS Y MONITOREO:\n";
    echo "   - Extracciones: /var/log/hotel-extractions.log\n";
    echo "   - Monitoreo: /var/log/hotel-monitor.log\n";
    echo "   - Backup: /var/log/hotel-backup.log\n\n";
    
    echo "🎯 EL SISTEMA ESTÁ AHORA COMPLETAMENTE AUTOMATIZADO!\n";
    echo "Las reseñas se extraerán automáticamente 24/7 sin intervención manual.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR EN LA CONFIGURACIÓN:\n";
    echo "   " . $e->getMessage() . "\n\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN CONFIGURACIÓN AUTOMÁTICA ===\n";
?>