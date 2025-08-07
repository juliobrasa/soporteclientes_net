/**
 * ==========================================================================
 * ADMIN API CLIENT - Kavia Hoteles Panel de Administración
 * Cliente centralizado para todas las llamadas API
 * ==========================================================================
 */

class AdminAPIClient {
    constructor() {
        this.baseUrl = AdminConfig?.api?.baseUrl || '';
        this.timeout = AdminConfig?.api?.timeout || 30000;
        this.retries = AdminConfig?.api?.retries || 3;
        this.cache = new Map();
        this.activeRequests = new Map();
        
        // Bind methods
        this.call = this.call.bind(this);
        this.get = this.get.bind(this);
        this.post = this.post.bind(this);
        this.put = this.put.bind(this);
        this.delete = this.delete.bind(this);
    }
    
    /**
     * Realiza una llamada API con retry automático
     * @param {string} endpoint - Endpoint de la API
     * @param {Object} data - Datos a enviar
     * @param {Object} options - Opciones adicionales
     * @returns {Promise<Object>} Respuesta de la API
     */
    async call(endpoint, data = {}, options = {}) {
        const config = {
            method: 'POST',
            timeout: this.timeout,
            retries: this.retries,
            cache: false,
            ...options
        };
        
        // Verificar caché si está habilitado
        if (config.cache && config.method === 'GET') {
            const cached = this.getFromCache(endpoint, data);
            if (cached) {
                if (AdminConfig?.debug?.enabled) {
                    console.log(`📦 Cache hit para ${endpoint}:`, cached);
                }
                return cached;
            }
        }
        
        // Evitar llamadas duplicadas
        const requestKey = this.getRequestKey(endpoint, data, config.method);
        if (this.activeRequests.has(requestKey)) {
            if (AdminConfig?.debug?.enabled) {
                console.log(`⏳ Esperando request existente: ${endpoint}`);
            }
            return this.activeRequests.get(requestKey);
        }
        
        const requestPromise = this.executeRequest(endpoint, data, config);
        this.activeRequests.set(requestKey, requestPromise);
        
        try {
            const result = await requestPromise;
            
            // Guardar en caché si es necesario
            if (config.cache && config.method === 'GET' && result.success) {
                this.setToCache(endpoint, data, result);
            }
            
            return result;
        } finally {
            this.activeRequests.delete(requestKey);
        }
    }
    
    /**
     * Ejecuta la request con retry
     */
    async executeRequest(endpoint, data, config) {
        let lastError;
        
        for (let attempt = 1; attempt <= config.retries; attempt++) {
            try {
                if (AdminConfig?.debug?.enabled) {
                    console.log(`🌐 API Call (${attempt}/${config.retries}): ${endpoint}`, data);
                }
                
                const result = await this.makeHttpRequest(endpoint, data, config);
                
                if (AdminConfig?.debug?.enabled) {
                    console.log(`✅ API Success: ${endpoint}`, result);
                }
                
                return result;
                
            } catch (error) {
                lastError = error;
                
                if (AdminConfig?.debug?.enabled) {
                    console.warn(`❌ API Error (${attempt}/${config.retries}): ${endpoint}`, error);
                }
                
                // No reintentar en ciertos tipos de error
                if (this.shouldNotRetry(error)) {
                    break;
                }
                
                // Esperar antes del siguiente intento
                if (attempt < config.retries) {
                    await this.delay(1000 * attempt);
                }
            }
        }
        
        // Retornar error formateado
        return this.formatError(lastError, endpoint);
    }
    
    /**
     * Realiza la request HTTP real
     */
    async makeHttpRequest(endpoint, data, config) {
        const isLaravelEndpoint = this.isLaravelEndpoint(endpoint);
        const url = this.buildUrl(endpoint, config.method === 'GET' ? data : {}, config.method);
        const controller = new AbortController();
        
        // Timeout
        const timeoutId = setTimeout(() => controller.abort(), config.timeout);
        
        try {
            const requestOptions = {
                method: config.method,
                signal: controller.signal,
                headers: {}
            };
            
            if (isLaravelEndpoint) {
                // Configuración para Laravel API
                requestOptions.headers = {
                    ...AdminConfig.api.laravel.headers,
                    ...(config.headers || {})
                };
                
                // Para Laravel, siempre enviar JSON (excepto GET)
                if (config.method !== 'GET') {
                    requestOptions.body = JSON.stringify(data);
                }
                
            } else {
                // Configuración para admin_api.php legacy
                requestOptions.headers = {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...config.headers
                };
                
                // Agregar datos según el método para legacy
                if (config.method === 'GET') {
                    // Para GET simples sin parámetros, usar GET
                    if (Object.keys(data).length === 0) {
                        // No hay datos adicionales, solo el action como query param
                    } else {
                        // Si hay parámetros, cambiar a POST
                        requestOptions.method = 'POST';
                        requestOptions.body = JSON.stringify({ action: endpoint, ...data });
                    }
                } else {
                    requestOptions.body = JSON.stringify({ action: endpoint, ...data });
                }
            }
            
            const response = await fetch(url, requestOptions);
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            // Validar formato de respuesta
            if (typeof result !== 'object') {
                throw new Error('Respuesta inválida del servidor');
            }
            
            return result;
            
        } catch (error) {
            clearTimeout(timeoutId);
            
            if (error.name === 'AbortError') {
                throw new Error('Timeout: La operación tardó demasiado tiempo');
            }
            
            throw error;
        }
    }
    
    /**
     * Construye la URL completa - HÍBRIDO Laravel/Legacy
     */
    buildUrl(endpoint, data = {}, method = 'POST') {
        // Si ya es una URL completa, usarla tal como está
        if (endpoint.startsWith('http')) {
            return endpoint;
        }
        
        // Determinar si es endpoint de Laravel o legacy
        const isLaravelEndpoint = this.isLaravelEndpoint(endpoint);
        
        if (isLaravelEndpoint) {
            // Usar API de Laravel
            const baseUrl = this.baseUrl || 'public/api';
            let finalUrl = `${baseUrl.replace(/\/$/, '')}/${endpoint}`;
            
            // Para GET requests con parámetros, agregar query string
            if (method === 'GET' && Object.keys(data).length > 0) {
                const params = new URLSearchParams(data);
                finalUrl += `?${params}`;
            }
            
            return finalUrl;
        } else {
            // Usar admin_api.php legacy
            const baseUrl = 'admin_api.php';
            let finalUrl = baseUrl;
            
            // Para requests legacy, agregar action como query parameter
            if (Object.keys(data).length === 0) {
                finalUrl += `?action=${endpoint}`;
            }
            
            return finalUrl;
        }
    }
    
    /**
     * Determina si es un endpoint de Laravel o legacy
     */
    isLaravelEndpoint(endpoint) {
        const laravelModules = AdminConfig?.api?.laravel?.migrated || {};
        
        // Verificar por módulo
        if (endpoint.startsWith('hotels') && laravelModules.hotels) return true;
        if (endpoint.startsWith('ai-providers') && laravelModules.aiProviders) return true;
        if (endpoint.startsWith('prompts') && laravelModules.prompts) return true;
        if (endpoint.startsWith('external-apis') && laravelModules.externalApis) return true;
        
        // Endpoints específicos de Laravel
        const laravelEndpoints = [
            'hotels/', 'ai-providers/', 'prompts/', 'external-apis/',
            'hotels/stats', 'ai-providers/stats', 'prompts/stats', 'external-apis/stats'
        ];
        
        return laravelEndpoints.some(prefix => endpoint.startsWith(prefix));
    }
    
    /**
     * Genera clave única para request
     */
    getRequestKey(endpoint, data, method) {
        return `${method}:${endpoint}:${JSON.stringify(data)}`;
    }
    
    /**
     * Determina si no se debe reintentar
     */
    shouldNotRetry(error) {
        const noRetryErrors = [
            'Unauthorized',
            'Forbidden', 
            'Not Found',
            'Bad Request',
            'Validation Error'
        ];
        
        return noRetryErrors.some(errorType => 
            error.message.includes(errorType)
        );
    }
    
    /**
     * Formatea error para respuesta consistente
     */
    formatError(error, endpoint) {
        let errorMessage = AdminConfig?.messages?.error?.generic || 'Error desconocido';
        
        if (error.message.includes('Timeout')) {
            errorMessage = AdminConfig?.messages?.error?.timeout || 'Timeout';
        } else if (error.message.includes('Failed to fetch')) {
            errorMessage = AdminConfig?.messages?.error?.network || 'Error de red';
        } else if (error.message.includes('HTTP 500')) {
            errorMessage = AdminConfig?.messages?.error?.server || 'Error del servidor';
        } else if (error.message.includes('HTTP 404')) {
            errorMessage = AdminConfig?.messages?.error?.notFound || 'No encontrado';
        } else if (error.message.includes('HTTP 401')) {
            errorMessage = AdminConfig?.messages?.error?.unauthorized || 'No autorizado';
        } else {
            errorMessage = error.message;
        }
        
        return {
            success: false,
            error: errorMessage,
            details: error.message,
            endpoint: endpoint,
            timestamp: new Date().toISOString()
        };
    }
    
    /**
     * Utilidad para delay
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    // Métodos de conveniencia para HTTP
    async get(endpoint, params = {}, options = {}) {
        return this.call(endpoint, params, { ...options, method: 'GET' });
    }
    
    async post(endpoint, data = {}, options = {}) {
        return this.call(endpoint, data, { ...options, method: 'POST' });
    }
    
    async put(endpoint, data = {}, options = {}) {
        return this.call(endpoint, data, { ...options, method: 'PUT' });
    }
    
    async delete(endpoint, data = {}, options = {}) {
        return this.call(endpoint, data, { ...options, method: 'DELETE' });
    }
    
    // Gestión de caché
    getFromCache(endpoint, data) {
        if (!AdminConfig?.cache?.enabled) return null;
        
        const key = this.getCacheKey(endpoint, data);
        const cached = this.cache.get(key);
        
        if (!cached) return null;
        
        // Verificar TTL
        if (Date.now() - cached.timestamp > AdminConfig.cache.defaultTTL) {
            this.cache.delete(key);
            return null;
        }
        
        return cached.data;
    }
    
    setToCache(endpoint, data, result) {
        if (!AdminConfig?.cache?.enabled) return;
        
        const key = this.getCacheKey(endpoint, data);
        
        // Limpiar caché si está lleno
        if (this.cache.size >= AdminConfig.cache.maxSize) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
        
        this.cache.set(key, {
            data: result,
            timestamp: Date.now()
        });
    }
    
    getCacheKey(endpoint, data) {
        return `${endpoint}:${JSON.stringify(data)}`;
    }
    
    clearCache(pattern = null) {
        if (pattern) {
            for (const key of this.cache.keys()) {
                if (key.includes(pattern)) {
                    this.cache.delete(key);
                }
            }
        } else {
            this.cache.clear();
        }
        
        if (AdminConfig?.debug?.enabled) {
            console.log('🗑️ Cache cleared:', pattern || 'all');
        }
    }
    
    // ================================================================
    // MÉTODOS REST ESTÁNDAR PARA LARAVEL
    // ================================================================
    
    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        return this.call(endpoint, params, { method: 'GET', cache: true });
    }
    
    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        const result = await this.call(endpoint, data, { method: 'POST' });
        this.invalidateRelatedCache(endpoint);
        return result;
    }
    
    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        const result = await this.call(endpoint, data, { method: 'PUT' });
        this.invalidateRelatedCache(endpoint);
        return result;
    }
    
    /**
     * DELETE request
     */
    async delete(endpoint, data = {}) {
        const result = await this.call(endpoint, data, { method: 'DELETE' });
        this.invalidateRelatedCache(endpoint);
        return result;
    }
    
    /**
     * Invalida cache relacionado basado en el endpoint
     */
    invalidateRelatedCache(endpoint) {
        if (endpoint.includes('hotels')) {
            this.clearCache('hotels');
            this.clearCache('getHotels');
            this.clearCache('getExtractionHotels');
        } else if (endpoint.includes('ai-providers')) {
            this.clearCache('ai-providers');
            this.clearCache('getProviders');
        } else if (endpoint.includes('prompts')) {
            this.clearCache('prompts');
            this.clearCache('getPrompts');
        } else if (endpoint.includes('external-apis')) {
            this.clearCache('external-apis');
            this.clearCache('getApiProviders');
            this.clearCache('getExternalApis');
        }
    }
    
    // ================================================================
    // MÉTODOS ESPECÍFICOS PARA HOTELES - HÍBRIDO Laravel/Legacy
    // ================================================================
    
    async getHotels(filters = {}) {
        if (AdminConfig?.api?.laravel?.migrated?.hotels) {
            return this.get('hotels', filters);
        } else {
            return this.call('getHotels', filters, { cache: true });
        }
    }
    
    async saveHotel(hotelData) {
        if (AdminConfig?.api?.laravel?.migrated?.hotels) {
            if (hotelData.id) {
                return this.put(`hotels/${hotelData.id}`, hotelData);
            } else {
                return this.post('hotels', hotelData);
            }
        } else {
            const result = await this.call('saveHotel', hotelData);
            if (result.success) {
                this.clearCache('getHotels');
                this.clearCache('getExtractionHotels');
            }
            return result;
        }
    }
    
    async deleteHotel(hotelId) {
        if (AdminConfig?.api?.laravel?.migrated?.hotels) {
            return this.delete(`hotels/${hotelId}`);
        } else {
            const result = await this.call('deleteHotel', { id: hotelId });
            if (result.success) {
                this.clearCache('getHotels');
                this.clearCache('getExtractionHotels');
            }
            return result;
        }
    }
    
    async toggleHotelStatus(hotelId) {
        if (AdminConfig?.api?.laravel?.migrated?.hotels) {
            return this.post(`hotels/${hotelId}/toggle-status`);
        } else {
            const result = await this.call('toggleHotelStatus', { id: hotelId });
            if (result.success) {
                this.clearCache('getHotels');
            }
            return result;
        }
    }
    
    // API Providers
    async getApiProviders() {
        return this.call('getApiProviders', {}, { cache: true });
    }
    
    async saveApiProvider(providerData) {
        const result = await this.call('saveApiProvider', providerData);
        if (result.success) {
            this.clearCache('getApiProviders');
        }
        return result;
    }
    
    async testApiProvider(providerId) {
        return this.call('testApiProvider', { id: providerId });
    }
    
    async deleteApiProvider(providerId) {
        const result = await this.call('deleteApiProvider', { id: providerId });
        if (result.success) {
            this.clearCache('getApiProviders');
        }
        return result;
    }
    
    // ================================================================
    // MÉTODOS ESPECÍFICOS PARA AI PROVIDERS - HÍBRIDO Laravel/Legacy
    // ================================================================
    
    async getProviders() {
        if (AdminConfig?.api?.laravel?.migrated?.aiProviders) {
            return this.get('ai-providers');
        } else {
            return this.call('getProviders', {}, { cache: true });
        }
    }
    
    async getAiProviders() {
        return this.getProviders(); // Alias
    }
    
    async saveAiProvider(providerData) {
        if (AdminConfig?.api?.laravel?.migrated?.aiProviders) {
            if (providerData.id) {
                return this.put(`ai-providers/${providerData.id}`, providerData);
            } else {
                return this.post('ai-providers', providerData);
            }
        } else {
            const result = await this.call('saveAiProvider', providerData);
            if (result.success) {
                this.clearCache('getProviders');
            }
            return result;
        }
    }
    
    async toggleAiProvider(providerId, status) {
        if (AdminConfig?.api?.laravel?.migrated?.aiProviders) {
            return this.post(`ai-providers/${providerId}/toggle`);
        } else {
            const result = await this.call('toggleAiProvider', { id: providerId, active: status });
            if (result.success) {
                this.clearCache('getProviders');
            }
            return result;
        }
    }
    
    async testAiProvider(providerId, testData = {}) {
        if (AdminConfig?.api?.laravel?.migrated?.aiProviders) {
            return this.post(`ai-providers/${providerId}/test`, testData);
        } else {
            return this.call('testAiProvider', { id: providerId });
        }
    }
    
    async deleteAiProvider(providerId) {
        if (AdminConfig?.api?.laravel?.migrated?.aiProviders) {
            return this.delete(`ai-providers/${providerId}`);
        } else {
            const result = await this.call('deleteAiProvider', { id: providerId });
            if (result.success) {
                this.clearCache('getProviders');
            }
            return result;
        }
    }
    
    async getAiProvidersDefaults() {
        if (AdminConfig?.api?.laravel?.migrated?.aiProviders) {
            return this.get('ai-providers/defaults');
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    async getAiProvidersStats() {
        if (AdminConfig?.api?.laravel?.migrated?.aiProviders) {
            return this.get('ai-providers/stats');
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    // Extracción
    async getExtractionHotels() {
        return this.call('getExtractionHotels', {}, { cache: true });
    }
    
    async startExtraction(extractionData) {
        return this.call('startExtraction', extractionData);
    }
    
    async getApifyStatus() {
        return this.call('getApifyStatus', {}, { cache: true });
    }
    
    // ================================================================
    // MÉTODOS ESPECÍFICOS PARA PROMPTS - HÍBRIDO Laravel/Legacy
    // ================================================================
    
    async getPrompts(filters = {}) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.get('prompts', filters);
        } else {
            return this.call('getPrompts', {}, { cache: true });
        }
    }
    
    async savePrompt(promptData) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            if (promptData.id) {
                return this.put(`prompts/${promptData.id}`, promptData);
            } else {
                return this.post('prompts', promptData);
            }
        } else {
            const result = await this.call('savePrompt', promptData);
            if (result.success) {
                this.clearCache('getPrompts');
            }
            return result;
        }
    }
    
    async updatePrompt(promptData) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.put(`prompts/${promptData.id}`, promptData);
        } else {
            const result = await this.call('updatePrompt', promptData);
            if (result.success) {
                this.clearCache('getPrompts');
            }
            return result;
        }
    }
    
    async deletePrompt(promptId) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.delete(`prompts/${promptId}`);
        } else {
            const result = await this.call('deletePrompt', { id: promptId });
            if (result.success) {
                this.clearCache('getPrompts');
            }
            return result;
        }
    }
    
    async togglePrompt(promptId, status) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            // En Laravel no existe toggle para prompts, usar update status
            return this.put(`prompts/${promptId}`, { status: status ? 'active' : 'draft' });
        } else {
            const result = await this.call('togglePrompt', { id: promptId, active: status });
            if (result.success) {
                this.clearCache('getPrompts');
            }
            return result;
        }
    }
    
    async editPrompt(promptId) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.get(`prompts/${promptId}`);
        } else {
            return this.call('editPrompt', { id: promptId });
        }
    }
    
    async duplicatePrompt(promptId) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.post(`prompts/${promptId}/duplicate`);
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    async testPrompt(promptId, testData) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.post(`prompts/${promptId}/test`, testData);
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    async getPromptsStats() {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.get('prompts/stats');
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    async getPromptsTemplatesLibrary() {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.get('prompts/templates-library');
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    async importPromptTemplate(templateData) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.post('prompts/import-template', templateData);
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    async exportPrompts(filters = {}) {
        if (AdminConfig?.api?.laravel?.migrated?.prompts) {
            return this.get('prompts/export', filters);
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    // ================================================================
    // MÉTODOS ESPECÍFICOS PARA EXTERNAL APIS - HÍBRIDO Laravel/Legacy
    // ================================================================
    
    async getExternalApis(filters = {}) {
        if (AdminConfig?.api?.laravel?.migrated?.externalApis) {
            return this.get('external-apis', filters);
        } else {
            return this.call('getApiProviders', filters, { cache: true });
        }
    }
    
    async getApiProviders() {
        return this.getExternalApis(); // Alias para compatibilidad
    }
    
    async saveExternalApi(apiData) {
        if (AdminConfig?.api?.laravel?.migrated?.externalApis) {
            if (apiData.id) {
                return this.put(`external-apis/${apiData.id}`, apiData);
            } else {
                return this.post('external-apis', apiData);
            }
        } else {
            const result = await this.call('saveApiProvider', apiData);
            if (result.success) {
                this.clearCache('getApiProviders');
            }
            return result;
        }
    }
    
    async saveApiProvider(providerData) {
        return this.saveExternalApi(providerData); // Alias para compatibilidad
    }
    
    async deleteExternalApi(apiId) {
        if (AdminConfig?.api?.laravel?.migrated?.externalApis) {
            return this.delete(`external-apis/${apiId}`);
        } else {
            const result = await this.call('deleteApiProvider', { id: apiId });
            if (result.success) {
                this.clearCache('getApiProviders');
            }
            return result;
        }
    }
    
    async deleteApiProvider(providerId) {
        return this.deleteExternalApi(providerId); // Alias para compatibilidad
    }
    
    async testExternalApi(apiId, testData = {}) {
        if (AdminConfig?.api?.laravel?.migrated?.externalApis) {
            return this.post(`external-apis/${apiId}/test`, testData);
        } else {
            return this.call('testApiProvider', { id: apiId });
        }
    }
    
    async testApiProvider(providerId) {
        return this.testExternalApi(providerId); // Alias para compatibilidad
    }
    
    async toggleExternalApi(apiId, status) {
        if (AdminConfig?.api?.laravel?.migrated?.externalApis) {
            return this.post(`external-apis/${apiId}/toggle`);
        } else {
            const result = await this.call('toggleApiProvider', { id: apiId, active: status });
            if (result.success) {
                this.clearCache('getApiProviders');
            }
            return result;
        }
    }
    
    async getExternalApiDefaults() {
        if (AdminConfig?.api?.laravel?.migrated?.externalApis) {
            return this.get('external-apis/defaults');
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    async getExternalApiStats() {
        if (AdminConfig?.api?.laravel?.migrated?.externalApis) {
            return this.get('external-apis/stats');
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    async incrementExternalApiUsage(apiId) {
        if (AdminConfig?.api?.laravel?.migrated?.externalApis) {
            return this.post(`external-apis/${apiId}/usage`);
        } else {
            return { success: false, error: 'No disponible en versión legacy' };
        }
    }
    
    // Logs
    async getLogs(filters = {}) {
        return this.call('getLogs', filters);
    }
    
    async clearLogs() {
        return this.call('clearLogs');
    }
    
    // Herramientas
    async getDbStats() {
        return this.call('getDbStats', {}, { cache: true });
    }
    
    async scanDuplicateReviews() {
        return this.call('scanDuplicateReviews');
    }
    
    async deleteDuplicateReviews() {
        const result = await this.call('deleteDuplicateReviews');
        if (result.success) {
            this.clearCache('getDbStats');
        }
        return result;
    }
    
    async optimizeTables() {
        const result = await this.call('optimizeTables');
        if (result.success) {
            this.clearCache('getDbStats');
        }
        return result;
    }
    
    async checkIntegrity() {
        return this.call('checkIntegrity');
    }
    
    // API Providers (edición)
    async editApiProvider(providerId) {
        return this.call('editApiProvider', { id: providerId });
    }
    
    async updateApiProvider(providerData) {
        const result = await this.call('updateApiProvider', providerData);
        if (result.success) {
            this.clearCache('getApiProviders');
        }
        return result;
    }
    
    // Función mejorada para hacer peticiones con compatibilidad total
    async apiCall(action, data = {}) {
        try {
            // Para GET requests simples
            if (Object.keys(data).length === 0) {
                const response = await fetch(`admin_api.php?action=${action}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const text = await response.text();
                console.log('Response text:', text); // Debug
                
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    throw new Error('Respuesta del servidor no válida');
                }
            } else {
                // Para POST con JSON
                const response = await fetch('admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({action, ...data})
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const text = await response.text();
                console.log('Response text:', text);
                
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    throw new Error('Respuesta del servidor no válida');
                }
            }
            
        } catch (error) {
            console.error('Error en API call:', error);
            return {
                success: false, 
                error: error.message || 'Error de conexión'
            };
        }
    }
}

// Crear instancia global
window.apiClient = new AdminAPIClient();

// Función legacy para compatibilidad total
window.apiCall = function(action, data = {}) {
    return window.apiClient.apiCall(action, data);
};

// Log de inicialización
if (AdminConfig?.debug?.enabled) {
    console.log('🔌 API Client inicializado');
}

// Exportar para ES6 modules si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminAPIClient;
}