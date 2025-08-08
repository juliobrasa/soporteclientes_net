<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromptResource extends JsonResource
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
            'category' => $this->category,
            'category_name' => $this->category_name,
            'language' => $this->language,
            'language_name' => $this->language_name,
            'description' => $this->description,
            'content' => $this->content,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'version' => $this->version,
            'tags' => $this->tags ?? [],
            'custom_variables' => $this->custom_variables ?? [],
            'config' => $this->config_with_defaults,
            'usage_count' => $this->usage_count,
            'last_used' => $this->last_used?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // Campos calculados
            'variables_in_content' => $this->variables_in_content,
            'is_ready' => $this->is_ready,
            'usage_stats' => $this->usage_stats,
            
            // Información adicional para frontend
            'status_class' => $this->getStatusClass(),
            'category_icon' => $this->getCategoryIcon(),
            'language_flag' => $this->getLanguageFlag(),
            
            // Campos condicionales según el contexto
            'similar_prompts' => $this->when(
                $request->input('include_similar') === 'true',
                function() {
                    return $this->resource->findSimilar(3)->map(function($prompt) {
                        return [
                            'id' => $prompt->id,
                            'name' => $prompt->name,
                            'category' => $prompt->category
                        ];
                    });
                }
            ),
            
            // Solo para vista detallada
            'content_preview' => $this->when(
                $request->routeIs('*.index'),
                \Str::limit($this->content, 100)
            ),
            
            // Metadatos para formularios
            'available_variables' => $this->when(
                $request->routeIs('*.create') || $request->routeIs('*.edit'),
                \App\Models\Prompt::SYSTEM_VARIABLES
            )
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'categories' => \App\Models\Prompt::CATEGORIES,
                'statuses' => \App\Models\Prompt::STATUSES,
                'languages' => \App\Models\Prompt::LANGUAGES,
                'system_variables' => \App\Models\Prompt::SYSTEM_VARIABLES,
                'default_config' => \App\Models\Prompt::DEFAULT_CONFIG,
            ]
        ];
    }

    /**
     * Get CSS class for status badge
     */
    private function getStatusClass(): string
    {
        return match($this->status) {
            'active' => 'success',
            'draft' => 'warning',
            'archived' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get icon for category
     */
    private function getCategoryIcon(): string
    {
        return match($this->category) {
            'sentiment' => '😊',
            'extraction' => '📊',
            'translation' => '🌐',
            'classification' => '🏷️',
            'summary' => '📝',
            'custom' => '⚙️',
            default => '📄'
        };
    }

    /**
     * Get flag emoji for language
     */
    private function getLanguageFlag(): string
    {
        return match($this->language) {
            'es' => '🇪🇸',
            'en' => '🇺🇸',
            'fr' => '🇫🇷',
            'de' => '🇩🇪',
            'it' => '🇮🇹',
            'pt' => '🇵🇹',
            default => '🌐'
        };
    }
}