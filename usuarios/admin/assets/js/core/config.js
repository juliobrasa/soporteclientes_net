/**
 * ==========================================================================
 * ADMIN CONFIG - Kavia Hoteles Panel de Administración
 * Configuración global del sistema
 * ==========================================================================
 */

// Configuración global del sistema
window.AdminConfig = {
    // Configuración de la API - MIGRADO A LARAVEL
    api: {
        baseUrl: 'public/api',  // URL base para Laravel API
        endpoints: {
            // === HOTELES (MIGRADO A LARAVEL) ===
            hotels: {
                list: 'hotels',                        // GET /api/hotels
                create: 'hotels',                       // POST /api/hotels
                show: 'hotels/{id}',                    // GET /api/hotels/{id}
                update: 'hotels/{id}',                  // PUT /api/hotels/{id}
                delete: 'hotels/{id}',                  // DELETE /api/hotels/{id}
                toggleStatus: 'hotels/{id}/toggle-status', // POST /api/hotels/{id}/toggle-status
                stats: 'hotels/stats/summary'           // GET /api/hotels/stats/summary
            },
            
            // === AI PROVIDERS (MIGRADO A LARAVEL) ===
            aiProviders: {
                list: 'ai-providers',                   // GET /api/ai-providers
                create: 'ai-providers',                 // POST /api/ai-providers
                show: 'ai-providers/{id}',              // GET /api/ai-providers/{id}
                update: 'ai-providers/{id}',            // PUT /api/ai-providers/{id}
                delete: 'ai-providers/{id}',            // DELETE /api/ai-providers/{id}
                toggle: 'ai-providers/{id}/toggle',     // POST /api/ai-providers/{id}/toggle
                test: 'ai-providers/{id}/test',         // POST /api/ai-providers/{id}/test
                stats: 'ai-providers/stats',            // GET /api/ai-providers/stats
                defaults: 'ai-providers/defaults'       // GET /api/ai-providers/defaults
            },
            
            // === PROMPTS (MIGRADO A LARAVEL) ===
            prompts: {
                list: 'prompts',                        // GET /api/prompts
                create: 'prompts',                      // POST /api/prompts
                show: 'prompts/{id}',                   // GET /api/prompts/{id}
                update: 'prompts/{id}',                 // PUT /api/prompts/{id}
                delete: 'prompts/{id}',                 // DELETE /api/prompts/{id}
                duplicate: 'prompts/{id}/duplicate',    // POST /api/prompts/{id}/duplicate
                test: 'prompts/{id}/test',              // POST /api/prompts/{id}/test
                stats: 'prompts/stats',                 // GET /api/prompts/stats
                templates: 'prompts/templates-library', // GET /api/prompts/templates-library
                importTemplate: 'prompts/import-template', // POST /api/prompts/import-template
                export: 'prompts/export',               // GET /api/prompts/export
                recommended: 'prompts/recommended/{category}' // GET /api/prompts/recommended/{category}
            },
            
            // === EXTERNAL APIS (MIGRADO A LARAVEL) ===
            externalApis: {
                list: 'external-apis',                  // GET /api/external-apis
                create: 'external-apis',                // POST /api/external-apis
                show: 'external-apis/{id}',             // GET /api/external-apis/{id}
                update: 'external-apis/{id}',           // PUT /api/external-apis/{id}
                delete: 'external-apis/{id}',           // DELETE /api/external-apis/{id}
                toggle: 'external-apis/{id}/toggle',    // POST /api/external-apis/{id}/toggle
                test: 'external-apis/{id}/test',        // POST /api/external-apis/{id}/test
                usage: 'external-apis/{id}/usage',      // POST /api/external-apis/{id}/usage
                stats: 'external-apis/stats',           // GET /api/external-apis/stats
                defaults: 'external-apis/defaults'      // GET /api/external-apis/defaults
            },
            
            // === SYSTEM LOGS (MIGRADO A LARAVEL) ===
            systemLogs: {
                list: 'system-logs',                       // GET /api/system-logs
                create: 'system-logs',                      // POST /api/system-logs
                show: 'system-logs/{id}',                   // GET /api/system-logs/{id}
                delete: 'system-logs/{id}',                 // DELETE /api/system-logs/{id}
                resolve: 'system-logs/{id}/resolve',        // POST /api/system-logs/{id}/resolve
                stats: 'system-logs/stats',                 // GET /api/system-logs/stats
                timeline: 'system-logs/timeline',           // GET /api/system-logs/timeline
                config: 'system-logs/config',               // GET /api/system-logs/config
                export: 'system-logs/export',               // GET /api/system-logs/export
                cleanup: 'system-logs/cleanup'              // POST /api/system-logs/cleanup
            },
            
            // === EXTRACTION JOBS (MIGRADO A LARAVEL) ===
            extractionJobs: {
                list: 'extraction-jobs',                   // GET /api/extraction-jobs
                create: 'extraction-jobs',                 // POST /api/extraction-jobs
                show: 'extraction-jobs/{id}',              // GET /api/extraction-jobs/{id}
                update: 'extraction-jobs/{id}',            // PUT /api/extraction-jobs/{id}
                delete: 'extraction-jobs/{id}',            // DELETE /api/extraction-jobs/{id}
                start: 'extraction-jobs/{id}/start',       // POST /api/extraction-jobs/{id}/start
                pause: 'extraction-jobs/{id}/pause',       // POST /api/extraction-jobs/{id}/pause
                cancel: 'extraction-jobs/{id}/cancel',     // POST /api/extraction-jobs/{id}/cancel
                retry: 'extraction-jobs/{id}/retry',       // POST /api/extraction-jobs/{id}/retry
                clone: 'extraction-jobs/{id}/clone',       // POST /api/extraction-jobs/{id}/clone
                runs: 'extraction-jobs/{id}/runs',         // GET /api/extraction-jobs/{id}/runs
                logs: 'extraction-jobs/{id}/logs',         // GET /api/extraction-jobs/{id}/logs
                stats: 'extraction-jobs/stats',            // GET /api/extraction-jobs/stats
                hotels: 'extraction-jobs/hotels'           // GET /api/extraction-jobs/hotels
            },
            
            // === PENDIENTES DE MIGRAR (USAR admin_api.php TEMPORAL) ===
            
            // Extracción
            getExtractionHotels: 'getExtractionHotels',
            startExtraction: 'startExtraction',
            getExtractionStatus: 'getExtractionStatus',
            getApifyStatus: 'getApifyStatus',
            
            
            // === TOOLS (MIGRADO A LARAVEL) ===
            tools: {
                stats: 'tools/stats',                      // GET /api/tools/stats
                scanDuplicates: 'tools/duplicates',        // GET /api/tools/duplicates
                deleteDuplicates: 'tools/duplicates',      // DELETE /api/tools/duplicates
                optimizeTables: 'tools/optimize',          // POST /api/tools/optimize
                checkIntegrity: 'tools/integrity',         // GET /api/tools/integrity
                systemInfo: 'tools/system-info'            // GET /api/tools/system-info
            }
        },
        timeout: 30000, // 30 segundos
        retries: 3,
        
        // Configuración para Laravel
        laravel: {
            // Módulos ya migrados a Laravel (true = usar Laravel API, false = usar admin_api.php)
            migrated: {
                hotels: true,
                aiProviders: true,
                prompts: true,
                externalApis: true,     // ✅ MIGRADO 
                systemLogs: true,       // ✅ MIGRADO
                extractionJobs: true,   // ✅ MIGRADO
                extraction: true,       // ✅ MIGRADO (alias for extractionJobs)
                tools: true             // ✅ MIGRADO
            },
            
            // Headers para requests a Laravel
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
                // 'Authorization': 'Bearer {token}' // Para cuando implementemos auth
            }
        }
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