// global-notifications.js - Versão simplificada para inclusão rápida

// Verifica se o sistema principal já foi carregado
if (typeof Notification === 'undefined') {
    // Sistema básico de notificações
    window.showNotification = function(message, type = 'info') {
        // Cria elemento de notificação
        const notification = document.createElement('div');
        notification.className = `notification-global ${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 99999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
            display: flex;
            align-items: center;
            gap: 10px;
            max-width: 400px;
        `;

        // Cores baseadas no tipo
        const colors = {
            success: 'linear-gradient(135deg, #28a745 0%, #218838 100%)',
            error: 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)',
            warning: 'linear-gradient(135deg, #ffc107 0%, #e0a800 100%)',
            info: 'linear-gradient(135deg, #17a2b8 0%, #138496 100%)'
        };

        notification.style.background = colors[type] || colors.info;

        // Ícone
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-circle',
            info: 'info-circle'
        };

        notification.innerHTML = `
            <i class="fas fa-${icons[type] || 'info-circle'}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" 
                    style="margin-left: auto; background: none; border: none; color: inherit; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        `;

        document.body.appendChild(notification);

        // Remove após 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    };

    // Funções auxiliares
    window.showSuccess = (msg) => showNotification(msg, 'success');
    window.showError = (msg) => showNotification(msg, 'error');
    window.showWarning = (msg) => showNotification(msg, 'warning');
    window.showInfo = (msg) => showNotification(msg, 'info');
}