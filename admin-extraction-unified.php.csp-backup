<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';

// Obtener trabajos de extracción
function getExtractionJobs() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("
            SELECT ej.*, h.nombre_hotel, h.url_booking, h.google_place_id
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

// Obtener estadísticas de extracción
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
        error_log("Error obteniendo stats de extracción: " . $e->getMessage());
        return ['total' => 0, 'completed' => 0, 'pending' => 0, 'failed' => 0];
    }
}

// Obtener reseñas recientes
function getRecentReviews() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as today_count
            FROM reviews 
            WHERE DATE(scraped_at) = CURDATE()
        ");
        $today = $stmt->fetch();
        
        $stmt = $pdo->query("
            SELECT COUNT(*) as total_count
            FROM reviews
        ");
        $total = $stmt->fetch();
        
        return [
            'today' => $today['today_count'] ?? 0,
            'total' => $total['total_count'] ?? 0
        ];
    } catch (PDOException $e) {
        error_log("Error obteniendo reseñas recientes: " . $e->getMessage());
        return ['today' => 0, 'total' => 0];
    }
}

// Obtener hoteles activos
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
                tripadvisor_url,
                CASE 
                    WHEN (url_booking IS NOT NULL AND url_booking != '') THEN 1 ELSE 0 
                END as has_booking,
                CASE 
                    WHEN google_place_id IS NOT NULL AND google_place_id != '' THEN 1 ELSE 0 
                END as has_google,
                CASE 
                    WHEN tripadvisor_url IS NOT NULL AND tripadvisor_url != '' THEN 1 ELSE 0 
                END as has_tripadvisor
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

$jobs = getExtractionJobs();
$stats = getExtractionStats();
$reviews = getRecentReviews();
$hotels = getActiveHotels();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extracción de Reseñas - Panel Admin Kavia</title>
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
                            <a class="nav-link active" href="admin-extraction-unified.php">
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
                    <h1 class="h2"><i class="fas fa-download"></i> Extracción de Reseñas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newExtractionModal">
                                <i class="fas fa-plus"></i> Nueva Extracción
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="refreshJobs()">
                                <i class="fas fa-sync-alt"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Trabajos</h6>
                                        <h3><?php echo $stats['total']; ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-tasks fa-2x"></i>
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
                                        <h6>Completados</h6>
                                        <h3><?php echo $stats['completed']; ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-check fa-2x"></i>
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
                                        <h6>Pendientes</h6>
                                        <h3><?php echo $stats['pending']; ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-clock fa-2x"></i>
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
                                        <h6>Reseñas Hoy</h6>
                                        <h3><?php echo $reviews['today']; ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-star fa-2x"></i>
                                    </div>
                                </div>
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
                            <p class="text-muted">Inicia una nueva extracción para comenzar a obtener reseñas.</p>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newExtractionModal">
                                <i class="fas fa-plus"></i> Crear Primera Extracción
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table id="jobsTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Hotel</th>
                                        <th>Portales</th>
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
                                            $platform = $job['platform'] ?? 'multiple';
                                            if ($platform === 'booking') {
                                                echo '<span class="badge bg-primary"><i class="fas fa-bed"></i> Booking</span>';
                                            } elseif ($platform === 'google') {
                                                echo '<span class="badge bg-success"><i class="fas fa-map-marker-alt"></i> Google</span>';
                                            } elseif ($platform === 'tripadvisor') {
                                                echo '<span class="badge bg-warning"><i class="fas fa-plane"></i> TripAdvisor</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary"><i class="fas fa-globe"></i> Múltiple</span>';
                                            }
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

    <!-- New Extraction Modal -->
    <div class="modal fade" id="newExtractionModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Extracción de Reseñas Multi-Portal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newExtractionForm">
                        <div class="mb-3">
                            <label class="form-label">Hoteles y Portales *</label>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                <div class="row mb-2">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="select_all_hotels" onclick="toggleAllHotels()">
                                            <label class="form-check-label fw-bold" for="select_all_hotels">
                                                Seleccionar todos los hoteles
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="select_all_booking" onclick="toggleAllPortals('booking')">
                                                <label class="form-check-label text-primary" for="select_all_booking">
                                                    <i class="fas fa-bed"></i> Todos Booking
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="select_all_google" onclick="toggleAllPortals('google')" disabled>
                                                <label class="form-check-label text-success" for="select_all_google">
                                                    <i class="fas fa-map-marker-alt"></i> Todos Google (Próximamente)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="select_all_tripadvisor" onclick="toggleAllPortals('tripadvisor')" disabled>
                                                <label class="form-check-label text-warning" for="select_all_tripadvisor">
                                                    <i class="fas fa-plane"></i> Todos TripAdvisor (Próximamente)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr class="my-2">
                                
                                <?php foreach ($hotels as $hotel): ?>
                                <div class="card mb-2 hotel-card">
                                    <div class="card-body p-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input hotel-checkbox" type="checkbox" name="hotel_ids[]" value="<?php echo $hotel['id']; ?>" id="hotel_<?php echo $hotel['id']; ?>">
                                                    <label class="form-check-label fw-bold" for="hotel_<?php echo $hotel['id']; ?>">
                                                        <?php echo htmlspecialchars($hotel['nombre_hotel']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-8">
                                                <div class="d-flex gap-3">
                                                    <?php if ($hotel['has_booking']): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input platform-checkbox booking-checkbox" type="checkbox" name="platforms[<?php echo $hotel['id']; ?>][]" value="booking" id="booking_<?php echo $hotel['id']; ?>">
                                                        <label class="form-check-label text-primary" for="booking_<?php echo $hotel['id']; ?>">
                                                            <i class="fas fa-bed"></i> Booking.com
                                                        </label>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" disabled>
                                                        <label class="form-check-label text-muted">
                                                            <i class="fas fa-bed"></i> Booking (Sin URL)
                                                        </label>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($hotel['has_google']): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input platform-checkbox google-checkbox" type="checkbox" name="platforms[<?php echo $hotel['id']; ?>][]" value="google" id="google_<?php echo $hotel['id']; ?>" disabled>
                                                        <label class="form-check-label text-muted" for="google_<?php echo $hotel['id']; ?>">
                                                            <i class="fas fa-map-marker-alt"></i> Google (Próximamente)
                                                        </label>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" disabled>
                                                        <label class="form-check-label text-muted">
                                                            <i class="fas fa-map-marker-alt"></i> Google (Sin Place ID)
                                                        </label>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($hotel['has_tripadvisor']): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input platform-checkbox tripadvisor-checkbox" type="checkbox" name="platforms[<?php echo $hotel['id']; ?>][]" value="tripadvisor" id="tripadvisor_<?php echo $hotel['id']; ?>" disabled>
                                                        <label class="form-check-label text-muted" for="tripadvisor_<?php echo $hotel['id']; ?>">
                                                            <i class="fas fa-plane"></i> TripAdvisor (Próximamente)
                                                        </label>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" disabled>
                                                        <label class="form-check-label text-muted">
                                                            <i class="fas fa-plane"></i> TripAdvisor (Sin URL)
                                                        </label>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">
                                Selecciona los hoteles y los portales de donde quieres extraer reseñas
                            </small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Máximo de Reseñas por Portal</label>
                                    <input type="number" class="form-control" name="max_reviews" value="20" min="1" max="100">
                                    <small class="text-muted">Recomendado: 20 para pruebas, 50 para producción</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Modo de Extracción</label>
                                    <select class="form-select" name="sync_mode">
                                        <option value="1" selected>🚀 Modo Rápido (2-5 minutos)</option>
                                        <option value="0">⏳ Modo Avanzado (Para grandes volúmenes)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Información de Portales:</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong><i class="fas fa-bed text-primary"></i> Booking.com</strong><br>
                                    <small>✅ Operativo - Actor verificado</small><br>
                                    <small>💰 ~$0.002 por reseña</small>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="fas fa-map-marker-alt text-success"></i> Google Maps</strong><br>
                                    <small>🚧 En desarrollo</small><br>
                                    <small>💰 Costo por determinar</small>
                                </div>
                                <div class="col-md-4">
                                    <strong><i class="fas fa-plane text-warning"></i> TripAdvisor</strong><br>
                                    <small>🚧 En desarrollo</small><br>
                                    <small>💰 Costo por determinar</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="startMultiPortalExtraction()">
                        <i class="fas fa-play"></i> Iniciar Extracción Multi-Portal
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
                    <div class="spinner-border text-success mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <h5>Extracción Multi-Portal en Progreso</h5>
                    <p id="extractionMessage" class="text-muted">Iniciando extracción...</p>
                    <div class="progress mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                             role="progressbar" style="width: 100%"></div>
                    </div>
                    <div id="extractionDetails" class="text-start small text-muted">
                        <div id="extractionProgress"></div>
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

    function toggleAllHotels() {
        const selectAllCheckbox = document.getElementById('select_all_hotels');
        const hotelCheckboxes = document.querySelectorAll('.hotel-checkbox');
        
        hotelCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
    }

    function toggleAllPortals(platform) {
        const selectAllCheckbox = document.getElementById('select_all_' + platform);
        const platformCheckboxes = document.querySelectorAll('.' + platform + '-checkbox');
        
        platformCheckboxes.forEach(checkbox => {
            if (!checkbox.disabled) {
                checkbox.checked = selectAllCheckbox.checked;
            }
        });
    }

    function getSelectedHotelsWithPlatforms() {
        const selections = [];
        const hotelCheckboxes = document.querySelectorAll('.hotel-checkbox:checked');
        
        hotelCheckboxes.forEach(hotelCheckbox => {
            const hotelId = parseInt(hotelCheckbox.value);
            const platforms = [];
            
            // Buscar plataformas seleccionadas para este hotel
            const platformCheckboxes = document.querySelectorAll(`input[name="platforms[${hotelId}][]"]:checked`);
            platformCheckboxes.forEach(platformCheckbox => {
                platforms.push(platformCheckbox.value);
            });
            
            if (platforms.length > 0) {
                selections.push({
                    hotel_id: hotelId,
                    platforms: platforms
                });
            }
        });
        
        return selections;
    }

    function startMultiPortalExtraction() {
        console.log('🚀 Iniciando extracción multi-portal...');
        
        const selections = getSelectedHotelsWithPlatforms();
        if (selections.length === 0) {
            alert('Por favor selecciona al menos un hotel y una plataforma');
            return;
        }

        const form = document.getElementById('newExtractionForm');
        const formData = new FormData(form);
        const maxReviews = parseInt(formData.get('max_reviews') || 20);
        const syncMode = formData.get('sync_mode') === '1';

        // Mostrar progreso
        showExtractionLoader();
        updateExtractionMessage('🔄 Procesando ' + selections.length + ' hotel(es)...');

        let totalExtractions = 0;
        selections.forEach(selection => {
            totalExtractions += selection.platforms.length;
        });

        updateExtractionMessage(`🎯 ${totalExtractions} extracciones programadas`);

        const allExtractions = [];
        
        // Preparar extracciones
        selections.forEach(selection => {
            selection.platforms.forEach(platform => {
                if (platform === 'booking') {
                    // Solo Booking está implementado por ahora
                    const data = {
                        hotel_id: selection.hotel_id,
                        max_reviews: maxReviews,
                        sync_mode: syncMode,
                        timeout: syncMode ? 300 : null
                    };
                    
                    const extraction = fetch('booking-extraction-api.php', {
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
                        updateExtractionProgress(`✅ Hotel ${selection.hotel_id} - Booking: ${result.success ? result.reviews_saved + ' reseñas' : 'Error'}`);
                        return { ...result, hotel_id: selection.hotel_id, platform: 'booking' };
                    })
                    .catch(error => {
                        updateExtractionProgress(`❌ Hotel ${selection.hotel_id} - Booking: Error`);
                        throw error;
                    });
                    
                    allExtractions.push(extraction);
                }
            });
        });
        
        // Ejecutar todas las extracciones
        Promise.allSettled(allExtractions)
        .then(results => {
            console.log('📊 Resultados finales:', results);
            hideExtractionLoader();
            
            const successful = results.filter(r => r.status === 'fulfilled' && r.value.success);
            const failed = results.filter(r => r.status === 'rejected' || !r.value.success);
            
            let message = `🎉 Extracción multi-portal completada:\n`;
            message += `✅ Exitosas: ${successful.length}/${allExtractions.length}\n`;
            
            if (successful.length > 0) {
                const totalReviews = successful.reduce((sum, r) => sum + (r.value.reviews_saved || 0), 0);
                const avgTime = successful.reduce((sum, r) => sum + (r.value.execution_time || 0), 0) / successful.length;
                
                message += `📊 Total de reseñas extraídas: ${totalReviews}\n`;
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
            alert('❌ Error en extracción multi-portal: ' + error.message);
            console.error('Error:', error);
        });
        
        // Cerrar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('newExtractionModal'));
        if (modal) modal.hide();
    }
    
    function showExtractionLoader() {
        const modal = document.getElementById('extractionLoader');
        new bootstrap.Modal(modal).show();
    }
    
    function hideExtractionLoader() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('extractionLoader'));
        if (modal) modal.hide();
    }

    function updateExtractionMessage(message) {
        document.getElementById('extractionMessage').textContent = message;
    }

    function updateExtractionProgress(progressText) {
        const progressDiv = document.getElementById('extractionProgress');
        progressDiv.innerHTML += '<div>' + progressText + '</div>';
        progressDiv.scrollTop = progressDiv.scrollHeight;
    }

    function viewJobDetails(id) {
        alert('Ver detalles del trabajo ID: ' + id + '\n(Funcionalidad disponible)');
    }

    function deleteJob(id) {
        if (confirm('¿Estás seguro de que quieres eliminar este trabajo de extracción?\n\nEsta acción no se puede deshacer.')) {
            console.log('🗑️ Eliminando trabajo ID:', id);
            
            // Intentar eliminar usando la API de Booking como fallback
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

    function refreshJobs() {
        location.reload();
    }

    // Reset form when modal closes
    $('#newExtractionModal').on('hidden.bs.modal', function () {
        document.getElementById('newExtractionForm').reset();
        document.querySelectorAll('.hotel-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.platform-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('select_all_hotels').checked = false;
        document.getElementById('select_all_booking').checked = false;
    });
    </script>
</body>
</html>