<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtractionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'run_id',
        'level',
        'message',
        'data'
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // Level constants
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';

    /**
     * Relationship with ExtractionJob
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(ExtractionJob::class, 'job_id');
    }

    /**
     * Relationship with ExtractionRun
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(ExtractionRun::class, 'run_id');
    }

    /**
     * Get level badge HTML
     */
    public function getLevelBadgeAttribute(): string
    {
        $badges = [
            self::LEVEL_INFO => '<span class="badge badge-info">Info</span>',
            self::LEVEL_WARNING => '<span class="badge badge-warning">Warning</span>',
            self::LEVEL_ERROR => '<span class="badge badge-danger">Error</span>',
        ];

        return $badges[$this->level] ?? '<span class="badge badge-light">Unknown</span>';
    }

    /**
     * Get level icon
     */
    public function getLevelIconAttribute(): string
    {
        $icons = [
            self::LEVEL_INFO => 'fas fa-info-circle text-blue',
            self::LEVEL_WARNING => 'fas fa-exclamation-triangle text-yellow',
            self::LEVEL_ERROR => 'fas fa-times-circle text-red',
        ];

        return $icons[$this->level] ?? 'fas fa-circle';
    }

    /**
     * Create a log entry
     */
    public static function log($jobId, $level, $message, $data = [], $runId = null)
    {
        return self::create([
            'job_id' => $jobId,
            'run_id' => $runId,
            'level' => $level,
            'message' => $message,
            'data' => empty($data) ? null : $data,
        ]);
    }

    /**
     * Create info log
     */
    public static function info($jobId, $message, $data = [], $runId = null)
    {
        return self::log($jobId, self::LEVEL_INFO, $message, $data, $runId);
    }

    /**
     * Create warning log
     */
    public static function warning($jobId, $message, $data = [], $runId = null)
    {
        return self::log($jobId, self::LEVEL_WARNING, $message, $data, $runId);
    }

    /**
     * Create error log
     */
    public static function error($jobId, $message, $data = [], $runId = null)
    {
        return self::log($jobId, self::LEVEL_ERROR, $message, $data, $runId);
    }

    /**
     * Scope for filtering by level
     */
    public function scopeByLevel($query, $level)
    {
        if ($level) {
            return $query->where('level', $level);
        }
        return $query;
    }

    /**
     * Scope for filtering by job
     */
    public function scopeByJob($query, $jobId)
    {
        if ($jobId) {
            return $query->where('job_id', $jobId);
        }
        return $query;
    }

    /**
     * Scope for filtering by run
     */
    public function scopeByRun($query, $runId)
    {
        if ($runId) {
            return $query->where('run_id', $runId);
        }
        return $query;
    }
}