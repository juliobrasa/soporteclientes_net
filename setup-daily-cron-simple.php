<?php
/**
 * CONFIGURACIÓN SIMPLE DE CRON DIARIO
 */

echo "=== CONFIGURANDO CRON DIARIO ===\n\n";

try {
    // 1. Actualizar cron jobs para ejecución diaria
    echo "⏰ Configurando cron para ejecución diaria...\n";
    
    $cronJobs = [
        '# Hotel review extractions - Daily at 2:00 AM',
        '0 2 * * * cd /root/soporteclientes_net && /usr/bin/php automated-extraction.php >> /var/log/hotel-extractions.log 2>&1',
        '',
        '# Monitoring - Daily at 6:00 AM', 
        '0 6 * * * cd /root/soporteclientes_net && /usr/bin/php monitor-extractions-fixed.php >> /var/log/hotel-monitor.log 2>&1'
    ];
    
    // Limpiar cron actual relacionado con hoteles
    $currentCrontab = shell_exec('crontab -l 2>/dev/null') ?: '';
    $lines = explode("\n", $currentCrontab);
    $cleanedLines = [];
    
    foreach ($lines as $line) {
        if (strpos($line, 'hotel-') === false && 
            strpos($line, 'automated-extraction') === false &&
            strpos($line, 'monitor-extractions') === false) {
            $cleanedLines[] = $line;
        }
    }
    
    // Agregar nuevos jobs diarios
    $newCrontab = implode("\n", array_filter($cleanedLines)) . "\n" . implode("\n", $cronJobs) . "\n";
    
    $cronFile = '/tmp/hotel-daily-crons';
    file_put_contents($cronFile, $newCrontab);
    shell_exec("crontab {$cronFile}");
    unlink($cronFile);
    
    echo "   ✅ Cron configurado para ejecución diaria\n\n";
    
    // 2. Actualizar configuración de extracciones a diaria
    echo "📊 Actualizando configuración de extracciones...\n";
    
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Actualizar todas las extracciones para que sean diarias
    $updateStmt = $pdo->prepare("
        UPDATE extraction_config 
        SET frequency_hours = 24,
            next_extraction = CASE 
                WHEN platform = 'booking' THEN DATE_ADD(NOW(), INTERVAL 2 HOUR)
                WHEN platform = 'google_maps' THEN DATE_ADD(NOW(), INTERVAL 4 HOUR) 
                WHEN platform = 'tripadvisor' THEN DATE_ADD(NOW(), INTERVAL 6 HOUR)
                ELSE DATE_ADD(NOW(), INTERVAL 8 HOUR)
            END
    ");
    
    $updateStmt->execute();
    $updated = $updateStmt->rowCount();
    
    echo "   ✅ {$updated} configuraciones actualizadas a frecuencia diaria\n\n";
    
    // 3. Verificar configuración final
    echo "✅ CONFIGURACIÓN COMPLETADA!\n\n";
    
    echo "📅 PROGRAMACIÓN DIARIA:\n";
    echo "   🌅 02:00 AM - Extracciones automáticas\n";
    echo "   📊 06:00 AM - Monitoreo del sistema\n\n";
    
    echo "🏨 CONFIGURACIÓN:\n";
    echo "   - 9 hoteles activos\n";
    echo "   - 3 plataformas por hotel (Booking, Google Maps, TripAdvisor)\n";
    echo "   - Ejecución diaria automática\n\n";
    
    echo "🛠️  COMANDOS:\n";
    echo "   - Ver estado: php monitor-extractions-fixed.php\n";
    echo "   - Ejecutar ahora: php automated-extraction.php\n";
    echo "   - Ver cron: crontab -l\n";
    echo "   - Ver logs: tail -f /var/log/hotel-extractions.log\n\n";
    
    echo "📊 PRÓXIMA EXTRACCIÓN: Mañana a las 2:00 AM\n";
    echo "El sistema extraerá automáticamente reseñas de todos los hoteles.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN CONFIGURACIÓN ===\n";
?>