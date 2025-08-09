<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';

// Obtener AI providers
function getAIProviders() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM ai_providers ORDER BY name ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo AI providers: " . $e->getMessage());
        return [];
    }
}

$providers = getAIProviders();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Providers - Panel Admin Kavia</title>
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
                            <a class="nav-link active" href="admin-ai.php">
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
                    <h1 class="h2"><i class="fas fa-robot"></i> AI Providers</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProviderModal">
                        <i class="fas fa-plus"></i> Nuevo Provider
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Providers</h6>
                                        <h3><?php echo count($providers); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-robot fa-2x"></i>
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
                                        <h6>Providers Activos</h6>
                                        <h3><?php echo count(array_filter($providers, fn($p) => $p['is_active'] == 1)); ?></h3>
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
                                        <h6>Providers Inactivos</h6>
                                        <h3><?php echo count(array_filter($providers, fn($p) => $p['is_active'] == 0)); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-pause fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Providers Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de AI Providers</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="providersTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>API Key</th>
                                        <th>Estado</th>
                                        <th>Creado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($providers as $provider): ?>
                                    <tr>
                                        <td><?php echo $provider['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($provider['name']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($provider['provider_type'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($provider['api_key'])): ?>
                                                <code><?php echo substr($provider['api_key'], 0, 8) . '...'; ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">No configurada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($provider['is_active']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><i class="fas fa-pause"></i> Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($provider['created_at'] ?? 'now')); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="Editar" onclick="editProvider(<?php echo $provider['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Test" onclick="testProvider(<?php echo $provider['id']; ?>)">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="deleteProvider(<?php echo $provider['id']; ?>, '<?php echo addslashes($provider['name']); ?>')">
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

    <!-- Add Provider Modal -->
    <div class="modal fade" id="addProviderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Agregar Nuevo AI Provider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProviderForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo *</label>
                                    <select class="form-select" name="provider_type" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="openai">OpenAI</option>
                                        <option value="claude">Claude</option>
                                        <option value="gemini">Gemini</option>
                                        <option value="deepseek">DeepSeek</option>
                                        <option value="local">Local</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API Key *</label>
                            <input type="password" class="form-control" name="api_key" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">API URL</label>
                            <input type="url" class="form-control" name="api_url" placeholder="https://api.openai.com/v1/chat/completions">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Configuración (JSON)</label>
                            <textarea class="form-control" name="config" rows="3" placeholder='{"model": "gpt-3.5-turbo", "max_tokens": 1000}'></textarea>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" checked>
                                <label class="form-check-label">Provider Activo</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveProvider()">Guardar Provider</button>
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
        table = $('#providersTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[1, 'asc']]
        });
    });

    function saveProvider() {
        const form = document.getElementById('addProviderForm');
        const formData = new FormData(form);
        
        const data = {
            name: formData.get('name'),
            provider_type: formData.get('provider_type'),
            api_key: formData.get('api_key'),
            api_url: formData.get('api_url'),
            config: formData.get('config'),
            is_active: formData.get('is_active') ? true : false
        };

        const url = editingId ? `api-ai-providers.php?id=${editingId}` : 'api-ai-providers.php';
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

    function editProvider(id) {
        editingId = id;
        
        fetch(`api-ai-providers.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const provider = data.data;
                document.querySelector('input[name="name"]').value = provider.name;
                document.querySelector('select[name="provider_type"]').value = provider.provider_type || '';
                document.querySelector('input[name="api_key"]').value = provider.api_key || '';
                document.querySelector('input[name="api_url"]').value = provider.api_url || '';
                document.querySelector('textarea[name="config"]').value = provider.config || '';
                document.querySelector('input[name="is_active"]').checked = provider.is_active == 1;
                
                document.querySelector('.modal-title').textContent = 'Editar AI Provider';
                new bootstrap.Modal(document.getElementById('addProviderModal')).show();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error de conexión: ' + error);
        });
    }

    function deleteProvider(id, name) {
        if (confirm(`¿Estás seguro de que quieres eliminar el provider "${name}"?`)) {
            fetch(`api-ai-providers.php?id=${id}`, {
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

    function testProvider(id) {
        alert('Función de test en desarrollo para provider ID: ' + id);
    }

    // Reset form when modal closes
    $('#addProviderModal').on('hidden.bs.modal', function () {
        document.getElementById('addProviderForm').reset();
        editingId = null;
        document.querySelector('.modal-title').textContent = 'Agregar Nuevo AI Provider';
    });
    </script>
</body>
</html>