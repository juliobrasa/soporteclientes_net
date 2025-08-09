<?php
/**
 * Script de migración para normalizar columnas JSON
 * Ejecutar después de aplicar correcciones JSON_EXTRACT
 */

require_once 'env-loader.php';

class JSONColumnNormalizer {
    private $pdo;

    public function __construct() {
        $this->pdo = EnvironmentLoader::createDatabaseConnection();
    }

    public function normalizeDebugLogs() {
        // Agregar columnas normalizadas si no existen
        $alterQueries = [
            'ALTER TABLE debug_logs ADD COLUMN job_id_extracted VARCHAR(255) NULL',
            'ALTER TABLE debug_logs ADD COLUMN hotel_id_extracted INT NULL',
            'ALTER TABLE debug_logs ADD COLUMN run_id_extracted VARCHAR(255) NULL'
        ];

        foreach ($alterQueries as $query) {
            try {
                $this->pdo->exec($query);
            } catch (PDOException $e) {
                // Columna ya existe
            }
        }

        // Llenar columnas normalizadas desde JSON existente
        $updateQueries = [
            "UPDATE debug_logs SET job_id_extracted = JSON_UNQUOTE(JSON_EXTRACT(context, '\$.job_id')) WHERE context LIKE '%job_id%' AND job_id_extracted IS NULL",
            "UPDATE debug_logs SET hotel_id_extracted = JSON_UNQUOTE(JSON_EXTRACT(context, '\$.hotel_id')) WHERE context LIKE '%hotel_id%' AND hotel_id_extracted IS NULL",
            "UPDATE debug_logs SET run_id_extracted = JSON_UNQUOTE(JSON_EXTRACT(context, '\$.run_id')) WHERE context LIKE '%run_id%' AND run_id_extracted IS NULL"
        ];

        foreach ($updateQueries as $query) {
            try {
                $affected = $this->pdo->exec($query);
                echo "Normalizadas $affected filas\n";
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }

        // Crear índices para performance
        $indexQueries = [
            'CREATE INDEX idx_debug_logs_job_id ON debug_logs (job_id_extracted)',
            'CREATE INDEX idx_debug_logs_hotel_id ON debug_logs (hotel_id_extracted)',
            'CREATE INDEX idx_debug_logs_run_id ON debug_logs (run_id_extracted)'
        ];

        foreach ($indexQueries as $query) {
            try {
                $this->pdo->exec($query);
            } catch (PDOException $e) {
                // Índice ya existe
            }
        }
    }
}

// Ejecutar normalización
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $normalizer = new JSONColumnNormalizer();
    $normalizer->normalizeDebugLogs();
    echo "✅ Normalización completada\n";
}
