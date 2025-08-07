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
                                        <th>Estado</th>
                                        <th>Progreso</th>
                                        <th>Reseñas</th>
                                        <th>Iniciado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                    <tr>
                                        <td><?php echo $job['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($job['nombre_hotel'] ?? 'Hotel N/A'); ?></strong></td>
                                        <td>
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
                                        <td>
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
                                        <td>
                                            <span class="badge bg-primary"><?php echo $job['extracted_count'] ?? 0; ?></span>
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
                            <label class="form-label">Hotel *</label>
                            <select class="form-select" name="hotel_id" required>
                                <option value="">Seleccionar hotel...</option>
                                <?php foreach ($hotels as $hotel): ?>
                                    <option value="<?php echo $hotel['id']; ?>"><?php echo htmlspecialchars($hotel['nombre_hotel']); ?></option>
                                <?php endforeach; ?>
                            </select>
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
                                <input class="form-check-input" type="checkbox" name="extract_images">
                                <label class="form-check-label">Análisis de sentimientos automático</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="translate_reviews">
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
        const form = document.getElementById('newExtractionForm');
        const formData = new FormData(form);
        
        if (!formData.get('hotel_id')) {
            alert('Por favor selecciona un hotel');
            return;
        }
        
        const data = {
            hotel_id: formData.get('hotel_id'),
            max_reviews: formData.get('max_reviews') || 100,
            platforms: getSelectedPlatforms(),
            languages: ['en', 'es'],
            extract_images: formData.get('extract_images') ? true : false,
            translate_reviews: formData.get('translate_reviews') ? true : false
        };

        // Mostrar loader
        showExtractionLoader('Iniciando extracción con Apify Hotel Review Aggregator...');
        
        // Llamada real a la API de extracción
        fetch('api-extraction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            hideExtractionLoader();
            
            if (data.success) {
                alert(`✅ Extracción iniciada exitosamente\n\nRun ID: ${data.run_id}\nCosto estimado: $${data.cost_estimate}\n\nLa extracción puede tomar varios minutos. Actualiza la página para ver el progreso.`);
                location.reload();
            } else {
                alert('❌ Error: ' + (data.error || 'Error desconocido'));
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
        if (confirm('¿Eliminar este trabajo de extracción?')) {
            alert('Eliminar trabajo ID: ' + id);
        }
    }

    function refreshJobs() {
        location.reload();
    }

    // Reset form when modal closes
    $('#newExtractionModal').on('hidden.bs.modal', function () {
        document.getElementById('newExtractionForm').reset();
    });
    </script>
</body>
</html>