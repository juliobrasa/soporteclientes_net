// Dashboard principal del panel de clientes
class HotelDashboard {
    constructor() {
        this.selectedHotel = 'Hotel Terracaribe Cancun';
        this.activeSection = 'resumen';
        this.dateRange = '30';
        this.isLoading = false;
        
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
        document.getElementById('hotelSelector').addEventListener('change', (e) => {
            this.selectedHotel = e.target.value;
            this.updateCurrentHotel();
            this.loadDashboardData();
        });
        
        // Eventos del selector de rango de fechas
        document.getElementById('dateRange').addEventListener('change', (e) => {
            this.dateRange = e.target.value;
            this.loadDashboardData();
        });
        
        // Eventos de los botones del menú
        document.querySelectorAll('.menu-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const section = e.currentTarget.getAttribute('data-section');
                this.switchSection(section);
            });
        });
        
        // Evento del botón de reporte
        document.querySelector('[data-action="report"]')?.addEventListener('click', () => {
            this.generateReport();
        });
    }
    
    switchSection(section) {
        // Actualizar estado activo
        this.activeSection = section;
        
        // Actualizar botones del menú
        document.querySelectorAll('.menu-button').forEach(btn => {
            btn.classList.remove('active', 'bg-cyan-500', 'text-white');
            btn.classList.add('text-gray-300');
        });
        
        document.querySelector(`[data-section="${section}"]`).classList.remove('text-gray-300');
        document.querySelector(`[data-section="${section}"]`).classList.add('active', 'bg-cyan-500', 'text-white');
        
        // Mostrar/ocultar secciones
        document.querySelectorAll('.content-section').forEach(section => {
            section.style.display = 'none';
        });
        
        document.getElementById(`${section}-section`).style.display = 'block';
        
        // Cargar datos específicos de la sección
        this.loadSectionData(section);
    }
    
    updateCurrentHotel() {
        const currentHotelSpan = document.getElementById('current-hotel');
        if (currentHotelSpan) {
            currentHotelSpan.textContent = this.selectedHotel;
        }
    }
    
    async loadInitialData() {
        await this.loadDashboardData();
        await this.loadSectionData(this.activeSection);
    }
    
    async loadDashboardData() {
        this.showLoading(true);
        
        try {
            // Simular carga de datos (aquí conectarías con tu API)
            const data = await this.fetchDashboardData();
            this.updateDashboardMetrics(data);
        } catch (error) {
            this.showError('Error cargando datos del dashboard', error);
        } finally {
            this.showLoading(false);
        }
    }
    
    async loadSectionData(section) {
        switch (section) {
            case 'resumen':
                await this.loadResumenData();
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
            const hotelId = this.getSelectedHotelId();
            if (!hotelId) return this.getDefaultDashboardData();
            
            const response = await fetch(`api/dashboard.php?action=dashboard&hotel_id=${hotelId}&date_range=${this.dateRange}`);
            const result = await response.json();
            
            if (result.success) {
                return result.data;
            } else {
                throw new Error(result.error || 'Error obteniendo datos');
            }
        } catch (error) {
            console.error('Error fetching dashboard data:', error);
            return this.getDefaultDashboardData();
        }
    }
    
    getSelectedHotelId() {
        // Por ahora usar ID fijo, en producción obtendrías esto del selector de hotel
        const hotelMapping = {
            'Hotel Terracaribe Cancun': 6, // ID del hotel en la base de datos
            'Hotel Plaza Kokai Cancún': 7,
            'Top 10% myHotel': 8,
            'Suites Cancun Center': 9,
            'Promedio myHotel': 10
        };
        
        return hotelMapping[this.selectedHotel] || 6;
    }
    
    getDefaultDashboardData() {
        return {
            iro: {
                score: 74,
                change: 9,
                trend: 'up',
                calificacion: { value: 77, trend: 'up' },
                cobertura: { value: 82, trend: 'up' },
                reseñas: { value: 56, trend: 'down' }
            },
            semantico: {
                score: 29,
                status: 'bad',
                change: -50,
                message: 'Cuidado, tu propiedad tiene bastantes menciones negativas en los comentarios.'
            },
            stats: {
                total_reviews: 30,
                avg_rating: 3.85,
                changes: { reviews: 11, rating: -4 }
            }
        };
    }
    
    updateDashboardMetrics(data) {
        // Actualizar círculo de progreso IRO
        this.updateCircularProgress('iro-progress', data.iro.score);
        document.getElementById('iro-score').textContent = `${data.iro.score}%`;
        
        // Actualizar estado IRO
        const iroStatus = document.getElementById('iro-status');
        if (data.iro.score >= 80) {
            iroStatus.textContent = 'Excelente';
            iroStatus.className = 'text-sm text-green-600';
        } else if (data.iro.score >= 60) {
            iroStatus.textContent = 'Regular';
            iroStatus.className = 'text-sm text-gray-600';
        } else {
            iroStatus.textContent = 'Malo';
            iroStatus.className = 'text-sm text-red-600';
        }
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
    
    async loadResumenData() {
        // Los datos del resumen ya están cargados en el HTML
        // Aquí podrías hacer actualizaciones dinámicas si fuera necesario
        console.log('Cargando datos de resumen para:', this.selectedHotel);
    }
    
    async loadOTAsData() {
        console.log('Cargando datos de OTAs para:', this.selectedHotel);
        // Aquí cargarías datos dinámicos de OTAs desde tu API
    }
    
    async loadReseñasData() {
        console.log('Cargando reseñas para:', this.selectedHotel);
        
        const reviewsContainer = document.getElementById('reviews-container');
        if (!reviewsContainer) return;
        
        // Mostrar loading
        reviewsContainer.innerHTML = `
            <div class="reviews-loading">
                <div class="spinner"></div>
                <span class="ml-3">Cargando reseñas...</span>
            </div>
        `;
        
        try {
            const reviews = await this.fetchReviews();
            this.renderReviews(reviews, reviewsContainer);
        } catch (error) {
            reviewsContainer.innerHTML = `
                <div class="text-center py-8 text-red-600">
                    Error cargando reseñas: ${error.message}
                </div>
            `;
        }
    }
    
    async fetchReviews() {
        try {
            const hotelId = this.getSelectedHotelId();
            if (!hotelId) return this.getDefaultReviews();
            
            const response = await fetch(`api/dashboard.php?action=reviews&hotel_id=${hotelId}&date_range=${this.dateRange}&limit=20`);
            const result = await response.json();
            
            if (result.success) {
                return result.data || [];
            } else {
                throw new Error(result.error || 'Error obteniendo reseñas');
            }
        } catch (error) {
            console.error('Error fetching reviews:', error);
            return this.getDefaultReviews();
        }
    }
    
    getDefaultReviews() {
        return [
            {
                id: 1,
                guest: 'Gabriel',
                country: 'Mexico',
                date: '01 ago 2025',
                tripType: 'Viajo En Pareja',
                reviewId: '29053315',
                platform: 'booking',
                rating: 5,
                title: 'Recomendadísimo en todos los aspectos',
                positive: 'Excelente atención y predisposición de parte de todo el staff, desde recepción, limpieza y la gente del restaurante.',
                negative: 'Que tienen tortugas en cautiverio, en un espacio súper pequeño y no en muy buen estado. Me gustaría que estén al aire libre, en su hábitat natural.',
                hasResponse: false
            },
            {
                id: 2,
                guest: 'Seenu',
                country: 'Mexico',
                date: '01 ago 2025',
                tripType: 'Viajo Con Amigos',
                reviewId: '29053314',
                platform: 'booking',
                rating: 4.5,
                title: 'Excellent',
                positive: 'Location is safe and secure',
                negative: '',
                hasResponse: false
            }
        ];
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
        
        // Inicializar eventos de los botones de reseñas
        this.bindReviewEvents();
    }
    
    createReviewHTML(review) {
        const platformColors = {
            booking: 'bg-blue-700',
            google: 'bg-red-500',
            tripadvisor: 'bg-green-600',
            expedia: 'bg-blue-600'
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
                            <span class="text-xs text-gray-500">ID Reseña: ${review.reviewId}</span>
                            <div class="platform-badge ${review.platform} ${platformColors[review.platform] || 'bg-gray-500'}">
                                ${review.platform === 'booking' ? 'Booking.com' : 
                                  review.platform === 'google' ? 'Google' :
                                  review.platform === 'tripadvisor' ? 'TripAdvisor' :
                                  review.platform.charAt(0).toUpperCase() + review.platform.slice(1)}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="star-rating flex">
                                ${starsHTML}
                            </div>
                            <span class="text-sm font-medium">${review.rating} / 5</span>
                        </div>
                        <h5 class="font-medium text-gray-900 mb-3">${review.title}</h5>
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

                <div class="review-actions flex gap-2">
                    <button class="px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50" 
                            onclick="dashboard.integrateOTA('${review.id}')">
                        Integrar OTA
                    </button>
                    ${review.negative ? `
                    <button class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50"
                            onclick="dashboard.translateReview('${review.id}')">
                        Traducir
                    </button>
                    ` : ''}
                    <button class="px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600 flex items-center gap-1"
                            onclick="dashboard.generateResponse('${review.id}')">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                        Generar respuesta
                    </button>
                    <button class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50"
                            onclick="dashboard.createCase('${review.id}')">
                        Crear Caso
                    </button>
                </div>
            </div>
        `;
    }
    
    bindReviewEvents() {
        // Reinicializar iconos después de agregar nuevo HTML
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
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
        console.log('Generar reporte para:', this.selectedHotel, 'Período:', this.dateRange);
        
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
        notification.className = `notification ${colors[type]} text-white p-4`;
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
        
        document.body.appendChild(notification);
        
        // Inicializar iconos
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
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
let dashboard;
document.addEventListener('DOMContentLoaded', () => {
    dashboard = new HotelDashboard();
});

// Exponer dashboard globalmente para uso en onclick handlers
window.dashboard = dashboard;