<?php

/**
 * Actualizar extraction_jobs cuando el run async termina
 */
function updateExtractionJobFromRun($runId, $status, $reviewsCount = 0, $progress = 100) {
    global $pdo;
    
    try {
        // Buscar job_id asociado al run
        $stmt = $pdo->prepare("
            SELECT job_id, hotel_id 
            FROM apify_extraction_runs 
            WHERE apify_run_id = ? AND job_id IS NOT NULL
        ");
        $stmt->execute([$runId]);
        $run = $stmt->fetch();
        
        if (!$run) {
            error_log("No se encontró job_id para run: $runId");
            return false;
        }
        
        $jobId = $run['job_id'];
        
        // Actualizar extraction_jobs
        $mappedStatus = [
            'SUCCEEDED' => 'completed',
            'FAILED' => 'failed', 
            'ABORTED' => 'failed',
            'TIMED-OUT' => 'timeout'
        ][$status] ?? 'pending';
        
        $stmt = $pdo->prepare("
            UPDATE extraction_jobs 
            SET status = ?, 
                progress = ?, 
                reviews_extracted = ?,
                completed_at = CASE WHEN ? IN ('completed', 'failed', 'timeout') THEN NOW() ELSE completed_at END,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$mappedStatus, $progress, $reviewsCount, $mappedStatus, $jobId]);
        
        error_log("Job $jobId actualizado: status=$mappedStatus, reviews=$reviewsCount");
        return true;
        
    } catch (Exception $e) {
        error_log("Error actualizando job desde run: " . $e->getMessage());
        return false;
    }
}
?>