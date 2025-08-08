<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'features',
        'modules',
        'max_hotels',
        'max_reviews_per_month',
        'monthly_price',
        'active',
        'sort_order'
    ];

    protected $casts = [
        'features' => 'array',
        'modules' => 'array',
        'active' => 'boolean',
        'monthly_price' => 'decimal:2'
    ];

    /**
     * Relación con usuarios clientes
     */
    public function users()
    {
        return $this->hasMany(ClientUser::class);
    }

    /**
     * Verificar si tiene una característica específica
     */
    public function hasFeature(string $feature): bool
    {
        return $this->features[$feature] ?? false;
    }

    /**
     * Verificar si tiene acceso a un módulo específico
     */
    public function hasModule(string $module): bool
    {
        return $this->modules[$module] ?? false;
    }

    /**
     * Scope para niveles activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope ordenado por sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}