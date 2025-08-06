/**
 * ==========================================================================
 * ADMIN CONFIG - Kavia Hoteles Panel de Administración
 * Configuración global del sistema
 * ==========================================================================
 */

// Configuración global del sistema
window.AdminConfig = {
    // Configuración de la API
    api: {
        baseUrl: 'admin_api.php',  // URL base para las llamadas API (ahora en el mismo directorio)
        endpoints: {
            // Hoteles
            getHotels: 'getHotels',
            saveHotel: 'saveHotel',
            deleteHotel: 'deleteHotel',
            
            // APIs/Proveedores
            getApiProviders: 'getApiProviders',
            saveApiProvider: 'saveApiProvider',
            deleteApiProvider: 'deleteApiProvider',
            testApiProvider: 'testApiProvider',
            
            // Proveedores IA
            getAiProviders: 'getAiProviders',
            saveAiProvider: 'saveAiProvider',
            toggleAiProvider: 'toggleAiProvider',
            testAiProvider: 'testAiProvider',
            
            // Extracción
            getExtractionHotels: 'getExtractionHotels',
            startExtraction: 'startExtraction',
            getExtractionStatus: 'getExtractionStatus',
            getApifyStatus: 'getApifyStatus',
            
            // Prompts
            getPrompts: 'getPrompts',
            savePrompt: 'savePrompt',
            deletePrompt: 'deletePrompt',
            togglePrompt: 'togglePrompt',
            
            // Logs
            getLogs: 'getLogs',
            clearLogs: 'clearLogs',
            
            // Herramientas
            getDbStats: 'getDbStats',
            scanDuplicates: 'scanDuplicates',
            deleteDuplicates: 'deleteDuplicates',
            optimizeTables: 'optimizeTables',
            checkIntegrity: 'checkIntegrity'
        },
        timeout: 30000, // 30 segundos
        retries: 3
    },
    
    // Configuración de UI
    ui: {
        // Duración de notificaciones (ms)
        notificationDuration: 5000,
        
        // Duración de animaciones (ms)
        animationDuration: 300,
        
        // Configuración de tablas
        tables: {
            defaultPageSize: 25,
            pageSizes: [10, 25, 50, 100],
            autoRefresh: false,
            refreshInterval: 30000 // 30 segundos
        },
        
        // Configuración de modales
        modals: {
            closeOnEscape: true,
            closeOnBackdrop: true,
            backdrop: true
        },
        
        // Temas y colores
        theme: {
            primary: '#6366f1',
            secondary: '#8b5cf6',
            success: '#10b981',
            danger: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        }
    },
    
    // Configuración de tabs
    tabs: {
        default: 'hotels',
        available: [
            'hotels',
            'apis', 
            'extraction',
            'ia',
            'prompts',
            'logs',
            'tools'
        ],
        labels: {
            hotels: 'Hoteles',
            apis: 'APIs',
            extraction: 'Extractor',
            ia: 'Proveedores IA',
            prompts: 'Prompts',
            logs: 'Logs',
            tools: 'Herramientas'
        },
        icons: {
            hotels: 'fas fa-hotel',
            apis: 'fas fa-plug',
            extraction: 'fas fa-download',
            ia: 'fas fa-robot',
            prompts: 'fas fa-file-alt',
            logs: 'fas fa-chart-line',
            tools: 'fas fa-tools'
        }
    },
    
    // Configuración de validación
    validation: {
        hotel: {
            name: {
                required: true,
                minLength: 2,
                maxLength: 100
            }
        },
        apiProvider: {
            name: {
                required: true,
                minLength: 2,
                maxLength: 50
            },
            provider_type: {
                required: true,
                options: ['openai', 'anthropic', 'google', 'azure', 'local']
            },
            api_key: {
                required: false,
                minLength: 10
            }
        },
        prompt: {
            title: {
                required: true,
                minLength: 3,
                maxLength: 100
            },
            content: {
                required: true,
                minLength: 10
            }
        }
    },
    
    // Configuración de formato de datos
    format: {
        date: {
            short: 'DD/MM/YYYY',
            long: 'DD/MM/YYYY HH:mm:ss',
            locale: 'es-ES'
        },
        number: {
            locale: 'es-ES',
            decimals: 2
        }
    },
    
    // Configuración de debug
    debug: {
        enabled: true, // Cambiar a false en producción
        logLevel: 'info', // 'error', 'warn', 'info', 'debug'
        logToConsole: true,
        logToServer: false,
        simulateData: true  // Activar simulación hasta que backend esté listo
    },
    
    // Mensajes del sistema
    messages: {
        loading: 'Cargando...',
        saving: 'Guardando...',
        deleting: 'Eliminando...',
        processing: 'Procesando...',
        
        success: {
            saved: 'Datos guardados correctamente',
            deleted: 'Elemento eliminado correctamente',
            updated: 'Datos actualizados correctamente'
        },
        
        error: {
            generic: 'Ha ocurrido un error inesperado',
            network: 'Error de conexión. Verifica tu conexión a internet',
            timeout: 'La operación ha tardado demasiado tiempo',
            validation: 'Por favor, corrige los errores en el formulario',
            unauthorized: 'No tienes permisos para realizar esta acción',
            notFound: 'El elemento solicitado no existe',
            server: 'Error del servidor. Inténtalo más tarde'
        },
        
        confirm: {
            delete: '¿Estás seguro de que quieres eliminar este elemento?',
            deleteMultiple: '¿Estás seguro de que quieres eliminar los elementos seleccionados?',
            clear: '¿Estás seguro de que quieres limpiar todos los datos?',
            reset: '¿Estás seguro de que quieres resetear la configuración?'
        }
    },
    
    // Configuración de cacheado
    cache: {
        enabled: true,
        defaultTTL: 300000, // 5 minutos
        maxSize: 100, // máximo 100 elementos en cache
        keys: {
            hotels: 'admin_hotels',
            apiProviders: 'admin_api_providers',
            aiProviders: 'admin_ai_providers',
            prompts: 'admin_prompts',
            logs: 'admin_logs',
            dbStats: 'admin_db_stats'
        }
    },
    
    // Configuración de extracción
    extraction: {
        batchSize: 10,
        maxRetries: 3,
        retryDelay: 1000, // 1 segundo
        statusCheckInterval: 5000, // 5 segundos
        platforms: {
            booking: {
                name: 'Booking.com',
                enabled: true,
                icon: 'fas fa-bed'
            },
            tripadvisor: {
                name: 'TripAdvisor',
                enabled: true,
                icon: 'fas fa-map-marker-alt'
            },
            google: {
                name: 'Google Reviews',
                enabled: true,
                icon: 'fab fa-google'
            }
        }
    },
    
    // Configuración de herramientas
    tools: {
        duplicates: {
            scanBatchSize: 1000,
            similarity: 0.8 // 80% de similitud
        },
        optimization: {
            autoVacuum: true,
            analyzeAfterOptimization: true
        }
    }
};

// Configuración de entorno
AdminConfig.env = {
    isDevelopment: AdminConfig.debug.enabled,
    isProduction: !AdminConfig.debug.enabled,
    version: '2.0.0',
    buildDate: new Date().toISOString()
};

// Utilidades de configuración
AdminConfig.utils = {
    /**
     * Obtiene una configuración por ruta usando notación de punto
     * @param {string} path - Ruta de la configuración (ej: 'ui.tables.defaultPageSize')
     * @param {*} defaultValue - Valor por defecto si no se encuentra
     * @returns {*} El valor de la configuración
     */
    get(path, defaultValue = null) {
        return path.split('.').reduce((obj, key) => {
            return (obj && obj[key] !== undefined) ? obj[key] : defaultValue;
        }, AdminConfig);
    },
    
    /**
     * Establece una configuración por ruta
     * @param {string} path - Ruta de la configuración
     * @param {*} value - Valor a establecer
     */
    set(path, value) {
        const keys = path.split('.');
        const lastKey = keys.pop();
        const target = keys.reduce((obj, key) => {
            if (!(key in obj)) obj[key] = {};
            return obj[key];
        }, AdminConfig);
        target[lastKey] = value;
    },
    
    /**
     * Valida una configuración
     * @param {string} section - Sección de configuración a validar
     * @returns {boolean} True si es válida
     */
    validate(section) {
        // Implementar validación según la sección
        switch(section) {
            case 'api':
                return AdminConfig.api.baseUrl !== undefined;
            case 'ui':
                return AdminConfig.ui.notificationDuration > 0;
            default:
                return true;
        }
    }
};

// Inicialización de configuración desde localStorage
AdminConfig.init = function() {
    // Cargar configuración personalizada desde localStorage
    const savedConfig = localStorage.getItem('admin_config');
    if (savedConfig) {
        try {
            const customConfig = JSON.parse(savedConfig);
            // Merge con configuración por defecto
            Object.assign(AdminConfig, customConfig);
        } catch (e) {
            console.warn('Error cargando configuración personalizada:', e);
        }
    }
    
    // Log de inicialización
    if (AdminConfig.debug.enabled) {
        console.log('🔧 AdminConfig inicializado:', AdminConfig);
    }
};

// Guardar configuración en localStorage
AdminConfig.save = function() {
    try {
        const configToSave = {
            ui: AdminConfig.ui,
            debug: AdminConfig.debug
        };
        localStorage.setItem('admin_config', JSON.stringify(configToSave));
        
        if (AdminConfig.debug.enabled) {
            console.log('💾 Configuración guardada');
        }
    } catch (e) {
        console.error('Error guardando configuración:', e);
    }
};

// Auto-inicialización
if (typeof window !== 'undefined') {
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', AdminConfig.init);
    } else {
        AdminConfig.init();
    }
    
    // Exportar globalmente para fácil acceso
    window.AdminConfig = AdminConfig;
}

// Exportar para módulos ES6 si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminConfig;
}