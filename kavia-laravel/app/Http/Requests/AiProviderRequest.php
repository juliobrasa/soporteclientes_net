<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\AiProvider;

class AiProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // TODO: Implementar autorización cuando se agregue autenticación
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2'
            ],
            'provider_type' => [
                'required',
                Rule::in(array_keys(AiProvider::PROVIDER_TYPES))
            ],
            'api_key' => [
                'nullable',
                'string',
                'min:10'
            ],
            'api_url' => [
                'nullable',
                'url',
                'max:500'
            ],
            'model_name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'parameters' => [
                'nullable',
                'array'
            ],
            'parameters.temperature' => [
                'nullable',
                'numeric',
                'between:0,2'
            ],
            'parameters.max_tokens' => [
                'nullable',
                'integer',
                'min:1',
                'max:32000'
            ],
            'parameters.max_output_tokens' => [
                'nullable',
                'integer',
                'min:1',
                'max:32000'
            ],
            'parameters.top_p' => [
                'nullable',
                'numeric',
                'between:0,1'
            ],
            'parameters.top_k' => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ],
            'parameters.frequency_penalty' => [
                'nullable',
                'numeric',
                'between:-2,2'
            ],
            'parameters.presence_penalty' => [
                'nullable',
                'numeric',
                'between:-2,2'
            ],
            'is_active' => [
                'sometimes',
                'boolean'
            ]
        ];

        // Validaciones específicas para actualización
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $providerId = $this->route('aiProvider')?->id ?? $this->route('ai_provider');
            
            // Nombre único (excepto el proveedor actual)
            $rules['name'][] = Rule::unique('ai_providers', 'name')->ignore($providerId);
        } else {
            // Para creación, nombre debe ser único
            $rules['name'][] = Rule::unique('ai_providers', 'name');
        }

        // Validaciones condicionales según el tipo de proveedor
        if ($this->input('provider_type')) {
            $providerType = $this->input('provider_type');
            
            switch ($providerType) {
                case 'openai':
                    $rules['api_key'][] = 'required_if:provider_type,openai';
                    $rules['model_name'][] = 'required_if:provider_type,openai';
                    break;
                    
                case 'claude':
                    $rules['api_key'][] = 'required_if:provider_type,claude';
                    $rules['model_name'][] = 'required_if:provider_type,claude';
                    break;
                    
                case 'deepseek':
                    $rules['api_key'][] = 'required_if:provider_type,deepseek';
                    $rules['model_name'][] = 'required_if:provider_type,deepseek';
                    break;
                    
                case 'gemini':
                    $rules['api_key'][] = 'required_if:provider_type,gemini';
                    $rules['model_name'][] = 'required_if:provider_type,gemini';
                    break;
                    
                case 'local':
                    $rules['api_url'][] = 'required_if:provider_type,local';
                    break;
            }
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'name.unique' => 'Ya existe un proveedor con este nombre.',
            'name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            
            'provider_type.required' => 'El tipo de proveedor es obligatorio.',
            'provider_type.in' => 'El tipo de proveedor seleccionado no es válido.',
            
            'api_key.required_if' => 'La clave API es obligatoria para este tipo de proveedor.',
            'api_key.min' => 'La clave API debe tener al menos 10 caracteres.',
            
            'api_url.url' => 'La URL del API debe ser una URL válida.',
            'api_url.required_if' => 'La URL del API es obligatoria para modelos locales.',
            'api_url.max' => 'La URL no puede superar los 500 caracteres.',
            
            'model_name.required_if' => 'El nombre del modelo es obligatorio para este tipo de proveedor.',
            'model_name.max' => 'El nombre del modelo no puede superar los 255 caracteres.',
            
            'parameters.array' => 'Los parámetros deben ser un objeto JSON válido.',
            
            'parameters.temperature.numeric' => 'La temperatura debe ser un número.',
            'parameters.temperature.between' => 'La temperatura debe estar entre 0 y 2.',
            
            'parameters.max_tokens.integer' => 'El máximo de tokens debe ser un número entero.',
            'parameters.max_tokens.min' => 'El máximo de tokens debe ser al menos 1.',
            'parameters.max_tokens.max' => 'El máximo de tokens no puede superar 32000.',
            
            'parameters.max_output_tokens.integer' => 'El máximo de tokens de salida debe ser un número entero.',
            'parameters.max_output_tokens.min' => 'El máximo de tokens de salida debe ser al menos 1.',
            'parameters.max_output_tokens.max' => 'El máximo de tokens de salida no puede superar 32000.',
            
            'parameters.top_p.numeric' => 'El valor top_p debe ser un número.',
            'parameters.top_p.between' => 'El valor top_p debe estar entre 0 y 1.',
            
            'parameters.top_k.integer' => 'El valor top_k debe ser un número entero.',
            'parameters.top_k.min' => 'El valor top_k debe ser al menos 1.',
            'parameters.top_k.max' => 'El valor top_k no puede superar 100.',
            
            'parameters.frequency_penalty.numeric' => 'La penalización de frecuencia debe ser un número.',
            'parameters.frequency_penalty.between' => 'La penalización de frecuencia debe estar entre -2 y 2.',
            
            'parameters.presence_penalty.numeric' => 'La penalización de presencia debe ser un número.',
            'parameters.presence_penalty.between' => 'La penalización de presencia debe estar entre -2 y 2.',
            
            'is_active.boolean' => 'El estado activo debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'provider_type' => 'tipo de proveedor',
            'api_key' => 'clave API',
            'api_url' => 'URL del API',
            'model_name' => 'nombre del modelo',
            'parameters' => 'parámetros',
            'parameters.temperature' => 'temperatura',
            'parameters.max_tokens' => 'máximo de tokens',
            'parameters.max_output_tokens' => 'máximo de tokens de salida',
            'parameters.top_p' => 'top_p',
            'parameters.top_k' => 'top_k',
            'parameters.frequency_penalty' => 'penalización de frecuencia',
            'parameters.presence_penalty' => 'penalización de presencia',
            'is_active' => 'estado activo'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir is_active a boolean si viene como string
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // Limpiar parámetros nulos o vacíos
        if ($this->has('parameters') && is_array($this->input('parameters'))) {
            $parameters = array_filter($this->input('parameters'), function ($value) {
                return $value !== null && $value !== '';
            });
            
            $this->merge(['parameters' => $parameters]);
        }

        // Establecer valores por defecto si no se proporcionan
        if ($this->input('provider_type') && !$this->has('api_url')) {
            $defaultUrl = AiProvider::DEFAULT_URLS[$this->input('provider_type')] ?? null;
            if ($defaultUrl) {
                $this->merge(['api_url' => $defaultUrl]);
            }
        }

        if ($this->input('provider_type') && !$this->has('model_name')) {
            $defaultModel = AiProvider::DEFAULT_MODELS[$this->input('provider_type')] ?? null;
            if ($defaultModel) {
                $this->merge(['model_name' => $defaultModel]);
            }
        }
    }
}