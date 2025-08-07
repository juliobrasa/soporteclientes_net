<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ExtractionJob;
use App\Models\ExtractionRun;
use App\Models\ExtractionLog;
use App\Models\Hotel;
use App\Models\ExternalApi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use DB;

class ExtractionController extends Controller
{
    /**
     * Lista de trabajos de extracción
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100',
                'search' => 'string|max:255',
                'status' => Rule::in(['pending', 'running', 'completed', 'failed', 'cancelled']),
                'period' => 'string|in:all,today,week,month,3months',
                'sort_field' => 'string|in:id,name,status,created_at,progress',
                'sort_direction' => 'string|in:asc,desc'
            ]);

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 25);
            $search = $request->get('search', '');
            $status = $request->get('status', '');
            $period = $request->get('period', 'month');
            $sortField = $request->get('sort_field', 'id');
            $sortDirection = $request->get('sort_direction', 'desc');

            $query = ExtractionJob::with(['apiProvider'])
                ->search($search)
                ->byStatus($status)
                ->byPeriod($period)
                ->orderBy($sortField, $sortDirection);

            $total = $query->count();
            $jobs = $query->skip(($page - 1) * $limit)->take($limit)->get();

            // Formatear datos para respuesta
            $formattedJobs = $jobs->map(function($job) {
                return [
                    'id' => $job->id,
                    'name' => $job->name,
                    'description' => $job->description,
                    'status' => $job->status,
                    'status_badge' => $job->status_badge,
                    'status_class' => $job->status_class,
                    'mode' => $job->mode,
                    'mode_badge' => $job->mode_badge,
                    'priority' => $job->priority,
                    'api_provider_id' => $job->api_provider_id,
                    'api_provider_name' => $job->api_provider_name,
                    'api_provider_type' => $job->api_provider_type,
                    'api_icon' => $job->api_icon,
                    'hotel_count' => $job->hotel_count,
                    'max_reviews_per_hotel' => $job->max_reviews_per_hotel,
                    'progress' => $job->progress,
                    'progress_bar' => $job->progress_bar,
                    'completed_hotels' => $job->completed_hotels,
                    'reviews_extracted' => number_format($job->reviews_extracted),
                    'reviews_target' => $job->reviews_target,
                    'estimated_reviews' => $job->estimated_reviews,
                    'total_cost' => $job->total_cost_formatted,
                    'execution_mode' => $job->execution_mode,
                    'scheduled_datetime' => $job->scheduled_datetime?->format('Y-m-d H:i:s'),
                    'started_at' => $job->started_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $job->completed_at?->format('Y-m-d H:i:s'),
                    'running_time' => $job->running_time,
                    'error_message' => $job->error_message,
                    'created_at_formatted' => $job->created_at_formatted,
                    'created_at' => $job->created_at?->format('Y-m-d H:i:s'),
                    'updated_at' => $job->updated_at?->format('Y-m-d H:i:s'),
                    'action_buttons' => $job->action_buttons,
                    'mobile_action_buttons' => $job->mobile_action_buttons,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedJobs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'last_page' => ceil($total / $limit),
                    'from' => (($page - 1) * $limit) + 1,
                    'to' => min($page * $limit, $total)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener trabajos de extracción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo trabajo de extracción
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'mode' => 'required|in:active,all,selected',
                'priority' => 'in:normal,high,critical',
                'api_provider_id' => 'required|exists:external_apis,id',
                'max_reviews_per_hotel' => 'integer|min:1|max:10000',
                'selected_hotels' => 'array',
                'selected_hotels.*' => 'exists:hoteles,id',
                'execution_mode' => 'in:immediate,schedule,draft',
                'scheduled_datetime' => 'nullable|date|after:now',
                'options' => 'array'
            ]);

            // Obtener información del proveedor de API
            $apiProvider = ExternalApi::findOrFail($validated['api_provider_id']);

            // Determinar hoteles según el modo
            $hotelCount = 0;
            $selectedHotels = [];

            switch ($validated['mode']) {
                case 'active':
                    $hotelCount = Hotel::where('activo', 1)->count();
                    break;
                case 'all':
                    $hotelCount = Hotel::count();
                    break;
                case 'selected':
                    $selectedHotels = $validated['selected_hotels'] ?? [];
                    $hotelCount = count($selectedHotels);
                    break;
            }

            $maxReviewsPerHotel = $validated['max_reviews_per_hotel'] ?? 200;
            $estimatedReviews = $hotelCount * $maxReviewsPerHotel;

            // Calcular costo estimado (ejemplo: $0.001 por review)
            $costPerReview = 0.001;
            $estimatedCost = $estimatedReviews * $costPerReview;

            $job = ExtractionJob::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'mode' => $validated['mode'],
                'priority' => $validated['priority'] ?? 'normal',
                'api_provider_id' => $apiProvider->id,
                'api_provider_name' => $apiProvider->name,
                'api_provider_type' => $apiProvider->provider_type,
                'hotel_count' => $hotelCount,
                'max_reviews_per_hotel' => $maxReviewsPerHotel,
                'selected_hotels' => $selectedHotels,
                'estimated_reviews' => $estimatedReviews,
                'total_cost' => $estimatedCost,
                'execution_mode' => $validated['execution_mode'] ?? 'immediate',
                'scheduled_datetime' => $validated['scheduled_datetime'] ?? null,
                'options' => $validated['options'] ?? []
            ]);

            // Crear log inicial
            ExtractionLog::info($job->id, 'Trabajo de extracción creado', [
                'user_id' => session('user_id'),
                'hotel_count' => $hotelCount,
                'estimated_reviews' => $estimatedReviews,
                'estimated_cost' => $estimatedCost
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trabajo de extracción creado exitosamente',
                'data' => $job
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear trabajo de extracción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener trabajo específico
     */
    public function show($id): JsonResponse
    {
        try {
            $job = ExtractionJob::with(['apiProvider', 'runs.hotel', 'logs'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Trabajo de extracción no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar trabajo de extracción
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $job = ExtractionJob::findOrFail($id);

            // Solo permitir editar trabajos pendientes o en draft
            if (!in_array($job->status, ['pending', 'draft'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar trabajos pendientes'
                ], 400);
            }

            $validated = $request->validate([
                'name' => 'string|max:255',
                'description' => 'nullable|string',
                'priority' => 'in:normal,high,critical',
                'max_reviews_per_hotel' => 'integer|min:1|max:10000',
                'execution_mode' => 'in:immediate,schedule,draft',
                'scheduled_datetime' => 'nullable|date|after:now',
                'options' => 'array'
            ]);

            $job->update($validated);

            ExtractionLog::info($job->id, 'Trabajo de extracción actualizado', [
                'user_id' => session('user_id'),
                'changes' => $validated
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trabajo actualizado exitosamente',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar trabajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar trabajo de extracción
     */
    public function destroy($id): JsonResponse
    {
        try {
            $job = ExtractionJob::findOrFail($id);

            // No permitir eliminar trabajos en ejecución
            if ($job->status === 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un trabajo en ejecución'
                ], 400);
            }

            $jobName = $job->name;
            $job->delete();

            return response()->json([
                'success' => true,
                'message' => "Trabajo '{$jobName}' eliminado exitosamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar trabajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Iniciar trabajo de extracción
     */
    public function start($id): JsonResponse
    {
        try {
            $job = ExtractionJob::findOrFail($id);

            if ($job->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden iniciar trabajos pendientes'
                ], 400);
            }

            $job->start();

            return response()->json([
                'success' => true,
                'message' => 'Trabajo iniciado exitosamente',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar trabajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pausar trabajo de extracción
     */
    public function pause($id): JsonResponse
    {
        try {
            $job = ExtractionJob::findOrFail($id);

            if ($job->status !== 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden pausar trabajos en ejecución'
                ], 400);
            }

            $job->update(['status' => 'pending']);

            ExtractionLog::info($job->id, 'Trabajo pausado', [
                'user_id' => session('user_id'),
                'progress_at_pause' => $job->progress
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trabajo pausado exitosamente',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al pausar trabajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar trabajo de extracción
     */
    public function cancel($id): JsonResponse
    {
        try {
            $job = ExtractionJob::findOrFail($id);

            if (!in_array($job->status, ['pending', 'running'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cancelar trabajos pendientes o en ejecución'
                ], 400);
            }

            $job->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Trabajo cancelado exitosamente',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar trabajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reintentar trabajo de extracción
     */
    public function retry($id): JsonResponse
    {
        try {
            $job = ExtractionJob::findOrFail($id);

            if (!in_array($job->status, ['failed', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden reintentar trabajos fallidos o cancelados'
                ], 400);
            }

            $job->update([
                'status' => 'pending',
                'error_message' => null,
                'progress' => 0,
                'reviews_extracted' => 0,
                'completed_hotels' => 0,
                'started_at' => null,
                'completed_at' => null,
                'running_time' => 0
            ]);

            ExtractionLog::info($job->id, 'Trabajo reintentado', [
                'user_id' => session('user_id')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trabajo preparado para reintento',
                'data' => $job
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reintentar trabajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clonar trabajo de extracción
     */
    public function clone($id): JsonResponse
    {
        try {
            $originalJob = ExtractionJob::findOrFail($id);

            $clonedJob = ExtractionJob::create([
                'name' => $originalJob->name . ' (Copia)',
                'description' => $originalJob->description,
                'mode' => $originalJob->mode,
                'priority' => $originalJob->priority,
                'api_provider_id' => $originalJob->api_provider_id,
                'api_provider_name' => $originalJob->api_provider_name,
                'api_provider_type' => $originalJob->api_provider_type,
                'hotel_count' => $originalJob->hotel_count,
                'max_reviews_per_hotel' => $originalJob->max_reviews_per_hotel,
                'selected_hotels' => $originalJob->selected_hotels,
                'estimated_reviews' => $originalJob->estimated_reviews,
                'total_cost' => $originalJob->total_cost,
                'execution_mode' => 'draft',
                'options' => $originalJob->options
            ]);

            ExtractionLog::info($clonedJob->id, 'Trabajo clonado desde ID: ' . $originalJob->id, [
                'user_id' => session('user_id'),
                'original_job_id' => $originalJob->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trabajo clonado exitosamente',
                'data' => $clonedJob
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al clonar trabajo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de extracción
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', '30d');
            $stats = ExtractionJob::getStats($period);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'metadata' => [
                    'period' => $period,
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener hoteles para extracción
     */
    public function hotels(): JsonResponse
    {
        try {
            $hotels = Hotel::select([
                'id',
                'nombre_hotel as name',
                'hoja_destino as destination',
                'activo as active'
            ])
            ->with(['reviews' => function($query) {
                $query->select('hotel_name', DB::raw('COUNT(*) as count'))
                    ->groupBy('hotel_name');
            }])
            ->get()
            ->map(function($hotel) {
                return [
                    'id' => $hotel->id,
                    'name' => $hotel->name,
                    'destination' => $hotel->destination,
                    'active' => $hotel->active,
                    'total_reviews' => $hotel->reviews->sum('count') ?? 0,
                    'recent_reviews' => 0, // TODO: calcular reviews recientes
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $hotels
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener hoteles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener runs de un trabajo
     */
    public function runs($id): JsonResponse
    {
        try {
            $job = ExtractionJob::findOrFail($id);
            $runs = $job->runs()->with(['hotel'])->get();

            return response()->json([
                'success' => true,
                'data' => $runs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener runs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener logs de un trabajo
     */
    public function logs($id, Request $request): JsonResponse
    {
        try {
            $job = ExtractionJob::findOrFail($id);
            
            $level = $request->get('level');
            $limit = $request->get('limit', 50);

            $logs = $job->logs()
                ->byLevel($level)
                ->latest()
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}