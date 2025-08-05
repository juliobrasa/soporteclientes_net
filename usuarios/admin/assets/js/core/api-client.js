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
        const url = this.buildUrl(endpoint);
        const controller = new AbortController();
        
        // Timeout
        const timeoutId = setTimeout(() => controller.abort(), config.timeout);
        
        try {
            const requestOptions = {
                method: config.method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...config.headers
                },
                signal: controller.signal
            };
            
            // Agregar datos según el método
            if (config.method === 'GET') {
                // Para GET, agregar datos como query parameters
                if (Object.keys(data).length > 0) {
                    const params = new URLSearchParams(data);
                    requestOptions.method = 'POST'; // Cambiar a POST con action en data
                    requestOptions.body = JSON.stringify({ action: endpoint, ...data });
                }
            } else {
                requestOptions.body = JSON.stringify({ action: endpoint, ...data });
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
     * Construye la URL completa
     */
    buildUrl(endpoint) {
        // Si ya es una URL completa, usarla tal como está
        if (endpoint.startsWith('http')) {
            return endpoint;
        }
        
        // Usar admin_api.php como endpoint por defecto
        const baseUrl = this.baseUrl || 'admin_api.php';
        
        // Si baseUrl no termina en .php, asumir que es un directorio
        if (!baseUrl.includes('.php')) {
            return `${baseUrl.replace(/\/$/, '')}/admin_api.php`;
        }
        
        return baseUrl;
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
    
    // Métodos específicos para endpoints del sistema
    
    // Hoteles
    async getHotels(filters = {}) {
        // Usar simulación mientras el backend no esté disponible
        if (AdminConfig?.debug?.simulateData !== false) {
            return this.simulateHotelsData(filters);
        }
        return this.call('getHotels', filters, { cache: true });
    }
    
    async saveHotel(hotelData) {
        // Usar simulación mientras el backend no esté disponible
        if (AdminConfig?.debug?.simulateData !== false) {
            return this.simulateSaveHotel(hotelData);
        }
        const result = await this.call('saveHotel', hotelData);
        if (result.success) {
            this.clearCache('getHotels');
        }
        return result;
    }
    
    async deleteHotel(hotelId) {
        // Usar simulación mientras el backend no esté disponible
        if (AdminConfig?.debug?.simulateData !== false) {
            return this.simulateDeleteHotel(hotelId);
        }
        const result = await this.call('deleteHotel', { id: hotelId });
        if (result.success) {
            this.clearCache('getHotels');
        }
        return result;
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
    
    // Proveedores IA
    async getAiProviders() {
        return this.call('getAiProviders', {}, { cache: true });
    }
    
    async toggleAiProvider(providerId, status) {
        const result = await this.call('toggleAiProvider', { id: providerId, status });
        if (result.success) {
            this.clearCache('getAiProviders');
        }
        return result;
    }
    
    // Logs
    async getLogs(filters = {}) {
        return this.call('getLogs', filters);
    }
    
    // Herramientas
    async getDbStats() {
        return this.call('getDbStats', {}, { cache: true });
    }
    
    async scanDuplicates() {
        return this.call('scanDuplicates');
    }
    
    async optimizeTables() {
        return this.call('optimizeTables');
    }
    
    // Métodos de simulación para desarrollo (temporal)
    async simulateHotelsData(params = {}) {
        // Simular delay de red
        await this.delay(500 + Math.random() * 1000);
        
        const mockHotels = [
            {
                id: 1,
                name: 'Hotel Paradise Beach',
                code: 'HPB001',
                description: 'Un hermoso resort frente al mar con todas las comodidades modernas.',
                status: 'active',
                priority: 'featured',
                category: 'resort',
                website: 'https://paradise-beach.com',
                contact_email: 'info@paradise-beach.com',
                phone: '+1 (555) 123-4567',
                total_rooms: 150,
                address: '123 Ocean Drive',
                city: 'Miami Beach',
                country: 'US',
                timezone: 'America/New_York',
                created_at: '2024-01-15 10:00:00',
                updated_at: '2024-01-20 15:30:00'
            },
            {
                id: 2,
                name: 'City Business Hotel',
                code: 'CBH002',
                description: 'Hotel moderno en el corazón de la ciudad, ideal para viajeros de negocios.',
                status: 'active',
                priority: 'high',
                category: 'business',
                website: 'https://citybusiness.com',
                contact_email: 'reservas@citybusiness.com',
                phone: '+1 (555) 987-6543',
                total_rooms: 75,
                address: '456 Business District Ave',
                city: 'Nueva York',
                country: 'US',
                timezone: 'America/New_York',
                created_at: '2024-01-10 09:15:00',
                updated_at: '2024-01-18 11:45:00'
            },
            {
                id: 3,
                name: 'Boutique Villa Madrid',
                code: 'BVM003',
                description: 'Villa boutique en el centro histórico de Madrid con encanto clásico.',
                status: 'active',
                priority: 'normal',
                category: 'boutique',
                website: 'https://villamadrid.es',
                contact_email: 'contacto@villamadrid.es',
                phone: '+34 91 123 4567',
                total_rooms: 25,
                address: 'Calle Gran Vía 123',
                city: 'Madrid',
                country: 'ES',
                timezone: 'Europe/Madrid',
                created_at: '2024-01-05 14:20:00',
                updated_at: '2024-01-19 16:10:00'
            },
            {
                id: 4,
                name: 'Mountain Lodge Retreat',
                code: 'MLR004',
                description: 'Refugio de montaña perfecto para escapadas relajantes.',
                status: 'maintenance',
                priority: 'normal',
                category: 'other',
                website: 'https://mountainlodge.com',
                contact_email: 'info@mountainlodge.com',
                phone: '+1 (555) 456-7890',
                total_rooms: 40,
                address: '789 Mountain View Road',
                city: 'Aspen',
                country: 'US',
                timezone: 'America/Denver',
                created_at: '2024-01-08 12:00:00',
                updated_at: '2024-01-17 10:30:00'
            },
            {
                id: 5,
                name: 'Economy Inn Central',
                code: 'EIC005',
                description: 'Alojamiento económico con excelente ubicación.',
                status: 'inactive',
                priority: 'normal',
                category: 'economy',
                website: 'https://economyinn.com',
                contact_email: 'reservas@economyinn.com',
                phone: '+1 (555) 321-0987',
                total_rooms: 60,
                address: '321 Central Street',
                city: 'Chicago',
                country: 'US',
                timezone: 'America/Chicago',
                created_at: '2024-01-12 16:45:00',
                updated_at: '2024-01-16 09:20:00'
            }
        ];
        
        // Aplicar filtros básicos
        let filteredHotels = [...mockHotels];
        
        if (params.search) {
            const searchTerm = params.search.toLowerCase();
            filteredHotels = filteredHotels.filter(hotel => 
                hotel.name.toLowerCase().includes(searchTerm) ||
                hotel.description.toLowerCase().includes(searchTerm)
            );
        }
        
        if (params.status) {
            filteredHotels = filteredHotels.filter(hotel => hotel.status === params.status);
        }
        
        // Aplicar ordenamiento
        if (params.sort) {
            const sortField = params.sort;
            const direction = params.direction === 'desc' ? -1 : 1;
            
            filteredHotels.sort((a, b) => {
                let aVal = a[sortField];
                let bVal = b[sortField];
                
                if (typeof aVal === 'string') {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                }
                
                if (aVal < bVal) return -1 * direction;
                if (aVal > bVal) return 1 * direction;
                return 0;
            });
        }
        
        // Aplicar paginación
        const page = parseInt(params.page) || 1;
        const limit = parseInt(params.limit) || 25;
        const startIndex = (page - 1) * limit;
        const endIndex = startIndex + limit;
        
        const paginatedHotels = filteredHotels.slice(startIndex, endIndex);
        
        return {
            success: true,
            data: paginatedHotels,
            total: filteredHotels.length,
            page: page,
            pages: Math.ceil(filteredHotels.length / limit),
            limit: limit
        };
    }
    
    async simulateSaveHotel(hotelData) {
        await this.delay(800 + Math.random() * 1200);
        
        // Simular validación de errores ocasionales
        if (Math.random() < 0.1) {
            return {
                success: false,
                error: 'Error de simulación: El nombre del hotel ya existe'
            };
        }
        
        return {
            success: true,
            data: {
                id: hotelData.id || Date.now(),
                ...hotelData,
                created_at: hotelData.id ? hotelData.created_at : new Date().toISOString(),
                updated_at: new Date().toISOString()
            },
            message: hotelData.id ? 'Hotel actualizado correctamente' : 'Hotel creado correctamente'
        };
    }
    
    async simulateDeleteHotel(hotelId) {
        await this.delay(600 + Math.random() * 800);
        
        // Simular errores ocasionales
        if (Math.random() < 0.05) {
            return {
                success: false,
                error: 'Error de simulación: No se puede eliminar el hotel'
            };
        }
        
        return {
            success: true,
            message: 'Hotel eliminado correctamente'
        };
    }
}

// Crear instancia global
window.apiClient = new AdminAPIClient();

// Función legacy para compatibilidad
window.apiCall = function(action, data = {}) {
    return window.apiClient.call(action, data);
};

// Log de inicialización
if (AdminConfig?.debug?.enabled) {
    console.log('🔌 API Client inicializado');
}

// Exportar para ES6 modules si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminAPIClient;
}