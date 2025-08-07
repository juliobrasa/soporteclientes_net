<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class HotelController extends Controller
{
    /**
     * 📋 Listar todos los hoteles
     * GET /api/hotels
     * 
     * Compatible con: admin_api.php?action=getHotels
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Hotel::query();

            // Filtros opcionales
            if ($request->has('active')) {
                $query->active();
            }

            if ($request->has('search')) {
                $query->search($request->search);
            }

            // Cargar relaciones para estadísticas
            $hotels = $query->with(['reviews' => function ($query) {
                    $query->select('hotel_id', 'rating');
                }])
                ->orderBy('id', 'desc')
                ->get();

            // Calcular estadísticas para cada hotel
            $hotelsWithStats = $hotels->map(function ($hotel) {
                return [
                    'id' => $hotel->id,
                    'nombre_hotel' => $hotel->nombre_hotel,
                    'hoja_destino' => $hotel->hoja_destino ?? '',
                    'url_booking' => $hotel->url_booking ?? '',
                    'max_reviews' => $hotel->max_reviews ?? 200,
                    'activo' => $hotel->activo,
                    'total_reviews' => $hotel->reviews->count(),
                    'avg_rating' => $hotel->reviews->count() > 0 
                        ? round($hotel->reviews->avg('rating'), 1) 
                        : 0,
                    'status_text' => $hotel->activo ? 'Activo' : 'Inactivo',
                    'created_at' => $hotel->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $hotel->updated_at?->format('Y-m-d H:i:s')
                ];
            });

            return response()->json([
                'success' => true,
                'hotels' => $hotelsWithStats,
                'total' => $hotels->count(),
                'message' => 'Hoteles obtenidos correctamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener hoteles',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📝 Crear nuevo hotel
     * POST /api/hotels
     * 
     * Compatible con: admin_api.php?action=saveHotel (sin ID)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validación
            $validated = $request->validate([
                'nombre_hotel' => 'required|string|max:255',
                'hoja_destino' => 'nullable|string|max:255',
                'url_booking' => 'nullable|url|max:500',
                'max_reviews' => 'nullable|integer|min:1|max:10000',
                'activo' => 'nullable|boolean'
            ]);

            // Mapear campos del frontend (compatibilidad)
            if ($request->has('name')) {
                $validated['nombre_hotel'] = $request->name;
            }
            if ($request->has('description')) {
                $validated['hoja_destino'] = $request->description;
            }
            if ($request->has('website')) {
                $validated['url_booking'] = $request->website;
            }
            if ($request->has('total_rooms')) {
                $validated['max_reviews'] = (int) $request->total_rooms;
            }
            if ($request->has('status')) {
                $validated['activo'] = $request->status === 'active';
            }

            $hotel = Hotel::create($validated);

            return response()->json([
                'success' => true,
                'hotel' => $hotel,
                'message' => 'Hotel creado correctamente'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al crear hotel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 👁️ Mostrar hotel específico
     * GET /api/hotels/{id}
     */
    public function show(Hotel $hotel): JsonResponse
    {
        try {
            $hotel->load('reviews');

            $hotelData = [
                'id' => $hotel->id,
                'nombre_hotel' => $hotel->nombre_hotel,
                'hoja_destino' => $hotel->hoja_destino,
                'url_booking' => $hotel->url_booking,
                'max_reviews' => $hotel->max_reviews,
                'activo' => $hotel->activo,
                'stats' => $hotel->getStats(),
                'created_at' => $hotel->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $hotel->updated_at?->format('Y-m-d H:i:s')
            ];

            return response()->json([
                'success' => true,
                'hotel' => $hotelData
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener hotel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✏️ Actualizar hotel existente
     * PUT /api/hotels/{id}
     * 
     * Compatible con: admin_api.php?action=saveHotel (con ID)
     */
    public function update(Request $request, Hotel $hotel): JsonResponse
    {
        try {
            // Validación
            $validated = $request->validate([
                'nombre_hotel' => 'sometimes|required|string|max:255',
                'hoja_destino' => 'nullable|string|max:255',
                'url_booking' => 'nullable|url|max:500',
                'max_reviews' => 'nullable|integer|min:1|max:10000',
                'activo' => 'nullable|boolean'
            ]);

            // Mapear campos del frontend (compatibilidad)
            if ($request->has('name')) {
                $validated['nombre_hotel'] = $request->name;
            }
            if ($request->has('description')) {
                $validated['hoja_destino'] = $request->description;
            }
            if ($request->has('website')) {
                $validated['url_booking'] = $request->website;
            }
            if ($request->has('total_rooms')) {
                $validated['max_reviews'] = (int) $request->total_rooms;
            }
            if ($request->has('status')) {
                $validated['activo'] = $request->status === 'active';
            }

            $hotel->update($validated);

            return response()->json([
                'success' => true,
                'hotel' => $hotel,
                'message' => 'Hotel actualizado correctamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar hotel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar hotel
     * DELETE /api/hotels/{id}
     * 
     * Compatible con: admin_api.php?action=deleteHotel
     */
    public function destroy(Hotel $hotel): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Eliminar reviews asociadas primero
            $hotel->reviews()->delete();
            
            // Eliminar hotel
            $hotel->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hotel eliminado correctamente'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar hotel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 Alternar estado del hotel
     * POST /api/hotels/{id}/toggle-status
     * 
     * Método adicional para compatibilidad con frontend actual
     */
    public function toggleStatus(Hotel $hotel): JsonResponse
    {
        try {
            $hotel->toggleStatus();

            return response()->json([
                'success' => true,
                'hotel' => $hotel,
                'message' => $hotel->activo 
                    ? 'Hotel activado correctamente' 
                    : 'Hotel desactivado correctamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al cambiar estado del hotel',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Obtener estadísticas de hoteles
     * GET /api/hotels/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total_hotels' => Hotel::count(),
                'active_hotels' => Hotel::active()->count(),
                'inactive_hotels' => Hotel::where('activo', false)->count(),
                'hotels_with_reviews' => Hotel::whereHas('reviews')->count(),
                'average_rating' => DB::table('reviews')
                    ->join('hoteles', 'reviews.hotel_id', '=', 'hoteles.id')
                    ->avg('reviews.rating') ?? 0
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener estadísticas',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
