<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ClientUser extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company_name',
        'password',
        'client_level_id',
        'active',
        'last_login_at',
        'preferences',
        'custom_max_hotels',
        'custom_max_reviews_per_month',
        'subscription_start',
        'subscription_end',
        'subscription_status',
        'metadata'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'timestamp',
        'last_login_at' => 'timestamp',
        'preferences' => 'array',
        'metadata' => 'array',
        'active' => 'boolean',
        'subscription_start' => 'date',
        'subscription_end' => 'date',
        'password' => 'hashed',
    ];

    /**
     * Relación con el nivel de cliente
     */
    public function clientLevel()
    {
        return $this->belongsTo(ClientLevel::class);
    }

    /**
     * Relación many-to-many con hoteles
     */
    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'client_hotel_access', 'client_user_id', 'hotel_id')
            ->withPivot(['active', 'permissions'])
            ->withTimestamps();
    }

    /**
     * Obtener hoteles activos del usuario
     */
    public function activeHotels()
    {
        return $this->hotels()->wherePivot('active', true);
    }

    /**
     * Verificar si tiene acceso a un hotel específico
     */
    public function hasAccessToHotel(int $hotelId): bool
    {
        return $this->activeHotels()->where('hotel_id', $hotelId)->exists();
    }

    /**
     * Verificar si tiene una característica específica
     */
    public function hasFeature(string $feature): bool
    {
        return $this->clientLevel->hasFeature($feature);
    }

    /**
     * Verificar si tiene acceso a un módulo específico
     */
    public function hasModule(string $module): bool
    {
        return $this->clientLevel->hasModule($module);
    }

    /**
     * Obtener límite máximo de hoteles (considerando límites personalizados)
     */
    public function getMaxHotelsAttribute(): int
    {
        return $this->custom_max_hotels ?? $this->clientLevel->max_hotels;
    }

    /**
     * Obtener límite máximo de reseñas por mes (considerando límites personalizados)
     */
    public function getMaxReviewsPerMonthAttribute(): int
    {
        return $this->custom_max_reviews_per_month ?? $this->clientLevel->max_reviews_per_month;
    }

    /**
     * Verificar si la suscripción está activa
     */
    public function isSubscriptionActive(): bool
    {
        if (!$this->active) {
            return false;
        }

        if ($this->subscription_status === 'canceled' || $this->subscription_status === 'expired') {
            return false;
        }

        if ($this->subscription_end && $this->subscription_end->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si está en período de prueba
     */
    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial';
    }

    /**
     * Obtener días restantes de suscripción
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->subscription_end) {
            return null;
        }

        return max(0, now()->diffInDays($this->subscription_end, false));
    }

    /**
     * Scope para usuarios activos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para usuarios con suscripción activa
     */
    public function scopeSubscriptionActive($query)
    {
        return $query->where('active', true)
            ->whereIn('subscription_status', ['active', 'trial'])
            ->where(function($q) {
                $q->whereNull('subscription_end')
                  ->orWhere('subscription_end', '>=', now());
            });
    }

    /**
     * Actualizar último login
     */
    public function updateLastLogin()
    {
        $this->update(['last_login_at' => now()]);
    }
}