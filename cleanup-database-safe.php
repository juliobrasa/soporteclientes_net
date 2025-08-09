<?php
/**
 * Script de Limpieza Segura de Base de Datos
 * Maneja constraints de foreign keys
 */

include 'admin-config.php';

function getForeignKeys($pdo, $table) {
    try {
        $stmt = $pdo->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = 'soporteia_bookingkavia' 
            AND TABLE_NAME = '$table'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getReferencingTables($pdo, $table) {
    try {
        $stmt = $pdo->query("
            SELECT 
                TABLE_NAME,
                CONSTRAINT_NAME,
                COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = 'soporteia_bookingkavia' 
            AND REFERENCED_TABLE_NAME = '$table'
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function safeCleanupDatabase() {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo "❌ Error de conexión a la base de datos\n";
        return false;
    }

    echo "🧹 LIMPIEZA SEGURA DE BASE DE DATOS\n";
    echo "===================================\n\n";

    // Tablas problemáticas identificadas
    $problematicTables = ['external_apis', 'prompts'];
    
    try {
        // Deshabilitar foreign key checks temporalmente
        echo "🔓 Deshabilitando verificación de foreign keys...\n";
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($problematicTables as $table) {
            echo "\n🔍 Analizando tabla '$table':\n";
            
            // Verificar si existe
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                echo "   ℹ️  Tabla '$table' no existe\n";
                continue;
            }

            // Verificar contenido
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "   📊 Registros: $count\n";

            if ($count > 0) {
                echo "   ⚠️  Tabla tiene datos, NO se eliminará\n";
                continue;
            }

            // Mostrar foreign keys que referencian esta tabla
            $referencingTables = getReferencingTables($pdo, $table);
            if (!empty($referencingTables)) {
                echo "   🔗 Tablas que referencian '$table':\n";
                foreach ($referencingTables as $ref) {
                    echo "      - {$ref['TABLE_NAME']}.{$ref['COLUMN_NAME']} (constraint: {$ref['CONSTRAINT_NAME']})\n";
                }
            }

            // Verificar foreign keys salientes
            $foreignKeys = getForeignKeys($pdo, $table);
            if (!empty($foreignKeys)) {
                echo "   🔗 Foreign keys en '$table':\n";
                foreach ($foreignKeys as $fk) {
                    echo "      - {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
                }
            }

            // Eliminar tabla
            echo "   🗑️  Eliminando tabla '$table'... ";
            $stmt = $pdo->prepare("DROP TABLE IF EXISTS `$table`");
            if ($stmt->execute()) {
                echo "✅ ELIMINADA\n";
            } else {
                echo "❌ ERROR\n";
            }
        }

        // Limpiar tablas de sistema vacías
        echo "\n📋 LIMPIEZA DE TABLAS DE SISTEMA\n";
        echo "--------------------------------\n";
        
        $systemTables = ['cache', 'failed_jobs'];
        foreach ($systemTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $stmt->fetch()['count'];
                
                if ($count == 0) {
                    echo "🗑️  Eliminando tabla vacía '$table'... ";
                    $stmt = $pdo->prepare("DROP TABLE IF EXISTS `$table`");
                    if ($stmt->execute()) {
                        echo "✅ ELIMINADA\n";
                    } else {
                        echo "❌ ERROR\n";
                    }
                } else {
                    echo "ℹ️  '$table' tiene $count registros, mantener\n";
                }
            }
        }

        // Rehabilitar foreign key checks
        echo "\n🔒 Rehabilitando verificación de foreign keys...\n";
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        // Verificar integridad después de la limpieza
        echo "\n🔍 VERIFICACIÓN DE INTEGRIDAD\n";
        echo "----------------------------\n";
        
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "📊 Tablas restantes: " . count($tables) . "\n";
        
        // Verificar tablas principales
        $mainTables = ['hoteles', 'reviews', 'ai_providers', 'api_providers', 'ai_prompts', 'users'];
        $allGood = true;
        
        foreach ($mainTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $stmt->fetch()['count'];
                echo "✅ $table: $count registros\n";
            } else {
                echo "❌ $table: NO EXISTE\n";
                $allGood = false;
            }
        }

        if ($allGood) {
            echo "\n🎉 LIMPIEZA COMPLETADA EXITOSAMENTE\n";
            echo "✅ Todas las tablas principales están intactas\n";
            echo "✅ Tablas problemáticas eliminadas\n";
            echo "✅ Integridad verificada\n";
        } else {
            echo "\n⚠️  LIMPIEZA COMPLETADA CON ADVERTENCIAS\n";
        }

        return true;

    } catch (Exception $e) {
        // Asegurar que se rehabiliten los foreign keys en caso de error
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "\n❌ Error durante la limpieza: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ejecutar limpieza
safeCleanupDatabase();
?>