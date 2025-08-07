<?php
/**
 * Script de Backup de Base de Datos
 * Genera un backup completo de la base de datos soporteia_bookingkavia
 */

include 'admin-config.php';

function createDatabaseBackup() {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo "❌ Error de conexión a la base de datos\n";
        return false;
    }

    $backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $backup = "-- ================================================================\n";
    $backup .= "-- BACKUP DE BASE DE DATOS - BOOKINGKAVIA\n";
    $backup .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $backup .= "-- Base de datos: soporteia_bookingkavia\n";
    $backup .= "-- ================================================================\n\n";

    try {
        // Obtener lista de todas las tablas
        $stmt = $pdo->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "📊 Iniciando backup de " . count($tables) . " tablas...\n";
        
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
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($rows) {
                $backup .= "-- Datos para la tabla `$table`\n";
                $backup .= "INSERT INTO `$table` VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
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
        
        // Guardar backup
        if (file_put_contents($backupFile, $backup)) {
            echo "\n✅ Backup creado exitosamente: $backupFile\n";
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
echo "🗃️  INICIANDO BACKUP DE BASE DE DATOS\n";
echo "====================================\n";

$backupFile = createDatabaseBackup();

if ($backupFile) {
    echo "\n🎉 BACKUP COMPLETADO EXITOSAMENTE\n";
    echo "Archivo: $backupFile\n";
} else {
    echo "\n💥 ERROR EN EL BACKUP\n";
}
?>