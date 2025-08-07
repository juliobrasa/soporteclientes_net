<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

include 'admin-config.php';

// Obtener prompts
function getPrompts() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM prompts ORDER BY name ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo prompts: " . $e->getMessage());
        return [];
    }
}

$prompts = getPrompts();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prompts - Panel Admin Kavia</title>
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
                            <a class="nav-link active" href="admin-prompts.php">
                                <i class="fas fa-comments"></i> Prompts
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin-apis.php">
                                <i class="fas fa-plug"></i> APIs Externas
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
                    <h1 class="h2"><i class="fas fa-comments"></i> Gestión de Prompts</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromptModal">
                        <i class="fas fa-plus"></i> Nuevo Prompt
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Prompts</h6>
                                        <h3><?php echo count($prompts); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-comments fa-2x"></i>
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
                                        <h6>Prompts Activos</h6>
                                        <h3><?php echo count(array_filter($prompts, fn($p) => $p['status'] == 'active')); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Categorías</h6>
                                        <h3><?php echo count(array_unique(array_column($prompts, 'category'))); ?></h3>
                                    </div>
                                    <div>
                                        <i class="fas fa-tags fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Prompts Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">Lista de Prompts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="promptsTable" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Categoría</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Creado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prompts as $prompt): ?>
                                    <tr>
                                        <td><?php echo $prompt['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($prompt['name']); ?></strong></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($prompt['category'] ?? 'General'); ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $desc = htmlspecialchars($prompt['description'] ?? '');
                                            echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = $prompt['status'] ?? 'draft';
                                            $status_colors = [
                                                'active' => 'bg-success',
                                                'draft' => 'bg-warning text-dark',
                                                'archived' => 'bg-secondary'
                                            ];
                                            $color = $status_colors[$status] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $color; ?>">
                                                <i class="fas fa-<?php echo $status == 'active' ? 'check' : ($status == 'draft' ? 'edit' : 'archive'); ?>"></i> 
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($prompt['created_at'] ?? 'now')); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" title="Ver" onclick="viewPrompt(<?php echo $prompt['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" title="Editar" onclick="editPrompt(<?php echo $prompt['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Test" onclick="testPrompt(<?php echo $prompt['id']; ?>)">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="deletePrompt(<?php echo $prompt['id']; ?>, '<?php echo addslashes($prompt['name']); ?>')">
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

    <!-- Add Prompt Modal -->
    <div class="modal fade" id="addPromptModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Nuevo Prompt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPromptForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoría</label>
                                    <select class="form-select" name="category">
                                        <option value="extraction">Extracción</option>
                                        <option value="analysis">Análisis</option>
                                        <option value="summary">Resumen</option>
                                        <option value="classification">Clasificación</option>
                                        <option value="general">General</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Breve descripción del prompt"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contenido del Prompt *</label>
                            <textarea class="form-control" name="content" rows="8" required placeholder="Eres un asistente experto en analizar reseñas de hoteles..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Variables (separadas por comas)</label>
                                    <input type="text" class="form-control" name="variables" placeholder="hotel_name, review_text, rating">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Versión</label>
                                    <input type="text" class="form-control" name="version" value="1.0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="status">
                                <option value="active" selected>Activo</option>
                                <option value="draft">Borrador</option>
                                <option value="archived">Archivado</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="savePrompt()">Guardar Prompt</button>
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
        table = $('#promptsTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            pageLength: 25,
            order: [[1, 'asc']]
        });
    });

    function savePrompt() {
        const form = document.getElementById('addPromptForm');
        const formData = new FormData(form);
        
        const data = {
            name: formData.get('name'),
            category: formData.get('category'),
            description: formData.get('description'),
            content: formData.get('content'),
            variables: formData.get('variables'),
            version: formData.get('version'),
            active: formData.get('status') === 'active'
        };

        const url = editingId ? `api-prompts.php?id=${editingId}` : 'api-prompts.php';
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

    function editPrompt(id) {
        editingId = id;
        
        fetch(`api-prompts.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const prompt = data.data;
                document.querySelector('input[name="name"]').value = prompt.name;
                document.querySelector('select[name="category"]').value = prompt.category || 'general';
                document.querySelector('textarea[name="description"]').value = prompt.description || '';
                document.querySelector('textarea[name="content"]').value = prompt.content || '';
                document.querySelector('input[name="variables"]').value = prompt.variables || '';
                document.querySelector('input[name="version"]').value = prompt.version || '1.0';
                document.querySelector('select[name="status"]').value = prompt.status || 'draft';
                
                document.querySelector('.modal-title').textContent = 'Editar Prompt';
                new bootstrap.Modal(document.getElementById('addPromptModal')).show();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error de conexión: ' + error);
        });
    }

    function deletePrompt(id, name) {
        if (confirm(`¿Estás seguro de que quieres eliminar el prompt "${name}"?`)) {
            fetch(`api-prompts.php?id=${id}`, {
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

    function viewPrompt(id) {
        fetch(`api-prompts.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const prompt = data.data;
                alert(`Prompt: ${prompt.name}\n\nContent: ${prompt.content}`);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            alert('Error de conexión: ' + error);
        });
    }

    function testPrompt(id) {
        alert('Función de test en desarrollo para prompt ID: ' + id);
    }

    // Reset form when modal closes
    $('#addPromptModal').on('hidden.bs.modal', function () {
        document.getElementById('addPromptForm').reset();
        editingId = null;
        document.querySelector('.modal-title').textContent = 'Crear Nuevo Prompt';
    });
    </script>
</body>
</html>