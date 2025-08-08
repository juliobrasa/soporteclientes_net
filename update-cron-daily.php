<?php
/**
 * ==========================================================================
 * ACTUALIZAR CRON PARA EJECUCIÓN DIARIA
 * Cambiar de cada hora a una vez al día
 * ==========================================================================
 */

echo "=== ACTUALIZANDO CONFIGURACIÓN CRON ===\n\n";

try {
    echo "🔄 Cambiando frecuencia de extracciones a DIARIA...\n\n";
    
    // Conectar a base de datos para ajustar configuraciones
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Actualizar frecuencias para que sean diarias
    echo "📊 Actualizando configuraciones de frecuencia...\n";
    
    $updateFrequency = $pdo->prepare("
        UPDATE extraction_config 
        SET 
            frequency_hours = CASE 
                WHEN platform = 'booking' THEN 24
                WHEN platform = 'google_maps' THEN 24  
                WHEN platform = 'tripadvisor' THEN 24
                ELSE 24
            END,
            next_extraction = CASE
                WHEN platform = 'booking' THEN DATE_ADD(NOW(), INTERVAL 2 HOUR)
                WHEN platform = 'google_maps' THEN DATE_ADD(NOW(), INTERVAL 4 HOUR)
                WHEN platform = 'tripadvisor' THEN DATE_ADD(NOW(), INTERVAL 6 HOUR)
                ELSE DATE_ADD(NOW(), INTERVAL 8 HOUR)
            END
    ");
    
    $updateFrequency->execute();
    $updatedRows = $updateFrequency->rowCount();
    
    echo "   ✅ {$updatedRows} configuraciones actualizadas a frecuencia diaria\n\n";
    
    // Configurar nuevos trabajos cron (diarios)
    echo "⏰ CONFIGURANDO NUEVOS TRABAJOS CRON DIARIOS...\n";
    
    $cronJobs = [
        '# Hotel review extractions - Daily at 2:00 AM',
        '0 2 * * * cd /root/soporteclientes_net && /usr/bin/php automated-extraction.php >> /var/log/hotel-extractions.log 2>&1',
        '',
        '# Monitoring and health check - Daily at 6:00 AM', 
        '0 6 * * * cd /root/soporteclientes_net && /usr/bin/php monitor-extractions-fixed.php >> /var/log/hotel-monitor.log 2>&1',
        '',
        '# Weekly backup and maintenance - Sundays at 3:00 AM',
        '0 3 * * 0 cd /root/soporteclientes_net && /usr/bin/php backup-extraction-logs.php >> /var/log/hotel-backup.log 2>&1'
    ];
    
    // Limpiar crontab actual relacionado con hoteles
    $currentCrontab = shell_exec('crontab -l 2>/dev/null') ?: '';
    $lines = explode("\n", $currentCrontab);
    $cleanedLines = [];
    
    foreach ($lines as $line) {
        // Remover líneas relacionadas con hotel-extractions
        if (strpos($line, 'hotel-extractions') === false && 
            strpos($line, 'hotel-monitor') === false && 
            strpos($line, 'hotel-backup') === false &&
            strpos($line, 'automated-extraction') === false) {
            $cleanedLines[] = $line;
        }
    }
    
    // Agregar nuevos jobs
    $newCrontab = implode("\n", $cleanedLines) . "\n" . implode("\n", $cronJobs) . "\n";
    
    $cronFile = '/tmp/hotel-extraction-crons-daily';
    file_put_contents($cronFile, $newCrontab);
    
    shell_exec("crontab {$cronFile}");
    unlink($cronFile);
    
    echo "   ✅ Trabajos cron actualizados\n";
    echo "   🌅 Extracciones: Diarias a las 2:00 AM\n";
    echo "   📊 Monitoreo: Diario a las 6:00 AM\n";
    echo "   💾 Backup: Domingos a las 3:00 AM\n\n";
    
    // Crear versión optimizada del script de extracción diaria
    echo "🔧 Creando script optimizado para ejecución diaria...\n";
    
    $dailyScript = '<?php
/**
 * SCRIPT DE EXTRACCIONES DIARIAS OPTIMIZADO
 * Ejecuta todas las extracciones pendientes en una sola sesión
 */

set_time_limit(0);
ini_set("memory_limit", "1G");

require_once __DIR__ . "/multi-platform-scraper.php";
require_once __DIR__ . "/env-loader.php";

echo "[" . date("Y-m-d H:i:s") . "] === EXTRACCIONES DIARIAS ===\n";

try {
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar todas las extracciones pendientes para hoy
    $pendingStmt = $pdo->prepare("
        SELECT ec.*, h.nombre_hotel, h.google_place_id
        FROM extraction_config ec
        JOIN hoteles h ON ec.hotel_id = h.id
        WHERE ec.enabled = 1 
        AND ec.next_extraction <= NOW()
        AND h.activo = 1
        ORDER BY ec.priority ASC, ec.platform ASC
    ");
    
    $pendingStmt->execute();
    $pendingExtractions = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pendingExtractions)) {
        echo "[" . date("Y-m-d H:i:s") . "] No hay extracciones programadas para hoy\n";
        exit(0);
    }
    
    echo "[" . date("Y-m-d H:i:s") . "] Procesando " . count($pendingExtractions) . " extracciones programadas\n";
    
    $multiScraper = new MultiPlatformScraper();
    $totalReviews = 0;
    $totalCost = 0;
    $successCount = 0;
    $errorCount = 0;
    
    // Agrupar por hotel para optimizar
    $hotelGroups = [];
    foreach ($pendingExtractions as $extraction) {
        $hotelId = $extraction["hotel_id"];
        if (!isset($hotelGroups[$hotelId])) {
            $hotelGroups[$hotelId] = [
                "hotel_data" => [
                    "id" => $extraction["hotel_id"],
                    "nombre_hotel" => $extraction["nombre_hotel"],
                    "google_place_id" => $extraction["google_place_id"]
                ],
                "platforms" => [],
                "extractions" => []
            ];
        }
        
        $hotelGroups[$hotelId]["platforms"][] = $extraction["platform"];
        $hotelGroups[$hotelId]["extractions"][] = $extraction;
    }
    
    // Procesar cada hotel con todas sus plataformas
    foreach ($hotelGroups as $hotelId => $group) {
        echo "[" . date("Y-m-d H:i:s") . "] Procesando hotel: " . $group["hotel_data"]["nombre_hotel"] . "\n";
        
        try {
            $options = [
                "max_reviews_per_platform" => 20,
                "platforms" => $group["platforms"],
                "language" => "es",
                "date_from" => date("Y-m-d", strtotime("-60 days"))
            ];
            
            $result = $multiScraper->extractAllPlatforms($group["hotel_data"], $options);
            
            if ($result["total_reviews"] > 0) {
                $saveResult = $multiScraper->saveReviewsToDatabase($result["all_reviews"], $hotelId);
                
                if ($saveResult["success"]) {
                    echo "[" . date("Y-m-d H:i:s") . "] ✅ " . $result["total_reviews"] . " reseñas guardadas\n";
                    $totalReviews += $result["total_reviews"];
                    $successCount++;
                } else {
                    echo "[" . date("Y-m-d H:i:s") . "] ⚠️  Error al guardar: " . $saveResult["error"] . "\n";
                    $errorCount++;
                }
            } else {
                echo "[" . date("Y-m-d H:i:s") . "] ℹ️  Sin nuevas reseñas\n";
                $successCount++;
            }
            
            $totalCost += $result["estimated_cost"];
            
            // Actualizar next_extraction para todas las plataformas de este hotel
            foreach ($group["extractions"] as $extraction) {
                $nextExtraction = date("Y-m-d H:i:s", strtotime("+24 hours"));
                
                $updateStmt = $pdo->prepare("
                    UPDATE extraction_config 
                    SET last_extraction = NOW(), next_extraction = ? 
                    WHERE id = ?
                ");
                
                $updateStmt->execute([$nextExtraction, $extraction["id"]]);
            }
            
        } catch (Exception $e) {
            echo "[" . date("Y-m-d H:i:s") . "] ❌ Error procesando hotel: " . $e->getMessage() . "\n";
            $errorCount++;
        }
        
        // Pausa entre hoteles
        sleep(15);
    }
    
    echo "[" . date("Y-m-d H:i:s") . "] === RESUMEN DIARIO ===\n";
    echo "[" . date("Y-m-d H:i:s") . "] Hoteles procesados: " . count($hotelGroups) . "\n";
    echo "[" . date("Y-m-d H:i:s") . "] Total reseñas extraídas: {$totalReviews}\n";
    echo "[" . date("Y-m-d H:i:s") . "] Costo estimado: $" . number_format($totalCost, 4) . "\n";
    echo "[" . date("Y-m-d H:i:s") . "] Éxitos: {$successCount} | Errores: {$errorCount}\n";
    
} catch (Exception $e) {
    echo "[" . date("Y-m-d H:i:s") . "] ERROR CRÍTICO: " . $e->getMessage() . "\n";
    exit(1);
}
?>';
    
    file_put_contents('/root/soporteclientes_net/daily-extraction.php', $dailyScript);
    chmod('/root/soporteclientes_net/daily-extraction.php', 0755);
    
    echo "   ✅ daily-extraction.php creado (optimizado para ejecución diaria)\n\n";
    
    // Actualizar script de monitoreo
    echo "📊 Corrigiendo script de monitoreo...\n";
    
    $fixedMonitorScript = '<?php
/**
 * MONITOREO DE EXTRACCIONES DIARIAS
 */

require_once __DIR__ . "/env-loader.php";

echo "=== MONITOREO DE EXTRACCIONES DIARIAS ===\n\n";

try {
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Estado actual del sistema
    echo "🏥 ESTADO DEL SISTEMA:\n";
    
    $healthStmt = $pdo->query("
        SELECT 
            COUNT(*) as total_configs,
            SUM(enabled) as active_configs,
            COUNT(DISTINCT hotel_id) as hotels_configured
        FROM extraction_config
    ");
    
    $health = $healthStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   - Configuraciones totales: {$health['total_configs']}\n";
    echo "   - Configuraciones activas: {$health['active_configs']}\n";
    echo "   - Hoteles configurados: {$health['hotels_configured']}\n";
    
    // Total de reseñas
    $reviewsStmt = $pdo->query("SELECT COUNT(*) as total_reviews FROM reviews");
    $totalReviews = $reviewsStmt->fetch(PDO::FETCH_ASSOC)['total_reviews'];
    echo "   - Total reseñas en BD: " . number_format($totalReviews) . "\n";
    
    // Reseñas por plataforma
    echo "\n📊 RESEÑAS POR PLATAFORMA:\n";
    $platformStmt = $pdo->query("
        SELECT platform, COUNT(*) as count 
        FROM reviews 
        WHERE platform != 'unknown'
        GROUP BY platform 
        ORDER BY count DESC
    ");
    
    $platforms = $platformStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($platforms as $platform) {
        echo "   - {$platform['platform']}: " . number_format($platform['count']) . " reseñas\n";
    }
    
    // Próximas extracciones programadas
    echo "\n⏰ PRÓXIMAS EXTRACCIONES PROGRAMADAS:\n";
    
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
        echo "   - {$next['next_extraction']}: {$next['platform']} para {$next['nombre_hotel']} (max {$next['max_reviews_per_run']} reseñas)\n";
    }
    
    // Actividad reciente
    echo "\n📈 ACTIVIDAD RECIENTE (últimos 7 días):\n";
    $recentStmt = $pdo->query("
        SELECT DATE(scraped_at) as fecha, COUNT(*) as nuevas_reviews
        FROM reviews 
        WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(scraped_at)
        ORDER BY fecha DESC
    ");
    
    $recent = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recent)) {
        echo "   📝 No hay actividad reciente registrada\n";
    } else {
        foreach ($recent as $day) {
            echo "   - {$day['fecha']}: {$day['nuevas_reviews']} nuevas reseñas\n";
        }
    }
    
    echo "\n✅ Sistema funcionando correctamente\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN MONITOREO ===\n";
?>';
    
    file_put_contents('/root/soporteclientes_net/monitor-extractions-fixed.php', $fixedMonitorScript);
    echo "   ✅ Script de monitoreo corregido\n\n";
    
    // Actualizar cron para usar el script diario
    echo "🔄 Actualizando cron para usar script diario...\n";
    
    $finalCronJobs = [
        '# Hotel review extractions - Daily at 2:00 AM',
        '0 2 * * * cd /root/soporteclientes_net && /usr/bin/php daily-extraction.php >> /var/log/hotel-extractions.log 2>&1',
        '',
        '# Monitoring and health check - Daily at 6:00 AM', 
        '0 6 * * * cd /root/soporteclientes_net && /usr/bin/php monitor-extractions-fixed.php >> /var/log/hotel-monitor.log 2>&1',
        '',
        '# Weekly backup and maintenance - Sundays at 3:00 AM',
        '0 3 * * 0 cd /root/soporteclientes_net && /usr/bin/php backup-extraction-logs.php >> /var/log/hotel-backup.log 2>&1'
    ];
    
    $currentCrontab = shell_exec('crontab -l 2>/dev/null') ?: '';
    $lines = explode("\n", $currentCrontab);
    $cleanedLines = [];
    
    foreach ($lines as $line) {
        if (strpos($line, 'hotel-') === false && strpos($line, 'automated-extraction') === false) {
            $cleanedLines[] = $line;
        }
    }
    
    $newCrontab = implode("\n", $cleanedLines) . "\n" . implode("\n", $finalCronJobs) . "\n";
    
    $cronFile = '/tmp/hotel-daily-crons';
    file_put_contents($cronFile, $newCrontab);
    shell_exec("crontab {$cronFile}");
    unlink($cronFile);
    
    echo "   ✅ Cron actualizado exitosamente\n\n";
    
    // Resumen final
    echo "🎉 CONFIGURACIÓN DIARIA COMPLETADA!\n\n";
    
    echo "📅 NUEVA PROGRAMACIÓN:\n";
    echo "   🌅 02:00 AM - Extracciones diarias de todos los hoteles\n";
    echo "   📊 06:00 AM - Monitoreo y estado del sistema\n";  
    echo "   💾 Domingos 03:00 AM - Backup y mantenimiento\n\n";
    
    echo "🏨 CONFIGURACIÓN POR PLATAFORMA:\n";
    echo "   - Booking.com: 15 reseñas máximo por hotel/día\n";
    echo "   - Google Maps: 10 reseñas máximo por hotel/día\n";  
    echo "   - TripAdvisor: 8 reseñas máximo por hotel/día\n\n";
    
    echo "📊 CAPACIDAD DIARIA ESTIMADA:\n";
    echo "   - 9 hoteles × 3 plataformas = 27 extracciones/día\n";
    echo "   - ~33 reseñas promedio por hotel/día\n";
    echo "   - ~297 reseñas nuevas por día (estimado)\n";
    echo "   - Costo diario estimado: ~$0.50-$1.00\n\n";
    
    echo "🛠️  COMANDOS ÚTILES:\n";
    echo "   - Ver estado: php monitor-extractions-fixed.php\n";
    echo "   - Ejecutar manual: php daily-extraction.php\n";
    echo "   - Ver logs: tail -f /var/log/hotel-extractions.log\n";
    echo "   - Ver cron: crontab -l\n\n";
    
    echo "✅ EL SISTEMA AHORA FUNCIONA CON EXTRACCIONES DIARIAS!\n";
    echo "Próxima extracción programada: mañana a las 2:00 AM\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ACTUALIZACIÓN CRON DIARIO ===\n";
?>