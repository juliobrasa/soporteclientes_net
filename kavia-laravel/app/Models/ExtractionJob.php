<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtractionJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'mode',
        'priority',
        'api_provider_id',
        'api_provider_name',
        'api_provider_type',
        'hotel_count',
        'max_reviews_per_hotel',
        'selected_hotels',
        'progress',
        'completed_hotels',
        'reviews_extracted',
        'estimated_reviews',
        'total_cost',
        'options',
        'execution_mode',
        'scheduled_datetime',
        'started_at',
        'completed_at',
        'estimated_completion',
        'running_time',
        'error_message'
    ];

    protected $casts = [
        'selected_hotels' => 'array',
        'options' => 'array',
        'progress' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'scheduled_datetime' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_completion' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Mode constants
    const MODE_ACTIVE = 'active';
    const MODE_ALL = 'all';
    const MODE_SELECTED = 'selected';

    // Priority constants
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Execution mode constants
    const EXECUTION_IMMEDIATE = 'immediate';
    const EXECUTION_SCHEDULE = 'schedule';
    const EXECUTION_DRAFT = 'draft';

    /**
     * Relationship with ExternalApi
     */
    public function apiProvider(): BelongsTo
    {
        return $this->belongsTo(ExternalApi::class, 'api_provider_id');
    }

    /**
     * Relationship with extraction runs
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ExtractionRun::class, 'job_id');
    }

    /**
     * Relationship with extraction logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ExtractionLog::class, 'job_id');
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            self::STATUS_PENDING => '<span class="badge badge-secondary">Pendiente</span>',
            self::STATUS_RUNNING => '<span class="badge badge-primary">En Proceso</span>',
            self::STATUS_COMPLETED => '<span class="badge badge-success">Completado</span>',
            self::STATUS_FAILED => '<span class="badge badge-danger">Fallido</span>',
            self::STATUS_CANCELLED => '<span class="badge badge-warning">Cancelado</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge badge-light">Desconocido</span>';
    }

    /**
     * Get mode badge HTML
     */
    public function getModeBadgeAttribute(): string
    {
        $badges = [
            self::MODE_ACTIVE => '<span class="mode-badge mode-active">Activos</span>',
            self::MODE_ALL => '<span class="mode-badge mode-all">Todos</span>',
            self::MODE_SELECTED => '<span class="mode-badge mode-selected">Seleccionados</span>',
        ];

        return $badges[$this->mode] ?? '';
    }

    /**
     * Get progress bar HTML
     */
    public function getProgressBarAttribute(): string
    {
        $progressClass = $this->status === self::STATUS_RUNNING ? 'running' : 
                        ($this->status === self::STATUS_FAILED ? 'failed' : '');

        return '
            <div class="progress-bar">
                <div class="progress-fill ' . $progressClass . '" style="width: ' . $this->progress . '%"></div>
            </div>
            <div class="progress-text">' . number_format($this->progress, 1) . '%</div>
        ';
    }

    /**
     * Get formatted created date
     */
    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at ? $this->created_at->format('d/m/Y H:i') : '-';
    }

    /**
     * Get API provider icon
     */
    public function getApiIconAttribute(): string
    {
        $icons = [
            'apify' => 'fas fa-spider',
            'booking' => 'fas fa-bed',
            'tripadvisor' => 'fas fa-map-marker-alt',
            'google' => 'fab fa-google',
        ];

        return $icons[$this->api_provider_type] ?? 'fas fa-plug';
    }

    /**
     * Get status class for row styling
     */
    public function getStatusClassAttribute(): string
    {
        return 'status-' . $this->status;
    }

    /**
     * Get reviews target display
     */
    public function getReviewsTargetAttribute(): string
    {
        if ($this->estimated_reviews > 0) {
            return '<small class="text-gray">/ ' . number_format($this->estimated_reviews) . '</small>';
        }
        return '';
    }

    /**
     * Get formatted total cost
     */
    public function getTotalCostFormattedAttribute(): string
    {
        if ($this->total_cost > 0) {
            return '$' . number_format($this->total_cost, 2);
        }
        return '$0.00';
    }

    /**
     * Get action buttons HTML
     */
    public function getActionButtonsAttribute(): string
    {
        $buttons = [];

        switch ($this->status) {
            case self::STATUS_PENDING:
                $buttons[] = '<button class="btn btn-sm btn-success" onclick="extractorModule.startJob(' . $this->id . ')" title="Iniciar trabajo">
                    <i class="fas fa-play"></i>
                </button>';
                $buttons[] = '<button class="btn btn-sm btn-secondary" onclick="extractorModule.editJob(' . $this->id . ')" title="Editar trabajo">
                    <i class="fas fa-edit"></i>
                </button>';
                $buttons[] = '<button class="btn btn-sm btn-danger" onclick="extractorModule.deleteJob(' . $this->id . ')" title="Eliminar trabajo">
                    <i class="fas fa-trash"></i>
                </button>';
                break;

            case self::STATUS_RUNNING:
                $buttons[] = '<button class="btn btn-sm btn-warning" onclick="extractorModule.pauseJob(' . $this->id . ')" title="Pausar trabajo">
                    <i class="fas fa-pause"></i>
                </button>';
                $buttons[] = '<button class="btn btn-sm btn-danger" onclick="extractorModule.cancelJob(' . $this->id . ')" title="Cancelar trabajo">
                    <i class="fas fa-stop"></i>
                </button>';
                $buttons[] = '<button class="btn btn-sm btn-info" onclick="extractorModule.viewJob(' . $this->id . ')" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>';
                break;

            case self::STATUS_COMPLETED:
                $buttons[] = '<button class="btn btn-sm btn-info" onclick="extractorModule.viewJob(' . $this->id . ')" title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>';
                $buttons[] = '<button class="btn btn-sm btn-primary" onclick="extractorModule.downloadResults(' . $this->id . ')" title="Descargar resultados">
                    <i class="fas fa-download"></i>
                </button>';
                $buttons[] = '<button class="btn btn-sm btn-secondary" onclick="extractorModule.cloneJob(' . $this->id . ')" title="Clonar trabajo">
                    <i class="fas fa-copy"></i>
                </button>';
                break;

            case self::STATUS_FAILED:
                $buttons[] = '<button class="btn btn-sm btn-warning" onclick="extractorModule.retryJob(' . $this->id . ')" title="Reintentar trabajo">
                    <i class="fas fa-redo"></i>
                </button>';
                $buttons[] = '<button class="btn btn-sm btn-info" onclick="extractorModule.viewJob(' . $this->id . ')" title="Ver error">
                    <i class="fas fa-exclamation-triangle"></i>
                </button>';
                $buttons[] = '<button class="btn btn-sm btn-danger" onclick="extractorModule.deleteJob(' . $this->id . ')" title="Eliminar trabajo">
                    <i class="fas fa-trash"></i>
                </button>';
                break;

            case self::STATUS_CANCELLED:
                $buttons[] = '<button class="btn btn-sm btn-warning" onclick="extractorModule.retryJob(' . $this->id . ')" title="Reintentar trabajo">
                    <i class="fas fa-redo"></i>
                </button>';
                $buttons[] = '<button class="btn btn-sm btn-danger" onclick="extractorModule.deleteJob(' . $this->id . ')" title="Eliminar trabajo">
                    <i class="fas fa-trash"></i>
                </button>';
                break;
        }

        return implode("\n", $buttons);
    }

    /**
     * Get mobile action buttons HTML
     */
    public function getMobileActionButtonsAttribute(): string
    {
        // Similar to action buttons but for mobile layout
        return $this->getActionButtonsAttribute();
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope for filtering by period
     */
    public function scopeByPeriod($query, $period)
    {
        switch ($period) {
            case 'today':
                return $query->whereDate('created_at', today());
            case 'week':
                return $query->where('created_at', '>=', now()->startOfWeek());
            case 'month':
                return $query->where('created_at', '>=', now()->startOfMonth());
            case '3months':
                return $query->where('created_at', '>=', now()->subMonths(3));
            default:
                return $query;
        }
    }

    /**
     * Scope for searching
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('api_provider_name', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * Get statistics for dashboard
     */
    public static function getStats($period = '30d')
    {
        $query = self::query();
        
        if ($period === '30d') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($period === '7d') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($period === '24h') {
            $query->where('created_at', '>=', now()->subHours(24));
        }

        return [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', self::STATUS_PENDING)->count(),
            'running' => (clone $query)->where('status', self::STATUS_RUNNING)->count(),
            'completed' => (clone $query)->where('status', self::STATUS_COMPLETED)->count(),
            'failed' => (clone $query)->where('status', self::STATUS_FAILED)->count(),
            'total_reviews' => (clone $query)->sum('reviews_extracted'),
            'total_cost' => (clone $query)->sum('total_cost'),
            'active_apis' => ExternalApi::where('is_active', true)->count(),
            'active_hotels' => Hotel::where('activo', 1)->count(),
        ];
    }

    /**
     * Start job execution
     */
    public function start()
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        // Log start event
        $this->logs()->create([
            'level' => 'info',
            'message' => 'Trabajo de extracción iniciado',
            'data' => [
                'user_id' => session('user_id'),
                'timestamp' => now()->toISOString(),
            ]
        ]);

        return $this;
    }

    /**
     * Complete job execution
     */
    public function complete()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress' => 100,
            'running_time' => $this->started_at ? now()->diffInSeconds($this->started_at) : 0,
        ]);

        // Log completion event
        $this->logs()->create([
            'level' => 'info',
            'message' => 'Trabajo de extracción completado',
            'data' => [
                'duration' => $this->running_time,
                'reviews_extracted' => $this->reviews_extracted,
                'total_cost' => $this->total_cost,
            ]
        ]);

        return $this;
    }

    /**
     * Fail job execution
     */
    public function fail($errorMessage)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'running_time' => $this->started_at ? now()->diffInSeconds($this->started_at) : 0,
        ]);

        // Log error event
        $this->logs()->create([
            'level' => 'error',
            'message' => 'Trabajo de extracción falló: ' . $errorMessage,
            'data' => [
                'error' => $errorMessage,
                'progress_at_failure' => $this->progress,
            ]
        ]);

        return $this;
    }

    /**
     * Cancel job execution
     */
    public function cancel()
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'running_time' => $this->started_at ? now()->diffInSeconds($this->started_at) : 0,
        ]);

        // Log cancellation event
        $this->logs()->create([
            'level' => 'warning',
            'message' => 'Trabajo de extracción cancelado',
            'data' => [
                'progress_at_cancellation' => $this->progress,
                'user_id' => session('user_id'),
            ]
        ]);

        return $this;
    }
}