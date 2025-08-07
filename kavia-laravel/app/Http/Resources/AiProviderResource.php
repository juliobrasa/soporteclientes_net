<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider_type' => $this->provider_type,
            'provider_type_name' => $this->provider_type_name,
            'api_url' => $this->api_url,
            'model_name' => $this->model_name,
            'parameters' => $this->parameters_with_defaults,
            'is_active' => $this->is_active,
            'has_valid_config' => $this->hasValidConfiguration(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Campos condicionales
            'masked_api_key' => $this->when(
                $request->input('show_keys') === 'true' || $request->routeIs('*.show'),
                $this->masked_api_key
            ),
            
            // Información adicional para frontend
            'status_text' => $this->is_active ? 'Activo' : 'Inactivo',
            'status_class' => $this->is_active ? 'success' : 'secondary',
            
            // Configuración para formularios
            'default_parameters' => $this->when(
                $request->routeIs('*.create') || $request->routeIs('*.edit'),
                \App\Models\AiProvider::DEFAULT_PARAMETERS[$this->provider_type] ?? []
            ),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'provider_types' => \App\Models\AiProvider::PROVIDER_TYPES,
                'default_models' => \App\Models\AiProvider::DEFAULT_MODELS,
                'default_urls' => \App\Models\AiProvider::DEFAULT_URLS,
            ]
        ];
    }
}