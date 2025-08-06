<?php
/**
 * ==========================================================================
 * HEADER MODULAR - Kavia Hoteles Panel de Administración
 * Header reutilizable para el panel de administración
 * ==========================================================================
 */

// Configuración del header
$headerConfig = [
    'title' => 'Panel de Administración - Kavia Hoteles',
    'subtitle' => 'Gestión de Hoteles, IA, APIs y Extracción de Reseñas - Versión Modular 2.0',
    'version' => 'v2.0',
    'showUserInfo' => true,
    'showNotifications' => true,
    'showSearch' => false
];

// Obtener información del usuario si está disponible
$userInfo = null;
if (isset($_SESSION['user_id'])) {
    $userInfo = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Usuario',
        'role' => $_SESSION['user_role'] ?? 'admin'
    ];
}
?>

<!-- Header Principal -->
<div class="header">
    <div class="header-content">
        <!-- Logo y Título -->
        <div class="header-brand">
            <div class="brand-logo">
                <i class="fas fa-hotel"></i>
            </div>
            <div class="brand-text">
                <h1><?php echo htmlspecialchars($headerConfig['title']); ?></h1>
                <p><?php echo htmlspecialchars($headerConfig['subtitle']); ?></p>
            </div>
        </div>

        <!-- Información del Sistema -->
        <div class="header-info">
            <div class="system-status">
                <span class="status-indicator online"></span>
                <span class="status-text">Sistema Activo</span>
            </div>
            <div class="version-info">
                <span class="version-badge"><?php echo htmlspecialchars($headerConfig['version']); ?></span>
            </div>
        </div>

        <!-- Acciones del Header -->
        <div class="header-actions">
            <?php if ($headerConfig['showSearch']): ?>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar..." id="global-search">
                </div>
            </div>
            <?php endif; ?>

            <?php if ($headerConfig['showNotifications']): ?>
            <div class="notifications-container">
                <button class="notification-btn" id="notifications-toggle">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notification-count">0</span>
                </button>
                <div class="notifications-dropdown" id="notifications-dropdown">
                    <div class="notifications-header">
                        <h3>Notificaciones</h3>
                        <button class="mark-all-read" id="mark-all-read">Marcar todo como leído</button>
                    </div>
                    <div class="notifications-list" id="notifications-list">
                        <div class="empty-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <p>No hay notificaciones</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($headerConfig['showUserInfo'] && $userInfo): ?>
            <div class="user-container">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($userInfo['name']); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars(ucfirst($userInfo['role'])); ?></span>
                    </div>
                </div>
                <div class="user-dropdown">
                    <button class="user-menu-btn" id="user-menu-toggle">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown-menu" id="user-dropdown-menu">
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-user"></i> Perfil
                        </a>
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item text-danger" id="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de Progreso del Sistema -->
    <div class="system-progress" id="system-progress" style="display: none;">
        <div class="progress-bar">
            <div class="progress-fill" id="progress-fill"></div>
        </div>
        <div class="progress-text" id="progress-text">Inicializando sistema...</div>
    </div>
</div>

<!-- Scripts específicos del header -->
<script>
/**
 * Funcionalidades específicas del header
 */
class HeaderManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeNotifications();
        this.initializeUserMenu();
        this.initializeSearch();
    }

    bindEvents() {
        // Toggle de notificaciones
        const notificationsToggle = document.getElementById('notifications-toggle');
        const notificationsDropdown = document.getElementById('notifications-dropdown');
        
        if (notificationsToggle && notificationsDropdown) {
            notificationsToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationsDropdown.classList.toggle('show');
            });
        }

        // Toggle del menú de usuario
        const userMenuToggle = document.getElementById('user-menu-toggle');
        const userDropdownMenu = document.getElementById('user-dropdown-menu');
        
        if (userMenuToggle && userDropdownMenu) {
            userMenuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdownMenu.classList.toggle('show');
            });
        }

        // Cerrar dropdowns al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notifications-container')) {
                const notificationsDropdown = document.getElementById('notifications-dropdown');
                if (notificationsDropdown) {
                    notificationsDropdown.classList.remove('show');
                }
            }
            
            if (!e.target.closest('.user-container')) {
                const userDropdownMenu = document.getElementById('user-dropdown-menu');
                if (userDropdownMenu) {
                    userDropdownMenu.classList.remove('show');
                }
            }
        });

        // Botón de cerrar sesión
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        }
    }

    initializeNotifications() {
        // Simular carga de notificaciones
        setTimeout(() => {
            this.loadNotifications();
        }, 1000);
    }

    async loadNotifications() {
        try {
            // Aquí se cargarían las notificaciones reales desde el servidor
            const notifications = await this.fetchNotifications();
            this.updateNotificationCount(notifications.length);
            this.renderNotifications(notifications);
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
        }
    }

    async fetchNotifications() {
        // Simulación de notificaciones
        return [
            {
                id: 1,
                title: 'Nuevo hotel agregado',
                message: 'Se ha agregado el hotel "Hotel Plaza" al sistema',
                type: 'info',
                timestamp: new Date(),
                read: false
            },
            {
                id: 2,
                title: 'Extracción completada',
                message: 'La extracción de reseñas se ha completado exitosamente',
                type: 'success',
                timestamp: new Date(Date.now() - 300000),
                read: false
            }
        ];
    }

    updateNotificationCount(count) {
        const badge = document.getElementById('notification-count');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    }

    renderNotifications(notifications) {
        const container = document.getElementById('notifications-list');
        if (!container) return;

        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <p>No hay notificaciones</p>
                </div>
            `;
            return;
        }

        container.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.read ? 'read' : 'unread'}" data-id="${notification.id}">
                <div class="notification-icon ${notification.type}">
                    <i class="fas fa-${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${this.formatTime(notification.timestamp)}</div>
                </div>
                <button class="notification-close" onclick="headerManager.markAsRead(${notification.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    getNotificationIcon(type) {
        const icons = {
            'info': 'info-circle',
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'error': 'times-circle'
        };
        return icons[type] || 'info-circle';
    }

    formatTime(timestamp) {
        const now = new Date();
        const diff = now - timestamp;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Ahora mismo';
        if (minutes < 60) return `Hace ${minutes} min`;
        if (hours < 24) return `Hace ${hours} h`;
        if (days < 7) return `Hace ${days} días`;
        return timestamp.toLocaleDateString();
    }

    markAsRead(notificationId) {
        // Marcar notificación como leída
        const notificationItem = document.querySelector(`[data-id="${notificationId}"]`);
        if (notificationItem) {
            notificationItem.classList.remove('unread');
            notificationItem.classList.add('read');
        }
        
        // Actualizar contador
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        this.updateNotificationCount(unreadCount);
    }

    initializeUserMenu() {
        // Funcionalidades del menú de usuario
        const markAllRead = document.getElementById('mark-all-read');
        if (markAllRead) {
            markAllRead.addEventListener('click', () => {
                this.markAllNotificationsAsRead();
            });
        }
    }

    markAllNotificationsAsRead() {
        const unreadNotifications = document.querySelectorAll('.notification-item.unread');
        unreadNotifications.forEach(item => {
            item.classList.remove('unread');
            item.classList.add('read');
        });
        this.updateNotificationCount(0);
    }

    initializeSearch() {
        const searchInput = document.getElementById('global-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
        }
    }

    handleSearch(query) {
        if (query.length < 2) return;
        
        // Implementar búsqueda global
        console.log('Búsqueda global:', query);
        // Aquí se implementaría la lógica de búsqueda
    }

    handleLogout() {
        if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
            // Implementar lógica de logout
            window.location.href = 'logout.php';
        }
    }

    showSystemProgress(show = true) {
        const progressBar = document.getElementById('system-progress');
        if (progressBar) {
            progressBar.style.display = show ? 'block' : 'none';
        }
    }

    updateProgress(percentage, text) {
        const progressFill = document.getElementById('progress-fill');
        const progressText = document.getElementById('progress-text');
        
        if (progressFill) {
            progressFill.style.width = `${percentage}%`;
        }
        
        if (progressText) {
            progressText.textContent = text;
        }
    }
}

// Inicializar el header manager
let headerManager;
document.addEventListener('DOMContentLoaded', () => {
    headerManager = new HeaderManager();
});
</script>