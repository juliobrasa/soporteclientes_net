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
        $stmt = $pdo->query("SELECT id, nombre_hotel FROM hoteles WHERE activo = 1 ORDER BY nombre_hotel ASC");
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
                            <a class="nav-link active" href="admin-extraction.php">
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
                        <li class="nav-item">
                            <a class="nav-link" href="admin-debug-logs.php" style="color: #ffc107;">
                                <i class="fas fa-bug"></i> Debug Logs
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
                            <a href="admin-place-ids.php" class="btn btn-warning">
                                <i class="fas fa-map-marker-alt"></i> Configurar Place IDs
                            </a>
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
                                                <?php if ($status == 'running'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" title="Pausar" onclick="pauseJob(<?php echo $job['id']; ?>)">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if (in_array($status, ['pending', 'failed'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Reiniciar" onclick="restartJob(<?php echo $job['id']; ?>)">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                                <?php endif; ?>
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
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Extracción de Reseñas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newExtractionForm">
                        <div class="mb-3">
                            <label class="form-label">Hoteles *</label>
                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="select_all_hotels" onclick="toggleAllHotels()">
                                    <label class="form-check-label fw-bold" for="select_all_hotels">
                                        Seleccionar todos
                                    </label>
                                </div>
                                <hr class="my-2">
                                <?php foreach ($hotels as $hotel): ?>
                                    <div class="form-check">
                                        <input class="form-check-input hotel-checkbox" type="checkbox" name="hotel_ids[]" value="<?php echo $hotel['id']; ?>" id="hotel_<?php echo $hotel['id']; ?>">
                                        <label class="form-check-label" for="hotel_<?php echo $hotel['id']; ?>">
                                            <?php echo htmlspecialchars($hotel['nombre_hotel']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">
                                Selecciona uno o más hoteles para extraer reseñas
                            </small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Máximo de Reseñas</label>
                                    <input type="number" class="form-control" name="max_reviews" value="200" min="1" max="1000">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prioridad</label>
                                    <select class="form-select" name="priority">
                                        <option value="low">Baja</option>
                                        <option value="normal" selected>Normal</option>
                                        <option value="high">Alta</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Modo de Extracción</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="extraction_mode" id="mode_sync" value="sync" checked>
                                        <label class="form-check-label" for="mode_sync">
                                            <strong>🚀 Modo Rápido (Recomendado)</strong><br>
                                            <small class="text-muted">Resultados inmediatos en 30-300 segundos. Máximo 100 reseñas.</small>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="extraction_mode" id="mode_async" value="async">
                                        <label class="form-check-label" for="mode_async">
                                            <strong>⏳ Modo Avanzado</strong><br>
                                            <small class="text-muted">Mayor cantidad de reseñas. Requiere polling.</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Plataformas de Reseñas</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="platforms" value="tripadvisor" checked>
                                        <label class="form-check-label">TripAdvisor</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="platforms" value="booking" checked>
                                        <label class="form-check-label">Booking.com</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="platforms" value="google" checked>
                                        <label class="form-check-label">Google Maps</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="platforms" value="expedia">
                                        <label class="form-check-label">Expedia</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="platforms" value="hotels">
                                        <label class="form-check-label">Hotels.com</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="platforms" value="airbnb">
                                        <label class="form-check-label">Airbnb</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="platforms" value="yelp">
                                        <label class="form-check-label">Yelp</label>
                                    </div>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                💰 Costo: $1.50 por cada 1,000 reseñas extraídas
                            </small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="sentiment_analysis" checked>
                                <label class="form-check-label">Análisis de sentimientos automático</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="generate_alerts">
                                <label class="form-check-label">Generar alertas automáticas</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="startExtraction()">Iniciar Extracción</button>
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
                    <h5>Extracción en Progreso</h5>
                    <p id="extractionMessage" class="text-muted">Iniciando extracción...</p>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
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

    function startExtraction() {
        console.log('🚀 Iniciando extracción...');
        const form = document.getElementById('newExtractionForm');
        const formData = new FormData(form);
        
        // Obtener hoteles seleccionados
        const selectedHotels = getSelectedHotels();
        console.log('🏨 Hoteles seleccionados:', selectedHotels);
        
        if (selectedHotels.length === 0) {
            console.error('❌ No hay hoteles seleccionados');
            alert('Por favor selecciona al menos un hotel');
            return;
        }
        
        // Mostrar loader con mensaje específico según el modo
        const loaderMessage = isSync 
            ? '🚀 Ejecutando extracción rápida... Esto puede tomar hasta 5 minutos.'
            : '⏳ Iniciando extracción avanzada con Apify Hotel Review Aggregator...';
        showExtractionLoader(loaderMessage);
        
        // Detectar modo de extracción
        const extractionMode = document.querySelector('input[name="extraction_mode"]:checked').value;
        const isSync = extractionMode === 'sync';
        
        console.log(`🔧 Modo de extracción: ${extractionMode}`);
        
        // Procesar cada hotel seleccionado
        const extractions = selectedHotels.map((hotelId, index) => {
            const maxReviews = parseInt(formData.get('max_reviews') || 100);
            
            const data = {
                hotel_id: hotelId,
                max_reviews: isSync ? Math.min(maxReviews, 100) : maxReviews, // Limitar en modo sync
                platforms: getSelectedPlatforms(),
                languages: ['en', 'es'],
                sentiment_analysis: formData.get('sentiment_analysis') ? true : false,
                generate_alerts: formData.get('generate_alerts') ? true : false,
                sync_mode: isSync,
                timeout: isSync ? 300 : null // 5 minutos máximo para sync
            };
            
            console.log(`📋 Datos para hotel ${hotelId}:`, data);
            
            return fetch('api-extraction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Admin-Session': '<?php echo session_id(); ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log(`📡 Respuesta HTTP ${response.status} para hotel ${hotelId}`);
                return response.json();
            })
            .then(result => {
                console.log(`📋 Resultado para hotel ${hotelId}:`, result);
                return result;
            })
            .catch(error => {
                console.error(`❌ Error para hotel ${hotelId}:`, error);
                throw error;
            });
        });
        
        // Ejecutar todas las extracciones
        Promise.allSettled(extractions)
        .then(results => {
            console.log('📊 Resultados finales:', results);
            hideExtractionLoader();
            
            const successful = results.filter(r => r.status === 'fulfilled' && r.value.success);
            const failed = results.filter(r => r.status === 'rejected' || !r.value.success);
            
            console.log(`✅ Exitosas: ${successful.length}/${selectedHotels.length}`);
            console.log(`❌ Fallidas: ${failed.length}/${selectedHotels.length}`);
            
            const syncResults = successful.filter(r => r.value.sync_mode);
            const asyncResults = successful.filter(r => !r.value.sync_mode);
            
            let message = '';
            
            if (syncResults.length > 0) {
                const totalReviews = syncResults.reduce((sum, r) => sum + (r.value.reviews_saved || 0), 0);
                const avgTime = syncResults.reduce((sum, r) => sum + (r.value.execution_time || 0), 0) / syncResults.length;
                
                message += `🚀 Extracciones rápidas completadas: ${syncResults.length}/${selectedHotels.length}\n`;
                message += `📊 Total de reseñas obtenidas: ${totalReviews}\n`;
                message += `⏱️ Tiempo promedio: ${Math.round(avgTime)}s\n\n`;
                message += '¡Los datos ya están disponibles en tu base de datos!';
            }
            
            if (asyncResults.length > 0) {
                const totalCost = asyncResults.reduce((sum, r) => sum + parseFloat(r.value.cost_estimate || 0), 0);
                message += `\n\n⏳ Extracciones avanzadas iniciadas: ${asyncResults.length}\n`;
                message += `💰 Costo estimado: $${totalCost.toFixed(2)}\n`;
                message += 'Estas extracciones pueden tomar varios minutos.';
            }
            
            if (failed.length > 0) {
                message += `\n\n❌ Errores: ${failed.length}`;
                // Mostrar detalles de los errores
                failed.forEach((result, index) => {
                    if (result.status === 'rejected') {
                        console.error(`Error en hotel ${selectedHotels[index]}:`, result.reason);
                    } else if (!result.value.success) {
                        console.error(`Error en hotel ${selectedHotels[index]}:`, result.value);
                    }
                });
                message += '\n\nRevisa la consola del navegador para más detalles.';
            }
            
            alert(message);
            
            // Solo iniciar polling si hay extracciones asíncronas
            if (asyncResults.length > 0) {
                // Esperar un poco para asegurar que el DOM esté listo
                setTimeout(() => {
                    startProgressPolling();
                }, 1000);
            } else if (syncResults.length > 0) {
                // Para extracciones síncronas, solo recargar la tabla
                setTimeout(() => location.reload(), 2000);
            } else {
                location.reload();
            }
        })
        .catch(error => {
            hideExtractionLoader();
            alert('❌ Error de conexión: ' + error.message);
            console.error('Error:', error);
        });
        
        bootstrap.Modal.getInstance(document.getElementById('newExtractionModal')).hide();
    }
    
    function getSelectedPlatforms() {
        const platforms = [];
        const checkboxes = document.querySelectorAll('input[name="platforms"]:checked');
        checkboxes.forEach(cb => platforms.push(cb.value));
        return platforms.length > 0 ? platforms : ['tripadvisor', 'booking', 'google'];
    }
    
    function getSelectedHotels() {
        const hotels = [];
        const checkboxes = document.querySelectorAll('input[name="hotel_ids[]"]:checked');
        checkboxes.forEach(cb => hotels.push(parseInt(cb.value)));
        return hotels;
    }
    
    function toggleAllHotels() {
        const selectAllCheckbox = document.getElementById('select_all_hotels');
        const hotelCheckboxes = document.querySelectorAll('.hotel-checkbox');
        
        hotelCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
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
        alert('Ver detalles del trabajo ID: ' + id);
    }

    function pauseJob(id) {
        if (confirm('¿Pausar la extracción?')) {
            alert('Pausar trabajo ID: ' + id);
        }
    }

    function restartJob(id) {
        if (confirm('¿Reiniciar la extracción?')) {
            alert('Reiniciar trabajo ID: ' + id);
        }
    }

    function deleteJob(id) {
        if (confirm('¿Estás seguro de que quieres eliminar este trabajo de extracción?\n\nEsta acción no se puede deshacer.')) {
            console.log('🗑️ Eliminando trabajo ID:', id);
            
            fetch(`api-extraction.php?job_id=${id}`, {
                method: 'DELETE',
                headers: {
                    'X-Admin-Session': '<?php echo session_id(); ?>',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                console.log('📋 Respuesta eliminación:', data);
                
                if (data.success) {
                    showToast('✅ Trabajo eliminado correctamente', 'success');
                    
                    // Remover la fila de la tabla
                    const row = document.querySelector(`tr[data-job-id="${id}"]`);
                    if (row) {
                        row.remove();
                        console.log('✅ Fila eliminada del DOM');
                    }
                    
                    // Si la tabla queda vacía, recargar para mostrar mensaje
                    const tbody = document.querySelector('#jobsTable tbody');
                    if (tbody && tbody.children.length === 0) {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    showToast('❌ Error: ' + (data.error || 'Error desconocido'), 'danger');
                    console.error('❌ Error eliminando:', data);
                }
            })
            .catch(error => {
                showToast('❌ Error de conexión al eliminar', 'danger');
                console.error('❌ Error:', error);
            });
        }
    }

    function refreshJobs() {
        location.reload();
    }

    // Variables para polling
    let pollingInterval = null;
    let pollingCount = 0;
    const MAX_POLLING_COUNT = 30; // 60 minutos máximo (2min * 30 = 3600seg)
    
    // Sistema de polling para actualizar estado de extracciones
    function startProgressPolling() {
        console.log('🔄 Iniciando polling de progreso...');
        
        // Verificar que estamos en el DOM correcto
        if (!document.body) {
            console.error('❌ DOM no está listo para polling');
            return;
        }
        
        // Limpiar polling anterior si existe
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        
        // Mostrar indicador de que está activo el polling
        try {
            showPollingIndicator();
        } catch (error) {
            console.error('❌ Error mostrando indicador:', error);
        }
        
        pollingCount = 0;
        pollingInterval = setInterval(() => {
            pollingCount++;
            console.log(`🔄 Polling #${pollingCount}`);
            
            try {
                updateExtractionProgress();
            } catch (error) {
                console.error('❌ Error en polling:', error);
                stopProgressPolling();
                showToast('⚠️ Error en sistema de polling', 'warning');
            }
            
            // Detener polling después del máximo
            if (pollingCount >= MAX_POLLING_COUNT) {
                stopProgressPolling();
                showToast('⏰ Tiempo límite de seguimiento alcanzado. Actualiza la página manualmente.', 'warning');
            }
        }, 120000); // Cada 2 minutos
        
        console.log('✅ Sistema de polling iniciado correctamente');
    }
    
    function stopProgressPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
        hidePollingIndicator();
        console.log('⏹️  Polling detenido');
    }
    
    function updateExtractionProgress() {
        fetch('api-extraction.php?action=get_recent', {
            headers: {
                'X-Admin-Session': '<?php echo session_id(); ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📊 Datos de polling recibidos:', data);
            
            if (data.success && data.data) {
                updateJobsTable(data.data);
                
                // Verificar si todas las extracciones han terminado
                const activeJobs = data.data.filter(job => 
                    job.status === 'pending' || job.status === 'running'
                );
                
                console.log(`🔄 Jobs activos: ${activeJobs.length}/${data.data.length}`);
                
                if (activeJobs.length === 0 && data.data.length > 0) {
                    stopProgressPolling();
                    showToast('✅ Todas las extracciones han terminado', 'success');
                    setTimeout(() => location.reload(), 2000); // Recargar después de mostrar toast
                }
            } else if (!data.success) {
                console.error('❌ Error en API:', data.error || 'Error desconocido');
                showToast('⚠️ Error al actualizar estado de extracciones', 'warning');
            }
        })
        .catch(error => {
            console.error('❌ Error en polling:', error);
            showToast('⚠️ Error de conexión en polling', 'warning');
        });
    }
    
    function updateJobsTable(jobs) {
        if (!jobs || !Array.isArray(jobs)) {
            console.warn('⚠️ No hay datos de jobs para actualizar');
            return;
        }
        
        // Actualizar filas existentes en la tabla
        jobs.forEach(job => {
            if (!job.id) return;
            
            const row = document.querySelector(`tr[data-job-id="${job.id}"]`);
            if (row) {
                // Actualizar estado
                const statusCell = row.querySelector('.job-status');
                if (statusCell && job.status) {
                    statusCell.innerHTML = getStatusBadge(job.status);
                }
                
                // Actualizar progreso
                const progressCell = row.querySelector('.job-progress');
                if (progressCell) {
                    const progress = job.progress || 0;
                    progressCell.innerHTML = getProgressBar(progress);
                }
                
                // Actualizar contador de reseñas
                const reviewsCell = row.querySelector('.job-reviews');
                if (reviewsCell) {
                    const reviews = job.reviews_extracted || 0;
                    reviewsCell.innerHTML = `<span class="badge bg-primary">${reviews}</span>`;
                }
            }
        });
    }
    
    function getStatusBadge(status) {
        const statusColors = {
            'pending': 'bg-warning',
            'running': 'bg-info',
            'completed': 'bg-success',
            'failed': 'bg-danger',
            'cancelled': 'bg-secondary'
        };
        const color = statusColors[status] || 'bg-secondary';
        return `<span class="badge ${color}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    }
    
    function getProgressBar(progress) {
        const progressColor = progress == 100 ? 'bg-success' : (progress > 50 ? 'bg-info' : 'bg-warning');
        return `
            <div class="progress" style="width: 100px;">
                <div class="progress-bar ${progressColor}" role="progressbar" style="width: ${progress}%">
                    ${progress}%
                </div>
            </div>
        `;
    }
    
    function showPollingIndicator() {
        // Evitar duplicados
        if (document.getElementById('polling-indicator')) {
            return;
        }
        
        const indicator = document.createElement('div');
        indicator.id = 'polling-indicator';
        indicator.className = 'alert alert-info d-flex align-items-center mb-3';
        indicator.innerHTML = `
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Actualizando...</span>
            </div>
            <span>🔄 Actualizando estado de extracciones automáticamente...</span>
            <button type="button" class="btn-close ms-auto" onclick="stopProgressPolling()"></button>
        `;
        
        // Intentar múltiples ubicaciones para insertar el indicador
        let inserted = false;
        
        // Opción 1: Después del breadcrumb/header
        const mainHeader = document.querySelector('main h1');
        if (mainHeader && !inserted) {
            mainHeader.parentNode.insertBefore(indicator, mainHeader.nextSibling);
            inserted = true;
        }
        
        // Opción 2: Al inicio del contenido principal
        if (!inserted) {
            const container = document.querySelector('main .container-fluid');
            if (container) {
                container.insertBefore(indicator, container.firstChild);
                inserted = true;
            }
        }
        
        // Opción 3: Al inicio del main
        if (!inserted) {
            const main = document.querySelector('main');
            if (main) {
                main.insertBefore(indicator, main.firstChild);
                inserted = true;
            }
        }
        
        // Opción 4: Como último recurso, al body
        if (!inserted) {
            const body = document.body;
            const navbar = document.querySelector('.navbar');
            if (navbar && navbar.nextSibling) {
                body.insertBefore(indicator, navbar.nextSibling);
            } else {
                body.appendChild(indicator);
            }
        }
        
        console.log('✅ Indicador de polling insertado correctamente');
    }
    
    function hidePollingIndicator() {
        const indicator = document.getElementById('polling-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
    
    function showToast(message, type = 'info') {
        // Crear toast notification
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toastId = 'toast-' + Date.now();
        const toastEl = document.createElement('div');
        toastEl.id = toastId;
        toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'info')} border-0`;
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        // Auto-remove después de que se oculte
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }

    // Reset form when modal closes
    $('#newExtractionModal').on('hidden.bs.modal', function () {
        document.getElementById('newExtractionForm').reset();
        // Desmarcar todos los checkboxes de hoteles
        document.querySelectorAll('.hotel-checkbox').forEach(cb => cb.checked = false);
        document.getElementById('select_all_hotels').checked = false;
    });
    </script>
</body>
</html>