<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';
// Aplicar CSP específico para páginas administrativas
require_once 'csp-config.php';
setAdminCSP();


// Obtener lista de hoteles
function getHotels() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM hoteles ORDER BY nombre_hotel ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo hoteles: " . $e->getMessage());
        return [];
    }
}

$hotels = getHotels();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Hoteles - Panel Admin Kavia</title>
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
                            <a class="nav-link active" href="admin-hotels.php">
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
                    <h1 class="h2"><i class="fas fa-building"></i> Gestión de Hoteles</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHotelModal">
                        <i class="fas fa-plus"></i> Nuevo Hotel
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Hoteles</h6>
                                        <h3><?php echo count($hotels); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-building fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Hoteles Activos</h6>
                                        <h3><?php echo count(array_filter($hotels, fn($h) => $h['activo'] == 1)); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Hoteles Inactivos</h6>
                                        <h3><?php echo count(array_filter($hotels, fn($h) => $h['activo'] == 0)); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-pause fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hotels Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Hoteles</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="hotelsTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre Hotel</th>
                                        <th>URL Booking</th>
                                        <th>Hoja Destino</th>
                                        <th>Max Reviews</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hotels as $hotel): ?>
                                    <tr>
                                        <td><?php echo $hotel['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($hotel['nombre_hotel']); ?></strong></td>
                                        <td>
                                            <?php if ($hotel['url_booking']): ?>
                                                <a href="<?php echo htmlspecialchars($hotel['url_booking']); ?>" target="_blank" class="btn btn-sm btn-link">
                                                    <i class="fas fa-external-link-alt"></i> Ver
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No configurada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($hotel['hoja_destino'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $hotel['max_reviews'] ?? 200; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($hotel['activo']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-pause"></i> Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="Editar" onclick="editHotel(<?php echo $hotel['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="deleteHotel(<?php echo $hotel['id']; ?>, '<?php echo addslashes($hotel['nombre_hotel']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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

    <!-- Add Hotel Modal -->
    <div class="modal fade" id="addHotelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Nuevo Hotel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addHotelForm">
                        <div class="mb-3">
                            <label class="form-label">Nombre del Hotel *</label>
                            <input type="text" class="form-control" name="nombre_hotel" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL Booking</label>
                            <input type="url" class="form-control" name="url_booking" placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hoja Destino</label>
                            <input type="text" class="form-control" name="hoja_destino" placeholder="ej: Sheet1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Máximo Reviews</label>
                            <input type="number" class="form-control" name="max_reviews" value="200" min="1">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="activo" checked>
                                <label class="form-check-label">Hotel Activo</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveHotel()">Guardar Hotel</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
    let table;
    let editingId = null;

    $(document).ready(function() {
        table = $('#hotelsTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[1, 'asc']]
        });
    });

    function saveHotel() {
        const form = document.getElementById('addHotelForm');
        const formData = new FormData(form);
        
        const data = {
            nombre_hotel: formData.get('nombre_hotel'),
            url_booking: formData.get('url_booking'),
            hoja_destino: formData.get('hoja_destino'),
            max_reviews: formData.get('max_reviews'),
            activo: formData.get('activo') ? true : false
        };

        const url = editingId ? `api-hotels.php?id=${editingId}` : 'api-hotels.php';
        const method = editingId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error de conexión: ' + error);
        });
    }

    function editHotel(id) {
        editingId = id;
        
        fetch(`api-hotels.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const hotel = data.data;
                document.querySelector('input[name="nombre_hotel"]').value = hotel.nombre_hotel;
                document.querySelector('input[name="url_booking"]').value = hotel.url_booking || '';
                document.querySelector('input[name="hoja_destino"]').value = hotel.hoja_destino || '';
                document.querySelector('input[name="max_reviews"]').value = hotel.max_reviews || 200;
                document.querySelector('input[name="activo"]').checked = hotel.activo == 1;
                
                document.querySelector('.modal-title').textContent = 'Editar Hotel';
                new bootstrap.Modal(document.getElementById('addHotelModal')).show();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error de conexión: ' + error);
        });
    }

    function deleteHotel(id, name) {
        if (confirm(`¿Estás seguro de que quieres eliminar el hotel "${name}"?`)) {
            fetch(`api-hotels.php?id=${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error de conexión: ' + error);
            });
        }
    }

    // Reset form when modal closes
    $('#addHotelModal').on('hidden.bs.modal', function () {
        document.getElementById('addHotelForm').reset();
        editingId = null;
        document.querySelector('.modal-title').textContent = 'Agregar Nuevo Hotel';
    });
    </script>
</body>
</html>