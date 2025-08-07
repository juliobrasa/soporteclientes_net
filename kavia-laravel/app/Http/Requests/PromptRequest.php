<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Prompt;

class PromptRequest extends FormRequest
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
                'min:3'
            ],
            'category' => [
                'required',
                Rule::in(array_keys(Prompt::CATEGORIES))
            ],
            'language' => [
                'required',
                Rule::in(array_keys(Prompt::LANGUAGES))
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'content' => [
                'required',
                'string',
                'min:10'
            ],
            'status' => [
                'sometimes',
                Rule::in(array_keys(Prompt::STATUSES))
            ],
            'version' => [
                'sometimes',
                'string',
                'max:20'
            ],
            'tags' => [
                'nullable',
                'array',
                'max:20'
            ],
            'tags.*' => [
                'string',
                'max:50'
            ],
            'custom_variables' => [
                'nullable',
                'array'
            ],
            'custom_variables.*.name' => [
                'required_with:custom_variables',
                'string',
                'max:100'
            ],
            'custom_variables.*.type' => [
                'required_with:custom_variables',
                'in:text,number,date,boolean'
            ],
            'custom_variables.*.description' => [
                'nullable',
                'string',
                'max:255'
            ],
            'custom_variables.*.required' => [
                'sometimes',
                'boolean'
            ],
            'custom_variables.*.default_value' => [
                'nullable',
                'string'
            ],
            'config' => [
                'nullable',
                'array'
            ],
            'config.temperature' => [
                'nullable',
                'numeric',
                'between:0,2'
            ],
            'config.max_tokens' => [
                'nullable',
                'integer',
                'min:1',
                'max:32000'
            ],
            'config.top_p' => [
                'nullable',
                'numeric',
                'between:0,1'
            ],
            'config.frequency_penalty' => [
                'nullable',
                'numeric',
                'between:-2,2'
            ],
            'config.retry_attempts' => [
                'nullable',
                'integer',
                'min:1',
                'max:5'
            ],
            'config.timeout_seconds' => [
                'nullable',
                'integer',
                'min:5',
                'max:300'
            ],
            'config.enable_content_filter' => [
                'sometimes',
                'boolean'
            ],
            'config.track_usage' => [
                'sometimes',
                'boolean'
            ]
        ];

        // Validaciones específicas para actualización
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $promptId = $this->route('prompt')?->id ?? $this->route('id');
            
            // Nombre único (excepto el prompt actual)
            $rules['name'][] = Rule::unique('prompts', 'name')->ignore($promptId);
        } else {
            // Para creación, nombre debe ser único
            $rules['name'][] = Rule::unique('prompts', 'name');
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del prompt es obligatorio.',
            'name.unique' => 'Ya existe un prompt con este nombre.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            
            'category.required' => 'La categoría es obligatoria.',
            'category.in' => 'La categoría seleccionada no es válida.',
            
            'language.required' => 'El idioma es obligatorio.',
            'language.in' => 'El idioma seleccionado no es válido.',
            
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            
            'content.required' => 'El contenido del prompt es obligatorio.',
            'content.min' => 'El contenido debe tener al menos 10 caracteres.',
            
            'status.in' => 'El estado seleccionado no es válido.',
            
            'version.max' => 'La versión no puede superar los 20 caracteres.',
            
            'tags.array' => 'Las etiquetas deben ser un array.',
            'tags.max' => 'No puede haber más de 20 etiquetas.',
            'tags.*.string' => 'Cada etiqueta debe ser texto.',
            'tags.*.max' => 'Cada etiqueta no puede superar los 50 caracteres.',
            
            'custom_variables.array' => 'Las variables personalizadas deben ser un array.',
            'custom_variables.*.name.required_with' => 'El nombre de la variable es obligatorio.',
            'custom_variables.*.name.max' => 'El nombre de la variable no puede superar los 100 caracteres.',
            'custom_variables.*.type.required_with' => 'El tipo de variable es obligatorio.',
            'custom_variables.*.type.in' => 'El tipo de variable debe ser: text, number, date o boolean.',
            'custom_variables.*.description.max' => 'La descripción de la variable no puede superar los 255 caracteres.',
            
            'config.array' => 'La configuración debe ser un objeto.',
            'config.temperature.numeric' => 'La temperatura debe ser un número.',
            'config.temperature.between' => 'La temperatura debe estar entre 0 y 2.',
            'config.max_tokens.integer' => 'El máximo de tokens debe ser un número entero.',
            'config.max_tokens.min' => 'El máximo de tokens debe ser al menos 1.',
            'config.max_tokens.max' => 'El máximo de tokens no puede superar 32000.',
            'config.top_p.numeric' => 'El valor top_p debe ser un número.',
            'config.top_p.between' => 'El valor top_p debe estar entre 0 y 1.',
            'config.frequency_penalty.numeric' => 'La penalización de frecuencia debe ser un número.',
            'config.frequency_penalty.between' => 'La penalización de frecuencia debe estar entre -2 y 2.',
            'config.retry_attempts.integer' => 'Los reintentos deben ser un número entero.',
            'config.retry_attempts.min' => 'Los reintentos deben ser al menos 1.',
            'config.retry_attempts.max' => 'Los reintentos no pueden superar 5.',
            'config.timeout_seconds.integer' => 'El timeout debe ser un número entero.',
            'config.timeout_seconds.min' => 'El timeout debe ser al menos 5 segundos.',
            'config.timeout_seconds.max' => 'El timeout no puede superar 300 segundos.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'category' => 'categoría',
            'language' => 'idioma',
            'description' => 'descripción',
            'content' => 'contenido',
            'status' => 'estado',
            'version' => 'versión',
            'tags' => 'etiquetas',
            'custom_variables' => 'variables personalizadas',
            'config' => 'configuración'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar y normalizar tags
        if ($this->has('tags') && is_array($this->input('tags'))) {
            $tags = array_filter(array_map('trim', $this->input('tags')), function($tag) {
                return !empty($tag);
            });
            $this->merge(['tags' => array_unique($tags)]);
        }

        // Normalizar configuración
        if ($this->has('config') && is_array($this->input('config'))) {
            $config = array_filter($this->input('config'), function($value) {
                return $value !== null && $value !== '';
            });
            $this->merge(['config' => $config]);
        }
        
        // Establecer valores por defecto
        if (!$this->has('status')) {
            $this->merge(['status' => 'draft']);
        }
        
        if (!$this->has('version')) {
            $this->merge(['version' => '1.0']);
        }
        
        if (!$this->has('language')) {
            $this->merge(['language' => 'es']);
        }
    }
}