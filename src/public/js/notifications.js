// notifications.js - Sistema de notificações e loading para toda a aplicação

// CSS será injetado dinamicamente
const notificationStyles = `
/* Sistema de Notificações Globais */
.notification-global {
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
    min-width: 300px;
}

.notification-global.success {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    border-left: 4px solid #1e7e34;
}

.notification-global.error {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    border-left: 4px solid #bd2130;
}

.notification-global.warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    border-left: 4px solid #d39e00;
    color: #212529;
}

.notification-global.info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    border-left: 4px solid #117a8b;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

/* Loading Overlay Global */
.loading-overlay-global {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99998;
    backdrop-filter: blur(3px);
}

.loading-content-global {
    background: white;
    padding: 40px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 15px 40px rgba(0,0,0,0.3);
    min-width: 300px;
}

.spinner-global {
    width: 60px;
    height: 60px;
    border: 6px solid #f3f3f3;
    border-top: 6px solid #1f77b4;
    border-radius: 50%;
    animation: spinGlobal 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spinGlobal {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Progress Bar */
.progress-container {
    width: 100%;
    background-color: #f3f3f3;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-bar {
    height: 8px;
    background: linear-gradient(90deg, #1f77b4 0%, #00aa00 100%);
    border-radius: 10px;
    transition: width 0.3s ease;
}
`;

// Injeta os estilos no documento
if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = notificationStyles;
    document.head.appendChild(style);
}

// Sistema de Notificações
class NotificationSystem {
    constructor() {
        this.container = null;
        this.initContainer();
    }

    initContainer() {
        // Cria container para notificações se não existir
        if (!document.getElementById('notification-container')) {
            this.container = document.createElement('div');
            this.container.id = 'notification-container';
            this.container.style.position = 'fixed';
            this.container.style.top = '20px';
            this.container.style.right = '20px';
            this.container.style.zIndex = '99999';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('notification-container');
        }
    }

    show(message, type = 'info', duration = 5000) {
        // Remove notificações antigas
        const notifications = this.container.querySelectorAll('.notification-global');
        if (notifications.length >= 5) {
            notifications[0].remove();
        }

        // Cria nova notificação
        const notification = document.createElement('div');
        notification.className = `notification-global ${type}`;

        // Ícone baseado no tipo
        let icon = 'info-circle';
        switch(type) {
            case 'success': icon = 'check-circle'; break;
            case 'error': icon = 'exclamation-triangle'; break;
            case 'warning': icon = 'exclamation-circle'; break;
            case 'info': default: icon = 'info-circle'; break;
        }

        notification.innerHTML = `
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
            <button class="btn-close" onclick="this.parentElement.remove()" 
                    style="margin-left: auto; background: none; border: none; color: inherit; cursor: pointer; font-size: 1rem;">
                <i class="fas fa-times"></i>
            </button>
        `;

        this.container.appendChild(notification);

        // Remove automaticamente após o tempo especificado
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'fadeOut 0.3s ease-out';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, duration);

        return notification;
    }

    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 5000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }
}

// Sistema de Loading
class LoadingSystem {
    constructor() {
        this.overlay = null;
        this.counter = 0;
    }

    show(message = 'Processando...', progress = null) {
        // Incrementa o contador
        this.counter++;

        // Se já existe overlay, apenas atualiza
        if (this.overlay && this.overlay.parentNode) {
            if (message) {
                const title = this.overlay.querySelector('h5');
                if (title) title.textContent = message;
            }
            if (progress !== null) {
                this.updateProgress(progress);
            }
            return;
        }

        // Cria novo overlay
        this.overlay = document.createElement('div');
        this.overlay.className = 'loading-overlay-global';
        this.overlay.id = 'globalLoadingOverlay';

        let progressBar = '';
        if (progress !== null) {
            progressBar = `
                <div class="progress-container mt-3">
                    <div class="progress-bar" style="width: ${progress}%"></div>
                </div>
            `;
        }

        this.overlay.innerHTML = `
            <div class="loading-content-global">
                <div class="spinner-global"></div>
                <h5>${message}</h5>
                <p class="text-muted">Por favor, aguarde...</p>
                ${progressBar}
            </div>
        `;

        document.body.appendChild(this.overlay);

        // Impede scroll do body
        document.body.style.overflow = 'hidden';
    }

    hide() {
        // Decrementa o contador
        this.counter = Math.max(0, this.counter - 1);

        // Só remove se for o último loading
        if (this.counter === 0 && this.overlay && this.overlay.parentNode) {
            this.overlay.remove();
            this.overlay = null;
            document.body.style.overflow = '';
        }
    }

    updateProgress(percent) {
        if (this.overlay) {
            const progressBar = this.overlay.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = `${Math.min(100, Math.max(0, percent))}%`;
            }
        }
    }

    showProgress(message, duration = 5000) {
        this.show(message, 0);
        let progress = 0;
        const interval = setInterval(() => {
            progress += 100 / (duration / 100);
            this.updateProgress(progress);
            if (progress >= 100) {
                clearInterval(interval);
                this.hide();
            }
        }, 100);
    }
}

// Sistema de Confirmação
class ConfirmationSystem {
    static confirm(message, onConfirm, onCancel = null) {
        // Remove confirmações existentes
        const existing = document.querySelector('.confirmation-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay-global';
        overlay.style.zIndex = '100000';

        overlay.innerHTML = `
            <div class="loading-content-global" style="min-width: 400px;">
                <div class="mb-4">
                    <i class="fas fa-question-circle fa-3x text-warning mb-3"></i>
                    <h4>Confirmação</h4>
                    <p>${message}</p>
                </div>
                <div class="d-flex justify-content-center gap-3">
                    <button class="btn btn-outline-secondary" id="confirmCancel">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button class="btn btn-primary" id="confirmOk">
                        <i class="fas fa-check me-2"></i>Confirmar
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        // Event listeners
        overlay.querySelector('#confirmOk').addEventListener('click', () => {
            overlay.remove();
            document.body.style.overflow = '';
            if (typeof onConfirm === 'function') onConfirm();
        });

        overlay.querySelector('#confirmCancel').addEventListener('click', () => {
            overlay.remove();
            document.body.style.overflow = '';
            if (typeof onCancel === 'function') onCancel();
        });

        // Fecha ao clicar fora
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.remove();
                document.body.style.overflow = '';
                if (typeof onCancel === 'function') onCancel();
            }
        });
    }
}

// Cria instâncias globais
const Notification = new NotificationSystem();
const Loading = new LoadingSystem();

// Funções auxiliares para uso rápido
window.showNotification = (message, type = 'info') => Notification.show(message, type);
window.showSuccess = (message) => Notification.success(message);
window.showError = (message) => Notification.error(message);
window.showWarning = (message) => Notification.warning(message);
window.showInfo = (message) => Notification.info(message);

window.showLoading = (message) => Loading.show(message);
window.hideLoading = () => Loading.hide();
window.showProgress = (message, duration) => Loading.showProgress(message, duration);
window.updateProgress = (percent) => Loading.updateProgress(percent);

window.confirmAction = (message, onConfirm, onCancel) =>
    ConfirmationSystem.confirm(message, onConfirm, onCancel);

// Função para testar todas as notificações
window.testNotifications = () => {
    showSuccess('Operação realizada com sucesso!');
    setTimeout(() => showError('Ocorreu um erro na operação!'), 1000);
    setTimeout(() => showWarning('Atenção: Esta ação não pode ser desfeita!'), 2000);
    setTimeout(() => showInfo('Informação: Sistema atualizado com sucesso!'), 3000);
};

// Adiciona estilos CSS dinâmicos se ainda não existirem
document.addEventListener('DOMContentLoaded', function() {
    // Adiciona estilos CSS se não existirem
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = notificationStyles;
        document.head.appendChild(style);
    }
});

// Exporta para uso em módulos (se suportado)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        Notification,
        Loading,
        ConfirmationSystem,
        showNotification,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showLoading,
        hideLoading,
        showProgress,
        updateProgress,
        confirmAction,
        testNotifications
    };
}