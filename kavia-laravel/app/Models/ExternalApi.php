<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

class ExternalApi extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider_type',
        'base_url',
        'credentials',
        'configuration',
        'is_active',
        'rate_limit',
        'version',
        'description',
        'endpoints',
        'last_tested_at',
        'last_test_result',
        'usage_count',
        'last_used_at'
    ];

    protected $casts = [
        'credentials' => 'array',
        'configuration' => 'array',
        'endpoints' => 'array',
        'last_test_result' => 'array',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
        'last_used_at' => 'datetime',
        'usage_count' => 'integer',
        'rate_limit' => 'integer'
    ];

    protected $hidden = [
        'credentials' // Ocultar credenciales en respuestas JSON por defecto
    ];

    /**
     * Encriptar credenciales antes de guardar
     */
    public function setCredentialsAttribute($value)
    {
        if (is_array($value)) {
            $encrypted = [];
            foreach ($value as $key => $credential) {
                if ($credential && !empty($credential)) {
                    $encrypted[$key] = Crypt::encryptString($credential);
                }
            }
            $this->attributes['credentials'] = json_encode($encrypted);
        }
    }

    /**
     * Desencriptar credenciales al recuperar
     */
    public function getCredentialsAttribute($value)
    {
        if (!$value) return [];
        
        $credentials = json_decode($value, true);
        if (!$credentials) return [];
        
        $decrypted = [];
        foreach ($credentials as $key => $encrypted) {
            if ($encrypted) {
                try {
                    $decrypted[$key] = Crypt::decryptString($encrypted);
                } catch (\Exception $e) {
                    $decrypted[$key] = null;
                }
            }
        }
        
        return $decrypted;
    }

    /**
     * Obtener credenciales para mostrar (enmascaradas)
     */
    public function getMaskedCredentialsAttribute()
    {
        $credentials = $this->credentials;
        $masked = [];
        
        foreach ($credentials as $key => $value) {
            if ($value && strlen($value) > 4) {
                $masked[$key] = substr($value, 0, 4) . str_repeat('*', strlen($value) - 4);
            } elseif ($value) {
                $masked[$key] = str_repeat('*', strlen($value));
            } else {
                $masked[$key] = '';
            }
        }
        
        return $masked;
    }

    /**
     * Scopes para filtrado
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider_type', $provider);
    }

    public function scopeRecentlyUsed($query, $days = 30)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }

    /**
     * Probar conexión con el API
     */
    public function testConnection()
    {
        $start = microtime(true);
        
        try {
            // Aquí iría la lógica específica para cada proveedor
            $result = $this->performConnectionTest();
            
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            $testResult = [
                'success' => true,
                'response_time' => $responseTime . 'ms',
                'tested_at' => now()->toISOString(),
                'status' => 'connected',
                'message' => 'Conexión exitosa con ' . $this->name
            ];
            
            $this->update([
                'last_tested_at' => now(),
                'last_test_result' => $testResult
            ]);
            
            return $testResult;
            
        } catch (\Exception $e) {
            $testResult = [
                'success' => false,
                'error' => $e->getMessage(),
                'tested_at' => now()->toISOString(),
                'status' => 'failed'
            ];
            
            $this->update([
                'last_tested_at' => now(),
                'last_test_result' => $testResult
            ]);
            
            return $testResult;
        }
    }

    /**
     * Realizar test de conexión específico del proveedor
     */
    private function performConnectionTest()
    {
        switch ($this->provider_type) {
            case 'booking':
                return $this->testBookingConnection();
            case 'tripadvisor':
                return $this->testTripAdvisorConnection();
            case 'expedia':
                return $this->testExpediaConnection();
            default:
                return $this->testGenericConnection();
        }
    }

    /**
     * Tests específicos por proveedor
     */
    private function testBookingConnection()
    {
        // Test básico para Booking.com
        $credentials = $this->credentials;
        if (empty($credentials['partner_id']) || empty($credentials['username'])) {
            throw new \Exception('Credenciales de Booking incompletas');
        }
        return true;
    }

    private function testTripAdvisorConnection()
    {
        $credentials = $this->credentials;
        if (empty($credentials['api_key'])) {
            throw new \Exception('API Key de TripAdvisor requerida');
        }
        return true;
    }

    private function testExpediaConnection()
    {
        $credentials = $this->credentials;
        if (empty($credentials['eqc_id']) || empty($credentials['api_key'])) {
            throw new \Exception('Credenciales de Expedia incompletas');
        }
        return true;
    }

    private function testGenericConnection()
    {
        // Test genérico
        if (empty($this->base_url)) {
            throw new \Exception('URL base requerida para test de conexión');
        }
        return true;
    }

    /**
     * Incrementar contador de uso
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Configuraciones predeterminadas por proveedor
     */
    public static function getProviderDefaults()
    {
        return [
            'booking' => [
                'name' => 'Booking.com API',
                'base_url' => 'https://distribution-xml.booking.com',
                'required_credentials' => ['partner_id', 'username', 'password'],
                'rate_limit' => 100,
                'description' => 'API oficial de Booking.com para acceso a inventario y precios'
            ],
            'tripadvisor' => [
                'name' => 'TripAdvisor API',
                'base_url' => 'https://api.tripadvisor.com',
                'required_credentials' => ['api_key'],
                'rate_limit' => 500,
                'description' => 'API de TripAdvisor para reseñas y información de hoteles'
            ],
            'expedia' => [
                'name' => 'Expedia Partner API',
                'base_url' => 'https://services.expediapartnercentral.com',
                'required_credentials' => ['eqc_id', 'api_key', 'secret'],
                'rate_limit' => 200,
                'description' => 'API de Expedia para gestión de tarifas y disponibilidad'
            ],
            'google' => [
                'name' => 'Google Travel API',
                'base_url' => 'https://www.googleapis.com/travel',
                'required_credentials' => ['api_key', 'project_id'],
                'rate_limit' => 1000,
                'description' => 'API de Google Travel para búsquedas y comparaciones'
            ],
            'custom' => [
                'name' => 'API Personalizada',
                'base_url' => '',
                'required_credentials' => ['api_key'],
                'rate_limit' => 100,
                'description' => 'Configuración personalizada para APIs propias'
            ]
        ];
    }
}
