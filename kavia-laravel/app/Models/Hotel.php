<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hotel extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla (compatible con sistema actual)
     */
    protected $table = 'hoteles';

    /**
     * Campos que se pueden asignar masivamente
     */
    protected $fillable = [
        'nombre_hotel',
        'hoja_destino', 
        'url_booking',
        'max_reviews',
        'activo'
    ];

    /**
     * Casting de tipos de datos
     */
    protected $casts = [
        'activo' => 'boolean',
        'max_reviews' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Valores por defecto
     */
    protected $attributes = [
        'activo' => true,
        'max_reviews' => 200
    ];

    // ================================================================
    // RELACIONES
    // ================================================================

    /**
     * Relación con reviews del hotel
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'hotel_id');
    }

    // ================================================================
    // SCOPES (FILTROS)
    // ================================================================

    /**
     * Scope para hoteles activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por nombre
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('nombre_hotel', 'LIKE', "%{$search}%")
                        ->orWhere('hoja_destino', 'LIKE', "%{$search}%");
        }
        return $query;
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrderByName($query, $direction = 'asc')
    {
        return $query->orderBy('nombre_hotel', $direction);
    }

    // ================================================================
    // ACCESSORS (PROPIEDADES CALCULADAS)
    // ================================================================

    /**
     * Calcular rating promedio del hotel
     */
    public function getAverageRatingAttribute()
    {
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->avg('rating') ?? 0;
        }
        
        return $this->reviews()->avg('rating') ?? 0;
    }

    /**
     * Calcular total de reviews
     */
    public function getTotalReviewsAttribute()
    {
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->count();
        }
        
        return $this->reviews()->count();
    }

    /**
     * Obtener estado como texto
     */
    public function getStatusTextAttribute()
    {
        return $this->activo ? 'Activo' : 'Inactivo';
    }

    /**
     * Obtener URL limpia de Booking
     */
    public function getBookingUrlAttribute()
    {
        return $this->url_booking ?: null;
    }

    // ================================================================
    // MUTATORS (MODIFICADORES)
    // ================================================================

    /**
     * Limpiar nombre del hotel
     */
    public function setNombreHotelAttribute($value)
    {
        $this->attributes['nombre_hotel'] = trim($value);
    }

    /**
     * Limpiar destino
     */
    public function setHojaDestinoAttribute($value)
    {
        $this->attributes['hoja_destino'] = trim($value);
    }

    // ================================================================
    // MÉTODOS PERSONALIZADOS
    // ================================================================

    /**
     * Activar hotel
     */
    public function activate()
    {
        $this->activo = true;
        return $this->save();
    }

    /**
     * Desactivar hotel
     */
    public function deactivate()
    {
        $this->activo = false;
        return $this->save();
    }

    /**
     * Alternar estado del hotel
     */
    public function toggleStatus()
    {
        $this->activo = !$this->activo;
        return $this->save();
    }

    /**
     * Verificar si tiene reviews
     */
    public function hasReviews()
    {
        return $this->reviews()->exists();
    }

    /**
     * Obtener estadísticas del hotel
     */
    public function getStats()
    {
        return [
            'total_reviews' => $this->total_reviews,
            'average_rating' => round($this->average_rating, 1),
            'status' => $this->status_text,
            'booking_url' => $this->booking_url
        ];
    }
}
