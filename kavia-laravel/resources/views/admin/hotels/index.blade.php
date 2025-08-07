@extends('layouts.admin')

@section('title', 'Gestión de Hoteles')
@section('page-title', 'Gestión de Hoteles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Lista de Hoteles</h4>
        <p class="text-muted mb-0">Gestiona todos los hoteles del sistema</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHotelModal">
        <i class="fas fa-plus me-2"></i>
        Agregar Hotel
    </button>
</div>

<!-- Tarjetas de estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Total Hoteles</h6>
                    <div class="stat-value">{{ count($hotels) }}</div>
                </div>
                <div class="text-primary">
                    <i class="fas fa-hotel fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Hoteles Activos</h6>
                    <div class="stat-value">{{ collect($hotels)->where('activo', true)->count() }}</div>
                </div>
                <div class="text-success">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Total Reseñas</h6>
                    <div class="stat-value">{{ collect($hotels)->sum('total_reviews') }}</div>
                </div>
                <div class="text-info">
                    <i class="fas fa-star fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-grow-1">
                    <h6 class="text-muted mb-1">Rating Promedio</h6>
                    <div class="stat-value">{{ number_format(collect($hotels)->where('avg_rating', '>', 0)->avg('avg_rating'), 1) }}</div>
                </div>
                <div class="text-warning">
                    <i class="fas fa-chart-line fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de hoteles -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="hotelsTable" class="table table-striped table-hover data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hotel</th>
                        <th>Destino</th>
                        <th>URL Booking</th>
                        <th>Max Reviews</th>
                        <th>Reseñas</th>
                        <th>Rating</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hotels as $hotel)
                    <tr>
                        <td>{{ $hotel['id'] }}</td>
                        <td>
                            <div>
                                <strong>{{ $hotel['nombre_hotel'] }}</strong>
                                <br>
                                <small class="text-muted">
                                    Creado: {{ $hotel['created_at']->format('d/m/Y') }}
                                </small>
                            </div>
                        </td>
                        <td>{{ $hotel['hoja_destino'] ?? '-' }}</td>
                        <td>
                            @if($hotel['url_booking'])
                                <a href="{{ $hotel['url_booking'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt"></i>
                                    Ver
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $hotel['max_reviews'] ?? 200 }}</td>
                        <td>
                            <span class="badge bg-info">
                                {{ $hotel['total_reviews'] }} reseñas
                            </span>
                        </td>
                        <td>
                            @if($hotel['avg_rating'] > 0)
                                <div class="d-flex align-items-center">
                                    <span class="me-1">{{ $hotel['avg_rating'] }}</span>
                                    <i class="fas fa-star text-warning"></i>
                                </div>
                            @else
                                <span class="text-muted">Sin rating</span>
                            @endif
                        </td>
                        <td>
                            @if($hotel['activo'])
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="editHotel({{ json_encode($hotel) }})"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-{{ $hotel['activo'] ? 'warning' : 'success' }}" 
                                        onclick="toggleHotelStatus({{ $hotel['id'] }}, {{ $hotel['activo'] ? 'false' : 'true' }})"
                                        title="{{ $hotel['activo'] ? 'Desactivar' : 'Activar' }}">
                                    <i class="fas fa-{{ $hotel['activo'] ? 'pause' : 'play' }}"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteHotel({{ $hotel['id'] }}, '{{ $hotel['nombre_hotel'] }}')"
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Agregar Hotel -->
<div class="modal fade" id="addHotelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Agregar Nuevo Hotel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addHotelForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre_hotel" class="form-label">Nombre del Hotel *</label>
                        <input type="text" class="form-control" id="nombre_hotel" name="nombre_hotel" required>
                    </div>
                    <div class="mb-3">
                        <label for="hoja_destino" class="form-label">Destino</label>
                        <input type="text" class="form-control" id="hoja_destino" name="hoja_destino">
                    </div>
                    <div class="mb-3">
                        <label for="url_booking" class="form-label">URL Booking</label>
                        <input type="url" class="form-control" id="url_booking" name="url_booking">
                    </div>
                    <div class="mb-3">
                        <label for="max_reviews" class="form-label">Máximo de Reseñas</label>
                        <input type="number" class="form-control" id="max_reviews" name="max_reviews" value="200">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">
                                Hotel activo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Hotel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Editar Hotel -->
<div class="modal fade" id="editHotelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editar Hotel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editHotelForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_hotel_id" name="hotel_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nombre_hotel" class="form-label">Nombre del Hotel *</label>
                        <input type="text" class="form-control" id="edit_nombre_hotel" name="nombre_hotel" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_hoja_destino" class="form-label">Destino</label>
                        <input type="text" class="form-control" id="edit_hoja_destino" name="hoja_destino">
                    </div>
                    <div class="mb-3">
                        <label for="edit_url_booking" class="form-label">URL Booking</label>
                        <input type="url" class="form-control" id="edit_url_booking" name="url_booking">
                    </div>
                    <div class="mb-3">
                        <label for="edit_max_reviews" class="form-label">Máximo de Reseñas</label>
                        <input type="number" class="form-control" id="edit_max_reviews" name="max_reviews">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_activo" name="activo">
                            <label class="form-check-label" for="edit_activo">
                                Hotel activo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Actualizar Hotel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Variables globales
let hotelsTable;

$(document).ready(function() {
    // Inicializar DataTable
    hotelsTable = $('#hotelsTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
        },
        order: [[0, 'desc']]
    });
});

// Función para agregar hotel
$('#addHotelForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        nombre_hotel: $('#nombre_hotel').val(),
        hoja_destino: $('#hoja_destino').val() || '',
        url_booking: $('#url_booking').val() || '',
        max_reviews: $('#max_reviews').val() || 200,
        activo: $('#activo').is(':checked')
    };
    
    $.ajax({
        url: '/api/legacy/hotels',
        method: 'POST',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#addHotelModal').modal('hide');
                location.reload(); // Recargar página para mostrar cambios
            } else {
                alert('Error: ' + (response.error || 'No se pudo crear el hotel'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
});

// Función para editar hotel
function editHotel(hotel) {
    $('#edit_hotel_id').val(hotel.id);
    $('#edit_nombre_hotel').val(hotel.nombre_hotel);
    $('#edit_hoja_destino').val(hotel.hoja_destino || '');
    $('#edit_url_booking').val(hotel.url_booking || '');
    $('#edit_max_reviews').val(hotel.max_reviews || 200);
    $('#edit_activo').prop('checked', hotel.activo);
    
    $('#editHotelModal').modal('show');
}

$('#editHotelForm').on('submit', function(e) {
    e.preventDefault();
    
    const hotelId = $('#edit_hotel_id').val();
    const formData = {
        nombre_hotel: $('#edit_nombre_hotel').val(),
        hoja_destino: $('#edit_hoja_destino').val() || '',
        url_booking: $('#edit_url_booking').val() || '',
        max_reviews: $('#edit_max_reviews').val() || 200,
        activo: $('#edit_activo').is(':checked')
    };
    
    $.ajax({
        url: `/api/legacy/hotels/${hotelId}`,
        method: 'PUT',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                $('#editHotelModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo actualizar el hotel'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
});

// Función para cambiar estado del hotel
function toggleHotelStatus(hotelId, newStatus) {
    const action = newStatus ? 'activar' : 'desactivar';
    
    if (!confirm(`¿Estás seguro de que quieres ${action} este hotel?`)) {
        return;
    }
    
    $.ajax({
        url: `/api/legacy/hotels/${hotelId}/toggle-status`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo cambiar el estado'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
}

// Función para eliminar hotel
function deleteHotel(hotelId, hotelName) {
    if (!confirm(`¿Estás COMPLETAMENTE seguro de que quieres eliminar el hotel "${hotelName}"?\n\nEsta acción es IRREVERSIBLE y eliminará todas las reseñas asociadas.`)) {
        return;
    }
    
    if (!confirm('¿REALMENTE estás seguro? Esta acción es PERMANENTE.')) {
        return;
    }
    
    $.ajax({
        url: `/api/legacy/hotels/${hotelId}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.error || 'No se pudo eliminar el hotel'));
            }
        },
        error: function(xhr) {
            alert('Error de conexión: ' + xhr.responseText);
        }
    });
}
</script>
@endpush