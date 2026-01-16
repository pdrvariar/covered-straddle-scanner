// Layout Enhancement Script
class LayoutEnhancer {
    constructor() {
        this.init();
    }

    init() {
        this.initSidebar();
        this.initNavbar();
        this.initAnimations();
        this.initScrollEffects();
        this.initActiveLinks();
        this.initCardHoverEffects();
    }

    initSidebar() {
        // O gerenciamento da sidebar agora é feito principalmente via CSS e HTML no header.php
        // Removido o auto-hide agressivo que poderia conflitar com o layout desktop
    }

    initNavbar() {
        // A app-header agora é fixa e estilizada via CSS no header.php
    }

    initAnimations() {
        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                }
            });
        }, observerOptions);

        // Observe all cards and sections
        document.querySelectorAll('.card, .stat-card, .page-header-gradient').forEach(el => {
            observer.observe(el);
        });
    }

    initScrollEffects() {
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    initActiveLinks() {
        // Highlight active sidebar link
        const currentPath = window.location.pathname + window.location.search;
        const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');

        sidebarLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href.replace('/', '')) ||
                (currentPath === '/' && href === '/')) {
                link.classList.add('active');
            }

            // Add click animation
            link.addEventListener('click', (e) => {
                if (!link.classList.contains('active')) {
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');

                    // Ripple effect
                    const ripple = document.createElement('span');
                    const rect = link.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);

                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = e.clientX - rect.left - size/2 + 'px';
                    ripple.style.top = e.clientY - rect.top - size/2 + 'px';
                    ripple.className = 'ripple';

                    link.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 600);
                }
            });
        });
    }

    initCardHoverEffects() {
        // Enhanced card hover effects
        const cards = document.querySelectorAll('.card, .stat-card');

        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                const icon = card.querySelector('.stat-icon i, .card-header i');
                if (icon) {
                    icon.style.transform = 'scale(1.2) rotate(5deg)';
                }
            });

            card.addEventListener('mouseleave', () => {
                const icon = card.querySelector('.stat-icon i, .card-header i');
                if (icon) {
                    icon.style.transform = 'scale(1) rotate(0)';
                }
            });
        });
    }

    // Utility function to update market data in sidebar
    updateMarketData(data) {
        const marketElements = {
            'selic': document.querySelector('.market-selic')
        };

        for (const [key, element] of Object.entries(marketElements)) {
            if (element && data[key]) {
                element.textContent = data[key];

                // Add animation
                element.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                }, 300);
            }
        }
    }

    // Toast notifications
    static showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${this.getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => toast.classList.add('show'), 10);

        // Remove toast after delay
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    static getToastIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.layout = new LayoutEnhancer();

    // Add CSS for ripple effect
    const style = document.createElement('style');
    style.textContent = `
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            transform: translateX(150%);
            transition: transform 0.3s ease;
            z-index: 9999;
            min-width: 300px;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast-success {
            border-left: 4px solid #00c853;
        }
        
        .toast-error {
            border-left: 4px solid #f44336;
        }
        
        .toast-warning {
            border-left: 4px solid #ffc107;
        }
        
        .toast-info {
            border-left: 4px solid #2196f3;
        }
        
        .toast-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .toast-content i {
            font-size: 1.2rem;
        }
    `;
    document.head.appendChild(style);
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LayoutEnhancer;
}
