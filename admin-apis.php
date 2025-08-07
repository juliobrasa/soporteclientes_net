<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';

// Obtener APIs externas
function getExternalApis() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM api_providers ORDER BY name ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo APIs externas: " . $e->getMessage());
        return [];
    }
}

$apis = getExternalApis();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APIs Externas - Panel Admin Kavia</title>
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
                            <a class="nav-link active" href="admin-apis.php">
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
                    <h1 class="h2"><i class="fas fa-plug"></i> APIs Externas</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addApiModal">
                        <i class="fas fa-plus"></i> Nueva API
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total APIs</h6>
                                        <h3><?php echo count($apis); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-plug fa-2x"></i>
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
                                        <h6>APIs Activas</h6>
                                        <h3><?php echo count(array_filter($apis, fn($a) => $a['active'] == 1)); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-check fa-2x"></i>
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
                                        <h6>Booking APIs</h6>
                                        <h3><?php echo count(array_filter($apis, fn($a) => strpos(strtolower($a['name'] ?? ''), 'booking') !== false)); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-bed fa-2x"></i>
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
                                        <h6>Con Errores</h6>
                                        <h3>0</h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- APIs Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de APIs Externas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="apisTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>URL Base</th>
                                        <th>Estado</th>
                                        <th>Último Test</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($apis as $api): ?>
                                    <tr>
                                        <td><?php echo $api['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($api['name']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($api['provider_type'] ?? 'custom'); ?></span>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($api['api_url'] ?? ''); ?></code>
                                        </td>
                                        <td>
                                            <?php if ($api['is_active']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Activa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-pause"></i> Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($api['last_test_at']): ?>
                                                <span class="badge bg-<?php echo $api['last_test_status'] == 'success' ? 'success' : 'danger'; ?>">
                                                    <?php echo date('Y-m-d H:i', strtotime($api['last_test_at'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Nunca</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Test Conexión" onclick="testExternalApi(<?php echo $api['id']; ?>)">
                                                    <i class="fas fa-wifi"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="Editar" onclick="editExternalApi(<?php echo $api['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-info" title="Logs" onclick="viewApiLogs(<?php echo $api['id']; ?>)">
                                                    <i class="fas fa-file-alt"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="deleteExternalApi(<?php echo $api['id']; ?>, '<?php echo addslashes($api['name']); ?>')">
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

    <!-- Add API Modal -->
    <div class="modal fade" id="addApiModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Nueva API Externa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addApiForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Proveedor</label>
                                    <select class="form-select" name="provider_type">
                                        <option value="apify">Apify</option>
                                        <option value="booking">Booking API</option>
                                        <option value="tripadvisor">TripAdvisor</option>
                                        <option value="google">Google APIs</option>
                                        <option value="custom">Personalizada</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">URL API *</label>
                            <input type="url" class="form-control" name="api_url" required placeholder="https://api.apify.com/v2">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">API Key</label>
                                    <input type="password" class="form-control" name="api_key">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Método Auth</label>
                                    <select class="form-select" name="auth_type">
                                        <option value="none">Sin autenticación</option>
                                        <option value="api_key">API Key</option>
                                        <option value="bearer">Bearer Token</option>
                                        <option value="basic">Basic Auth</option>
                                        <option value="oauth">OAuth</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Headers (JSON)</label>
                            <textarea class="form-control" name="headers" rows="3" placeholder='{"Content-Type": "application/json", "User-Agent": "Kavia Bot"}'></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Configuración (JSON)</label>
                            <textarea class="form-control" name="config" rows="3" placeholder='{"timeout": 30, "retries": 3}'></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Descripción opcional de la API"></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="active" checked>
                                <label class="form-check-label">API Activa</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveExternalApi()">Guardar API</button>
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
        table = $('#apisTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[1, 'asc']]
        });
    });

    function saveExternalApi() {
        const form = document.getElementById('addApiForm');
        const formData = new FormData(form);
        
        let headers = {};
        let config = {};
        
        // Parsear headers JSON
        try {
            const headersText = formData.get('headers');
            if (headersText && headersText.trim()) {
                headers = JSON.parse(headersText);
            }
        } catch (e) {
            alert('Error en el formato JSON de headers: ' + e.message);
            return;
        }
        
        // Parsear config JSON
        try {
            const configText = formData.get('config');
            if (configText && configText.trim()) {
                config = JSON.parse(configText);
            }
        } catch (e) {
            alert('Error en el formato JSON de configuración: ' + e.message);
            return;
        }
        
        const data = {
            name: formData.get('name'),
            provider_type: formData.get('provider_type'),
            api_key: formData.get('api_key'),
            api_url: formData.get('api_url'),
            auth_type: formData.get('auth_type'),
            headers: headers,
            config: config,
            description: formData.get('description'),
            is_active: formData.get('active') ? true : false
        };

        const url = editingId ? `api-external-apis.php?id=${editingId}` : 'api-external-apis.php';
        const method = editingId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Admin-Session': '<?php echo session_id(); ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                bootstrap.Modal.getInstance(document.getElementById('addApiModal')).hide();
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error de conexión: ' + error);
        });
    }

    function editExternalApi(id) {
        editingId = id;
        
        fetch(`api-external-apis.php?id=${id}`, {
            headers: {
                'X-Admin-Session': '<?php echo session_id(); ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const api = data.data;
                document.querySelector('input[name="name"]').value = api.name || '';
                document.querySelector('select[name="provider_type"]').value = api.provider_type || 'custom';
                document.querySelector('input[name="api_url"]').value = api.api_url || '';
                document.querySelector('input[name="api_key"]').value = api.api_key || '';
                document.querySelector('select[name="auth_type"]').value = api.auth_type || 'none';
                document.querySelector('textarea[name="description"]').value = api.description || '';
                document.querySelector('input[name="active"]').checked = api.is_active == 1;
                
                // Manejar headers JSON
                try {
                    const headers = api.headers ? JSON.parse(api.headers) : {};
                    document.querySelector('textarea[name="headers"]').value = Object.keys(headers).length > 0 ? JSON.stringify(headers, null, 2) : '';
                } catch (e) {
                    document.querySelector('textarea[name="headers"]').value = '';
                }
                
                // Manejar config JSON
                try {
                    const config = api.config ? JSON.parse(api.config) : {};
                    document.querySelector('textarea[name="config"]').value = Object.keys(config).length > 0 ? JSON.stringify(config, null, 2) : '';
                } catch (e) {
                    document.querySelector('textarea[name="config"]').value = '';
                }
                
                document.querySelector('.modal-title').textContent = 'Editar API Externa';
                new bootstrap.Modal(document.getElementById('addApiModal')).show();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error de conexión: ' + error);
        });
    }

    function deleteExternalApi(id, name) {
        if (confirm(`¿Estás seguro de que quieres eliminar la API "${name}"?`)) {
            fetch(`api-external-apis.php?id=${id}`, {
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

    function testExternalApi(id) {
        const button = document.querySelector(`button[onclick="testExternalApi(${id})"]`);
        const originalContent = button.innerHTML;
        
        // Mostrar loading
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        fetch(`api-external-apis.php?id=${id}&test=1`, {
            headers: {
                'X-Admin-Session': '<?php echo session_id(); ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            button.innerHTML = originalContent;
            button.disabled = false;
            
            if (data.success) {
                alert(`✅ Test exitoso\n\nTiempo de respuesta: ${data.response_time}\nCódigo HTTP: ${data.http_code}\nEstado: ${data.status}`);
            } else {
                alert(`❌ Test fallido\n\nError: ${data.error}\nCódigo HTTP: ${data.http_code || 'N/A'}\nEstado: ${data.status || 'offline'}`);
            }
        })
        .catch(error => {
            button.innerHTML = originalContent;
            button.disabled = false;
            alert('❌ Error de conexión: ' + error.message);
        });
    }

    function viewApiLogs(id) {
        // Obtener información de la API
        fetch(`api-external-apis.php?id=${id}`, {
            headers: {
                'X-Admin-Session': '<?php echo session_id(); ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const api = data.data;
                let logInfo = `📋 Logs de API: ${api.name}\n\n`;
                logInfo += `🔗 URL: ${api.api_url}\n`;
                logInfo += `📅 Creada: ${api.created_at || 'N/A'}\n`;
                logInfo += `🔄 Actualizada: ${api.updated_at || 'N/A'}\n`;
                logInfo += `🧪 Último Test: ${api.last_test_at || 'Nunca'}\n`;
                logInfo += `✅ Estado Test: ${api.last_test_status || 'N/A'}\n\n`;
                logInfo += `ℹ️ Los logs detallados estarán disponibles pronto`;
                
                alert(logInfo);
            } else {
                alert('Error obteniendo información de la API');
            }
        })
        .catch(error => {
            alert('Error de conexión: ' + error);
        });
    }

    // Reset form when modal closes
    $('#addApiModal').on('hidden.bs.modal', function () {
        document.getElementById('addApiForm').reset();
        editingId = null;
        document.querySelector('.modal-title').textContent = 'Agregar Nueva API Externa';
    });
    </script>
</body>
</html>