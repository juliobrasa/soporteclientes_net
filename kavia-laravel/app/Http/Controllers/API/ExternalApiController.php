<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ExternalApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ExternalApiController extends Controller
{
    /**
     * Listar todas las APIs externas
     */
    public function index(Request $request)
    {
        try {
            $query = ExternalApi::query();

            // Filtrar por tipo de proveedor
            if ($request->has('provider_type') && $request->provider_type) {
                $query->where('provider_type', $request->provider_type);
            }

            // Filtrar por estado activo
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Buscar por nombre
            if ($request->has('search') && $request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // Ordenar
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Paginación
            $perPage = $request->get('per_page', 25);
            $apis = $query->paginate($perPage);

            // Agregar credenciales enmascaradas para la vista
            $apis->getCollection()->transform(function ($api) {
                $api->credentials_masked = $api->masked_credentials;
                return $api;
            });

            return response()->json([
                'success' => true,
                'data' => $apis->items(),
                'pagination' => [
                    'current_page' => $apis->currentPage(),
                    'last_page' => $apis->lastPage(),
                    'per_page' => $apis->perPage(),
                    'total' => $apis->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar APIs externas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva API externa
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'provider_type' => 'required|string|in:booking,tripadvisor,expedia,google,custom',
                'base_url' => 'nullable|url',
                'credentials' => 'required|array',
                'configuration' => 'nullable|array',
                'is_active' => 'boolean',
                'rate_limit' => 'nullable|integer|min:1',
                'version' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:1000',
                'endpoints' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            
            // Si no se proporciona base_url, usar la predeterminada
            if (!$data['base_url'] && isset(ExternalApi::getProviderDefaults()[$data['provider_type']])) {
                $data['base_url'] = ExternalApi::getProviderDefaults()[$data['provider_type']]['base_url'];
            }

            $api = ExternalApi::create($data);

            return response()->json([
                'success' => true,
                'message' => 'API externa creada correctamente',
                'data' => $api
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear API externa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar API externa específica
     */
    public function show(string $id)
    {
        try {
            $api = ExternalApi::findOrFail($id);
            
            // Agregar credenciales enmascaradas
            $api->credentials_masked = $api->masked_credentials;

            return response()->json([
                'success' => true,
                'data' => $api
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API externa no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar API externa
     */
    public function update(Request $request, string $id)
    {
        try {
            $api = ExternalApi::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'provider_type' => 'sometimes|required|string|in:booking,tripadvisor,expedia,google,custom',
                'base_url' => 'nullable|url',
                'credentials' => 'sometimes|required|array',
                'configuration' => 'nullable|array',
                'is_active' => 'boolean',
                'rate_limit' => 'nullable|integer|min:1',
                'version' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:1000',
                'endpoints' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $api->update($data);

            return response()->json([
                'success' => true,
                'message' => 'API externa actualizada correctamente',
                'data' => $api->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar API externa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar API externa
     */
    public function destroy(string $id)
    {
        try {
            $api = ExternalApi::findOrFail($id);
            $apiName = $api->name;
            
            $api->delete();

            return response()->json([
                'success' => true,
                'message' => "API externa '{$apiName}' eliminada correctamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar API externa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Alternar estado activo/inactivo
     */
    public function toggle(string $id)
    {
        try {
            $api = ExternalApi::findOrFail($id);
            $api->is_active = !$api->is_active;
            $api->save();

            $status = $api->is_active ? 'activada' : 'desactivada';

            return response()->json([
                'success' => true,
                'message' => "API externa '{$api->name}' {$status} correctamente",
                'data' => $api
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado de API externa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Probar conexión con la API
     */
    public function test(string $id)
    {
        try {
            $api = ExternalApi::findOrFail($id);
            $result = $api->testConnection();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexión exitosa con la API externa',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en la conexión con la API externa',
                    'data' => $result
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al probar conexión',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuraciones predeterminadas por proveedor
     */
    public function defaults()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => ExternalApi::getProviderDefaults()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar configuraciones predeterminadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de APIs externas
     */
    public function stats()
    {
        try {
            $stats = [
                'total' => ExternalApi::count(),
                'active' => ExternalApi::where('is_active', true)->count(),
                'inactive' => ExternalApi::where('is_active', false)->count(),
                'by_provider' => ExternalApi::select('provider_type', DB::raw('count(*) as total'))
                    ->groupBy('provider_type')
                    ->get()
                    ->pluck('total', 'provider_type'),
                'recently_used' => ExternalApi::recentlyUsed()->count(),
                'never_used' => ExternalApi::whereNull('last_used_at')->count(),
                'total_usage' => ExternalApi::sum('usage_count'),
                'last_test_results' => ExternalApi::whereNotNull('last_tested_at')
                    ->orderBy('last_tested_at', 'desc')
                    ->take(10)
                    ->get(['id', 'name', 'last_tested_at', 'last_test_result'])
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Incrementar uso de API
     */
    public function incrementUsage(string $id)
    {
        try {
            $api = ExternalApi::findOrFail($id);
            $api->incrementUsage();

            return response()->json([
                'success' => true,
                'message' => 'Uso incrementado correctamente',
                'data' => ['usage_count' => $api->usage_count]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al incrementar uso',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
