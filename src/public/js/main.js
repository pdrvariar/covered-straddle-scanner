/**
 * Main JavaScript file for Covered Straddle Scanner
 */

class ScannerApp {
    constructor() {
        this.init();
    }

    init() {
        // Initialize tooltips
        this.initTooltips();

        // Initialize form validation
        this.initFormValidation();

        // Initialize charts
        this.initCharts();

        // Initialize event listeners
        this.initEventListeners();
    }

    initTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    initFormValidation() {
        // Scanner form validation
        const scannerForm = document.getElementById('scannerForm');
        if (scannerForm) {
            scannerForm.addEventListener('submit', (e) => {
                const tickers = document.getElementById('tickers');

                if (!tickers.value.trim()) {
                    e.preventDefault();
                    this.showAlert('Por favor, insira pelo menos um ticker', 'danger');
                    tickers.focus();
                    return false;
                }

                // Show loading state
                const submitBtn = scannerForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processando...';
                }
            });
        }
    }

    initCharts() {
        // Payoff chart
        const payoffCanvas = document.getElementById('payoffChart');
        if (payoffCanvas) {
            this.initPayoffChart(payoffCanvas);
        }

        // Performance chart
        const perfCanvas = document.getElementById('performanceChart');
        if (perfCanvas) {
            this.initPerformanceChart(perfCanvas);
        }
    }

    initPayoffChart(canvas) {
        const ctx = canvas.getContext('2d');

        // Sample data - will be replaced with actual data
        const data = {
            labels: Array.from({length: 21}, (_, i) => i * 5),
            datasets: [{
                label: 'Payoff',
                data: Array.from({length: 21}, (_, i) => Math.sin(i * 0.5) * 100),
                borderColor: '#1f77b4',
                backgroundColor: 'rgba(31, 119, 180, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        };

        new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Preço da Ação'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Lucro/Prejuízo'
                        }
                    }
                }
            }
        });
    }

    initPerformanceChart(canvas) {
        const ctx = canvas.getContext('2d');

        // Sample data
        const data = {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
            datasets: [{
                label: 'Lucro (%)',
                data: [12, 19, 8, 15, 22, 18],
                borderColor: '#00aa00',
                backgroundColor: 'rgba(0, 170, 0, 0.1)',
                borderWidth: 2,
                fill: true
            }]
        };

        new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }

    initEventListeners() {
        // Price editing functionality
        document.querySelectorAll('.editable-price').forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateMetrics(e.target);
            });
        });

        // Quick filter toggles
        document.querySelectorAll('.quick-filter').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.applyQuickFilter(e.target.dataset.filter);
            });
        });

        // Export buttons
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.exportData(e.target.dataset.format);
            });
        });
    }

    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        const container = document.querySelector('.container-fluid') || document.body;
        container.insertBefore(alertDiv, container.firstChild);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    updateMetrics(input) {
        const row = input.closest('tr');
        const symbol = row.dataset.symbol;

        // Collect updated values
        const currentPrice = parseFloat(row.querySelector('.current-price').value) || 0;
        const callPremium = parseFloat(row.querySelector('.call-premium').value) || 0;
        const putPremium = parseFloat(row.querySelector('.put-premium').value) || 0;

        // Send to server for recalculation
        this.recalculateMetrics(symbol, currentPrice, callPremium, putPremium)
            .then(data => {
                this.updateRowMetrics(row, data);
            })
            .catch(error => {
                this.showAlert('Erro ao recalcular métricas: ' + error, 'danger');
            });
    }

    async recalculateMetrics(symbol, currentPrice, callPremium, putPremium) {
        const response = await fetch('/api/recalculate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                symbol,
                current_price: currentPrice,
                call_premium: callPremium,
                put_premium: putPremium
            })
        });

        if (!response.ok) {
            throw new Error('Erro na requisição');
        }

        return await response.json();
    }

    updateRowMetrics(row, data) {
        // Update metrics in the row
        if (data.max_profit !== undefined) {
            const profitCell = row.querySelector('.max-profit');
            if (profitCell) {
                profitCell.textContent = data.max_profit.toFixed(2);
                profitCell.className = `max-profit ${data.max_profit >= 0 ? 'text-success' : 'text-danger'}`;
            }
        }

        if (data.profit_percent !== undefined) {
            const percentCell = row.querySelector('.profit-percent');
            if (percentCell) {
                percentCell.textContent = data.profit_percent.toFixed(2) + '%';
            }
        }

        this.showAlert('Métricas atualizadas com sucesso!', 'success');
    }

    applyQuickFilter(filter) {
        // Apply filter to results table
        const table = document.querySelector('.results-table');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            let showRow = true;

            switch (filter) {
                case 'high-profit':
                    const profit = parseFloat(row.dataset.profitPercent) || 0;
                    showRow = profit >= 10;
                    break;

                case 'short-term':
                    const days = parseInt(row.dataset.daysToMaturity) || 0;
                    showRow = days <= 30;
                    break;

                case 'high-volume':
                    const volume = parseInt(row.dataset.volume) || 0;
                    showRow = volume >= 1000;
                    break;
            }

            row.style.display = showRow ? '' : 'none';
        });

        this.showAlert(`Filtro "${filter}" aplicado`, 'info');
    }

    exportData(format = 'csv') {
        // Get current results data
        const rows = document.querySelectorAll('.results-table tbody tr');
        const data = [];

        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const rowData = {};
                row.querySelectorAll('td').forEach((cell, index) => {
                    const header = document.querySelector(`.results-table thead th:nth-child(${index + 1})`);
                    if (header) {
                        rowData[header.textContent.trim()] = cell.textContent.trim();
                    }
                });
                data.push(rowData);
            }
        });

        if (format === 'csv') {
            this.exportCSV(data);
        } else if (format === 'json') {
            this.exportJSON(data);
        }
    }

    exportCSV(data) {
        if (data.length === 0) {
            this.showAlert('Nenhum dado para exportar', 'warning');
            return;
        }

        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(';'),
            ...data.map(row => headers.map(header => row[header]).join(';'))
        ].join('\n');

        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        this.downloadFile(blob, 'straddle_results.csv');
    }

    exportJSON(data) {
        if (data.length === 0) {
            this.showAlert('Nenhum dado para exportar', 'warning');
            return;
        }

        const jsonContent = JSON.stringify(data, null, 2);
        const blob = new Blob([jsonContent], { type: 'application/json' });
        this.downloadFile(blob, 'straddle_results.json');
    }

    downloadFile(blob, filename) {
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Utility function to format currency
    formatCurrency(value, currency = 'BRL') {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: currency
        }).format(value);
    }

    // Utility function to format percentage
    formatPercentage(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'percent',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value / 100);
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.scannerApp = new ScannerApp();
});

// Global utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}