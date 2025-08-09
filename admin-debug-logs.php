<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

require_once 'debug-logger.php';

// Manejar acciones
if ($_POST['action'] ?? null === 'clear') {
    DebugLogger::clearLogs();
    $message = "Logs limpiados exitosamente";
}

$logs = DebugLogger::getLogs(200); // Últimas 200 líneas
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
            max-height: 600px;
            overflow-y: auto;
            border: 1px solid #333;
        }
        .log-error { color: #ff6b6b; }
        .log-info { color: #74c0fc; }
        .log-debug { color: #ffd43b; }
    </style>
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

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2"><i class="fas fa-bug"></i> Debug Logs</h1>
                    <div>
                        <button class="btn btn-success" onclick="refreshLogs()">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('¿Limpiar todos los logs?')">
                                <i class="fas fa-trash"></i> Limpiar Logs
                            </button>
                        </form>
                        <a href="admin-dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-terminal"></i> Logs del Sistema (Últimas 200 líneas)
                            <small class="text-muted">Auto-refresh cada 5 segundos</small>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <pre class="log-container p-3 m-0" id="logContainer"><?php echo htmlspecialchars($logs); ?></pre>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Información sobre los logs:</h6>
                        <ul class="mb-0">
                            <li><span class="log-error">ERROR</span> - Errores críticos que impiden el funcionamiento</li>
                            <li><span class="log-info">INFO</span> - Información general del flujo</li>
                            <li><span class="log-debug">DEBUG</span> - Información detallada para depuración</li>
                            <li>Los logs se actualizan automáticamente cada 5 segundos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function refreshLogs() {
            fetch('admin-debug-logs.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Extraer solo el contenido de los logs
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newLogs = doc.getElementById('logContainer');
                if (newLogs) {
                    document.getElementById('logContainer').innerHTML = newLogs.innerHTML;
                }
            })
            .catch(error => {
                console.error('Error refreshing logs:', error);
            });
        }

        // Auto-refresh cada 5 segundos
        setInterval(refreshLogs, 5000);

        // Colorear los logs
        document.addEventListener('DOMContentLoaded', function() {
            const logContainer = document.getElementById('logContainer');
            const content = logContainer.innerHTML;
            
            const coloredContent = content
                .replace(/\[ERROR\]/g, '<span class="log-error">[ERROR]</span>')
                .replace(/\[INFO\]/g, '<span class="log-info">[INFO]</span>')
                .replace(/\[DEBUG\]/g, '<span class="log-debug">[DEBUG]</span>');
                
            logContainer.innerHTML = coloredContent;
        });
    </script>
</body>
</html>