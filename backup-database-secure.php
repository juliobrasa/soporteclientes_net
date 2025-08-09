<?php
/**
 * Script de Backup Seguro de Base de Datos
 * Excluye datos sensibles como API keys
 */

include 'admin-config.php';

function createSecureDatabaseBackup() {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo "❌ Error de conexión a la base de datos\n";
        return false;
    }

    $backupFile = 'secure_backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup = "-- ================================================================\n";
    $backup .= "-- BACKUP SEGURO DE BASE DE DATOS - BOOKINGKAVIA\n";
    $backup .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- Base de datos: soporteia_bookingkavia\n";
    $backup .= "-- Nota: Excluye datos sensibles (API keys, passwords)\n";
    $backup .= "-- ================================================================\n\n";

    // Tablas que contienen datos sensibles - solo estructura
    $sensitiveDataTables = ['ai_providers', 'api_providers', 'users'];
    
    // Campos sensibles que deben ser anonimizados
    $sensitiveFields = ['api_key', 'password', 'api_token', 'secret_key'];

    try {
        // Obtener lista de todas las tablas
        $stmt = $pdo->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "📊 Iniciando backup seguro de " . count($tables) . " tablas...\n";
        
        foreach ($tables as $table) {
            echo "   Procesando tabla: $table...";
            
            // Estructura de la tabla
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $backup .= "-- ================================================================\n";
            $backup .= "-- Tabla: $table\n";
            $backup .= "-- ================================================================\n\n";
            $backup .= "DROP TABLE IF EXISTS `$table`;\n";
            $backup .= $createTable['Create Table'] . ";\n\n";
            
            // Datos de la tabla
            if (in_array($table, $sensitiveDataTables)) {
                // Solo estructura para tablas sensibles
                $backup .= "-- DATOS DE TABLA SENSIBLE '$table' EXCLUIDOS POR SEGURIDAD\n";
                $backup .= "-- Para restaurar, agregar datos manualmente\n\n";
                echo " [ESTRUCTURA ÚNICAMENTE - datos sensibles excluidos]\n";
            } else {
                // Datos completos para tablas no sensibles
                $stmt = $pdo->query("SELECT * FROM `$table`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($rows) {
                    $backup .= "-- Datos para la tabla `$table`\n";
                    
                    // Obtener nombres de columnas
                    $columns = array_keys($rows[0]);
                    $backup .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
                    
                    $values = [];
                    foreach ($rows as $row) {
                        $rowValues = [];
                        foreach ($row as $key => $value) {
                            // Anonimizar campos sensibles
                            if (in_array($key, $sensitiveFields)) {
                                $rowValues[] = "'[REDACTED]'";
                            } else if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $rowValues[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }
                    
                    $backup .= implode(",\n", $values) . ";\n\n";
                    echo " [" . count($rows) . " registros]\n";
                } else {
                    $backup .= "-- La tabla `$table` está vacía\n\n";
                    echo " [vacía]\n";
                }
            }
        }
        
        // Guardar backup
        if (file_put_contents($backupFile, $backup)) {
            echo "\n✅ Backup seguro creado: $backupFile\n";
            echo "📁 Tamaño: " . formatBytes(filesize($backupFile)) . "\n";
            return $backupFile;
        } else {
            echo "\n❌ Error al crear el archivo de backup\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "\n❌ Error durante el backup: " . $e->getMessage() . "\n";
        return false;
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Ejecutar backup
echo "🔒 INICIANDO BACKUP SEGURO DE BASE DE DATOS\n";
echo "==========================================\n";

$backupFile = createSecureDatabaseBackup();

if ($backupFile) {
    echo "\n🎉 BACKUP SEGURO COMPLETADO\n";
    echo "Archivo: $backupFile\n";
    echo "⚠️  Nota: Datos sensibles (API keys, passwords) han sido excluidos\n";
} else {
    echo "\n💥 ERROR EN EL BACKUP\n";
}
?>