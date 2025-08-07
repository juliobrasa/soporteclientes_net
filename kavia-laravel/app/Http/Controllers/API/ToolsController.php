<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Hotel;
use App\Models\Review;
use App\Models\AiProvider;
use App\Models\ExternalApi;
use App\Models\Prompt;
use App\Models\SystemLog;
use App\Models\ExtractionJob;

class ToolsController extends Controller
{
    /**
     * Obtener estadísticas completas de la base de datos
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [];

            // Estadísticas de hoteles
            $stats['hotels'] = [
                'total' => Hotel::count(),
                'active' => Hotel::where('activo', 1)->count(),
                'inactive' => Hotel::where('activo', 0)->count(),
            ];

            // Estadísticas de reviews
            $stats['reviews'] = [
                'total' => Review::count(),
                'recent_30d' => Review::where('created_at', '>=', now()->subDays(30))->count(),
                'recent_7d' => Review::where('created_at', '>=', now()->subDays(7))->count(),
                'average_rating' => Review::avg('rating') ?: 0,
            ];

            // Estadísticas de AI Providers
            $stats['ai_providers'] = [
                'total' => AiProvider::count(),
                'active' => AiProvider::where('is_active', true)->count(),
                'by_type' => AiProvider::select('provider_type', DB::raw('count(*) as count'))
                    ->groupBy('provider_type')
                    ->pluck('count', 'provider_type')
                    ->toArray(),
            ];

            // Estadísticas de APIs Externas
            $stats['external_apis'] = [
                'total' => ExternalApi::count(),
                'active' => ExternalApi::where('is_active', true)->count(),
                'total_usage' => ExternalApi::sum('usage_count'),
            ];

            // Estadísticas de prompts
            $stats['prompts'] = [
                'total' => Prompt::count(),
                'active' => Prompt::where('status', 'active')->count(),
                'by_category' => Prompt::select('category', DB::raw('count(*) as count'))
                    ->whereNotNull('category')
                    ->groupBy('category')
                    ->pluck('count', 'category')
                    ->toArray(),
            ];

            // Estadísticas de extraction jobs
            $stats['extraction_jobs'] = [
                'total' => ExtractionJob::count(),
                'running' => ExtractionJob::where('status', 'running')->count(),
                'completed' => ExtractionJob::where('status', 'completed')->count(),
                'failed' => ExtractionJob::where('status', 'failed')->count(),
                'total_reviews_extracted' => ExtractionJob::sum('reviews_extracted'),
            ];

            // Estadísticas de logs del sistema
            $stats['system_logs'] = [
                'total' => SystemLog::count(),
                'recent_24h' => SystemLog::where('created_at', '>=', now()->subHours(24))->count(),
                'errors' => SystemLog::where('level', 'error')->count(),
                'warnings' => SystemLog::where('level', 'warning')->count(),
            ];

            // Estadísticas de tablas (tamaño aproximado)
            $tableSizes = $this->getTableSizes();
            $stats['database'] = [
                'total_tables' => count($tableSizes),
                'table_sizes' => $tableSizes,
                'total_size_mb' => array_sum(array_column($tableSizes, 'size_mb')),
            ];

            // Estadísticas de rendimiento
            $stats['performance'] = [
                'avg_response_time' => $this->getAverageResponseTime(),
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2), // MB
                'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2), // MB
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'summary' => [
                    'total_records' => array_sum([
                        $stats['hotels']['total'],
                        $stats['reviews']['total'],
                        $stats['ai_providers']['total'],
                        $stats['external_apis']['total'],
                        $stats['prompts']['total'],
                        $stats['extraction_jobs']['total'],
                        $stats['system_logs']['total']
                    ]),
                    'health_status' => $this->calculateHealthStatus($stats),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de BD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Escanear registros duplicados
     */
    public function scanDuplicates(Request $request): JsonResponse
    {
        try {
            $table = $request->get('table', 'all');
            $duplicates = [];

            if ($table === 'all' || $table === 'reviews') {
                // Buscar reviews duplicados
                $reviewDuplicates = DB::select("
                    SELECT hotel_name, review_text, rating, review_date, COUNT(*) as count
                    FROM reviews 
                    GROUP BY hotel_name, review_text, rating, review_date
                    HAVING COUNT(*) > 1
                ");

                $duplicates['reviews'] = [
                    'count' => count($reviewDuplicates),
                    'total_duplicate_records' => array_sum(array_column($reviewDuplicates, 'count')) - count($reviewDuplicates),
                    'examples' => array_slice($reviewDuplicates, 0, 5)
                ];
            }

            if ($table === 'all' || $table === 'hotels') {
                // Buscar hoteles duplicados por nombre
                $hotelDuplicates = DB::select("
                    SELECT nombre_hotel, COUNT(*) as count
                    FROM hoteles 
                    GROUP BY nombre_hotel
                    HAVING COUNT(*) > 1
                ");

                $duplicates['hotels'] = [
                    'count' => count($hotelDuplicates),
                    'total_duplicate_records' => array_sum(array_column($hotelDuplicates, 'count')) - count($hotelDuplicates),
                    'examples' => array_slice($hotelDuplicates, 0, 5)
                ];
            }

            if ($table === 'all' || $table === 'prompts') {
                // Buscar prompts duplicados
                $promptDuplicates = DB::select("
                    SELECT title, content, COUNT(*) as count
                    FROM prompts 
                    GROUP BY title, content
                    HAVING COUNT(*) > 1
                ");

                $duplicates['prompts'] = [
                    'count' => count($promptDuplicates),
                    'total_duplicate_records' => array_sum(array_column($promptDuplicates, 'count')) - count($promptDuplicates),
                    'examples' => array_slice($promptDuplicates, 0, 5)
                ];
            }

            $totalDuplicates = array_sum(array_column($duplicates, 'total_duplicate_records'));

            // Log de escaneo
            SystemLog::info('tools', 'Escaneo de duplicados completado', [
                'table' => $table,
                'duplicates_found' => $totalDuplicates,
                'scan_details' => array_map(fn($d) => ['count' => $d['count'], 'records' => $d['total_duplicate_records']], $duplicates)
            ]);

            return response()->json([
                'success' => true,
                'data' => $duplicates,
                'summary' => [
                    'total_duplicate_groups' => array_sum(array_column($duplicates, 'count')),
                    'total_duplicate_records' => $totalDuplicates,
                    'tables_scanned' => array_keys($duplicates),
                    'recommendation' => $totalDuplicates > 0 ? 
                        'Se encontraron duplicados. Considera ejecutar la limpieza.' : 
                        'Base de datos limpia, sin duplicados encontrados.'
                ]
            ]);

        } catch (\Exception $e) {
            SystemLog::error('tools', 'Error en escaneo de duplicados: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al escanear duplicados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar registros duplicados
     */
    public function deleteDuplicates(Request $request): JsonResponse
    {
        try {
            $table = $request->get('table', 'reviews');
            $preview = $request->get('preview', false); // Si true, solo muestra qué se eliminaría
            
            $deletedCount = 0;
            $results = [];

            DB::beginTransaction();

            if ($table === 'reviews' || $table === 'all') {
                // Eliminar reviews duplicados manteniendo el más reciente
                $query = "
                    DELETE r1 FROM reviews r1
                    INNER JOIN reviews r2 
                    WHERE r1.id < r2.id 
                    AND r1.hotel_name = r2.hotel_name 
                    AND r1.review_text = r2.review_text 
                    AND r1.rating = r2.rating 
                    AND r1.review_date = r2.review_date
                ";

                if ($preview) {
                    // Solo contar qué se eliminaría
                    $previewQuery = "
                        SELECT COUNT(*) as count FROM reviews r1
                        INNER JOIN reviews r2 
                        WHERE r1.id < r2.id 
                        AND r1.hotel_name = r2.hotel_name 
                        AND r1.review_text = r2.review_text 
                        AND r1.rating = r2.rating 
                        AND r1.review_date = r2.review_date
                    ";
                    $count = DB::select($previewQuery)[0]->count;
                    $results['reviews'] = ['would_delete' => $count];
                } else {
                    $deleted = DB::delete($query);
                    $results['reviews'] = ['deleted' => $deleted];
                    $deletedCount += $deleted;
                }
            }

            if ($table === 'hotels' || $table === 'all') {
                // Para hoteles, es más complejo porque pueden tener reviews asociados
                if ($preview) {
                    $count = DB::select("
                        SELECT COUNT(*) as count FROM hoteles h1
                        INNER JOIN hoteles h2 
                        WHERE h1.id < h2.id 
                        AND h1.nombre_hotel = h2.nombre_hotel
                    ")[0]->count;
                    $results['hotels'] = ['would_delete' => $count];
                } else {
                    // Eliminar hoteles duplicados (manteniendo el más reciente)
                    $deleted = DB::delete("
                        DELETE h1 FROM hoteles h1
                        INNER JOIN hoteles h2 
                        WHERE h1.id < h2.id 
                        AND h1.nombre_hotel = h2.nombre_hotel
                    ");
                    $results['hotels'] = ['deleted' => $deleted];
                    $deletedCount += $deleted;
                }
            }

            if ($table === 'prompts' || $table === 'all') {
                if ($preview) {
                    $count = DB::select("
                        SELECT COUNT(*) as count FROM prompts p1
                        INNER JOIN prompts p2 
                        WHERE p1.id < p2.id 
                        AND p1.title = p2.title 
                        AND p1.content = p2.content
                    ")[0]->count;
                    $results['prompts'] = ['would_delete' => $count];
                } else {
                    $deleted = DB::delete("
                        DELETE p1 FROM prompts p1
                        INNER JOIN prompts p2 
                        WHERE p1.id < p2.id 
                        AND p1.title = p2.title 
                        AND p1.content = p2.content
                    ");
                    $results['prompts'] = ['deleted' => $deleted];
                    $deletedCount += $deleted;
                }
            }

            if ($preview) {
                DB::rollback();
                
                return response()->json([
                    'success' => true,
                    'preview' => true,
                    'data' => $results,
                    'message' => 'Vista previa de eliminación de duplicados',
                    'summary' => [
                        'total_would_delete' => array_sum(array_column($results, 'would_delete')),
                        'warning' => 'Esta es solo una vista previa. Ejecuta sin preview=true para eliminar realmente.'
                    ]
                ]);
            }

            DB::commit();

            // Log de eliminación
            SystemLog::info('tools', 'Limpieza de duplicados completada', [
                'table' => $table,
                'deleted_count' => $deletedCount,
                'details' => $results
            ]);

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => "Eliminación de duplicados completada: {$deletedCount} registros eliminados",
                'summary' => [
                    'total_deleted' => $deletedCount,
                    'tables_cleaned' => array_keys($results),
                    'recommendation' => 'Se recomienda ejecutar optimización de tablas después de la limpieza.'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            SystemLog::error('tools', 'Error en eliminación de duplicados: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar duplicados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimizar tablas de la base de datos
     */
    public function optimizeTables(Request $request): JsonResponse
    {
        try {
            $tables = $request->get('tables', 'all');
            $operation = $request->get('operation', 'optimize'); // optimize, analyze, repair
            
            $targetTables = [];
            
            if ($tables === 'all') {
                $targetTables = ['hoteles', 'reviews', 'ai_providers', 'external_apis', 'prompts', 'system_logs', 'extraction_jobs', 'extraction_runs', 'extraction_logs'];
            } else {
                $targetTables = is_array($tables) ? $tables : [$tables];
            }

            $results = [];
            $totalProcessed = 0;

            foreach ($targetTables as $table) {
                try {
                    switch ($operation) {
                        case 'optimize':
                            DB::statement("OPTIMIZE TABLE {$table}");
                            $results[$table] = ['status' => 'optimized', 'operation' => 'optimize'];
                            break;
                            
                        case 'analyze':
                            DB::statement("ANALYZE TABLE {$table}");
                            $results[$table] = ['status' => 'analyzed', 'operation' => 'analyze'];
                            break;
                            
                        case 'repair':
                            DB::statement("REPAIR TABLE {$table}");
                            $results[$table] = ['status' => 'repaired', 'operation' => 'repair'];
                            break;
                            
                        case 'check':
                            $checkResult = DB::select("CHECK TABLE {$table}");
                            $results[$table] = [
                                'status' => 'checked', 
                                'operation' => 'check',
                                'result' => $checkResult[0]->Msg_text ?? 'OK'
                            ];
                            break;
                    }
                    $totalProcessed++;
                    
                } catch (\Exception $tableError) {
                    $results[$table] = [
                        'status' => 'error',
                        'operation' => $operation,
                        'error' => $tableError->getMessage()
                    ];
                }
            }

            // Operaciones adicionales de limpieza
            if ($operation === 'optimize') {
                try {
                    // Limpiar cache de consultas
                    DB::statement("FLUSH QUERY CACHE");
                    $results['_system'] = ['cache_flushed' => true];
                } catch (\Exception $e) {
                    $results['_system'] = ['cache_flush_error' => $e->getMessage()];
                }
            }

            // Log de optimización
            SystemLog::info('tools', "Operación de BD completada: {$operation}", [
                'operation' => $operation,
                'tables_processed' => $totalProcessed,
                'results' => $results
            ]);

            $successCount = count(array_filter($results, fn($r) => isset($r['status']) && $r['status'] !== 'error'));
            $errorCount = count(array_filter($results, fn($r) => isset($r['status']) && $r['status'] === 'error'));

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => "Operación '{$operation}' completada: {$successCount} tablas procesadas exitosamente",
                'summary' => [
                    'operation' => $operation,
                    'total_tables' => count($targetTables),
                    'successful' => $successCount,
                    'errors' => $errorCount,
                    'recommendation' => $errorCount > 0 ? 
                        'Revisa las tablas con errores y considera ejecutar operación de reparación.' : 
                        'Optimización completada exitosamente.'
                ]
            ]);

        } catch (\Exception $e) {
            SystemLog::error('tools', "Error en operación de BD '{$operation}': " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => "Error en operación de BD: {$operation}",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar integridad de la base de datos
     */
    public function checkIntegrity(): JsonResponse
    {
        try {
            $issues = [];
            $checksPerformed = 0;

            // 1. Verificar reviews huérfanos (sin hotel asociado)
            $orphanReviews = DB::select("
                SELECT COUNT(*) as count 
                FROM reviews r 
                LEFT JOIN hoteles h ON r.hotel_name = h.nombre_hotel 
                WHERE h.nombre_hotel IS NULL
            ")[0]->count;
            
            if ($orphanReviews > 0) {
                $issues[] = [
                    'type' => 'orphan_reviews',
                    'severity' => 'warning',
                    'count' => $orphanReviews,
                    'message' => "Encontradas {$orphanReviews} reseñas sin hotel asociado",
                    'suggestion' => 'Eliminar reseñas huérfanas o crear hoteles correspondientes'
                ];
            }
            $checksPerformed++;

            // 2. Verificar hoteles sin reviews
            $hotelsWithoutReviews = DB::select("
                SELECT COUNT(*) as count 
                FROM hoteles h 
                LEFT JOIN reviews r ON h.nombre_hotel = r.hotel_name 
                WHERE r.hotel_name IS NULL AND h.activo = 1
            ")[0]->count;
            
            if ($hotelsWithoutReviews > 5) { // Solo reportar si hay más de 5
                $issues[] = [
                    'type' => 'hotels_without_reviews',
                    'severity' => 'info',
                    'count' => $hotelsWithoutReviews,
                    'message' => "Encontrados {$hotelsWithoutReviews} hoteles activos sin reseñas",
                    'suggestion' => 'Considerar ejecutar extracción de reseñas para estos hoteles'
                ];
            }
            $checksPerformed++;

            // 3. Verificar extraction jobs sin API provider
            $jobsWithoutApi = DB::select("
                SELECT COUNT(*) as count 
                FROM extraction_jobs ej 
                LEFT JOIN external_apis ea ON ej.api_provider_id = ea.id 
                WHERE ea.id IS NULL AND ej.api_provider_id IS NOT NULL
            ")[0]->count;
            
            if ($jobsWithoutApi > 0) {
                $issues[] = [
                    'type' => 'jobs_without_api',
                    'severity' => 'error',
                    'count' => $jobsWithoutApi,
                    'message' => "Encontrados {$jobsWithoutApi} trabajos de extracción con API provider inexistente",
                    'suggestion' => 'Actualizar o eliminar trabajos con referencias incorrectas'
                ];
            }
            $checksPerformed++;

            // 4. Verificar logs huérfanos
            $orphanLogs = DB::select("
                SELECT COUNT(*) as count 
                FROM extraction_logs el 
                LEFT JOIN extraction_jobs ej ON el.job_id = ej.id 
                WHERE ej.id IS NULL AND el.job_id IS NOT NULL
            ")[0]->count;
            
            if ($orphanLogs > 0) {
                $issues[] = [
                    'type' => 'orphan_extraction_logs',
                    'severity' => 'warning',
                    'count' => $orphanLogs,
                    'message' => "Encontrados {$orphanLogs} logs de extracción huérfanos",
                    'suggestion' => 'Limpiar logs huérfanos para optimizar espacio'
                ];
            }
            $checksPerformed++;

            // 5. Verificar inconsistencias de estado en extraction jobs
            $inconsistentJobs = DB::select("
                SELECT COUNT(*) as count 
                FROM extraction_jobs 
                WHERE (status = 'completed' AND progress < 100) 
                   OR (status = 'running' AND (started_at IS NULL OR started_at > NOW()))
                   OR (status = 'failed' AND error_message IS NULL)
            ")[0]->count;
            
            if ($inconsistentJobs > 0) {
                $issues[] = [
                    'type' => 'inconsistent_job_status',
                    'severity' => 'warning',
                    'count' => $inconsistentJobs,
                    'message' => "Encontrados {$inconsistentJobs} trabajos con estado inconsistente",
                    'suggestion' => 'Revisar y corregir estados de trabajos de extracción'
                ];
            }
            $checksPerformed++;

            // 6. Verificar uso excesivo de espacio en logs
            $oldLogsCount = DB::select("
                SELECT COUNT(*) as count 
                FROM system_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
            ")[0]->count;
            
            if ($oldLogsCount > 10000) {
                $issues[] = [
                    'type' => 'old_logs_accumulation',
                    'severity' => 'info',
                    'count' => $oldLogsCount,
                    'message' => "Encontrados {$oldLogsCount} logs del sistema antiguos (>90 días)",
                    'suggestion' => 'Considerar limpiar logs antiguos para liberar espacio'
                ];
            }
            $checksPerformed++;

            // Log de verificación
            SystemLog::info('tools', 'Verificación de integridad completada', [
                'checks_performed' => $checksPerformed,
                'issues_found' => count($issues),
                'severity_breakdown' => [
                    'error' => count(array_filter($issues, fn($i) => $i['severity'] === 'error')),
                    'warning' => count(array_filter($issues, fn($i) => $i['severity'] === 'warning')),
                    'info' => count(array_filter($issues, fn($i) => $i['severity'] === 'info')),
                ]
            ]);

            $healthScore = max(0, 100 - (count(array_filter($issues, fn($i) => $i['severity'] === 'error')) * 30) 
                                      - (count(array_filter($issues, fn($i) => $i['severity'] === 'warning')) * 15)
                                      - (count(array_filter($issues, fn($i) => $i['severity'] === 'info')) * 5));

            return response()->json([
                'success' => true,
                'data' => $issues,
                'summary' => [
                    'checks_performed' => $checksPerformed,
                    'issues_found' => count($issues),
                    'health_score' => $healthScore,
                    'status' => $healthScore > 90 ? 'excellent' : ($healthScore > 70 ? 'good' : ($healthScore > 50 ? 'fair' : 'poor')),
                    'recommendation' => count($issues) === 0 ? 
                        'Base de datos íntegra, sin problemas detectados.' : 
                        'Se encontraron algunos problemas. Revisa las sugerencias para optimizar la BD.'
                ]
            ]);

        } catch (\Exception $e) {
            SystemLog::error('tools', 'Error en verificación de integridad: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar integridad de BD',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del sistema
     */
    public function getSystemInfo(): JsonResponse
    {
        try {
            $info = [
                'laravel' => [
                    'version' => app()->version(),
                    'environment' => app()->environment(),
                    'debug' => config('app.debug'),
                    'timezone' => config('app.timezone'),
                ],
                'database' => [
                    'connection' => config('database.default'),
                    'host' => config('database.connections.mysql.host'),
                    'database' => config('database.connections.mysql.database'),
                    'version' => DB::select('SELECT VERSION() as version')[0]->version,
                ],
                'php' => [
                    'version' => PHP_VERSION,
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                ],
                'server' => [
                    'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'os' => PHP_OS,
                    'current_time' => now()->format('Y-m-d H:i:s'),
                    'uptime' => $this->getSystemUptime(),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $info
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del sistema',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tamaños de tablas
     */
    private function getTableSizes(): array
    {
        try {
            $sizes = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ");

            return array_map(function($size) {
                return [
                    'table' => $size->table_name,
                    'size_mb' => $size->size_mb,
                    'rows' => $size->table_rows
                ];
            }, $sizes);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Calcular tiempo promedio de respuesta (simulado)
     */
    private function getAverageResponseTime(): float
    {
        // En una implementación real, esto vendría de logs de rendimiento
        return round(rand(50, 200) / 100, 2); // 0.5s - 2.0s simulado
    }

    /**
     * Calcular estado de salud general
     */
    private function calculateHealthStatus(array $stats): string
    {
        $score = 100;
        
        // Reducir puntuación por problemas potenciales
        if (($stats['system_logs']['errors'] ?? 0) > 50) $score -= 20;
        if (($stats['system_logs']['warnings'] ?? 0) > 100) $score -= 10;
        if (($stats['extraction_jobs']['failed'] ?? 0) > 5) $score -= 15;
        
        if ($score >= 90) return 'excellent';
        if ($score >= 75) return 'good';
        if ($score >= 60) return 'fair';
        return 'poor';
    }

    /**
     * Obtener uptime del sistema (simulado)
     */
    private function getSystemUptime(): string
    {
        // En un entorno real, esto leería el uptime del sistema
        $days = rand(1, 30);
        $hours = rand(0, 23);
        return "{$days} días, {$hours} horas";
    }
}