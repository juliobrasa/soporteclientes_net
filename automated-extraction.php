<?php
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
?>