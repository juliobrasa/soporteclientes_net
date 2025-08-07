<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class SystemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'module',
        'action', 
        'message',
        'context',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'request_id',
        'metadata',
        'duration_ms',
        'memory_mb',
        'trace_id',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes'
    ];

    protected $casts = [
        'context' => 'array',
        'metadata' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'duration_ms' => 'integer',
        'memory_mb' => 'integer'
    ];

    /**
     * Niveles de log disponibles
     */
    const LEVELS = [
        'debug' => 'Debug',
        'info' => 'Info',
        'warning' => 'Warning',
        'error' => 'Error',
        'critical' => 'Critical'
    ];

    /**
     * Módulos del sistema
     */
    const MODULES = [
        'auth' => 'Autenticación',
        'hotels' => 'Hoteles',
        'reviews' => 'Reseñas',
        'ai' => 'IA/Proveedores',
        'prompts' => 'Prompts',
        'apis' => 'APIs Externas',
        'extraction' => 'Extracción',
        'system' => 'Sistema',
        'database' => 'Base de Datos',
        'cache' => 'Cache',
        'queue' => 'Colas',
        'scheduler' => 'Programador',
        'security' => 'Seguridad'
    ];

    /**
     * Scopes para filtrado
     */
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeErrors($query)
    {
        return $query->whereIn('level', ['error', 'critical']);
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByTrace($query, $traceId)
    {
        return $query->where('trace_id', $traceId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('message', 'like', "%{$search}%")
              ->orWhere('action', 'like', "%{$search}%")
              ->orWhereRaw("JSON_SEARCH(context, 'one', '%{$search}%') IS NOT NULL");
        });
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Métodos estáticos para logging rápido
     */
    public static function debug($module, $message, $context = [], $options = [])
    {
        return self::log('debug', $module, $message, $context, $options);
    }

    public static function info($module, $message, $context = [], $options = [])
    {
        return self::log('info', $module, $message, $context, $options);
    }

    public static function warning($module, $message, $context = [], $options = [])
    {
        return self::log('warning', $module, $message, $context, $options);
    }

    public static function error($module, $message, $context = [], $options = [])
    {
        return self::log('error', $module, $message, $context, $options);
    }

    public static function critical($module, $message, $context = [], $options = [])
    {
        return self::log('critical', $module, $message, $context, $options);
    }

    /**
     * Método principal de logging
     */
    public static function log($level, $module, $message, $context = [], $options = [])
    {
        $data = [
            'level' => $level,
            'module' => $module,
            'message' => $message,
            'context' => empty($context) ? null : json_encode($context),
            'action' => $options['action'] ?? null,
            'user_id' => $options['user_id'] ?? session('user_id'),
            'session_id' => $options['session_id'] ?? session()->getId(),
            'ip_address' => $options['ip_address'] ?? Request::ip(),
            'user_agent' => $options['user_agent'] ?? Request::userAgent(),
            'request_id' => $options['request_id'] ?? self::getRequestId(),
            'metadata' => isset($options['metadata']) ? $options['metadata'] : null,
            'duration_ms' => $options['duration_ms'] ?? null,
            'memory_mb' => $options['memory_mb'] ?? round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'trace_id' => $options['trace_id'] ?? self::getTraceId()
        ];

        return self::create($data);
    }

    /**
     * Resolver un log (marcar como resuelto)
     */
    public function resolve($resolvedBy = null, $notes = null)
    {
        return $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy ?? session('user_id'),
            'resolution_notes' => $notes
        ]);
    }

    /**
     * Obtener ID de request único
     */
    private static function getRequestId()
    {
        if (!session()->has('request_id')) {
            session(['request_id' => Str::uuid()]);
        }
        return session('request_id');
    }

    /**
     * Obtener ID de trace único para la sesión
     */
    private static function getTraceId()
    {
        if (!session()->has('trace_id')) {
            session(['trace_id' => Str::uuid()]);
        }
        return session('trace_id');
    }

    /**
     * Limpiar logs antiguos (llamar desde comando/scheduler)
     */
    public static function cleanup($days = 90)
    {
        $cutoffDate = now()->subDays($days);
        
        // Mantener todos los logs críticos y errores no resueltos
        return self::where('created_at', '<', $cutoffDate)
            ->where('level', 'not in', ['error', 'critical'])
            ->orWhere(function($query) use ($cutoffDate) {
                $query->where('created_at', '<', $cutoffDate)
                      ->where('is_resolved', true);
            })
            ->delete();
    }

    /**
     * Obtener estadísticas de logs
     */
    public static function getStats($period = '24h')
    {
        $hours = match($period) {
            '1h' => 1,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        $startTime = now()->subHours($hours);

        return [
            'total' => self::where('created_at', '>=', $startTime)->count(),
            'by_level' => self::where('created_at', '>=', $startTime)
                ->selectRaw('level, count(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'by_module' => self::where('created_at', '>=', $startTime)
                ->selectRaw('module, count(*) as count')
                ->groupBy('module')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'module')
                ->toArray(),
            'errors_unresolved' => self::whereIn('level', ['error', 'critical'])
                ->where('is_resolved', false)
                ->where('created_at', '>=', $startTime)
                ->count(),
            'recent_critical' => self::where('level', 'critical')
                ->where('created_at', '>=', $startTime)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'message', 'module', 'created_at']),
            'top_errors' => self::where('level', 'error')
                ->where('created_at', '>=', $startTime)
                ->selectRaw('message, count(*) as count, max(created_at) as last_occurrence')
                ->groupBy('message')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
        ];
    }

    /**
     * Obtener timeline de logs para gráficos
     */
    public static function getTimeline($period = '24h', $interval = '1h')
    {
        $hours = match($period) {
            '1h' => 1,
            '24h' => 24,  
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        $intervalHours = match($interval) {
            '5m' => 0.083,
            '15m' => 0.25,
            '1h' => 1,
            '6h' => 6,
            '1d' => 24,
            default => 1
        };

        $startTime = now()->subHours($hours);

        return self::where('created_at', '>=', $startTime)
            ->selectRaw("
                DATE_FORMAT(created_at, '%Y-%m-%d %H:00:00') as time_bucket,
                level,
                count(*) as count
            ")
            ->groupBy('time_bucket', 'level')
            ->orderBy('time_bucket')
            ->get()
            ->groupBy('time_bucket')
            ->map(function($logs) {
                return $logs->pluck('count', 'level')->toArray();
            });
    }

    /**
     * Atributos calculados
     */
    public function getLevelNameAttribute()
    {
        return self::LEVELS[$this->level] ?? ucfirst($this->level);
    }

    public function getModuleNameAttribute()
    {
        return self::MODULES[$this->module] ?? ucfirst($this->module);
    }

    public function getIsRecentAttribute()
    {
        return $this->created_at->diffInMinutes(now()) <= 60;
    }

    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_ms) return null;
        
        if ($this->duration_ms < 1000) {
            return $this->duration_ms . 'ms';
        }
        
        return round($this->duration_ms / 1000, 2) . 's';
    }
}
