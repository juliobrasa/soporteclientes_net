<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';

// Obtener logs del sistema
function getSystemLogs($limit = 100) {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("SELECT sl.*, h.nombre_hotel 
                              FROM system_logs sl
                              LEFT JOIN hoteles h ON sl.hotel_id = h.id
                              ORDER BY sl.created_at DESC 
                              LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo logs: " . $e->getMessage());
        return [];
    }
}

// Obtener estadísticas de logs
function getLogStats() {
    $pdo = getDBConnection();
    if (!$pdo) return ['total' => 0, 'today' => 0, 'errors' => 0, 'warnings' => 0];
    
    try {
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today,
            COUNT(CASE WHEN level = 'error' THEN 1 END) as errors,
            COUNT(CASE WHEN level = 'warning' THEN 1 END) as warnings
            FROM system_logs");
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error obteniendo stats logs: " . $e->getMessage());
        return ['total' => 0, 'today' => 0, 'errors' => 0, 'warnings' => 0];
    }
}

$logs = getSystemLogs(200);
$stats = getLogStats();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs del Sistema - Panel Admin Kavia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
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
                            <a class="nav-link" href="admin-tools.php">
                                <i class="fas fa-tools"></i> Herramientas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin-logs.php">
                                <i class="fas fa-file-alt"></i> Logs del Sistema
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-file-alt"></i> Logs del Sistema</h1>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-danger" id="clearLogs">
                            <i class="fas fa-trash"></i> Limpiar Logs
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="refreshLogs">
                            <i class="fas fa-sync"></i> Actualizar
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Logs</h6>
                                        <h3><?php echo $stats['total']; ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-file-alt fa-2x"></i>
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
                                        <h6>Logs Hoy</h6>
                                        <h3><?php echo $stats['today']; ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar-day fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Errores</h6>
                                        <h3><?php echo $stats['errors']; ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-exclamation-circle fa-2x"></i>
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
                                        <h6>Advertencias</h6>
                                        <h3><?php echo $stats['warnings']; ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select id="levelFilter" class="form-select">
                                    <option value="">Todos los niveles</option>
                                    <option value="info">Info</option>
                                    <option value="warning">Warning</option>
                                    <option value="error">Error</option>
                                    <option value="success">Success</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="hotelFilter" class="form-select">
                                    <option value="">Todos los hoteles</option>
                                    <?php 
                                    $hotels = array_unique(array_filter(array_column($logs, 'nombre_hotel')));
                                    foreach ($hotels as $hotel): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($hotel); ?>">
                                            <?php echo htmlspecialchars($hotel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="dateFilter" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                                    <i class="fas fa-times"></i> Limpiar Filtros
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Logs Recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="logsTable" class="table table-striped table-hover table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Nivel</th>
                                        <th>Hotel</th>
                                        <th>Acción</th>
                                        <th>Mensaje</th>
                                        <th>Detalles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>
                                            <small><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $level_colors = [
                                                'info' => 'bg-info',
                                                'success' => 'bg-success',
                                                'warning' => 'bg-warning text-dark',
                                                'error' => 'bg-danger'
                                            ];
                                            $color = $level_colors[$log['level']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $color; ?>">
                                                <?php echo strtoupper($log['level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($log['nombre_hotel']): ?>
                                                <small><?php echo htmlspecialchars($log['nombre_hotel']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Sistema</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($log['action'] ?? '-'); ?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $message = htmlspecialchars($log['message'] ?? '');
                                            echo strlen($message) > 100 ? substr($message, 0, 100) . '...' : $message;
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['details'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                        onclick="showDetails(<?php echo htmlspecialchars(json_encode($log['details'])); ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Log</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="detailsContent" class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    $(document).ready(function() {
        var table = $('#logsTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            pageLength: 50,
            order: [[0, 'desc']],
            columnDefs: [
                { targets: [0], width: '140px' },
                { targets: [1], width: '80px' },
                { targets: [2], width: '120px' },
                { targets: [5], width: '60px', orderable: false }
            ]
        });

        // Filtros personalizados
        $('#levelFilter').on('change', function() {
            table.column(1).search(this.value).draw();
        });

        $('#hotelFilter').on('change', function() {
            table.column(2).search(this.value).draw();
        });

        $('#clearFilters').on('click', function() {
            $('#levelFilter, #hotelFilter, #dateFilter').val('');
            table.search('').columns().search('').draw();
        });

        $('#refreshLogs').on('click', function() {
            location.reload();
        });
    });

    function showDetails(details) {
        $('#detailsContent').text(JSON.stringify(details, null, 2));
    }
    </script>
</body>
</html>