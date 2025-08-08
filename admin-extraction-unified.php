<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';

// Obtener hoteles activos con información de portales
function getActiveHotels() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("
            SELECT 
                id, 
                nombre_hotel, 
                url_booking,
                google_place_id,
                CASE 
                    WHEN (url_booking IS NOT NULL AND url_booking != '') THEN 1 ELSE 0 
                END as has_booking,
                CASE 
                    WHEN google_place_id IS NOT NULL AND google_place_id != '' THEN 1 ELSE 0 
                END as has_google
            FROM hoteles 
            WHERE activo = 1 
            ORDER BY nombre_hotel ASC
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo hoteles activos: " . $e->getMessage());
        return [];
    }
}

// Obtener trabajos de extracción
function getExtractionJobs() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("
            SELECT ej.*, h.nombre_hotel
            FROM extraction_jobs ej 
            LEFT JOIN hoteles h ON ej.hotel_id = h.id 
            ORDER BY ej.created_at DESC 
            LIMIT 50
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo trabajos de extracción: " . $e->getMessage());
        return [];
    }
}

// Obtener estadísticas
function getExtractionStats() {
    $pdo = getDBConnection();
    if (!$pdo) return ['total' => 0, 'completed' => 0, 'pending' => 0, 'failed' => 0];
    
    try {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed
            FROM extraction_jobs
        ");
        return $stmt->fetch();
    } catch (PDOException $e) {
        return ['total' => 0, 'completed' => 0, 'pending' => 0, 'failed' => 0];
    }
}

$hotels = getActiveHotels();
$jobs = getExtractionJobs();
$stats = getExtractionStats();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extracción de Reseñas - Panel Admin Kavia</title>
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
                            <a class="nav-link active" href="admin-extraction-unified.php">
                                <i class="fas fa-download"></i> Extracción
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-logs.php">
                                <i class="fas fa-file-alt"></i> Logs
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-download"></i> Extracción de Reseñas Multi-Portal</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newExtractionModal">
                            <i class="fas fa-plus"></i> Nueva Extracción
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6>Total Trabajos</h6>
                                <h3><?php echo $stats['total']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6>Completados</h6>
                                <h3><?php echo $stats['completed']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6>Pendientes</h6>
                                <h3><?php echo $stats['pending']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6>Errores</h6>
                                <h3><?php echo $stats['failed']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jobs Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Trabajos de Extracción Recientes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jobs)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay trabajos de extracción</h5>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newExtractionModal">
                                <i class="fas fa-plus"></i> Crear Primera Extracción
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Hotel</th>
                                        <th>Portal</th>
                                        <th>Estado</th>
                                        <th>Reseñas</th>
                                        <th>Creado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                    <tr>
                                        <td><?php echo $job['id']; ?></td>
                                        <td><?php echo htmlspecialchars($job['nombre_hotel'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if (($job['platform'] ?? '') === 'booking'): ?>
                                                <span class="badge bg-primary"><i class="fas fa-bed"></i> Booking</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-globe"></i> General</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $job['status'] === 'completed' ? 'success' : ($job['status'] === 'failed' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($job['status'] ?? 'pending'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $job['reviews_extracted'] ?? 0; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($job['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteJob(<?php echo $job['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- New Extraction Modal -->
    <div class="modal fade" id="newExtractionModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Extracción Multi-Portal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="extractionForm">
                        <div class="mb-3">
                            <label class="form-label">Selecciona Hoteles y Portales</label>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($hotels as $hotel): ?>
                                <div class="card mb-2">
                                    <div class="card-body p-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <strong><?php echo htmlspecialchars($hotel['nombre_hotel']); ?></strong>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="d-flex gap-3">
                                                    <?php if ($hotel['has_booking']): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input platform-checkbox" type="checkbox" 
                                                               name="selections[]" 
                                                               value="<?php echo $hotel['id']; ?>:booking" 
                                                               id="booking_<?php echo $hotel['id']; ?>">
                                                        <label class="form-check-label text-primary" for="booking_<?php echo $hotel['id']; ?>">
                                                            <i class="fas fa-bed"></i> Booking.com
                                                        </label>
                                                    </div>
                                                    <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-bed"></i> Booking (Sin URL)</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($hotel['has_google']): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" disabled>
                                                        <label class="form-check-label text-muted">
                                                            <i class="fas fa-map-marker-alt"></i> Google (Próximamente)
                                                        </label>
                                                    </div>
                                                    <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-map-marker-alt"></i> Google (Sin Place ID)</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Máximo Reseñas por Portal</label>
                                    <input type="number" class="form-control" name="max_reviews" value="20" min="1" max="100">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Timeout (segundos)</label>
                                    <input type="number" class="form-control" name="timeout" value="300" min="60" max="600">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="startExtraction()">
                        <i class="fas fa-play"></i> Iniciar Extracción
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-success mb-3" style="width: 3rem; height: 3rem;"></div>
                    <h5>Extracción en Progreso</h5>
                    <p id="loadingMessage">Iniciando extracción...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function startExtraction() {
            const form = document.getElementById('extractionForm');
            const formData = new FormData(form);
            const selections = formData.getAll('selections[]');
            
            if (selections.length === 0) {
                alert('Por favor selecciona al menos un hotel y portal');
                return;
            }
            
            const maxReviews = parseInt(formData.get('max_reviews')) || 20;
            const timeout = parseInt(formData.get('timeout')) || 300;
            
            // Mostrar loading
            const modal = new bootstrap.Modal(document.getElementById('loadingModal'));
            modal.show();
            
            // Cerrar modal de configuración
            const configModal = bootstrap.Modal.getInstance(document.getElementById('newExtractionModal'));
            configModal.hide();
            
            const promises = selections.map(selection => {
                const [hotelId, platform] = selection.split(':');
                
                if (platform === 'booking') {
                    return fetch('booking-extraction-api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Admin-Session': '<?php echo session_id(); ?>',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            hotel_id: parseInt(hotelId),
                            max_reviews: maxReviews,
                            timeout: timeout
                        })
                    }).then(response => response.json());
                }
            }).filter(Boolean);
            
            Promise.allSettled(promises)
            .then(results => {
                modal.hide();
                
                const successful = results.filter(r => r.status === 'fulfilled' && r.value.success);
                const failed = results.filter(r => r.status === 'rejected' || !r.value.success);
                
                let message = `Extracción completada:\n`;
                message += `✅ Exitosas: ${successful.length}\n`;
                message += `❌ Fallidas: ${failed.length}`;
                
                if (successful.length > 0) {
                    const totalReviews = successful.reduce((sum, r) => sum + (r.value.reviews_saved || 0), 0);
                    message += `\n📊 Total reseñas: ${totalReviews}`;
                }
                
                alert(message);
                location.reload();
            });
        }
        
        function deleteJob(id) {
            if (confirm('¿Eliminar este trabajo de extracción?')) {
                fetch(`booking-extraction-api.php?job_id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Admin-Session': '<?php echo session_id(); ?>',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Trabajo eliminado');
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }
    </script>
</body>
</html>