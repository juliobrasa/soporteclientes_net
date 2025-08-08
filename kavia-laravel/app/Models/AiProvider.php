<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class AiProvider extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'ai_providers';

    /**
     * Campos que se pueden asignar masivamente
     */
    protected $fillable = [
        'name',
        'provider_type',
        'api_key',
        'api_url',
        'model_name',
        'parameters',
        'is_active'
    ];

    /**
     * Casting de tipos de datos
     */
    protected $casts = [
        'parameters' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Valores por defecto para atributos
     */
    protected $attributes = [
        'is_active' => false,
        'parameters' => '{}'
    ];

    /**
     * Ocultar campos sensibles en JSON
     */
    protected $hidden = [
        'api_key'
    ];

    // ================================================================
    // ENUMS Y CONSTANTES
    // ================================================================

    public const PROVIDER_TYPES = [
        'openai' => 'OpenAI',
        'claude' => 'Anthropic Claude',
        'deepseek' => 'DeepSeek',
        'gemini' => 'Google Gemini',
        'local' => 'Local Model'
    ];

    public const DEFAULT_PARAMETERS = [
        'openai' => [
            'temperature' => 0.7,
            'max_tokens' => 1500,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        ],
        'claude' => [
            'temperature' => 0.7,
            'max_tokens' => 1500,
            'top_p' => 1.0
        ],
        'deepseek' => [
            'temperature' => 0.7,
            'max_tokens' => 1500,
            'top_p' => 1.0
        ],
        'gemini' => [
            'temperature' => 0.7,
            'max_output_tokens' => 1500,
            'top_p' => 1.0,
            'top_k' => 40
        ],
        'local' => [
            'temperature' => 0.7,
            'max_tokens' => 1500
        ]
    ];

    public const DEFAULT_MODELS = [
        'openai' => 'gpt-3.5-turbo',
        'claude' => 'claude-3-sonnet-20240229',
        'deepseek' => 'deepseek-chat',
        'gemini' => 'gemini-pro',
        'local' => 'llama-2-7b'
    ];

    public const DEFAULT_URLS = [
        'openai' => 'https://api.openai.com/v1',
        'claude' => 'https://api.anthropic.com/v1',
        'deepseek' => 'https://api.deepseek.com/v1',
        'gemini' => 'https://generativelanguage.googleapis.com/v1',
        'local' => 'http://localhost:8080'
    ];

    // ================================================================
    // SCOPES
    // ================================================================

    /**
     * Scope para proveedores activos
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar por tipo de proveedor
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('provider_type', $type);
    }

    /**
     * Scope para buscar por nombre
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', '%' . $search . '%');
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrderByName(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('name', $direction);
    }

    // ================================================================
    // ACCESSORS Y MUTATORS
    // ================================================================

    /**
     * Obtener el nombre legible del tipo de proveedor
     */
    public function getProviderTypeNameAttribute(): string
    {
        return self::PROVIDER_TYPES[$this->provider_type] ?? $this->provider_type;
    }

    /**
     * Obtener la API key parcialmente ocultada para mostrar en frontend
     */
    public function getMaskedApiKeyAttribute(): ?string
    {
        if (!$this->api_key) {
            return null;
        }

        $key = $this->api_key;
        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }

        return substr($key, 0, 4) . str_repeat('*', strlen($key) - 8) . substr($key, -4);
    }

    /**
     * Obtener parámetros con valores por defecto si están vacíos
     */
    public function getParametersWithDefaultsAttribute(): array
    {
        $defaults = self::DEFAULT_PARAMETERS[$this->provider_type] ?? [];
        $current = $this->parameters ?? [];
        
        return array_merge($defaults, $current);
    }

    /**
     * Encriptar API key al guardar
     */
    public function setApiKeyAttribute($value): void
    {
        if ($value) {
            $this->attributes['api_key'] = encrypt($value);
        }
    }

    /**
     * Desencriptar API key al leer
     */
    public function getApiKeyAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Exception $e) {
            // Si falla la desencriptación, asumir que no está encriptada
            return $value;
        }
    }

    // ================================================================
    // MÉTODOS PERSONALIZADOS
    // ================================================================

    /**
     * Activar el proveedor
     */
    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Desactivar el proveedor
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Alternar estado activo/inactivo
     */
    public function toggleStatus(): bool
    {
        $this->is_active = !$this->is_active;
        return $this->save();
    }

    /**
     * Verificar si el proveedor tiene configuración válida
     */
    public function hasValidConfiguration(): bool
    {
        return !empty($this->name) && 
               !empty($this->provider_type) && 
               !empty($this->api_key) && 
               !empty($this->api_url) &&
               !empty($this->model_name);
    }

    /**
     * Obtener configuración completa para usar en API calls
     */
    public function getApiConfiguration(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->provider_type,
            'api_key' => $this->api_key,
            'api_url' => $this->api_url,
            'model' => $this->model_name,
            'parameters' => $this->parameters_with_defaults
        ];
    }

    /**
     * Crear proveedor con valores por defecto
     */
    public static function createWithDefaults(array $data): self
    {
        $type = $data['provider_type'] ?? 'openai';
        
        $defaults = [
            'api_url' => self::DEFAULT_URLS[$type] ?? '',
            'model_name' => self::DEFAULT_MODELS[$type] ?? '',
            'parameters' => self::DEFAULT_PARAMETERS[$type] ?? []
        ];

        return self::create(array_merge($defaults, $data));
    }

    /**
     * Obtener proveedores activos por tipo
     */
    public static function getActiveByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()->byType($type)->get();
    }

    /**
     * Obtener el proveedor activo principal (para usar por defecto)
     */
    public static function getDefaultProvider(): ?self
    {
        return self::active()->orderBy('created_at')->first();
    }
}