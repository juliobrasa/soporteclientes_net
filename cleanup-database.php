<?php
/**
 * Script de Limpieza de Base de Datos
 * Elimina tablas innecesarias que causan confusión
 */

include 'admin-config.php';

function getTableInfo($pdo, $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return "ERROR: " . $e->getMessage();
    }
}

function cleanupDatabase() {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo "❌ Error de conexión a la base de datos\n";
        return false;
    }

    echo "🧹 INICIANDO LIMPIEZA DE BASE DE DATOS\n";
    echo "=====================================\n\n";

    // Tablas que deben ser eliminadas porque causan confusión
    $tablesToDrop = [
        'external_apis' => 'api_providers', // usar api_providers en su lugar
        'prompts' => 'ai_prompts',          // usar ai_prompts en su lugar
    ];

    // Tablas que podrían estar vacías y ser candidatas para eliminación
    $candidateTables = [
        'sessions',
        'cache',
        'failed_jobs',
        'password_resets',
        'personal_access_tokens',
        'migrations',
    ];

    try {
        echo "📊 ANÁLISIS DE TABLAS PROBLEMÁTICAS\n";
        echo "-----------------------------------\n";
        
        foreach ($tablesToDrop as $oldTable => $newTable) {
            echo "🔍 Analizando tabla '$oldTable':\n";
            
            // Verificar si la tabla existe
            $stmt = $pdo->query("SHOW TABLES LIKE '$oldTable'");
            if ($stmt->rowCount() > 0) {
                $count = getTableInfo($pdo, $oldTable);
                echo "   - Registros en '$oldTable': $count\n";
                
                // Verificar tabla de reemplazo
                $stmt = $pdo->query("SHOW TABLES LIKE '$newTable'");
                if ($stmt->rowCount() > 0) {
                    $newCount = getTableInfo($pdo, $newTable);
                    echo "   - Registros en '$newTable' (reemplazo): $newCount\n";
                    
                    if ($count == 0) {
                        echo "   ✅ '$oldTable' está vacía, seguro de eliminar\n";
                    } else {
                        echo "   ⚠️  '$oldTable' tiene datos, REVISAR ANTES DE ELIMINAR\n";
                    }
                } else {
                    echo "   ❌ Tabla de reemplazo '$newTable' no existe\n";
                }
            } else {
                echo "   ℹ️  Tabla '$oldTable' no existe\n";
            }
            echo "\n";
        }

        echo "\n📋 ANÁLISIS DE TABLAS CANDIDATAS\n";
        echo "--------------------------------\n";
        
        foreach ($candidateTables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $count = getTableInfo($pdo, $table);
                echo "🔍 $table: $count registros\n";
                
                if ($count == 0) {
                    echo "   ✅ Candidata para eliminación (vacía)\n";
                } else if ($count < 10) {
                    echo "   ⚠️  Pocos registros, revisar contenido\n";
                } else {
                    echo "   ❌ Muchos registros, NO eliminar\n";
                }
            } else {
                echo "ℹ️  $table: No existe\n";
            }
        }

        echo "\n🎯 RECOMENDACIONES DE LIMPIEZA\n";
        echo "==============================\n";

        // Proceder con limpieza automática SOLO de tablas vacías y problemáticas
        $safeToDrop = [];
        
        foreach ($tablesToDrop as $oldTable => $newTable) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$oldTable'");
            if ($stmt->rowCount() > 0) {
                $count = getTableInfo($pdo, $oldTable);
                if ($count == 0) {
                    $safeToDrop[] = $oldTable;
                    echo "✅ Programada para eliminación: $oldTable (vacía)\n";
                } else {
                    echo "⚠️  MANTENER: $oldTable (tiene $count registros)\n";
                }
            }
        }

        // Ejecutar eliminación de tablas seguras
        if (!empty($safeToDrop)) {
            echo "\n🗑️  ELIMINANDO TABLAS SEGURAS\n";
            echo "----------------------------\n";
            
            foreach ($safeToDrop as $table) {
                echo "Eliminando tabla '$table'... ";
                $stmt = $pdo->prepare("DROP TABLE IF EXISTS `$table`");
                if ($stmt->execute()) {
                    echo "✅ ELIMINADA\n";
                } else {
                    echo "❌ ERROR\n";
                }
            }
        } else {
            echo "ℹ️  No hay tablas seguras para eliminar automáticamente\n";
        }

        echo "\n✨ LIMPIEZA COMPLETADA\n";
        return true;

    } catch (Exception $e) {
        echo "\n❌ Error durante la limpieza: " . $e->getMessage() . "\n";
        return false;
    }
}

// Ejecutar limpieza
cleanupDatabase();
?>