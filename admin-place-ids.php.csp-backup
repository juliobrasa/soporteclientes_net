<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: admin-login.php');
    exit;
}

require_once 'admin-config.php';

// Obtener hoteles
$pdo = getDBConnection();
$stmt = $pdo->query("SELECT id, nombre_hotel, google_place_id FROM hoteles ORDER BY nombre_hotel");
$hotels = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Google Place IDs - Panel Admin Kavia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin-dashboard.php"><i class="fas fa-hotel"></i> Kavia Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin-extraction.php"><i class="fas fa-arrow-left"></i> Volver a Extracciones</a>
                <a class="nav-link" href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h2 mb-4"><i class="fas fa-map-marker-alt"></i> Configurar Google Place IDs</h1>
                
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> Cómo obtener Google Place IDs:</h6>
                    <ol class="mb-0">
                        <li>Ve a <a href="https://maps.google.com" target="_blank">Google Maps</a></li>
                        <li>Busca tu hotel por nombre y ubicación</li>
                        <li>Haz clic en el hotel para ver su página</li>
                        <li>Copia la URL que aparece (contiene el Place ID)</li>
                        <li>Pégala en el campo "URL de Google Maps" a continuación</li>
                    </ol>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Hoteles configurados</h5>
                        <button class="btn btn-success" onclick="autoFindAllPlaceIds()">
                            <i class="fas fa-magic"></i> Auto-completar todos
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Hotel</th>
                                        <th>Place ID Actual</th>
                                        <th>Estado</th>
                                        <th>URL de Google Maps</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hotels as $hotel): ?>
                                    <tr id="hotel-<?php echo $hotel['id']; ?>">
                                        <td><strong><?php echo htmlspecialchars($hotel['nombre_hotel']); ?></strong></td>
                                        <td>
                                            <code class="place-id-display">
                                                <?php echo $hotel['google_place_id'] ?: 'No configurado'; ?>
                                            </code>
                                        </td>
                                        <td>
                                            <?php 
                                            $isDemo = strpos($hotel['google_place_id'] ?? '', 'ChIJDemo_') === 0;
                                            $isEmpty = empty($hotel['google_place_id']);
                                            if ($isEmpty): ?>
                                                <span class="badge bg-warning">Sin configurar</span>
                                            <?php elseif ($isDemo): ?>
                                                <span class="badge bg-danger">Demo (no funcional)</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Real</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="url-<?php echo $hotel['id']; ?>"
                                                   placeholder="https://maps.google.com/maps?..."
                                                   onchange="extractPlaceId(<?php echo $hotel['id']; ?>)">
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-info btn-sm" onclick="autoFindPlaceId(<?php echo $hotel['id']; ?>, '<?php echo htmlspecialchars($hotel['nombre_hotel'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-search"></i> Auto
                                                </button>
                                                <button class="btn btn-primary btn-sm" onclick="updatePlaceId(<?php echo $hotel['id']; ?>)">
                                                    <i class="fas fa-save"></i> Guardar
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
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function autoFindAllPlaceIds() {
        const button = event.target;
        const originalText = button.innerHTML;
        const hotels = <?php echo json_encode($hotels); ?>;
        
        if (!confirm(`¿Estás seguro de que quieres autocompletar los Place IDs de ${hotels.length} hoteles?\n\nEsto sobrescribirá los Place IDs existentes.`)) {
            return;
        }
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        button.disabled = true;
        
        let completed = 0;
        let successful = 0;
        
        showToast(`🚀 Iniciando búsqueda automática para ${hotels.length} hoteles...`, 'info');
        
        hotels.forEach((hotel, index) => {
            // Agregar delay para evitar sobrecarga
            setTimeout(() => {
                autoFindSingleHotel(hotel.id, hotel.nombre_hotel, () => {
                    completed++;
                    if (hotel.google_place_id && !hotel.google_place_id.includes('ChIJDemo_')) {
                        // Ya tiene un Place ID real, no contar como éxito
                    } else {
                        successful++;
                    }
                    
                    // Actualizar progreso
                    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${completed}/${hotels.length}`;
                    
                    if (completed === hotels.length) {
                        button.innerHTML = originalText;
                        button.disabled = false;
                        showToast(`✅ Proceso completado: ${successful} Place IDs encontrados de ${hotels.length} hoteles`, 'success');
                    }
                });
            }, index * 1000); // 1 segundo entre cada búsqueda
        });
    }
    
    function autoFindSingleHotel(hotelId, hotelName, callback) {
        fetch('api-search-place-id.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Admin-Session': '<?php echo session_id(); ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                hotel_name: hotelName,
                location: 'Cancún, México'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar display del Place ID
                const display = document.querySelector(`#hotel-${hotelId} .place-id-display`);
                display.textContent = data.place_id;
                display.style.backgroundColor = '#e8f5e8';
                display.style.padding = '2px 4px';
                display.style.borderRadius = '3px';
                
                // Actualizar campo URL
                const urlInput = document.getElementById(`url-${hotelId}`);
                urlInput.value = `Auto-detectado: ${data.place_id}`;
                urlInput.style.color = '#28a745';
                
                console.log(`✅ ${hotelName}: ${data.place_id}`);
            } else {
                console.warn(`❌ ${hotelName}: No encontrado`);
            }
            callback();
        })
        .catch(error => {
            console.error(`❌ ${hotelName}: Error`, error);
            callback();
        });
    }
    
    function autoFindPlaceId(hotelId, hotelName) {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        
        // Mostrar loading
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
        button.disabled = true;
        
        fetch('api-search-place-id.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Admin-Session': '<?php echo session_id(); ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                hotel_name: hotelName,
                location: 'Cancún, México'
            })
        })
        .then(response => response.json())
        .then(data => {
            button.innerHTML = originalText;
            button.disabled = false;
            
            if (data.success) {
                // Actualizar display del Place ID
                const display = document.querySelector(`#hotel-${hotelId} .place-id-display`);
                display.textContent = data.place_id;
                display.style.backgroundColor = '#e8f5e8';
                display.style.padding = '2px 4px';
                display.style.borderRadius = '3px';
                
                // Limpiar campo URL ya que se encontró automáticamente
                const urlInput = document.getElementById(`url-${hotelId}`);
                urlInput.value = `Auto-detectado: ${data.place_id}`;
                urlInput.style.color = '#28a745';
                
                // Mostrar notificación de éxito
                showToast(`✅ Place ID encontrado automáticamente para ${hotelName}`, 'success');
                
                console.log(`✅ Place ID auto-encontrado para ${hotelName}: ${data.place_id}`);
            } else {
                showToast(`❌ No se pudo encontrar automáticamente el Place ID para ${hotelName}`, 'warning');
                console.warn(`❌ Auto-búsqueda fallida para ${hotelName}:`, data.error);
                
                if (data.suggestions) {
                    console.log('Sugerencias:', data.suggestions);
                }
            }
        })
        .catch(error => {
            button.innerHTML = originalText;
            button.disabled = false;
            showToast(`❌ Error de conexión al buscar Place ID`, 'danger');
            console.error('Error:', error);
        });
    }
    
    function showToast(message, type = 'info') {
        // Crear toast notification
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toastId = 'toast-' + Date.now();
        const toastEl = document.createElement('div');
        toastEl.id = toastId;
        toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : (type === 'warning' ? 'warning' : (type === 'danger' ? 'danger' : 'info'))} border-0`;
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
    
    function extractPlaceId(hotelId) {
        const urlInput = document.getElementById(`url-${hotelId}`);
        const url = urlInput.value;
        
        if (!url) return;
        
        // Extraer Place ID de la URL de Google Maps
        let placeId = null;
        
        // Método 1: Extraer de URL con place/
        const placeMatch = url.match(/place\/([^\/\?]+)/);
        if (placeMatch) {
            placeId = decodeURIComponent(placeMatch[1]);
        }
        
        // Método 2: Extraer de URL con cid=
        const cidMatch = url.match(/cid=(\d+)/);
        if (cidMatch && !placeId) {
            placeId = `CID_${cidMatch[1]}`;
        }
        
        if (placeId) {
            // Actualizar display
            const display = document.querySelector(`#hotel-${hotelId} .place-id-display`);
            display.textContent = placeId;
            display.style.backgroundColor = '#e8f5e8';
            
            console.log(`Place ID extraído para hotel ${hotelId}: ${placeId}`);
        } else {
            alert('No se pudo extraer Place ID de la URL. Asegúrate de usar una URL de Google Maps válida.');
        }
    }
    
    function updatePlaceId(hotelId) {
        const display = document.querySelector(`#hotel-${hotelId} .place-id-display`);
        const placeId = display.textContent;
        
        if (!placeId || placeId === 'No configurado') {
            alert('Primero introduce una URL de Google Maps válida');
            return;
        }
        
        fetch('api-update-place-id.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Admin-Session': '<?php echo session_id(); ?>',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                hotel_id: hotelId,
                place_id: placeId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar badge de estado
                const row = document.getElementById(`hotel-${hotelId}`);
                const statusCell = row.querySelector('td:nth-child(3)');
                statusCell.innerHTML = '<span class="badge bg-success">Real</span>';
                
                alert('✅ Place ID guardado correctamente');
            } else {
                alert('❌ Error: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            alert('❌ Error de conexión: ' + error.message);
            console.error('Error:', error);
        });
    }
    </script>
</body>
</html>