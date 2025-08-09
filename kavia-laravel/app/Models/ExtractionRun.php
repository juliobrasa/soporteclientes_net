<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExtractionRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'hotel_id',
        'hotel_name',
        'status',
        'progress',
        'reviews_extracted',
        'reviews_target',
        'started_at',
        'completed_at',
        'duration',
        'error_message'
    ];

    protected $casts = [
        'progress' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_SKIPPED = 'skipped';

    /**
     * Relationship with ExtractionJob
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(ExtractionJob::class, 'job_id');
    }

    /**
     * Relationship with Hotel
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    /**
     * Relationship with extraction logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ExtractionLog::class, 'run_id');
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
            self::STATUS_SKIPPED => '<span class="badge badge-warning">Omitido</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge badge-light">Desconocido</span>';
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
     * Get formatted duration
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration) return '-';
        
        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Start run execution
     */
    public function start()
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        return $this;
    }

    /**
     * Complete run execution
     */
    public function complete($reviewsExtracted = null)
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;
        
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress' => 100,
            'duration' => $duration,
            'reviews_extracted' => $reviewsExtracted ?? $this->reviews_extracted,
        ]);

        return $this;
    }

    /**
     * Fail run execution
     */
    public function fail($errorMessage)
    {
        $duration = $this->started_at ? now()->diffInSeconds($this->started_at) : 0;
        
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'duration' => $duration,
            'error_message' => $errorMessage,
        ]);

        return $this;
    }

    /**
     * Skip run execution
     */
    public function skip($reason = null)
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'error_message' => $reason,
        ]);

        return $this;
    }
}