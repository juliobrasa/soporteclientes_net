<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AiProvider;
use App\Http\Requests\AiProviderRequest;
use App\Http\Resources\AiProviderResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiProviderController extends Controller
{
    /**
     * Lista todos los proveedores de IA con filtros opcionales
     * GET /api/ai-providers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AiProvider::query();

            // Filtro por estado activo
            if ($request->has('active')) {
                $query->where('is_active', $request->boolean('active'));
            }

            // Filtro por tipo de proveedor
            if ($request->has('type')) {
                $query->byType($request->input('type'));
            }

            // Búsqueda por nombre
            if ($request->has('search')) {
                $query->search($request->input('search'));
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'name');
            $sortDirection = $request->input('sort_direction', 'asc');
            
            if ($sortBy === 'name') {
                $query->orderByName($sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            $providers = $query->get();

            return response()->json([
                'success' => true,
                'ai_providers' => AiProviderResource::collection($providers),
                'total' => $providers->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo proveedores de IA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener proveedores de IA',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Crear nuevo proveedor de IA
     * POST /api/ai-providers
     */
    public function store(AiProviderRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Crear proveedor con valores por defecto
            $provider = AiProvider::createWithDefaults($data);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor de IA creado exitosamente',
                'ai_provider' => new AiProviderResource($provider)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creando proveedor de IA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al crear proveedor de IA',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Mostrar proveedor específico
     * GET /api/ai-providers/{id}
     */
    public function show(AiProvider $aiProvider): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'ai_provider' => new AiProviderResource($aiProvider)
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo proveedor de IA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener proveedor de IA',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Actualizar proveedor de IA
     * PUT /api/ai-providers/{id}
     */
    public function update(AiProviderRequest $request, AiProvider $aiProvider): JsonResponse
    {
        try {
            $data = $request->validated();
            
            $aiProvider->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor de IA actualizado exitosamente',
                'ai_provider' => new AiProviderResource($aiProvider->fresh())
            ]);

        } catch (\Exception $e) {
            Log::error('Error actualizando proveedor de IA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar proveedor de IA',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Eliminar proveedor de IA
     * DELETE /api/ai-providers/{id}
     */
    public function destroy(AiProvider $aiProvider): JsonResponse
    {
        try {
            $providerName = $aiProvider->name;
            $aiProvider->delete();

            return response()->json([
                'success' => true,
                'message' => "Proveedor de IA '{$providerName}' eliminado exitosamente"
            ]);

        } catch (\Exception $e) {
            Log::error('Error eliminando proveedor de IA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar proveedor de IA',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Alternar estado activo/inactivo del proveedor
     * POST /api/ai-providers/{id}/toggle
     */
    public function toggle(AiProvider $aiProvider): JsonResponse
    {
        try {
            $previousStatus = $aiProvider->is_active;
            $aiProvider->toggleStatus();
            
            $status = $aiProvider->is_active ? 'activado' : 'desactivado';
            
            return response()->json([
                'success' => true,
                'message' => "Proveedor '{$aiProvider->name}' {$status} exitosamente",
                'ai_provider' => new AiProviderResource($aiProvider),
                'previous_status' => $previousStatus,
                'new_status' => $aiProvider->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error alternando estado del proveedor: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al cambiar estado del proveedor',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Probar conexión con el proveedor de IA
     * POST /api/ai-providers/{id}/test
     */
    public function test(AiProvider $aiProvider): JsonResponse
    {
        try {
            // Verificar configuración válida
            if (!$aiProvider->hasValidConfiguration()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Configuración incompleta',
                    'message' => 'El proveedor no tiene toda la configuración necesaria'
                ], 400);
            }

            $testResult = $this->performApiTest($aiProvider);
            
            if ($testResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conexión exitosa con el proveedor de IA',
                    'test_result' => $testResult,
                    'provider' => $aiProvider->name
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Error en la conexión',
                    'message' => $testResult['message'],
                    'details' => $testResult
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error probando proveedor de IA: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al probar conexión',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener proveedores con datos por defecto (para formularios)
     * GET /api/ai-providers/defaults
     */
    public function getDefaults(): JsonResponse
    {
        try {
            $defaults = [];
            
            foreach (AiProvider::PROVIDER_TYPES as $type => $name) {
                $defaults[$type] = [
                    'name' => $name,
                    'type' => $type,
                    'api_url' => AiProvider::DEFAULT_URLS[$type] ?? '',
                    'model_name' => AiProvider::DEFAULT_MODELS[$type] ?? '',
                    'parameters' => AiProvider::DEFAULT_PARAMETERS[$type] ?? []
                ];
            }

            return response()->json([
                'success' => true,
                'defaults' => $defaults,
                'provider_types' => AiProvider::PROVIDER_TYPES
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo valores por defecto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener valores por defecto',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de proveedores
     * GET /api/ai-providers/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => AiProvider::count(),
                'active' => AiProvider::active()->count(),
                'inactive' => AiProvider::where('is_active', false)->count(),
                'by_type' => []
            ];

            // Estadísticas por tipo
            foreach (AiProvider::PROVIDER_TYPES as $type => $name) {
                $stats['by_type'][$type] = [
                    'name' => $name,
                    'total' => AiProvider::byType($type)->count(),
                    'active' => AiProvider::byType($type)->active()->count()
                ];
            }

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener estadísticas',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    // ================================================================
    // MÉTODOS PRIVADOS
    // ================================================================

    /**
     * Realizar prueba de conexión específica por tipo de proveedor
     */
    private function performApiTest(AiProvider $provider): array
    {
        try {
            $config = $provider->getApiConfiguration();
            
            switch ($provider->provider_type) {
                case 'openai':
                    return $this->testOpenAI($config);
                case 'claude':
                    return $this->testClaude($config);
                case 'deepseek':
                    return $this->testDeepSeek($config);
                case 'gemini':
                    return $this->testGemini($config);
                case 'local':
                    return $this->testLocal($config);
                default:
                    return [
                        'success' => false,
                        'message' => 'Tipo de proveedor no soportado para testing'
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error en prueba de conexión: ' . $e->getMessage(),
                'error_type' => 'connection_error'
            ];
        }
    }

    /**
     * Probar conexión con OpenAI
     */
    private function testOpenAI(array $config): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
            'Content-Type' => 'application/json'
        ])->timeout(10)->get($config['api_url'] . '/models');

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Conexión exitosa con OpenAI',
                'response_time' => $response->transferStats?->getTransferTime(),
                'models_available' => count($response->json('data', []))
            ];
        }

        return [
            'success' => false,
            'message' => 'Error de conexión con OpenAI: ' . $response->body(),
            'status_code' => $response->status()
        ];
    }

    /**
     * Probar conexión con Claude
     */
    private function testClaude(array $config): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $config['api_key'],
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->timeout(10)->post($config['api_url'] . '/messages', [
            'model' => $config['model'],
            'max_tokens' => 10,
            'messages' => [
                ['role' => 'user', 'content' => 'Test']
            ]
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Conexión exitosa con Claude',
                'response_time' => $response->transferStats?->getTransferTime()
            ];
        }

        return [
            'success' => false,
            'message' => 'Error de conexión con Claude: ' . $response->body(),
            'status_code' => $response->status()
        ];
    }

    /**
     * Probar conexión con DeepSeek
     */
    private function testDeepSeek(array $config): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
            'Content-Type' => 'application/json'
        ])->timeout(10)->get($config['api_url'] . '/models');

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Conexión exitosa con DeepSeek',
                'response_time' => $response->transferStats?->getTransferTime()
            ];
        }

        return [
            'success' => false,
            'message' => 'Error de conexión con DeepSeek: ' . $response->body(),
            'status_code' => $response->status()
        ];
    }

    /**
     * Probar conexión con Gemini
     */
    private function testGemini(array $config): array
    {
        $response = Http::timeout(10)->get($config['api_url'] . '/models', [
            'key' => $config['api_key']
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Conexión exitosa con Gemini',
                'response_time' => $response->transferStats?->getTransferTime(),
                'models_available' => count($response->json('models', []))
            ];
        }

        return [
            'success' => false,
            'message' => 'Error de conexión con Gemini: ' . $response->body(),
            'status_code' => $response->status()
        ];
    }

    /**
     * Probar conexión con modelo local
     */
    private function testLocal(array $config): array
    {
        $response = Http::timeout(5)->get($config['api_url'] . '/v1/models');

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => 'Conexión exitosa con modelo local',
                'response_time' => $response->transferStats?->getTransferTime()
            ];
        }

        return [
            'success' => false,
            'message' => 'Error de conexión con modelo local: ' . $response->body(),
            'status_code' => $response->status()
        ];
    }
}