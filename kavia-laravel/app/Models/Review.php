<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla (compatible con sistema actual)
     */
    protected $table = 'reviews';

    /**
     * Campos que se pueden asignar masivamente
     */
    protected $fillable = [
        'hotel_id',
        'platform',
        'rating',
        'title',
        'content',
        'liked_text',
        'disliked_text',
        'review_date'
    ];

    /**
     * Casting de tipos de datos
     */
    protected $casts = [
        'rating' => 'decimal:1',
        'review_date' => 'date',
        'hotel_id' => 'integer'
    ];

    // ================================================================
    // RELACIONES
    // ================================================================

    /**
     * Relación con hotel
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}
