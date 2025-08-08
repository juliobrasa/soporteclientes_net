// Dashboard principal del panel de clientes Laravel
class ClientDashboard {
    constructor() {
        this.selectedHotelId = null;
        this.activeSection = 'resumen';
        this.dateRange = 30;
        this.isLoading = false;
        this.apiBaseUrl = window.dashboardConfig?.apiBaseUrl || '/api/client';
        this.csrfToken = window.dashboardConfig?.csrfToken || window.Laravel?.csrfToken;
        
        this.init();
    }
    
    init() {
        this.initializeIcons();
        this.bindEvents();
        this.loadInitialData();
    }
    
    initializeIcons() {
        // Inicializar iconos de Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    bindEvents() {
        // Eventos del selector de hotel
        const hotelSelector = document.getElementById('hotelSelector');
        if (hotelSelector) {
            this.selectedHotelId = hotelSelector.value;
            hotelSelector.addEventListener('change', (e) => {
                this.selectedHotelId = e.target.value;
                this.updateCurrentHotel();
                this.loadDashboardData();
                this.loadSectionData(this.activeSection);
            });
        }
        
        // Eventos del selector de rango de fechas
        const dateRangeSelector = document.getElementById('dateRange');
        if (dateRangeSelector) {
            dateRangeSelector.addEventListener('change', (e) => {
                this.dateRange = parseInt(e.target.value);
                this.loadDashboardData();
                this.loadSectionData(this.activeSection);
            });
        }
        
        // Eventos de los botones del menú
        document.querySelectorAll('.menu-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const section = e.currentTarget.getAttribute('data-section');
                this.switchSection(section);
            });
        });
        
        // Evento del botón de reporte
        const reportButton = document.getElementById('reportButton');
        if (reportButton) {
            reportButton.addEventListener('click', () => {
                this.generateReport();
            });
        }
    }
    
    switchSection(section) {
        // Actualizar estado activo
        this.activeSection = section;
        
        // Actualizar botones del menú
        document.querySelectorAll('.menu-button').forEach(btn => {
            btn.classList.remove('active', 'bg-cyan-500', 'text-white');
            btn.classList.add('text-gray-300');
        });
        
        const activeButton = document.querySelector(`[data-section="${section}"]`);
        if (activeButton) {
            activeButton.classList.remove('text-gray-300');
            activeButton.classList.add('active', 'bg-cyan-500', 'text-white');
        }
        
        // Mostrar/ocultar secciones
        document.querySelectorAll('.content-section').forEach(sectionEl => {
            sectionEl.style.display = 'none';
        });
        
        const activeSection = document.getElementById(`${section}-section`);
        if (activeSection) {
            activeSection.style.display = 'block';
        }
        
        // Cargar datos específicos de la sección
        this.loadSectionData(section);
    }
    
    updateCurrentHotel() {
        const currentHotelSpan = document.getElementById('current-hotel');
        const hotelSelector = document.getElementById('hotelSelector');
        
        if (currentHotelSpan && hotelSelector) {
            const selectedOption = hotelSelector.options[hotelSelector.selectedIndex];
            currentHotelSpan.textContent = selectedOption ? selectedOption.text : 'Cargando...';
        }
    }
    
    async loadInitialData() {
        if (!this.selectedHotelId) {
            const hotelSelector = document.getElementById('hotelSelector');
            if (hotelSelector && hotelSelector.options.length > 0) {
                this.selectedHotelId = hotelSelector.value;
            }
        }
        
        this.updateCurrentHotel();
        await this.loadDashboardData();
        await this.loadSectionData(this.activeSection);
    }
    
    async loadDashboardData() {
        if (!this.selectedHotelId) return;
        
        this.showLoading(true);
        
        try {
            const data = await this.fetchDashboardData();
            this.updateDashboardMetrics(data);
            this.updateDimensionsTable(data);
        } catch (error) {
            this.showError('Error cargando datos del dashboard', error);
        } finally {
            this.showLoading(false);
        }
    }
    
    async loadSectionData(section) {
        if (!this.selectedHotelId) return;
        
        switch (section) {
            case 'resumen':
                // Los datos del resumen ya se cargan en loadDashboardData
                break;
            case 'otas':
                await this.loadOTAsData();
                break;
            case 'reseñas':
                await this.loadReseñasData();
                break;
        }
    }
    
    async fetchDashboardData() {
        try {
            const response = await this.makeRequest('dashboard', {
                hotel_id: this.selectedHotelId,
                date_range: this.dateRange
            });
            
            return response.data;
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
            throw error;
        }
    }
    
    async makeRequest(endpoint, params = {}) {
        const url = new URL(`${this.apiBaseUrl}/${endpoint}`, window.location.origin);
        
        Object.keys(params).forEach(key => {
            url.searchParams.append(key, params[key]);
        });
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Error en la respuesta del servidor');
        }
        
        return result;
    }
    
    updateDashboardMetrics(data) {
        // Actualizar IRO
        this.updateCircularProgress('iro-progress', data.iro.score);
        const iroScore = document.getElementById('iro-score');
        if (iroScore) {
            iroScore.textContent = `${data.iro.score}%`;
        }
        
        // Actualizar estado IRO
        const iroStatus = document.getElementById('iro-status');
        if (iroStatus) {
            if (data.iro.score >= 80) {
                iroStatus.textContent = 'Excelente';
                iroStatus.className = 'text-sm text-green-600';
            } else if (data.iro.score >= 60) {
                iroStatus.textContent = 'Regular';
                iroStatus.className = 'text-sm text-yellow-600';
            } else {
                iroStatus.textContent = 'Necesita mejora';
                iroStatus.className = 'text-sm text-red-600';
            }
        }
        
        // Actualizar métricas IRO
        this.updateProgressBar('calificacion-bar', data.iro.calificacion.value);
        this.updateProgressBar('cobertura-bar', data.iro.cobertura.value);
        this.updateProgressBar('resenas-bar', data.iro.reseñas.value);
        
        this.updateValueSpan('calificacion-value', `${data.iro.calificacion.value}%`);
        this.updateValueSpan('cobertura-value', `${data.iro.cobertura.value}%`);
        this.updateValueSpan('resenas-value', `${data.iro.reseñas.value}%`);
        
        // Actualizar cambio IRO
        const iroChange = document.getElementById('iro-change');
        if (iroChange && data.iro.change !== undefined) {
            const changeText = data.iro.change >= 0 ? `+${data.iro.change}%` : `${data.iro.change}%`;
            const changeColor = data.iro.change >= 0 ? 'text-green-600' : 'text-red-600';
            iroChange.textContent = `${changeText} respecto al período anterior`;
            iroChange.className = `${changeColor} text-sm font-medium`;
        }
        
        // Actualizar índice semántico
        if (data.semantico) {
            this.updateCircularProgress('semantico-progress', data.semantico.score);
            const semanticoScore = document.getElementById('semantico-score');
            if (semanticoScore) {
                semanticoScore.textContent = `${data.semantico.score}%`;
            }
            
            const semanticoStatus = document.getElementById('semantico-status');
            if (semanticoStatus) {
                const statusColors = {
                    'good': 'text-green-600',
                    'regular': 'text-yellow-600',
                    'bad': 'text-red-600',
                    'unknown': 'text-gray-600'
                };
                
                const statusTexts = {
                    'good': 'Buen sentimiento',
                    'regular': 'Sentimiento mixto',
                    'bad': 'Necesita atención',
                    'unknown': 'Sin datos suficientes'
                };
                
                semanticoStatus.textContent = statusTexts[data.semantico.status] || 'Calculando...';
                semanticoStatus.className = `text-sm ${statusColors[data.semantico.status] || 'text-gray-600'}`;
            }
            
            // Mostrar mensaje de alerta si existe
            const semanticoAlert = document.getElementById('semantico-alert');
            const semanticoMessage = document.getElementById('semantico-message');
            if (semanticoAlert && semanticoMessage && data.semantico.message) {
                semanticoMessage.textContent = data.semantico.message;
                semanticoAlert.style.display = 'block';
            }
            
            // Actualizar cambio semántico
            const semanticoChange = document.getElementById('semantico-change');
            if (semanticoChange && data.semantico.change !== undefined) {
                const changeText = data.semantico.change >= 0 ? `+${data.semantico.change}%` : `${data.semantico.change}%`;
                const changeColor = data.semantico.change >= 0 ? 'text-green-600' : 'text-red-600';
                semanticoChange.textContent = `${changeText} respecto al período anterior`;
                semanticoChange.className = `${changeColor} text-sm font-medium`;
            }
        }
    }
    
    updateDimensionsTable(data) {
        // Esta función actualizaría la tabla de dimensiones con datos reales
        // Por ahora mantenemos los placeholders del HTML
        console.log('Datos para tabla de dimensiones:', data.stats);
    }
    
    updateCircularProgress(elementId, percentage) {
        const circle = document.getElementById(elementId);
        if (circle) {
            const radius = 52;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (percentage / 100) * circumference;
            circle.style.strokeDashoffset = offset;
        }
    }
    
    updateProgressBar(elementId, percentage) {
        const bar = document.getElementById(elementId);
        if (bar) {
            bar.style.width = `${Math.min(100, Math.max(0, percentage))}%`;
        }
    }
    
    updateValueSpan(elementId, value) {
        const span = document.getElementById(elementId);
        if (span) {
            span.textContent = value;
            span.classList.add('value-updated');
            setTimeout(() => span.classList.remove('value-updated'), 600);
        }
    }
    
    async loadOTAsData() {
        try {
            const response = await this.makeRequest('otas', {
                hotel_id: this.selectedHotelId,
                date_range: this.dateRange
            });
            
            this.renderOTAsTable(response.data);
        } catch (error) {
            this.showError('Error cargando datos de OTAs', error);
        }
    }
    
    renderOTAsTable(otasData) {
        const tableBody = document.getElementById('otas-table-body');
        if (!tableBody) return;
        
        if (!otasData || otasData.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-8 text-gray-500">
                        No hay datos de OTAs disponibles
                    </td>
                </tr>
            `;
            return;
        }
        
        tableBody.innerHTML = otasData.map(ota => `
            <tr class="border-b hover:bg-gray-50">
                <td class="py-3 text-sm">
                    <div class="flex items-center gap-3">
                        <div class="ota-logo ${ota.bgColor}">${ota.logo}</div>
                        <span class="font-medium">${ota.name}</span>
                    </div>
                </td>
                <td class="text-center py-3">
                    <div class="flex items-center justify-center gap-1">
                        <span class="text-sm font-medium">${ota.rating || '--'}</span>
                        <span class="text-gray-500 text-xs">${ota.rating ? '/5' : ''}</span>
                    </div>
                </td>
                <td class="text-center py-3">
                    <span class="text-sm font-medium">${ota.reviews || '--'}</span>
                </td>
                <td class="text-center py-3">
                    <div class="flex items-center justify-center gap-1">
                        <span class="text-sm font-medium">${ota.accumulated2025 || '--'}</span>
                        <span class="text-gray-500 text-xs">${ota.accumulated2025 ? '/5' : ''}</span>
                    </div>
                </td>
            </tr>
        `).join('');
    }
    
    async loadReseñasData() {
        const reviewsContainer = document.getElementById('reviews-container');
        const reviewsStats = document.getElementById('reviews-stats');
        
        if (!reviewsContainer) return;
        
        // Mostrar loading
        reviewsContainer.innerHTML = `
            <div class="reviews-loading">
                <div class="spinner border-2 border-gray-200 border-t-blue-600 rounded-full w-8 h-8 animate-spin"></div>
                <span class="ml-3">Cargando reseñas...</span>
            </div>
        `;
        
        try {
            // Cargar estadísticas y reseñas en paralelo
            const [statsResponse, reviewsResponse] = await Promise.all([
                this.makeRequest('stats', {
                    hotel_id: this.selectedHotelId,
                    date_range: this.dateRange
                }),
                this.makeRequest('reviews', {
                    hotel_id: this.selectedHotelId,
                    date_range: this.dateRange,
                    limit: 20
                })
            ]);
            
            this.renderReviewsStats(statsResponse.data, reviewsStats);
            this.renderReviews(reviewsResponse.data, reviewsContainer);
        } catch (error) {
            reviewsContainer.innerHTML = `
                <div class="text-center py-8 text-red-600">
                    Error cargando reseñas: ${error.message}
                </div>
            `;
        }
    }
    
    renderReviewsStats(stats, container) {
        if (!container) return;
        
        container.innerHTML = `
            <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-gray-900">${stats.total_reviews || 0}</div>
                <div class="text-sm text-gray-600">Total Reseñas</div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-gray-900">${stats.avg_rating || 0}</div>
                <div class="text-sm text-gray-600">Calificación Promedio</div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-gray-900">${stats.coverage_total || 0}%</div>
                <div class="text-sm text-gray-600">Cobertura</div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-gray-900">${stats.nps || 0}</div>
                <div class="text-sm text-gray-600">NPS</div>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-gray-900">${stats.cases_created || 0}</div>
                <div class="text-sm text-gray-600">Casos Creados</div>
            </div>
        `;
    }
    
    renderReviews(reviews, container) {
        if (!reviews || reviews.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    No hay reseñas disponibles para este período
                </div>
            `;
            return;
        }
        
        container.innerHTML = reviews.map(review => this.createReviewHTML(review)).join('');
        
        // Reinicializar iconos
        this.initializeIcons();
    }
    
    createReviewHTML(review) {
        const platformColors = {
            booking: 'bg-blue-700',
            google: 'bg-red-500', 
            tripadvisor: 'bg-green-600',
            expedia: 'bg-blue-600',
            despegar: 'bg-purple-600'
        };
        
        const platformNames = {
            booking: 'Booking.com',
            google: 'Google',
            tripadvisor: 'TripAdvisor',
            expedia: 'Expedia',
            despegar: 'Despegar'
        };
        
        const starsHTML = Array.from({length: 5}, (_, i) => 
            `<i data-lucide="star" class="w-4 h-4 ${i < review.rating ? 'text-yellow-400 fill-current' : 'text-gray-300'}"></i>`
        ).join('');
        
        return `
            <div class="review-card bg-white rounded-lg p-6 shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h4 class="font-semibold text-gray-900">${review.guest}</h4>
                            <span class="text-sm text-gray-600">${review.country}</span>
                            <span class="text-sm text-gray-600">${review.date}</span>
                            <span class="text-sm text-gray-600">${review.tripType}</span>
                        </div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs text-gray-500">ID: ${review.reviewId}</span>
                            <div class="platform-badge ${platformColors[review.platform] || 'bg-gray-500'} text-white px-2 py-1 rounded text-xs">
                                ${platformNames[review.platform] || review.platform}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="star-rating flex">
                                ${starsHTML}
                            </div>
                            <span class="text-sm font-medium">${review.rating} / 5</span>
                        </div>
                        <h5 class="font-medium text-gray-900 mb-3">${review.title || 'Sin título'}</h5>
                    </div>
                </div>

                ${review.positive ? `
                <div class="mb-3">
                    <span class="text-green-600 font-medium text-sm">(+) </span>
                    <span class="text-sm text-gray-700">${review.positive}</span>
                </div>
                ` : ''}

                ${review.negative ? `
                <div class="mb-4">
                    <span class="text-red-600 font-medium text-sm">(-) </span>
                    <span class="text-sm text-gray-700">${review.negative}</span>
                </div>
                ` : ''}

                ${!review.hasResponse ? `
                <div class="flex items-center gap-2 p-3 bg-yellow-50 rounded-lg mb-4">
                    <i data-lucide="alert-circle" class="text-yellow-600 w-4 h-4"></i>
                    <span class="text-sm text-yellow-800">No respondida</span>
                    <span class="text-xs text-yellow-600">responde para mejorar tu IRO</span>
                </div>
                ` : ''}

                <div class="review-actions flex gap-2 flex-wrap">
                    <button class="action-button px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50" 
                            onclick="clientDashboard.integrateOTA('${review.id}')">
                        Integrar OTA
                    </button>
                    ${review.negative ? `
                    <button class="action-button px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50"
                            onclick="clientDashboard.translateReview('${review.id}')">
                        Traducir
                    </button>
                    ` : ''}
                    <button class="action-button px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600 flex items-center gap-1"
                            onclick="clientDashboard.generateResponse('${review.id}')">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                        Generar respuesta
                    </button>
                    <button class="action-button px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50"
                            onclick="clientDashboard.createCase('${review.id}')">
                        Crear Caso
                    </button>
                </div>
            </div>
        `;
    }
    
    // Métodos para las acciones de reseñas
    integrateOTA(reviewId) {
        this.showNotification('Integrando con OTA...', 'info');
        console.log('Integrar OTA para reseña:', reviewId);
    }
    
    translateReview(reviewId) {
        this.showNotification('Traduciendo reseña...', 'info');
        console.log('Traducir reseña:', reviewId);
    }
    
    generateResponse(reviewId) {
        this.showNotification('Generando respuesta con IA...', 'info');
        console.log('Generar respuesta para reseña:', reviewId);
        
        // Simular generación de respuesta
        setTimeout(() => {
            this.showNotification('Respuesta generada exitosamente', 'success');
        }, 2000);
    }
    
    createCase(reviewId) {
        this.showNotification('Creando caso...', 'info');
        console.log('Crear caso para reseña:', reviewId);
    }
    
    generateReport() {
        this.showNotification('Generando reporte...', 'info');
        console.log('Generar reporte para hotel:', this.selectedHotelId, 'Período:', this.dateRange);
        
        // Simular generación de reporte
        setTimeout(() => {
            this.showNotification('Reporte generado exitosamente', 'success');
        }, 3000);
    }
    
    showLoading(show) {
        this.isLoading = show;
        const body = document.body;
        if (show) {
            body.classList.add('loading');
        } else {
            body.classList.remove('loading');
        }
    }
    
    showNotification(message, type = 'info') {
        const colors = {
            info: 'bg-blue-500',
            success: 'bg-green-500',
            warning: 'bg-yellow-500',
            error: 'bg-red-500'
        };
        
        const notification = document.createElement('div');
        notification.className = `notification ${colors[type]} text-white p-4 rounded-lg`;
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <p class="font-medium">${message}</p>
                </div>
                <button class="text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        `;
        
        const container = document.getElementById('notification-container') || document.body;
        container.appendChild(notification);
        
        // Inicializar iconos
        this.initializeIcons();
        
        // Auto-remove después de 5 segundos
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
    
    showError(message, error) {
        console.error(message, error);
        this.showNotification(`${message}: ${error.message || error}`, 'error');
    }
}

// Inicializar dashboard cuando se carga la página
let clientDashboard;
document.addEventListener('DOMContentLoaded', () => {
    clientDashboard = new ClientDashboard();
});

// Exponer dashboard globalmente para uso en onclick handlers
window.clientDashboard = clientDashboard;