<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SystemLogController extends Controller
{
    /**
     * Listar logs del sistema con filtros avanzados
     */
    public function index(Request $request)
    {
        try {
            $query = SystemLog::query();

            // Filtrar por nivel
            if ($request->has('level') && $request->level) {
                $query->level($request->level);
            }

            // Filtrar por módulo
            if ($request->has('module') && $request->module) {
                $query->module($request->module);
            }

            // Filtrar por rango de fechas
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->dateRange($request->start_date, $request->end_date);
            } elseif ($request->has('timerange')) {
                // Rangos predefinidos
                $hours = match($request->timerange) {
                    '1h' => 1,
                    '24h' => 24,
                    '7d' => 168,
                    '30d' => 720,
                    default => 24
                };
                $query->recent($hours);
            }

            // Filtrar por usuario
            if ($request->has('user_id') && $request->user_id) {
                $query->byUser($request->user_id);
            }

            // Filtrar por sesión
            if ($request->has('session_id') && $request->session_id) {
                $query->bySession($request->session_id);
            }

            // Filtrar por trace ID
            if ($request->has('trace_id') && $request->trace_id) {
                $query->byTrace($request->trace_id);
            }

            // Solo errores no resueltos
            if ($request->boolean('errors_only')) {
                $query->errors();
            }

            // Solo logs no resueltos
            if ($request->boolean('unresolved_only')) {
                $query->unresolved();
            }

            // Búsqueda en texto
            if ($request->has('search') && $request->search) {
                $query->search($request->search);
            }

            // Ordenamiento
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Paginación
            $perPage = min($request->get('per_page', 50), 200); // Max 200 por página
            $logs = $query->paginate($perPage);

            // Agregar atributos calculados
            $logs->getCollection()->transform(function ($log) {
                $log->level_name = $log->level_name;
                $log->module_name = $log->module_name;
                $log->is_recent = $log->is_recent;
                $log->formatted_duration = $log->formatted_duration;
                return $log;
            });

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar logs del sistema',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo log del sistema
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'level' => 'required|in:debug,info,warning,error,critical',
                'module' => 'required|string|max:50',
                'message' => 'required|string',
                'action' => 'nullable|string|max:100',
                'context' => 'nullable|array',
                'metadata' => 'nullable|array',
                'duration_ms' => 'nullable|integer|min:0',
                'trace_id' => 'nullable|string|max:36'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            
            // Agregar información de contexto automática
            $data['user_id'] = $request->get('user_id', session('user_id'));
            $data['session_id'] = session()->getId();
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();
            $data['request_id'] = $request->header('X-Request-ID', \Illuminate\Support\Str::uuid());

            $log = SystemLog::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Log creado correctamente',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar log específico
     */
    public function show(string $id)
    {
        try {
            $log = SystemLog::findOrFail($id);
            
            // Agregar atributos calculados
            $log->level_name = $log->level_name;
            $log->module_name = $log->module_name;
            $log->is_recent = $log->is_recent;
            $log->formatted_duration = $log->formatted_duration;

            return response()->json([
                'success' => true,
                'data' => $log
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Log no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Resolver un log (marcar como resuelto)
     */
    public function resolve(Request $request, string $id)
    {
        try {
            $log = SystemLog::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'resolution_notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $resolvedBy = $request->get('resolved_by', session('user_id'));
            $notes = $request->get('resolution_notes');

            $log->resolve($resolvedBy, $notes);

            return response()->json([
                'success' => true,
                'message' => 'Log marcado como resuelto',
                'data' => $log->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al resolver log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar logs (solo para limpieza administrativa)
     */
    public function destroy(string $id)
    {
        try {
            $log = SystemLog::findOrFail($id);
            $log->delete();

            return response()->json([
                'success' => true,
                'message' => 'Log eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar logs antiguos
     */
    public function cleanup(Request $request)
    {
        try {
            $days = $request->get('days', 90);
            
            $validator = Validator::make(['days' => $days], [
                'days' => 'integer|min:1|max:365'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetro de días inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $deletedCount = SystemLog::cleanup($days);

            return response()->json([
                'success' => true,
                'message' => "Se eliminaron {$deletedCount} logs antiguos",
                'data' => ['deleted_count' => $deletedCount]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de logs
     */
    public function stats(Request $request)
    {
        try {
            $period = $request->get('period', '24h');
            $stats = SystemLog::getStats($period);

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
     * Obtener timeline de logs para gráficos
     */
    public function timeline(Request $request)
    {
        try {
            $period = $request->get('period', '24h');
            $interval = $request->get('interval', '1h');
            
            $timeline = SystemLog::getTimeline($period, $interval);

            return response()->json([
                'success' => true,
                'data' => $timeline,
                'metadata' => [
                    'period' => $period,
                    'interval' => $interval,
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener timeline',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener configuración de niveles y módulos
     */
    public function config()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'levels' => SystemLog::LEVELS,
                    'modules' => SystemLog::MODULES,
                    'level_colors' => [
                        'debug' => '#6b7280',
                        'info' => '#3b82f6', 
                        'warning' => '#f59e0b',
                        'error' => '#ef4444',
                        'critical' => '#8b0000'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener configuración',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar logs (para análisis externo)
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'json'); // json, csv, txt
            
            $query = SystemLog::query();

            // Aplicar los mismos filtros que en index()
            if ($request->has('level') && $request->level) {
                $query->level($request->level);
            }

            if ($request->has('module') && $request->module) {
                $query->module($request->module);
            }

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->dateRange($request->start_date, $request->end_date);
            }

            // Limitar a máximo 10,000 registros para export
            $logs = $query->orderByDesc('created_at')
                ->limit(10000)
                ->get();

            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($logs);
                case 'txt':
                    return $this->exportToText($logs);
                default:
                    return response()->json([
                        'success' => true,
                        'data' => $logs,
                        'metadata' => [
                            'total_exported' => $logs->count(),
                            'format' => $format,
                            'exported_at' => now()->toISOString()
                        ]
                    ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar logs a CSV
     */
    private function exportToCsv($logs)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="system_logs_' . now()->format('Y-m-d_H-i-s') . '.csv"'
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Headers CSV
            fputcsv($file, [
                'ID', 'Level', 'Module', 'Action', 'Message', 'User ID', 
                'IP Address', 'Duration (ms)', 'Is Resolved', 'Created At'
            ]);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->level,
                    $log->module,
                    $log->action,
                    $log->message,
                    $log->user_id,
                    $log->ip_address,
                    $log->duration_ms,
                    $log->is_resolved ? 'Yes' : 'No',
                    $log->created_at->toDateTimeString()
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exportar logs a texto plano
     */
    private function exportToText($logs)
    {
        $headers = [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="system_logs_' . now()->format('Y-m-d_H-i-s') . '.txt"'
        ];

        $content = "SYSTEM LOGS EXPORT\n";
        $content .= "Generated: " . now()->toDateTimeString() . "\n";
        $content .= "Total Records: " . $logs->count() . "\n";
        $content .= str_repeat("=", 80) . "\n\n";

        foreach ($logs as $log) {
            $content .= "[{$log->created_at->toDateTimeString()}] ";
            $content .= strtoupper($log->level) . " ";
            $content .= "({$log->module}";
            if ($log->action) $content .= ":{$log->action}";
            $content .= ") ";
            $content .= $log->message;
            if ($log->user_id) $content .= " [User: {$log->user_id}]";
            $content .= "\n";
        }

        return response($content, 200, $headers);
    }
}
