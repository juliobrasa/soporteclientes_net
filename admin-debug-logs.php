<?php
/**
 * Debug Logs - Integrado en Panel de Admin
 * Solo accesible desde el panel de administración autenticado
 */

session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header('Location: admin-login.php');
    exit;
}

require_once 'csp-config.php';
setAdminCSP();
require_once 'debug-logger.php';

// Manejar acciones
$message = '';
if ($_POST['action'] ?? null === 'clear') {
    DebugLogger::clearLogs();
    $message = "Logs limpiados exitosamente";
}

$logs = DebugLogger::getLogs(300);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Logs - Panel Admin Kavia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .log-container {
            background: #1a1a1a;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.3;
            max-height: 500px;
            overflow-y: auto;
            border-radius: 6px;
            padding: 12px;
        }
        .log-line {
            margin: 1px 0;
            white-space: pre-wrap;
        }
        .log-error { color: #ff4444; }
        .log-warning { color: #ffaa00; }
        .log-info { color: #44aaff; }
        .log-success { color: #44ff44; }
        .sidebar { background-color: #f8f9fa; }
        .nav-link.active { background-color: #e9ecef; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-hotel"></i> Kavia Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="#"><i class="fas fa-user"></i> <?php echo $_SESSION['admin_email'] ?? 'Admin'; ?></a>
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
                            <a class="nav-link" href="admin-extraction.php">
                                <i class="fas fa-download"></i> Extracciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-hotels.php">
                                <i class="fas fa-hotel"></i> Hoteles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-logs.php">
                                <i class="fas fa-file-alt"></i> Logs del Sistema
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin-debug-logs.php">
                                <i class="fas fa-bug"></i> Debug Logs
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-bug"></i> Debug Logs</h1>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4><?= count($logs) ?></h4>
                                <small>Total Logs</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h4><?= count(array_filter($logs, fn($l) => strpos($l, 'ERROR') !== false)) ?></h4>
                                <small>Errores</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4><?= count(array_filter($logs, fn($l) => strpos($l, 'WARNING') !== false)) ?></h4>
                                <small>Advertencias</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4><?= count(array_filter($logs, fn($l) => strpos($l, 'INFO') !== false)) ?></h4>
                                <small>Info</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="refresh">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-sync"></i> Actualizar
                                </button>
                            </form>
                            
                            <form method="post" style="display: inline;" onsubmit="return confirm('¿Limpiar todos los logs?')">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fas fa-trash"></i> Limpiar
                                </button>
                            </form>

                            <div class="form-check align-self-center ms-3">
                                <input type="checkbox" class="form-check-input" id="autoRefresh">
                                <label class="form-check-label" for="autoRefresh">Auto-refresh 15s</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logs Display -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-terminal"></i> Registro de Debug (Últimas 300 líneas)</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="log-container" id="logContainer">
                            <?php if (empty($logs)): ?>
                                <div class="text-center text-muted py-4">No hay logs disponibles</div>
                            <?php else: ?>
                                <?php foreach (array_reverse($logs) as $log): ?>
                                    <?php
                                    $logClass = '';
                                    if (strpos($log, 'ERROR') !== false) $logClass = 'log-error';
                                    elseif (strpos($log, 'WARNING') !== false) $logClass = 'log-warning';
                                    elseif (strpos($log, 'INFO') !== false) $logClass = 'log-info';
                                    elseif (strpos($log, 'SUCCESS') !== false) $logClass = 'log-success';
                                    ?>
                                    <div class="log-line <?= $logClass ?>"><?= htmlspecialchars($log) ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll al final
        const logContainer = document.getElementById('logContainer');
        logContainer.scrollTop = logContainer.scrollHeight;

        // Auto-refresh cada 15s
        const autoRefresh = document.getElementById('autoRefresh');
        let refreshInterval;

        autoRefresh.addEventListener('change', function() {
            if (this.checked) {
                refreshInterval = setInterval(() => location.reload(), 15000);
            } else {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>