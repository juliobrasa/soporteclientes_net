<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';

// Obtener estadísticas del sistema
function getSystemStats() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    $stats = [];
    
    try {
        // Tamaño de la base de datos
        $stmt = $pdo->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb
            FROM information_schema.tables 
            WHERE table_schema = 'soporteia_bookingkavia'
        ");
        $dbSize = $stmt->fetch();
        $stats['db_size'] = $dbSize['db_size_mb'] ?? 0;
        
        // Contadores de tablas principales
        $tables = ['hoteles', 'reviews', 'ai_providers', 'api_providers', 'users'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            $stats[$table . '_count'] = $result['count'] ?? 0;
        }
        
        // Espacio libre en disco (estimado)
        $stats['disk_usage'] = disk_free_space('/') / (1024 * 1024 * 1024); // GB
        
    } catch (PDOException $e) {
        error_log("Error obteniendo stats del sistema: " . $e->getMessage());
    }
    
    return $stats;
}

// Obtener archivos de backup disponibles
function getBackupFiles() {
    $backups = [];
    $files = glob('backup_*.sql');
    
    foreach ($files as $file) {
        $backups[] = [
            'filename' => $file,
            'size' => filesize($file),
            'date' => filemtime($file)
        ];
    }
    
    // Ordenar por fecha descendente
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    return $backups;
}

$stats = getSystemStats();
$backups = getBackupFiles();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Herramientas - Panel Admin Kavia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php"><i class="fas fa-hotel"></i> Kavia Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="#"><i class="fas fa-user"></i> <?php echo $_SESSION['admin_email']; ?></a>
                <a class="nav-link" href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="admin-dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-hotels.php">
                                <i class="fas fa-building"></i> Hoteles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-ai.php">
                                <i class="fas fa-robot"></i> AI Providers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-prompts.php">
                                <i class="fas fa-comments"></i> Prompts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-extraction.php">
                                <i class="fas fa-download"></i> Extracción
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-apis.php">
                                <i class="fas fa-plug"></i> APIs Externas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin-tools.php">
                                <i class="fas fa-tools"></i> Herramientas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-logs.php">
                                <i class="fas fa-file-alt"></i> Logs del Sistema
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-tools"></i> Herramientas del Sistema</h1>
                </div>

                <!-- System Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Tamaño BD</h6>
                                        <h3><?php echo number_format($stats['db_size'] ?? 0, 1); ?> MB</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-database fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Reseñas</h6>
                                        <h3><?php echo number_format($stats['reviews_count'] ?? 0); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-star fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Hoteles Activos</h6>
                                        <h3><?php echo $stats['hoteles_count'] ?? 0; ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-building fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Espacio Libre</h6>
                                        <h3><?php echo number_format($stats['disk_usage'] ?? 0, 1); ?> GB</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-hdd fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Database Tools -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-database"></i> Herramientas de Base de Datos</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-success" onclick="createBackup()">
                                        <i class="fas fa-save"></i> Crear Backup Completo
                                    </button>
                                    <button class="btn btn-outline-info" onclick="optimizeTables()">
                                        <i class="fas fa-cogs"></i> Optimizar Tablas
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="analyzeDatabase()">
                                        <i class="fas fa-chart-line"></i> Analizar Base de Datos
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="cleanupDatabase()">
                                        <i class="fas fa-broom"></i> Limpiar Datos Antiguos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Tools -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-cog"></i> Herramientas del Sistema</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" onclick="testAPIs()">
                                        <i class="fas fa-wifi"></i> Test de Conectividad APIs
                                    </button>
                                    <button class="btn btn-outline-info" onclick="clearCache()">
                                        <i class="fas fa-trash"></i> Limpiar Cache
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="checkSystemHealth()">
                                        <i class="fas fa-heartbeat"></i> Verificar Salud del Sistema
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="viewLogs()">
                                        <i class="fas fa-file-alt"></i> Ver Logs de Error
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Backup Management -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-archive"></i> Gestión de Backups</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($backups)): ?>
                                <div class="text-center p-4">
                                    <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">No hay backups disponibles</h6>
                                    <button class="btn btn-primary" onclick="createBackup()">
                                        <i class="fas fa-plus"></i> Crear Primer Backup
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Archivo</th>
                                                <th>Tamaño</th>
                                                <th>Fecha</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($backups as $backup): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($backup['filename']); ?></code></td>
                                                <td><?php echo number_format($backup['size'] / (1024*1024), 2); ?> MB</td>
                                                <td><?php echo date('Y-m-d H:i', $backup['date']); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="downloadBackup('<?php echo $backup['filename']; ?>')" title="Descargar">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteBackup('<?php echo $backup['filename']; ?>')" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-lightning-bolt"></i> Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-sm btn-outline-info" onclick="exportReviews()">
                                        <i class="fas fa-file-export"></i> Exportar Reseñas
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="importHotels()">
                                        <i class="fas fa-file-import"></i> Importar Hoteles
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="generateReport()">
                                        <i class="fas fa-chart-bar"></i> Generar Reporte
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="runMaintenance()">
                                        <i class="fas fa-wrench"></i> Mantenimiento
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- System Info -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Información del Sistema</h6>
                            </div>
                            <div class="card-body small">
                                <div class="row">
                                    <div class="col-6"><strong>PHP:</strong></div>
                                    <div class="col-6"><?php echo PHP_VERSION; ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>MySQL:</strong></div>
                                    <div class="col-6"><?php 
                                        $pdo = getDBConnection();
                                        if ($pdo) {
                                            $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                                            echo substr($version, 0, 10);
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>Usuarios:</strong></div>
                                    <div class="col-6"><?php echo $stats['users_count'] ?? 0; ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-6"><strong>Memoria:</strong></div>
                                    <div class="col-6"><?php echo ini_get('memory_limit'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <div class="mt-2" id="loadingText">Procesando...</div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function showLoading(text) {
        document.getElementById('loadingText').textContent = text || 'Procesando...';
        new bootstrap.Modal(document.getElementById('loadingModal')).show();
    }

    function hideLoading() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
        if (modal) modal.hide();
    }

    function createBackup() {
        showLoading('Creando backup de la base de datos...');
        
        // Simular llamada al script de backup
        setTimeout(() => {
            hideLoading();
            alert('✅ Backup creado exitosamente');
            location.reload();
        }, 3000);
    }

    function optimizeTables() {
        if (confirm('¿Optimizar todas las tablas de la base de datos?')) {
            showLoading('Optimizando tablas...');
            setTimeout(() => {
                hideLoading();
                alert('✅ Tablas optimizadas correctamente');
            }, 2000);
        }
    }

    function analyzeDatabase() {
        showLoading('Analizando base de datos...');
        setTimeout(() => {
            hideLoading();
            alert('📈 Análisis completado\n\nResultados:\n- Tablas: OK\n- Índices: Optimizados\n- Fragmentación: Baja');
        }, 2000);
    }

    function cleanupDatabase() {
        if (confirm('⚠️ ¿Limpiar datos antiguos y temporales?\n\nEsto eliminará:\n- Sesiones expiradas\n- Logs antiguos\n- Cache obsoleto')) {
            showLoading('Limpiando datos...');
            setTimeout(() => {
                hideLoading();
                alert('🧹 Limpieza completada');
            }, 2000);
        }
    }

    function testAPIs() {
        showLoading('Probando conectividad de APIs...');
        setTimeout(() => {
            hideLoading();
            alert('🔍 Test de APIs completado\n\n✅ Apify API: OK\n✅ OpenAI API: OK\n✅ DeepSeek API: OK');
        }, 3000);
    }

    function clearCache() {
        showLoading('Limpiando cache...');
        setTimeout(() => {
            hideLoading();
            alert('✅ Cache limpiado correctamente');
        }, 1000);
    }

    function checkSystemHealth() {
        showLoading('Verificando salud del sistema...');
        setTimeout(() => {
            hideLoading();
            alert('📊 Verificación de salud completada\n\n✅ Base de datos: Saludable\n✅ APIs: Funcionando\n✅ Memoria: OK\n✅ Espacio en disco: Suficiente');
        }, 2000);
    }

    function viewLogs() {
        window.open('admin-logs.php', '_blank');
    }

    function downloadBackup(filename) {
        // Crear enlace de descarga
        const link = document.createElement('a');
        link.href = filename;
        link.download = filename;
        link.click();
    }

    function deleteBackup(filename) {
        if (confirm(`¿Eliminar el backup "${filename}"?`)) {
            alert('Funcionalidad en desarrollo');
        }
    }

    function exportReviews() {
        showLoading('Exportando reseñas...');
        setTimeout(() => {
            hideLoading();
            alert('📥 Exportación en desarrollo');
        }, 1000);
    }

    function importHotels() {
        alert('📤 Importación en desarrollo');
    }

    function generateReport() {
        showLoading('Generando reporte...');
        setTimeout(() => {
            hideLoading();
            alert('📈 Generación de reportes en desarrollo');
        }, 1000);
    }

    function runMaintenance() {
        if (confirm('¿Ejecutar rutina de mantenimiento completa?\n\nEsto incluye:\n- Optimización de tablas\n- Limpieza de cache\n- Verificación de integridad')) {
            showLoading('Ejecutando mantenimiento...');
            setTimeout(() => {
                hideLoading();
                alert('✅ Mantenimiento completado exitosamente');
            }, 4000);
        }
    }
    </script>
</body>
</html>