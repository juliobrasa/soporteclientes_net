<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Prompt extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'prompts';

    /**
     * Campos que se pueden asignar masivamente
     */
    protected $fillable = [
        'name',
        'category',
        'language',
        'description',
        'content',
        'status',
        'version',
        'tags',
        'custom_variables',
        'config',
        'usage_count',
        'last_used'
    ];

    /**
     * Casting de tipos de datos
     */
    protected $casts = [
        'tags' => 'array',
        'custom_variables' => 'array',
        'config' => 'array',
        'usage_count' => 'integer',
        'last_used' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Valores por defecto para atributos
     */
    protected $attributes = [
        'status' => 'draft',
        'version' => '1.0',
        'usage_count' => 0,
        'language' => 'es'
    ];

    // ================================================================
    // CONSTANTES Y ENUMS
    // ================================================================

    public const CATEGORIES = [
        'sentiment' => 'Análisis de Sentimiento',
        'extraction' => 'Extracción de Datos',
        'translation' => 'Traducción',
        'classification' => 'Clasificación',
        'summary' => 'Resumen',
        'custom' => 'Personalizado'
    ];

    public const STATUSES = [
        'draft' => 'Borrador',
        'active' => 'Activo',
        'archived' => 'Archivado'
    ];

    public const LANGUAGES = [
        'es' => 'Español',
        'en' => 'English',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português'
    ];

    public const DEFAULT_CONFIG = [
        'temperature' => 0.7,
        'max_tokens' => 1000,
        'top_p' => 0.9,
        'frequency_penalty' => 0,
        'retry_attempts' => 2,
        'timeout_seconds' => 30,
        'enable_content_filter' => false,
        'validate_output_format' => false,
        'track_usage' => true,
        'log_requests' => false,
        'log_level' => 'info',
        'retention_days' => 30
    ];

    public const SYSTEM_VARIABLES = [
        'review_text' => 'Texto completo de la reseña',
        'hotel_name' => 'Nombre del hotel',
        'rating' => 'Calificación numérica',
        'user_language' => 'Idioma del usuario',
        'date' => 'Fecha actual',
        'guest_name' => 'Nombre del huésped',
        'stay_date' => 'Fecha de estadía'
    ];

    // ================================================================
    // SCOPES
    // ================================================================

    /**
     * Scope para prompts activos
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope para prompts por categoría
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope para prompts por idioma
     */
    public function scopeByLanguage(Builder $query, string $language): Builder
    {
        return $query->where('language', $language);
    }

    /**
     * Scope para prompts por estado
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para búsqueda full-text
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope para prompts más usados
     */
    public function scopeMostUsed(Builder $query, int $limit = 10): Builder
    {
        return $query->where('usage_count', '>', 0)
                    ->orderBy('usage_count', 'desc')
                    ->limit($limit);
    }

    /**
     * Scope para prompts recientes
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days))
                    ->orderBy('created_at', 'desc');
    }

    // ================================================================
    // ACCESSORS Y MUTATORS
    // ================================================================

    /**
     * Obtener el nombre legible de la categoría
     */
    public function getCategoryNameAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Obtener el nombre legible del estado
     */
    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Obtener el nombre legible del idioma
     */
    public function getLanguageNameAttribute(): string
    {
        return self::LANGUAGES[$this->language] ?? $this->language;
    }

    /**
     * Obtener configuración con valores por defecto
     */
    public function getConfigWithDefaultsAttribute(): array
    {
        $defaults = self::DEFAULT_CONFIG;
        $current = $this->config ?? [];
        
        return array_merge($defaults, $current);
    }

    /**
     * Obtener lista de variables en el contenido
     */
    public function getVariablesInContentAttribute(): array
    {
        preg_match_all('/\{([^}]+)\}/', $this->content, $matches);
        return array_unique($matches[1]);
    }

    /**
     * Verificar si el prompt está listo para usar
     */
    public function getIsReadyAttribute(): bool
    {
        return !empty($this->name) && 
               !empty($this->content) && 
               $this->status === 'active';
    }

    /**
     * Obtener estadísticas de uso
     */
    public function getUsageStatsAttribute(): array
    {
        return [
            'total_uses' => $this->usage_count,
            'last_used' => $this->last_used?->diffForHumans(),
            'created' => $this->created_at->diffForHumans(),
            'days_since_creation' => $this->created_at->diffInDays(now()),
            'avg_uses_per_day' => $this->created_at->diffInDays(now()) > 0 
                ? round($this->usage_count / $this->created_at->diffInDays(now()), 2) 
                : 0
        ];
    }

    // ================================================================
    // MÉTODOS PERSONALIZADOS
    // ================================================================

    /**
     * Activar el prompt
     */
    public function activate(): bool
    {
        $this->status = 'active';
        return $this->save();
    }

    /**
     * Archivar el prompt
     */
    public function archive(): bool
    {
        $this->status = 'archived';
        return $this->save();
    }

    /**
     * Cambiar a borrador
     */
    public function draft(): bool
    {
        $this->status = 'draft';
        return $this->save();
    }

    /**
     * Incrementar contador de uso
     */
    public function incrementUsage(): bool
    {
        $this->usage_count++;
        $this->last_used = now();
        return $this->save();
    }

    /**
     * Duplicar prompt
     */
    public function duplicate(): self
    {
        $duplicate = $this->replicate();
        $duplicate->name = $this->name . ' (Copia)';
        $duplicate->status = 'draft';
        $duplicate->usage_count = 0;
        $duplicate->last_used = null;
        $duplicate->save();
        
        return $duplicate;
    }

    /**
     * Reemplazar variables en el contenido
     */
    public function replaceVariables(array $variables): string
    {
        $content = $this->content;
        
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        return $content;
    }

    /**
     * Validar que todas las variables requeridas estén presentes
     */
    public function validateVariables(array $variables): array
    {
        $errors = [];
        $requiredVars = $this->variables_in_content;
        
        foreach ($requiredVars as $var) {
            if (!array_key_exists($var, $variables) || empty($variables[$var])) {
                $errors[] = "Variable requerida faltante: {$var}";
            }
        }
        
        return $errors;
    }

    /**
     * Obtener prompts por categoría con estadísticas
     */
    public static function getCategoryStats(): array
    {
        $stats = [];
        
        foreach (self::CATEGORIES as $category => $name) {
            $stats[$category] = [
                'name' => $name,
                'total' => self::byCategory($category)->count(),
                'active' => self::byCategory($category)->active()->count(),
                'most_used' => self::byCategory($category)->mostUsed(1)->first()?->name,
                'total_usage' => self::byCategory($category)->sum('usage_count')
            ];
        }
        
        return $stats;
    }

    /**
     * Crear prompt con configuración por defecto
     */
    public static function createWithDefaults(array $data): self
    {
        $defaults = [
            'config' => self::DEFAULT_CONFIG,
            'tags' => [],
            'custom_variables' => []
        ];
        
        return self::create(array_merge($defaults, $data));
    }

    /**
     * Obtener prompts recomendados para una categoría
     */
    public static function getRecommended(string $category, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return self::byCategory($category)
                  ->active()
                  ->orderBy('usage_count', 'desc')
                  ->orderBy('updated_at', 'desc')
                  ->limit($limit)
                  ->get();
    }

    /**
     * Buscar prompts similares por contenido
     */
    public function findSimilar(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        // Extraer palabras clave del contenido
        $keywords = $this->extractKeywords($this->content);
        
        $query = self::where('id', '!=', $this->id)
                    ->where('category', $this->category)
                    ->where('language', $this->language);
        
        // Buscar por palabras clave
        foreach ($keywords as $keyword) {
            $query->orWhere('content', 'like', '%' . $keyword . '%');
        }
        
        return $query->limit($limit)->get();
    }

    /**
     * Extraer palabras clave del contenido
     */
    private function extractKeywords(string $content, int $limit = 10): array
    {
        // Remover variables {variable}
        $content = preg_replace('/\{[^}]+\}/', '', $content);
        
        // Convertir a minúsculas y extraer palabras
        $words = str_word_count(strtolower($content), 1);
        
        // Filtrar palabras comunes (stop words básicas en español)
        $stopWords = ['el', 'la', 'de', 'que', 'y', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son', 'con', 'para', 'como', 'las', 'del', 'los', 'una', 'por', 'este', 'esta', 'son', 'pero', 'más'];
        
        $words = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        // Contar frecuencia y tomar las más frecuentes
        $wordCount = array_count_values($words);
        arsort($wordCount);
        
        return array_slice(array_keys($wordCount), 0, $limit);
    }
}