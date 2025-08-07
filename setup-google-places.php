<?php
/**
 * Herramienta para configurar Google Place IDs en hoteles existentes
 */

require_once 'admin-config.php';

session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    die("❌ Error de conexión a la base de datos");
}

// Manejar actualizaciones
if ($_POST && isset($_POST['hotel_id']) && isset($_POST['place_id'])) {
    $hotelId = $_POST['hotel_id'];
    $placeId = $_POST['place_id'];
    
    if (empty($placeId)) {
        $message = ['type' => 'error', 'text' => 'Google Place ID no puede estar vacío'];
    } else {
        $stmt = $pdo->prepare("UPDATE hoteles SET google_place_id = ? WHERE id = ?");
        if ($stmt->execute([$placeId, $hotelId])) {
            $message = ['type' => 'success', 'text' => 'Google Place ID actualizado exitosamente'];
        } else {
            $message = ['type' => 'error', 'text' => 'Error actualizando Place ID'];
        }
    }
}

// Obtener hoteles
$stmt = $pdo->query("
    SELECT id, nombre_hotel, ciudad, google_place_id, 
           total_reviews_count, last_extraction_date
    FROM hoteles 
    WHERE activo = 1 
    ORDER BY nombre_hotel ASC
");
$hotels = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Google Place IDs - Kavia Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php">
                <i class="fas fa-hotel"></i> Kavia Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin-dashboard.php">
                    <i class="fas fa-arrow-left"></i> Volver al Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h2">
                        <i class="fas fa-map-marker-alt"></i> 
                        Configurar Google Place IDs
                    </h1>
                    <div>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#helpModal">
                            <i class="fas fa-question-circle"></i> ¿Cómo obtener Place ID?
                        </button>
                    </div>
                </div>

                <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $message['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <?php echo $message['text']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-building"></i> 
                            Hoteles Registrados (<?php echo count($hotels); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Hotel</th>
                                        <th>Ciudad</th>
                                        <th>Google Place ID</th>
                                        <th>Reseñas</th>
                                        <th>Última Extracción</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hotels as $hotel): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($hotel['nombre_hotel']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($hotel['ciudad'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($hotel['google_place_id']): ?>
                                                <code class="text-success"><?php echo substr($hotel['google_place_id'], 0, 20); ?>...</code>
                                                <i class="fas fa-check-circle text-success ms-1"></i>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                                    No configurado
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hotel['total_reviews_count'] > 0): ?>
                                                <span class="badge bg-primary"><?php echo $hotel['total_reviews_count']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hotel['last_extraction_date']): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($hotel['last_extraction_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Nunca</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="editPlaceId(<?php echo $hotel['id']; ?>, '<?php echo addslashes($hotel['nombre_hotel']); ?>', '<?php echo $hotel['google_place_id'] ?? ''; ?>')">
                                                <i class="fas fa-edit"></i> Configurar
                                            </button>
                                            
                                            <?php if ($hotel['google_place_id']): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="startExtraction(<?php echo $hotel['id']; ?>)">
                                                <i class="fas fa-download"></i> Extraer
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php 
                        $configured = count(array_filter($hotels, fn($h) => !empty($h['google_place_id'])));
                        $percentage = count($hotels) > 0 ? round(($configured / count($hotels)) * 100) : 0;
                        ?>
                        
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%">
                                            <?php echo $configured; ?>/<?php echo count($hotels); ?> configurados
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <small class="text-muted">
                                        <?php echo $percentage; ?>% completado
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar Place ID -->
    <div class="modal fade" id="editPlaceIdModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Configurar Google Place ID</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Hotel:</label>
                            <p class="fw-bold" id="hotelName"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="place_id" class="form-label">Google Place ID *</label>
                            <input type="text" class="form-control" id="place_id" name="place_id" required 
                                   placeholder="ChIJXXXXXXXXXX" pattern="ChIJ[a-zA-Z0-9_-]+">
                            <div class="form-text">
                                Debe comenzar con "ChIJ" seguido de caracteres alfanuméricos
                            </div>
                        </div>
                        
                        <input type="hidden" id="hotel_id" name="hotel_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Place ID</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de ayuda -->
    <div class="modal fade" id="helpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">¿Cómo obtener el Google Place ID?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="steps">
                        <h6><i class="fas fa-search"></i> Método 1: Google Maps</h6>
                        <ol>
                            <li>Ve a <a href="https://maps.google.com" target="_blank">Google Maps</a></li>
                            <li>Busca el hotel por nombre y ubicación</li>
                            <li>Haz clic en el hotel para abrir su información</li>
                            <li>Copia la URL de la página</li>
                            <li>El Place ID estará en la URL como: <code>ChIJXXXXXXXX</code></li>
                        </ol>
                        
                        <hr>
                        
                        <h6><i class="fas fa-tools"></i> Método 2: Place ID Finder</h6>
                        <ol>
                            <li>Ve a <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank">Google Place ID Finder</a></li>
                            <li>Busca el hotel</li>
                            <li>Copia el Place ID que aparece</li>
                        </ol>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle"></i> Formato del Place ID:</strong><br>
                            Los Place IDs siempre comienzan con <code>ChIJ</code> seguido de una cadena alfanumérica.<br>
                            Ejemplo: <code>ChIJN1t_tDeuEmsRUsoyG83frY4</code>
                        </div>
                        
                        <div class="alert alert-warning">
                            <strong><i class="fas fa-exclamation-triangle"></i> Importante:</strong><br>
                            Sin el Google Place ID configurado, no será posible extraer reseñas del hotel usando el sistema Apify.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function editPlaceId(hotelId, hotelName, currentPlaceId) {
        document.getElementById('hotel_id').value = hotelId;
        document.getElementById('hotelName').textContent = hotelName;
        document.getElementById('place_id').value = currentPlaceId || '';
        
        new bootstrap.Modal(document.getElementById('editPlaceIdModal')).show();
    }
    
    function startExtraction(hotelId) {
        if (confirm('¿Iniciar extracción de reseñas para este hotel?')) {
            // Redirigir al panel de extracción con el hotel preseleccionado
            window.location.href = `admin-extraction.php?hotel_id=${hotelId}`;
        }
    }
    
    // Validar formato de Place ID en tiempo real
    document.getElementById('place_id').addEventListener('input', function() {
        const placeId = this.value;
        const isValid = /^ChIJ[a-zA-Z0-9_-]+$/.test(placeId);
        
        if (placeId && !isValid) {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else if (placeId && isValid) {
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        } else {
            this.classList.remove('is-valid', 'is-invalid');
        }
    });
    </script>
</body>
</html>