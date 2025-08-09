<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';

// Obtener trabajos de extracción por portal
function getExtractionJobsByPortal() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("
            SELECT ej.*, h.nombre_hotel, h.url_booking, h.booking_url, h.google_place_id
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

// Obtener estadísticas por portal
function getPortalStats() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("
            SELECT 
                platform,
                COUNT(*) as total_jobs,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                SUM(reviews_extracted) as total_reviews
            FROM extraction_jobs
            WHERE platform IS NOT NULL
            GROUP BY platform
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo stats por portal: " . $e->getMessage());
        return [];
    }
}

// Obtener hoteles activos con URLs configuradas
function getHotelsWithPortals() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("
            SELECT 
                id, 
                nombre_hotel, 
                url_booking,
                booking_url,
                google_place_id,
                tripadvisor_url,
                CASE 
                    WHEN (url_booking IS NOT NULL AND url_booking != '') OR (booking_url IS NOT NULL AND booking_url != '') THEN 1 ELSE 0 
                END as has_booking,
                CASE 
                    WHEN google_place_id IS NOT NULL THEN 1 ELSE 0 
                END as has_google,
                CASE 
                    WHEN tripadvisor_url IS NOT NULL THEN 1 ELSE 0 
                END as has_tripadvisor
            FROM hoteles 
            WHERE activo = 1 
            ORDER BY nombre_hotel ASC
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo hoteles con portales: " . $e->getMessage());
        return [];
    }
}

$jobs = getExtractionJobsByPortal();
$portalStats = getPortalStats();
$hotels = getHotelsWithPortals();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extracción por Portales - Panel Admin Kavia</title>
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
                            <a class="nav-link active" href="admin-extraction-portals.php">
                                <i class="fas fa-download"></i> Extracción por Portales
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
                    <h1 class="h2"><i class="fas fa-download"></i> Extracción por Portales</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary" onclick="refreshPage()">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Portal Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-bed"></i> Booking.com</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $bookingStats = array_filter($portalStats, function($stat) {
                                    return $stat['platform'] === 'booking';
                                });
                                $bookingStats = reset($bookingStats) ?: ['total_jobs' => 0, 'completed' => 0, 'total_reviews' => 0];
                                ?>
                                <div class="mb-2">
                                    <small class="text-muted">Trabajos:</small>
                                    <span class="badge bg-primary"><?php echo $bookingStats['total_jobs']; ?></span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Completados:</small>
                                    <span class="badge bg-success"><?php echo $bookingStats['completed']; ?></span>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Reseñas extraídas:</small>
                                    <span class="badge bg-info"><?php echo number_format($bookingStats['total_reviews']); ?></span>
                                </div>
                                <button class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#bookingExtractionModal">
                                    <i class="fas fa-plus"></i> Nueva Extracción Booking
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Google Maps</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $googleStats = array_filter($portalStats, function($stat) {
                                    return $stat['platform'] === 'google';
                                });
                                $googleStats = reset($googleStats) ?: ['total_jobs' => 0, 'completed' => 0, 'total_reviews' => 0];
                                ?>
                                <div class="mb-2">
                                    <small class="text-muted">Trabajos:</small>
                                    <span class="badge bg-success"><?php echo $googleStats['total_jobs']; ?></span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Completados:</small>
                                    <span class="badge bg-success"><?php echo $googleStats['completed']; ?></span>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Reseñas extraídas:</small>
                                    <span class="badge bg-info"><?php echo number_format($googleStats['total_reviews']); ?></span>
                                </div>
                                <button class="btn btn-success btn-sm w-100" disabled>
                                    <i class="fas fa-clock"></i> Próximamente
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-plane"></i> TripAdvisor</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $tripadvisorStats = array_filter($portalStats, function($stat) {
                                    return $stat['platform'] === 'tripadvisor';
                                });
                                $tripadvisorStats = reset($tripadvisorStats) ?: ['total_jobs' => 0, 'completed' => 0, 'total_reviews' => 0];
                                ?>
                                <div class="mb-2">
                                    <small class="text-muted">Trabajos:</small>
                                    <span class="badge bg-warning"><?php echo $tripadvisorStats['total_jobs']; ?></span>
                                </div>
                                <div class="mb-2">
                                    <small class="text-muted">Completados:</small>
                                    <span class="badge bg-success"><?php echo $tripadvisorStats['completed']; ?></span>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Reseñas extraídas:</small>
                                    <span class="badge bg-info"><?php echo number_format($tripadvisorStats['total_reviews']); ?></span>
                                </div>
                                <button class="btn btn-warning btn-sm w-100" disabled>
                                    <i class="fas fa-clock"></i> Próximamente
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jobs Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Historial de Extracciones por Portal</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jobs)): ?>
                        <div class="text-center p-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay extracciones por portal</h5>
                            <p class="text-muted">Inicia una nueva extracción usando los botones de arriba.</p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table id="jobsTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Hotel</th>
                                        <th>Portal</th>
                                        <th>Estado</th>
                                        <th>Progreso</th>
                                        <th>Reseñas</th>
                                        <th>Iniciado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                    <tr data-job-id="<?php echo $job['id']; ?>">
                                        <td><?php echo $job['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($job['nombre_hotel'] ?? 'Hotel N/A'); ?></strong></td>
                                        <td>
                                            <?php 
                                            $platform = $job['platform'] ?? 'general';
                                            $platformIcons = [
                                                'booking' => '<i class="fas fa-bed text-primary"></i> Booking',
                                                'google' => '<i class="fas fa-map-marker-alt text-success"></i> Google',
                                                'tripadvisor' => '<i class="fas fa-plane text-warning"></i> TripAdvisor',
                                                'general' => '<i class="fas fa-globe text-secondary"></i> General'
                                            ];
                                            echo $platformIcons[$platform] ?? $platformIcons['general'];
                                            ?>
                                        </td>
                                        <td class="job-status">
                                            <?php 
                                            $status = $job['status'] ?? 'pending';
                                            $status_colors = [
                                                'pending' => 'bg-warning',
                                                'running' => 'bg-info',
                                                'completed' => 'bg-success',
                                                'failed' => 'bg-danger',
                                                'cancelled' => 'bg-secondary'
                                            ];
                                            $color = $status_colors[$status] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $color; ?>">
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td class="job-progress">
                                            <?php 
                                            $progress = $job['progress'] ?? 0;
                                            $progressColor = $progress == 100 ? 'bg-success' : ($progress > 50 ? 'bg-info' : 'bg-warning');
                                            ?>
                                            <div class="progress" style="width: 100px;">
                                                <div class="progress-bar <?php echo $progressColor; ?>" role="progressbar" style="width: <?php echo $progress; ?>%">
                                                    <?php echo $progress; ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td class="job-reviews">
                                            <span class="badge bg-primary"><?php echo $job['reviews_extracted'] ?? 0; ?></span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($job['created_at'] ?? 'now')); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" title="Ver Detalles" onclick="viewJobDetails(<?php echo $job['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="deleteJob(<?php echo $job['id']; ?>)">
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
            </main>
        </div>
    </div>

    <!-- Booking Extraction Modal -->
    <div class="modal fade" id="bookingExtractionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-bed text-primary"></i> Nueva Extracción de Booking.com</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="bookingExtractionForm">
                        <div class="mb-3">
                            <label class="form-label">Hoteles con URL de Booking *</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="select_all_booking_hotels" onclick="toggleAllBookingHotels()">
                                    <label class="form-check-label fw-bold" for="select_all_booking_hotels">
                                        Seleccionar todos los hoteles con Booking
                                    </label>
                                </div>
                                <hr class="my-2">
                                <?php foreach ($hotels as $hotel): ?>
                                    <?php if ($hotel['has_booking']): ?>
                                    <div class="form-check">
                                        <input class="form-check-input booking-hotel-checkbox" type="checkbox" name="hotel_ids[]" value="<?php echo $hotel['id']; ?>" id="booking_hotel_<?php echo $hotel['id']; ?>">
                                        <label class="form-check-label" for="booking_hotel_<?php echo $hotel['id']; ?>">
                                            <i class="fas fa-bed text-primary"></i> <?php echo htmlspecialchars($hotel['nombre_hotel']); ?>
                                            <small class="text-muted d-block"><?php 
                                                $bookingUrl = $hotel['url_booking'] ?: $hotel['booking_url'];
                                                echo $bookingUrl ? parse_url($bookingUrl, PHP_URL_HOST) : 'URL no configurada';
                                            ?></small>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">
                                Solo se muestran hoteles con URLs de Booking.com configuradas
                            </small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Máximo de Reseñas por Hotel</label>
                                    <input type="number" class="form-control" name="max_reviews" value="50" min="1" max="200">
                                    <small class="text-muted">Recomendado: 50 para pruebas, 100 para producción</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Modo de Extracción</label>
                                    <select class="form-select" name="sync_mode">
                                        <option value="1" selected>🚀 Modo Rápido (Síncrono)</option>
                                        <option value="0">⏳ Modo Avanzado (Asíncrono)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <strong>Booking.com Extractor</strong><br>
                            Usa el actor verificado de Apify: <code>voyager/booking-reviews-scraper</code><br>
                            💰 Costo estimado: ~$0.002 por reseña extraída
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="startBookingExtraction()">
                        <i class="fas fa-play"></i> Iniciar Extracción Booking
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Extraction Loader Modal -->
    <div class="modal fade" id="extractionLoader" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h5>Extracción de Booking en Progreso</h5>
                    <p id="extractionMessage" class="text-muted">Iniciando extracción de Booking.com...</p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                             role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    <?php if (!empty($jobs)): ?>
    $(document).ready(function() {
        $('#jobsTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[0, 'desc']]
        });
    });
    <?php endif; ?>

    function toggleAllBookingHotels() {
        const selectAllCheckbox = document.getElementById('select_all_booking_hotels');
        const hotelCheckboxes = document.querySelectorAll('.booking-hotel-checkbox');
        
        hotelCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    }

    function getSelectedBookingHotels() {
        const hotels = [];
        const checkboxes = document.querySelectorAll('input[name="hotel_ids[]"]:checked');
        checkboxes.forEach(cb => hotels.push(parseInt(cb.value)));
        return hotels;
    }

    function startBookingExtraction() {
        console.log('🚀 Iniciando extracción de Booking...');
        
        const selectedHotels = getSelectedBookingHotels();
        if (selectedHotels.length === 0) {
            alert('Por favor selecciona al menos un hotel con URL de Booking');
            return;
        }

        const form = document.getElementById('bookingExtractionForm');
        const formData = new FormData(form);
        const maxReviews = parseInt(formData.get('max_reviews') || 50);
        const syncMode = formData.get('sync_mode') === '1';

        showExtractionLoader('🏨 Procesando ' + selectedHotels.length + ' hotel(es) de Booking...');

        // Procesar cada hotel de Booking
        const extractions = selectedHotels.map(hotelId => {
            const data = {
                hotel_id: hotelId,
                max_reviews: maxReviews,
                sync_mode: syncMode,
                timeout: syncMode ? 300 : null
            };
            
            console.log(`📋 Datos para hotel ${hotelId}:`, data);
            
            return fetch('booking-extraction-api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Admin-Session': '<?php echo session_id(); ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                console.log(`📋 Resultado Booking hotel ${hotelId}:`, result);
                return result;
            })
            .catch(error => {
                console.error(`❌ Error Booking hotel ${hotelId}:`, error);
                throw error;
            });
        });
        
        // Ejecutar todas las extracciones de Booking
        Promise.allSettled(extractions)
        .then(results => {
            console.log('📊 Resultados Booking:', results);
            hideExtractionLoader();
            
            const successful = results.filter(r => r.status === 'fulfilled' && r.value.success);
            const failed = results.filter(r => r.status === 'rejected' || !r.value.success);
            
            let message = `🏨 Extracción de Booking completada:\n`;
            message += `✅ Exitosas: ${successful.length}/${selectedHotels.length}\n`;
            
            if (successful.length > 0) {
                const totalReviews = successful.reduce((sum, r) => sum + (r.value.reviews_saved || 0), 0);
                const avgTime = successful.reduce((sum, r) => sum + (r.value.execution_time || 0), 0) / successful.length;
                
                message += `📊 Total reseñas de Booking: ${totalReviews}\n`;
                message += `⏱️ Tiempo promedio: ${Math.round(avgTime)}s`;
            }
            
            if (failed.length > 0) {
                message += `\n❌ Errores: ${failed.length}`;
            }
            
            alert(message);
            setTimeout(() => location.reload(), 2000);
        })
        .catch(error => {
            hideExtractionLoader();
            alert('❌ Error en extracción de Booking: ' + error.message);
            console.error('Error:', error);
        });
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('bookingExtractionModal'));
        if (modal) modal.hide();
    }
    
    function showExtractionLoader(message) {
        const modal = document.getElementById('extractionLoader');
        document.getElementById('extractionMessage').textContent = message;
        new bootstrap.Modal(modal).show();
    }
    
    function hideExtractionLoader() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('extractionLoader'));
        if (modal) modal.hide();
    }

    function viewJobDetails(id) {
        alert('Ver detalles del trabajo ID: ' + id + '\n(Funcionalidad en desarrollo)');
    }

    function deleteJob(id) {
        if (confirm('¿Estás seguro de que quieres eliminar este trabajo de extracción?\n\nEsta acción no se puede deshacer.')) {
            console.log('🗑️ Eliminando trabajo ID:', id);
            
            fetch(`booking-extraction-api.php?job_id=${id}`, {
                method: 'DELETE',
                headers: {
                    'X-Admin-Session': '<?php echo session_id(); ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Trabajo eliminado correctamente');
                    location.reload();
                } else {
                    alert('❌ Error: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                alert('❌ Error de conexión al eliminar');
                console.error('❌ Error:', error);
            });
        }
    }

    function refreshPage() {
        location.reload();
    }

    // Reset form when modal closes
    $('#bookingExtractionModal').on('hidden.bs.modal', function () {
        document.getElementById('bookingExtractionForm').reset();
        document.querySelectorAll('.booking-hotel-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('select_all_booking_hotels').checked = false;
    });
    </script>
</body>
</html>