<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use App\Models\AiProvider;
use App\Http\Requests\PromptRequest;
use App\Http\Resources\PromptResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PromptController extends Controller
{
    /**
     * Lista todos los prompts con filtros y paginación
     * GET /api/prompts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Prompt::query();

            // Filtro por estado
            if ($request->has('status')) {
                $query->byStatus($request->input('status'));
            }

            // Filtro por categoría
            if ($request->has('category')) {
                $query->byCategory($request->input('category'));
            }

            // Filtro por idioma
            if ($request->has('language')) {
                $query->byLanguage($request->input('language'));
            }

            // Búsqueda full-text
            if ($request->has('search')) {
                $query->search($request->input('search'));
            }

            // Ordenamiento
            $sortBy = $request->input('sort_by', 'updated_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);

            // Paginación
            $perPage = min($request->input('per_page', 20), 100);
            $prompts = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'prompts' => PromptResource::collection($prompts->items()),
                'pagination' => [
                    'current_page' => $prompts->currentPage(),
                    'last_page' => $prompts->lastPage(),
                    'per_page' => $prompts->perPage(),
                    'total' => $prompts->total(),
                    'has_more' => $prompts->hasMorePages()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo prompts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener prompts',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Crear nuevo prompt
     * POST /api/prompts
     */
    public function store(PromptRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Crear prompt con configuración por defecto
            $prompt = Prompt::createWithDefaults($data);

            return response()->json([
                'success' => true,
                'message' => 'Prompt creado exitosamente',
                'prompt' => new PromptResource($prompt)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creando prompt: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al crear prompt',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Mostrar prompt específico
     * GET /api/prompts/{id}
     */
    public function show(Prompt $prompt): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'prompt' => new PromptResource($prompt)
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo prompt: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener prompt',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Actualizar prompt
     * PUT /api/prompts/{id}
     */
    public function update(PromptRequest $request, Prompt $prompt): JsonResponse
    {
        try {
            $data = $request->validated();
            
            $prompt->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Prompt actualizado exitosamente',
                'prompt' => new PromptResource($prompt->fresh())
            ]);

        } catch (\Exception $e) {
            Log::error('Error actualizando prompt: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar prompt',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Eliminar prompt
     * DELETE /api/prompts/{id}
     */
    public function destroy(Prompt $prompt): JsonResponse
    {
        try {
            $promptName = $prompt->name;
            $prompt->delete();

            return response()->json([
                'success' => true,
                'message' => "Prompt '{$promptName}' eliminado exitosamente"
            ]);

        } catch (\Exception $e) {
            Log::error('Error eliminando prompt: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar prompt',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Duplicar prompt
     * POST /api/prompts/{id}/duplicate
     */
    public function duplicate(Prompt $prompt): JsonResponse
    {
        try {
            $duplicate = $prompt->duplicate();

            return response()->json([
                'success' => true,
                'message' => "Prompt duplicado exitosamente como '{$duplicate->name}'",
                'prompt' => new PromptResource($duplicate)
            ]);

        } catch (\Exception $e) {
            Log::error('Error duplicando prompt: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al duplicar prompt',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas del módulo
     * GET /api/prompts/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_prompts' => Prompt::count(),
                'active_prompts' => Prompt::active()->count(),
                'draft_prompts' => Prompt::byStatus('draft')->count(),
                'archived_prompts' => Prompt::byStatus('archived')->count(),
                'total_usage' => Prompt::sum('usage_count'),
                'unique_languages' => Prompt::distinct('language')->count(),
                'categories' => Prompt::getCategoryStats(),
                'most_used' => Prompt::mostUsed(5)->get(['name', 'usage_count', 'category']),
                'recent' => Prompt::recent(7)->count()
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener estadísticas',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Probar prompt con IA
     * POST /api/prompts/{id}/test
     */
    public function testPrompt(Request $request, Prompt $prompt): JsonResponse
    {
        try {
            // Validar request
            $request->validate([
                'provider_id' => 'required|exists:ai_providers,id',
                'variables' => 'required|array',
                'test_content' => 'nullable|string'
            ]);

            // Obtener proveedor de IA
            $provider = AiProvider::findOrFail($request->provider_id);
            
            if (!$provider->is_active) {
                return response()->json([
                    'success' => false,
                    'error' => 'Proveedor de IA inactivo',
                    'message' => 'El proveedor seleccionado no está activo'
                ], 400);
            }

            // Validar variables requeridas
            $validationErrors = $prompt->validateVariables($request->variables);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Variables faltantes',
                    'message' => implode(', ', $validationErrors)
                ], 400);
            }

            // Reemplazar variables en el prompt
            $processedContent = $prompt->replaceVariables($request->variables);

            // Ejecutar prueba con IA
            $testResult = $this->executePromptTest($provider, $processedContent, $prompt->config_with_defaults);

            // Incrementar contador si la prueba fue exitosa
            if ($testResult['success']) {
                $prompt->incrementUsage();
            }

            return response()->json([
                'success' => $testResult['success'],
                'result' => $testResult,
                'processed_prompt' => $processedContent,
                'provider_used' => $provider->name,
                'variables_replaced' => count($request->variables)
            ]);

        } catch (\Exception $e) {
            Log::error('Error probando prompt: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al probar prompt',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener biblioteca de templates
     * GET /api/prompts/templates-library
     */
    public function getTemplatesLibrary(): JsonResponse
    {
        try {
            // Templates predefinidos por categoría
            $templates = [
                'sentiment' => [
                    [
                        'name' => 'Análisis de Sentimiento Básico',
                        'description' => 'Analiza el sentimiento general de una reseña',
                        'content' => 'Analiza el sentimiento de la siguiente reseña de hotel:\n\n"{review_text}"\n\nPor favor, proporciona:\n1. Sentimiento general (Positivo/Neutral/Negativo)\n2. Puntuación de 1-10\n3. Aspectos positivos mencionados\n4. Aspectos negativos mencionados\n5. Resumen en una frase',
                        'variables' => ['review_text'],
                        'language' => 'es'
                    ],
                    [
                        'name' => 'Análisis de Emociones Detallado',
                        'description' => 'Identifica emociones específicas en la reseña',
                        'content' => 'Analiza las emociones presentes en esta reseña del hotel "{hotel_name}":\n\n"{review_text}"\n\nIdentifica:\n1. Emociones principales (alegría, frustración, sorpresa, etc.)\n2. Intensidad emocional (1-10)\n3. Palabras clave que indican cada emoción\n4. Recomendación de respuesta apropiada',
                        'variables' => ['hotel_name', 'review_text'],
                        'language' => 'es'
                    ]
                ],
                'extraction' => [
                    [
                        'name' => 'Extracción de Datos Estructurados',
                        'description' => 'Extrae información estructurada de una reseña',
                        'content' => 'Extrae información estructurada de esta reseña:\n\n"{review_text}"\n\nFormato JSON con:\n{\n  "aspectos_mencionados": [],\n  "problemas_reportados": [],\n  "elogios_recibidos": [],\n  "servicios_utilizados": [],\n  "fecha_estadia_estimada": "",\n  "tipo_viajero": "",\n  "puntuacion_estimada": 0\n}',
                        'variables' => ['review_text'],
                        'language' => 'es'
                    ]
                ],
                'summary' => [
                    [
                        'name' => 'Resumen Ejecutivo',
                        'description' => 'Crea un resumen conciso de múltiples reseñas',
                        'content' => 'Crea un resumen ejecutivo de estas reseñas del hotel "{hotel_name}":\n\n{review_text}\n\nIncluye:\n1. Tendencias generales\n2. Aspectos más elogiados\n3. Problemas recurrentes\n4. Recomendaciones de mejora\n5. Puntuación promedio percibida',
                        'variables' => ['hotel_name', 'review_text'],
                        'language' => 'es'
                    ]
                ],
                'translation' => [
                    [
                        'name' => 'Traducción de Reseña',
                        'description' => 'Traduce una reseña manteniendo el tono y contexto',
                        'content' => 'Traduce la siguiente reseña de hotel al {target_language}, manteniendo el tono emocional y el contexto:\n\n"{review_text}"\n\nAsegúrate de:\n1. Mantener el sentimiento original\n2. Usar terminología hotelera apropiada\n3. Preservar nombres propios\n4. Adaptar expresiones culturales si es necesario',
                        'variables' => ['review_text', 'target_language'],
                        'language' => 'es'
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'templates' => $templates,
                'total_templates' => array_sum(array_map('count', $templates)),
                'categories_available' => array_keys($templates)
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo biblioteca de templates: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener biblioteca de templates',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Importar template como prompt
     * POST /api/prompts/import-template
     */
    public function importTemplate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'template' => 'required|array',
                'template.name' => 'required|string|max:255',
                'template.description' => 'nullable|string',
                'template.content' => 'required|string',
                'template.variables' => 'required|array',
                'template.language' => 'required|string|in:es,en,fr,de,it,pt',
                'category' => 'required|string|in:sentiment,extraction,translation,classification,summary,custom'
            ]);

            $template = $request->template;
            
            // Crear prompt desde template
            $prompt = Prompt::create([
                'name' => $template['name'],
                'description' => $template['description'] ?? null,
                'content' => $template['content'],
                'category' => $request->category,
                'language' => $template['language'],
                'status' => 'draft',
                'tags' => ['template', 'importado'],
                'custom_variables' => array_map(function($var) {
                    return [
                        'name' => $var,
                        'type' => 'text',
                        'required' => true,
                        'description' => "Variable: {$var}"
                    ];
                }, $template['variables'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template importado exitosamente como prompt',
                'prompt' => new PromptResource($prompt)
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error importando template: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al importar template',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Exportar prompts
     * GET /api/prompts/export
     */
    public function exportPrompts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'category' => 'nullable|string',
                'status' => 'nullable|string',
                'language' => 'nullable|string'
            ]);

            $query = Prompt::query();

            // Aplicar filtros si se proporcionan
            if ($request->category) {
                $query->byCategory($request->category);
            }
            if ($request->status) {
                $query->byStatus($request->status);
            }
            if ($request->language) {
                $query->byLanguage($request->language);
            }

            $prompts = $query->get();

            $exportData = [
                'export_info' => [
                    'generated_at' => now()->toISOString(),
                    'total_prompts' => $prompts->count(),
                    'filters_applied' => array_filter([
                        'category' => $request->category,
                        'status' => $request->status,
                        'language' => $request->language
                    ]),
                    'version' => '1.0'
                ],
                'prompts' => $prompts->map(function ($prompt) {
                    return [
                        'name' => $prompt->name,
                        'category' => $prompt->category,
                        'language' => $prompt->language,
                        'description' => $prompt->description,
                        'content' => $prompt->content,
                        'status' => $prompt->status,
                        'version' => $prompt->version,
                        'tags' => $prompt->tags,
                        'custom_variables' => $prompt->custom_variables,
                        'config' => $prompt->config,
                        'usage_stats' => $prompt->usage_stats
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'export_data' => $exportData
            ]);

        } catch (\Exception $e) {
            Log::error('Error exportando prompts: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al exportar prompts',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener prompts recomendados por categoría
     * GET /api/prompts/recommended/{category}
     */
    public function getRecommended(Request $request, string $category): JsonResponse
    {
        try {
            $limit = min($request->input('limit', 5), 20);
            $prompts = Prompt::getRecommended($category, $limit);

            return response()->json([
                'success' => true,
                'recommended_prompts' => PromptResource::collection($prompts),
                'category' => $category,
                'total_found' => $prompts->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo prompts recomendados: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener prompts recomendados',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    // ================================================================
    // MÉTODOS PRIVADOS
    // ================================================================

    /**
     * Ejecutar prueba de prompt con proveedor de IA
     */
    private function executePromptTest(AiProvider $provider, string $content, array $config): array
    {
        try {
            $apiConfig = $provider->getApiConfiguration();
            
            switch ($provider->provider_type) {
                case 'openai':
                    return $this->testWithOpenAI($apiConfig, $content, $config);
                case 'claude':
                    return $this->testWithClaude($apiConfig, $content, $config);
                case 'deepseek':
                    return $this->testWithDeepSeek($apiConfig, $content, $config);
                case 'gemini':
                    return $this->testWithGemini($apiConfig, $content, $config);
                default:
                    return [
                        'success' => false,
                        'message' => 'Proveedor no soportado para testing',
                        'provider_type' => $provider->provider_type
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error ejecutando prueba: ' . $e->getMessage(),
                'error_type' => 'execution_error'
            ];
        }
    }

    /**
     * Probar con OpenAI
     */
    private function testWithOpenAI(array $config, string $content, array $promptConfig): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
            'Content-Type' => 'application/json'
        ])->timeout($promptConfig['timeout_seconds'] ?? 30)
          ->post($config['api_url'] . '/chat/completions', [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'user', 'content' => $content]
            ],
            'temperature' => $promptConfig['temperature'] ?? 0.7,
            'max_tokens' => $promptConfig['max_tokens'] ?? 1000,
            'top_p' => $promptConfig['top_p'] ?? 0.9,
            'frequency_penalty' => $promptConfig['frequency_penalty'] ?? 0
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'model_used' => $config['model'],
                'response_time' => $response->transferStats?->getTransferTime()
            ];
        }

        return [
            'success' => false,
            'message' => 'Error de OpenAI: ' . $response->body(),
            'status_code' => $response->status()
        ];
    }

    /**
     * Probar con Claude
     */
    private function testWithClaude(array $config, string $content, array $promptConfig): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $config['api_key'],
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01'
        ])->timeout($promptConfig['timeout_seconds'] ?? 30)
          ->post($config['api_url'] . '/messages', [
            'model' => $config['model'],
            'max_tokens' => $promptConfig['max_tokens'] ?? 1000,
            'temperature' => $promptConfig['temperature'] ?? 0.7,
            'messages' => [
                ['role' => 'user', 'content' => $content]
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'response' => $data['content'][0]['text'] ?? '',
                'tokens_used' => $data['usage']['output_tokens'] ?? 0,
                'model_used' => $config['model'],
                'response_time' => $response->transferStats?->getTransferTime()
            ];
        }

        return [
            'success' => false,
            'message' => 'Error de Claude: ' . $response->body(),
            'status_code' => $response->status()
        ];
    }

    /**
     * Probar con DeepSeek
     */
    private function testWithDeepSeek(array $config, string $content, array $promptConfig): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['api_key'],
            'Content-Type' => 'application/json'
        ])->timeout($promptConfig['timeout_seconds'] ?? 30)
          ->post($config['api_url'] . '/chat/completions', [
            'model' => $config['model'],
            'messages' => [
                ['role' => 'user', 'content' => $content]
            ],
            'temperature' => $promptConfig['temperature'] ?? 0.7,
            'max_tokens' => $promptConfig['max_tokens'] ?? 1000
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'response' => $data['choices'][0]['message']['content'] ?? '',
                'tokens_used' => $data['usage']['total_tokens'] ?? 0,
                'model_used' => $config['model'],
                'response_time' => $response->transferStats?->getTransferTime()
            ];
        }

        return [
            'success' => false,
            'message' => 'Error de DeepSeek: ' . $response->body(),
            'status_code' => $response->status()
        ];
    }

    /**
     * Probar con Gemini
     */
    private function testWithGemini(array $config, string $content, array $promptConfig): array
    {
        $response = Http::timeout($promptConfig['timeout_seconds'] ?? 30)
                       ->post($config['api_url'] . '/models/' . $config['model'] . ':generateContent', [
            'contents' => [
                ['parts' => [['text' => $content]]]
            ],
            'generationConfig' => [
                'temperature' => $promptConfig['temperature'] ?? 0.7,
                'maxOutputTokens' => $promptConfig['max_tokens'] ?? 1000,
                'topP' => $promptConfig['top_p'] ?? 0.9
            ]
        ], [
            'key' => $config['api_key']
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'response' => $data['candidates'][0]['content']['parts'][0]['text'] ?? '',
                'model_used' => $config['model'],
                'response_time' => $response->transferStats?->getTransferTime()
            ];
        }

        return [
            'success' => false,
            'message' => 'Error de Gemini: ' . $response->body(),
            'status_code' => $response->status()
        ];
    }
}