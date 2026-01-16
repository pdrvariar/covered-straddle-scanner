<?php
// Define variáveis para o header
$page_title = 'Scanner de Opções - Covered Straddle';

// Incluir header
include __DIR__ . '/layout/header.php';
?>

    <div class="content-wrapper">
        <!-- Cabeçalho -->
        <div class="page-header-gradient mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2">
                        <i class="fas fa-search me-2"></i>
                        Scanner Rápido
                    </h1>
                    <p class="mb-0 opacity-75">
                        Configure os parâmetros para localizar as melhores oportunidades de Covered Straddle
                    </p>
                </div>
            </div>
        </div>

        <!-- Error/Success Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-xl me-3"></i>
                    <div>
                        <h6 class="alert-heading mb-1">Erro na operação</h6>
                        <p class="mb-0"><?= htmlspecialchars($_SESSION['error']) ?></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-xl me-3"></i>
                    <div>
                        <h6 class="alert-heading mb-1">Operação concluída</h6>
                        <p class="mb-0"><?= htmlspecialchars($_SESSION['success']) ?></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Scanner Form -->
        <form id="scannerForm" method="POST" action="/?action=results">
            <div class="row g-4">
                <!-- Left Column - Parameters -->
                <div class="col-lg-8">
                    <!-- Asset Selection -->
                    <div class="card scanner-card border-0 shadow-lg mt-4">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <div class="d-flex align-items-center mb-3">
                                <div class="card-icon-wrapper bg-success">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="card-title mb-1">Seleção de Ativos</h5>
                                    <p class="text-muted small mb-0">Selecione os ativos para análise</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="mb-4">
                                <label for="tickers" class="form-label fw-semibold">
                                    <i class="fas fa-list me-2"></i>
                                    Lista de Tickers
                                </label>
                                <textarea class="form-control form-control-lg"
                                          id="tickers"
                                          name="tickers"
                                          rows="4"
                                          required
                                          placeholder="Insira os tickers separados por vírgula"><?= htmlspecialchars($defaultTickers ?? 'PETR4,VALE3,ITUB4,BBAS3,BBDC4') ?></textarea>
                                <div class="form-text mt-2">
                                    <small class="d-flex align-items-center">
                                        <i class="fas fa-lightbulb me-2 text-warning"></i>
                                        Separe os tickers por vírgula. Ex: BBAS3,PETR4,VALE3
                                    </small>
                                </div>
                            </div>

                            <!-- Quick Tickers -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-3">Tickers Recomendados</label>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <?php
                                    $recommendedTickers = [
                                            'BBAS3' => 'Banco do Brasil',
                                            'PETR4' => 'Petrobras',
                                            'VALE3' => 'Vale',
                                            'ITUB4' => 'Itaú',
                                            'BBDC4' => 'Bradesco',
                                            'WEGE3' => 'Weg',
                                            'ABEV3' => 'Ambev',
                                            'MGLU3' => 'Magazine Luiza',
                                            'LREN3' => 'Renner',
                                            'RAIL3' => 'Rumo'
                                    ];
                                    ?>
                                    <?php foreach ($recommendedTickers as $ticker => $name): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary ticker-btn" onclick="addTicker('<?= $ticker ?>')" data-bs-toggle="tooltip" title="<?= $name ?>">
                                            <?= $ticker ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearTickers()">
                                        <i class="fas fa-trash me-1"></i> Limpar
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="addAllTickers()">
                                        <i class="fas fa-plus-circle me-1"></i> Adicionar Todos
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="suggestTickers()">
                                        <i class="fas fa-magic me-1"></i> Sugerir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Advanced Settings -->
                <div class="col-lg-4">
                    <!-- Expiration Settings -->
                    <div class="card scanner-card border-0 shadow-lg h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <div class="d-flex align-items-center mb-3">
                                <div class="card-icon-wrapper bg-warning">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="card-title mb-1">Vencimento</h5>
                                    <p class="text-muted small mb-0">Selecione a data de vencimento</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="mb-4">
                                <label for="expiration_date" class="form-label fw-semibold">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Data de Vencimento
                                </label>
                                <?php
                                $defaultDate = date('Y-m-d', strtotime('+30 days'));
                                ?>
                                <input type="date"
                                       class="form-control form-control-lg"
                                       id="expiration_date"
                                       name="expiration_date"
                                       value="<?= $defaultDate ?>"
                                       required>
                                <div class="form-text mt-2">
                                    <small>Data de vencimento das opções</small>
                                </div>
                            </div>

                            <!-- Quick Expiration Buttons -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold mb-3">Vencimentos Sugeridos:</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary" onclick="setExpiration(7)">
                                        7 dias
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="setExpiration(30)">
                                        30 dias
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="setExpiration(60)">
                                        60 dias
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Row -->
                <div class="col-12">
                    <div class="card scanner-card border-0 shadow-lg">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <div class="d-flex align-items-center mb-3">
                                <div class="card-icon-wrapper bg-info">
                                    <i class="fas fa-filter"></i>
                                </div>
                                <div class="ms-3">
                                    <h5 class="card-title mb-1">Filtros e Ordenação</h5>
                                    <p class="text-muted small mb-0">Ajuste os parâmetros da análise</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-4">
                                <!-- Filtro de Lucro Mínimo - MODIFICADO -->
                                <div class="col-md-6">
                                    <div class="filter-section">
                                        <label class="form-label fw-semibold mb-3">
                                            <i class="fas fa-chart-line me-2"></i>
                                            Lucro Mínimo (%)
                                        </label>

                                        <div class="profit-input-container mb-4">
                                            <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light">
                                                <i class="fas fa-percentage"></i>
                                            </span>
                                                <input type="number"
                                                       class="form-control profit-input"
                                                       id="min_profit"
                                                       name="min_profit"
                                                       min="0"
                                                       max="50"
                                                       step="0.1"
                                                       value="0"
                                                       oninput="updateProfitDisplay(this.value)">
                                                <button type="button" class="btn btn-outline-secondary" onclick="decreaseProfit()">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="increaseProfit()">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <div class="profit-value-display text-center mt-2">
                                                <span class="profit-value-badge" id="profitValueDisplay">0%</span>
                                            </div>
                                        </div>

                                        <!-- Quick Value Buttons -->
                                        <div class="mb-4">
                                            <label class="form-label mb-2 text-muted">Valores Rápidos:</label>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProfitValue(2)">2%</button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProfitValue(5)">5%</button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProfitValue(7.5)">7.5%</button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProfitValue(10)">10%</button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProfitValue(15)">15%</button>
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setProfitValue(20)">20%</button>
                                            </div>
                                        </div>

                                        <div class="form-text">
                                            <small class="d-flex align-items-center">
                                                <i class="fas fa-info-circle me-2 text-info"></i>
                                                Filtrar apenas operações com lucro acima deste valor
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filtros de Qualidade -->
                                <div class="col-md-6">
                                    <div class="filter-section">
                                        <label class="form-label fw-semibold mb-3">
                                            <i class="fas fa-sliders-h me-2"></i>
                                            Filtros de Qualidade
                                        </label>

                                        <div class="form-check form-switch form-switch-lg mb-3">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="filter_liquidity"
                                                   name="filter_liquidity"
                                                   checked>
                                            <label class="form-check-label" for="filter_liquidity">
                                                <i class="fas fa-exchange-alt me-2 text-success"></i>
                                                Filtro de Liquidez
                                                <small class="d-block text-muted">Spread máximo: R$ 0,05</small>
                                            </label>
                                        </div>

                                        <div class="form-check form-switch form-switch-lg mb-3">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   id="filter_recency"
                                                   name="filter_recency"
                                                   checked>
                                            <label class="form-check-label" for="filter_recency">
                                                <i class="fas fa-clock me-2 text-info"></i>
                                                Filtro de Recência
                                                <small class="d-block text-muted">Último negócio: 5 minutos</small>
                                            </label>
                                        </div>

                                        <div class="alert alert-light border mt-3">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-sort-amount-down fa-xl me-3 text-primary"></i>
                                                <div>
                                                    <h6 class="alert-heading mb-1">Ordenação Automática</h6>
                                                    <p class="mb-0 small">Os resultados serão automaticamente ordenados do maior para o menor lucro percentual.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <div class="card scanner-card border-0 shadow-lg">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="scanner-progress d-none" id="progressContainer">
                                        <div class="progress-label mb-2">
                                            <span class="fw-semibold">Analisando...</span>
                                            <span class="text-muted ms-2" id="progressText">0%</span>
                                        </div>
                                        <div class="progress" style="height: 12px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated"
                                                 id="progressBar"
                                                 style="width: 0%"></div>
                                        </div>
                                    </div>

                                    <div id="readyMessage">
                                        <h6 class="mb-2">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            Scanner Pronto
                                        </h6>
                                        <p class="text-muted small mb-0">
                                            Clique no botão para iniciar a análise dos ativos selecionados.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg btn-scanner" id="scanButton">
                                            <i class="fas fa-play me-2"></i>
                                            Executar Scanner
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-lg" onclick="resetForm()">
                                            <i class="fas fa-redo me-2"></i>
                                            Limpar Formulário
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

<?php
// O sidebar agora é incluído pelo header.php
?>

<?php
// Passar o JavaScript da página para o footer
ob_start();
?>
        // Add ticker to textarea
        function addTicker(ticker) {
            const textarea = document.getElementById('tickers');
            const currentValue = textarea.value.trim();
            const tickers = currentValue ? currentValue.split(',') : [];

            // Remove any whitespace from tickers
            const cleanTickers = tickers.map(t => t.trim()).filter(t => t);

            // Add new ticker if not already present
            if (!cleanTickers.includes(ticker)) {
                cleanTickers.push(ticker);
                textarea.value = cleanTickers.join(', ');

                // Show success feedback
                showTickerFeedback(`Ticker ${ticker} adicionado!`);
            } else {
                showTickerFeedback(`Ticker ${ticker} já está na lista!`, 'warning');
            }
        }

        // Add all suggested tickers
        function addAllTickers() {
            const allTickers = ['BBAS3', 'PETR4', 'VALE3', 'ITUB4', 'BBDC4', 'WEGE3', 'ABEV3', 'MGLU3', 'LREN3', 'RAIL3'];
            allTickers.forEach(ticker => addTicker(ticker));
        }

        // Suggest tickers based on market
        function suggestTickers() {
            const marketTickers = {
                'bancos': ['BBAS3', 'ITUB4', 'BBDC4', 'SANB11', 'BPAN4'],
                'energia': ['ELET3', 'ELET6', 'EGIE3', 'EQTL3', 'TAEE11'],
                'mineracao': ['VALE3', 'CSNA3', 'GGBR4', 'USIM5'],
                'vvarejo': ['MGLU3', 'LREN3', 'VVAR3', 'AMER3']
            };

            const selectedTickers = Object.values(marketTickers).flat();
            const shuffled = selectedTickers.sort(() => 0.5 - Math.random());
            const suggested = shuffled.slice(0, 8);

            document.getElementById('tickers').value = suggested.join(', ');
            showTickerFeedback('8 tickers sugeridos adicionados!', 'info');
        }

        // Clear all tickers
        function clearTickers() {
            if (confirm('Tem certeza que deseja limpar todos os tickers?')) {
                document.getElementById('tickers').value = '';
                showTickerFeedback('Lista de tickers limpa!', 'info');
            }
        }

        // Set expiration date
        function setExpiration(days) {
            const date = new Date();
            date.setDate(date.getDate() + days);

            const formattedDate = date.toISOString().split('T')[0];
            document.getElementById('expiration_date').value = formattedDate;

            showTickerFeedback(`Vencimento definido para ${days} dias`, 'info');
        }

        // Profit Input Functions
        function updateProfitDisplay(value) {
            const display = document.getElementById('profitValueDisplay');
            const input = document.getElementById('min_profit');

            // Ensure value is within bounds
            if (value < 0) value = 0;
            if (value > 50) value = 50;

            // Update display
            display.textContent = value + '%';

            // Update input value
            input.value = value;

            // Color coding based on value
            if (value >= 20) {
                display.className = 'profit-value-badge bg-success';
                input.className = 'form-control profit-input border-success';
            } else if (value >= 10) {
                display.className = 'profit-value-badge bg-warning';
                input.className = 'form-control profit-input border-warning';
            } else if (value >= 5) {
                display.className = 'profit-value-badge bg-info';
                input.className = 'form-control profit-input border-info';
            } else {
                display.className = 'profit-value-badge bg-secondary';
                input.className = 'form-control profit-input border-secondary';
            }
        }

        // Increase profit value by 0.1
        function increaseProfit() {
            const input = document.getElementById('min_profit');
            let value = parseFloat(input.value) || 0;
            value = Math.round((value + 0.1) * 10) / 10;
            if (value > 50) value = 50;
            updateProfitDisplay(value);
        }

        // Decrease profit value by 0.1
        function decreaseProfit() {
            const input = document.getElementById('min_profit');
            let value = parseFloat(input.value) || 0;
            value = Math.round((value - 0.1) * 10) / 10;
            if (value < 0) value = 0;
            updateProfitDisplay(value);
        }

        // Set profit value via quick buttons
        function setProfitValue(value) {
            updateProfitDisplay(value);
            showTickerFeedback(`Lucro mínimo definido para ${value}%`, 'info');
        }

        // Reset form
        function resetForm() {
            if (confirm('Tem certeza que deseja limpar todos os campos?')) {
                document.getElementById('scannerForm').reset();
                updateProfitDisplay(0);

                // Reset to default tickers
                document.getElementById('tickers').value = "<?= htmlspecialchars($defaultTickers) ?>";

                // Reset expiration date
                const defaultDate = new Date();
                defaultDate.setDate(defaultDate.getDate() + 30);
                const expInput = document.getElementById('expiration_date');
                if (expInput) expInput.value = defaultDate.toISOString().split('T')[0];

                showTickerFeedback('Formulário resetado para valores padrão!', 'info');
            }
        }

        // Form submission with progress bar
        document.getElementById('scannerForm').addEventListener('submit', function(e) {
            const scanButton = document.getElementById('scanButton');
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const readyMessage = document.getElementById('readyMessage');

            // Validate form
            const tickers = document.getElementById('tickers').value.trim();
            if (!tickers) {
                e.preventDefault();
                showTickerFeedback('Adicione pelo menos um ticker!', 'error');
                return false;
            }

            // Show progress bar
            if (progressContainer) {
                progressContainer.classList.remove('d-none');
                readyMessage.classList.add('d-none');
            }

            // Disable button and show loading state
            if (scanButton) {
                scanButton.disabled = true;
                scanButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Analisando...';
            }

            // Simulate progress
            if (progressBar && progressText) {
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 2;
                    progressBar.style.width = Math.min(progress, 98) + '%';
                    progressText.textContent = Math.min(progress, 98) + '%';

                    if (progress >= 98) {
                        clearInterval(interval);
                    }
                }, 100);
            }

            // Allow form submission
            return true;
        });

        // Initialize form
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const expirationInput = document.getElementById('expiration_date');
            if (expirationInput) expirationInput.min = today;

            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

            // Initialize profit display
            updateProfitDisplay(document.getElementById('min_profit').value);
        });

        // Show feedback for ticker operations
        function showTickerFeedback(message, type = 'success') {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `toast align-items-center border-0 bg-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'success'}`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');

            toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body text-white">
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'check-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

            // Add to container
            const container = document.getElementById('toastContainer') || (() => {
                const div = document.createElement('div');
                div.id = 'toastContainer';
                div.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(div);
                return div;
            })();

            container.appendChild(toast);

            // Show toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            // Remove after hiding
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // Atalhos de teclado para Lucro Mínimo
        document.addEventListener('keydown', function(e) {
            // Ignorar se o usuário estiver digitando em campos de texto (exceto o próprio lucro)
            const activeElement = document.activeElement;
            const isInput = activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA';

            const isTargetProfit = activeElement.id === 'min_profit';

            if (isInput && !isTargetProfit) {
                return;
            }

            // Teclas + (187 ou 107) e - (189 ou 109)
            if (e.key === '+' || e.key === '=') {
                increaseProfit();
                if (!isTargetProfit) e.preventDefault();
            } else if (e.key === '-' || e.key === '_') {
                decreaseProfit();
                if (!isTargetProfit) e.preventDefault();
            }
        });

        // Inicializar display ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            const minProfitInput = document.getElementById('min_profit');
            if (minProfitInput) {
                updateProfitDisplay(minProfitInput.value);
            }
        });
<?php
$page_js = ob_get_clean();

// Adicionar CSS específico para o scanner
ob_start();
?>
    <style>
        /* Scanner Specific Styles */
        .scanner-card {
            border-radius: 16px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .scanner-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1) !important;
        }

        .card-icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        /* Form Controls */
        .form-control-lg {
            border-radius: 10px;
            padding: 0.875rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .form-control-lg:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 0.2rem rgba(57, 73, 171, 0.2);
        }

        /* Profit Input Styling */
        .profit-input-container {
            position: relative;
        }

        .profit-input {
            text-align: center;
            font-weight: 600;
            font-size: 1.2rem;
            border: 2px solid #dee2e6;
            transition: all 0.3s;
        }

        .profit-input:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 0.2rem rgba(57, 73, 171, 0.2);
        }

        .profit-input.border-success {
            border-color: #198754 !important;
            background-color: rgba(25, 135, 84, 0.05);
        }

        .profit-input.border-warning {
            border-color: #ffc107 !important;
            background-color: rgba(255, 193, 7, 0.05);
        }

        .profit-input.border-info {
            border-color: #0dcaf0 !important;
            background-color: rgba(13, 202, 240, 0.05);
        }

        .profit-input.border-secondary {
            border-color: #6c757d !important;
            background-color: rgba(108, 117, 125, 0.05);
        }

        .profit-value-display {
            margin-top: 0.5rem;
        }

        .profit-value-badge {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-width: 80px;
            text-align: center;
        }

        /* Ticker Buttons */
        .ticker-btn {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s;
            border: 2px solid #dee2e6;
        }

        .ticker-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #3949ab;
        }

        /* Progress Bar */
        .progress-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-bar {
            border-radius: 8px;
            background: linear-gradient(90deg, #1a237e, #3949ab);
        }

        /* Scanner Button */
        .btn-scanner {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-scanner:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.25);
        }

        /* Form Switches */
        .form-switch-lg .form-check-input {
            width: 3rem;
            height: 1.5rem;
            margin-right: 0.5rem;
        }

        .form-switch-lg .form-check-input:checked {
            background-color: #3949ab;
            border-color: #3949ab;
        }

        /* Filter Section */
        .filter-section {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            height: 100%;
        }

        /* Input Group Buttons */
        .btn-outline-secondary {
            border-color: #dee2e6;
            color: #6c757d;
        }

        .btn-outline-secondary:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
        }

        /* Quick Value Buttons */
        .btn-sm.btn-outline-primary {
            border-radius: 20px;
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Alert Improvements */
        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-icon-wrapper {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }

            .btn-scanner {
                padding: 0.875rem 1.5rem;
            }

            .profit-input {
                font-size: 1.1rem;
            }

            .profit-value-badge {
                padding: 0.4rem 1.2rem;
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .profit-input-container .input-group-lg {
                flex-wrap: nowrap;
            }

            .profit-input {
                font-size: 1rem;
            }

            .btn-sm.btn-outline-primary {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
            }
        }
    </style>
<?php
$additional_css = [ob_get_clean()];

// Incluir footer
include __DIR__ . '/layout/footer.php';
?>