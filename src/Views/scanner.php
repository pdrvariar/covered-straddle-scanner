<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scanner de Opções - Covered Straddle</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom CSS -->
    <link href="/css/style.css" rel="stylesheet">

    <style>
        .scanner-header {
            background: linear-gradient(135deg, #1f77b4 0%, #2c3e50 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .param-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            border: 1px solid #eaeaea;
        }

        .param-card .card-title {
            color: #2c3e50;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f0f2f6;
            padding-bottom: 0.5rem;
        }

        .info-badge {
            background-color: #e8f4fd;
            color: #1f77b4;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .ticker-tag {
            display: inline-block;
            background: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            margin: 0.25rem;
            font-size: 0.85rem;
        }

        .ticker-tag:hover {
            background: #dee2e6;
        }

        .scanner-progress {
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }

        .scanner-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #1f77b4 0%, #00aa00 100%);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (included from layout) -->
        <?php include __DIR__ . '/layout/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <!-- Header -->
            <div class="scanner-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h2 mb-3">
                            <i class="fas fa-search me-2"></i>
                            Covered Straddle Scanner
                        </h1>
                        <p class="mb-0 opacity-75">
                            Analise múltiplos ativos simultaneamente e encontre as melhores oportunidades de operações de straddle coberto.
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                            <span class="info-badge">
                                <i class="fas fa-info-circle me-1"></i>
                                Filtros ativos: Liquidez + Recência
                            </span>
                    </div>
                </div>
            </div>

            <!-- Error/Success Messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Scanner Form -->
            <form id="scannerForm" method="POST" action="/?action=results">
                <div class="row">
                    <!-- Left Column - Parameters -->
                    <div class="col-lg-8">
                        <!-- API Configuration -->
                        <div class="param-card">
                            <h5 class="card-title">
                                <i class="fas fa-key me-2 text-primary"></i>
                                Configuração da API
                            </h5>
                            <div class="mb-3">
                                <label for="access_token" class="form-label">
                                    <i class="fas fa-fingerprint me-1"></i>
                                    Access Token OPLab
                                </label>
                                <div class="input-group">
                                    <input type="password"
                                           class="form-control"
                                           id="access_token"
                                           name="access_token"
                                           <?= empty($_ENV['OPLAB_TOKEN']) ? 'required' : '' ?>
                                           value="<?= htmlspecialchars($_ENV['OPLAB_TOKEN'] ?? '') ?>"
                                           placeholder="<?= !empty($_ENV['OPLAB_TOKEN']) ? 'Token carregado do .env' : 'Insira seu token de acesso da OPLab' ?>">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleToken">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>
                                        Token necessário para acessar os dados da OPLab API.
                                        <a href="https://oplab.com.br/api" target="_blank" class="ms-1">
                                            Obter token
                                        </a>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Asset Selection -->
                        <div class="param-card">
                            <h5 class="card-title">
                                <i class="fas fa-chart-line me-2 text-success"></i>
                                Seleção de Ativos
                            </h5>
                            <div class="mb-3">
                                <label for="tickers" class="form-label">
                                    <i class="fas fa-list me-1"></i>
                                    Lista de Tickers
                                </label>
                                <textarea class="form-control"
                                          id="tickers"
                                          name="tickers"
                                          rows="5"
                                          required
                                          placeholder="Insira os tickers separados por vírgula"><?= htmlspecialchars($defaultTickers) ?></textarea>
                                <div class="form-text">
                                    <small>
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Separe os tickers por vírgula. Ex: BBAS3,PETR4,VALE3
                                    </small>
                                </div>
                            </div>

                            <!-- Quick Tickers -->
                            <div class="mt-3">
                                <label class="form-label mb-2">Tickers Sugeridos:</label>
                                <div id="quickTickers">
                                    <span class="ticker-tag" onclick="addTicker('BBAS3')">BBAS3</span>
                                    <span class="ticker-tag" onclick="addTicker('PETR4')">PETR4</span>
                                    <span class="ticker-tag" onclick="addTicker('VALE3')">VALE3</span>
                                    <span class="ticker-tag" onclick="addTicker('ITUB4')">ITUB4</span>
                                    <span class="ticker-tag" onclick="addTicker('BBDC4')">BBDC4</span>
                                    <span class="ticker-tag" onclick="addTicker('WEGE3')">WEGE3</span>
                                    <span class="ticker-tag" onclick="addTicker('ABEV3')">ABEV3</span>
                                    <span class="ticker-tag" onclick="addTicker('MGLU3')">MGLU3</span>
                                    <span class="ticker-tag" onclick="addTicker('LREN3')">LREN3</span>
                                    <span class="ticker-tag" onclick="addTicker('RAIL3')">RAIL3</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Advanced Settings -->
                    <div class="col-lg-4">
                        <!-- Capital Settings -->
                        <div class="param-card">
                            <h5 class="card-title">
                                <i class="fas fa-wallet me-2 text-warning"></i>
                                Capital
                            </h5>
                            <div class="mb-3">
                                <label for="total_capital" class="form-label">
                                    Capital Total (R$)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number"
                                           class="form-control"
                                           id="total_capital"
                                           name="total_capital"
                                           min="1000"
                                           step="1000"
                                           value="50000"
                                           required>
                                </div>
                                <div class="form-text">
                                    <small>Capital disponível para investimento</small>
                                </div>
                            </div>
                        </div>

                        <!-- Expiration Settings -->
                        <div class="param-card">
                            <h5 class="card-title">
                                <i class="fas fa-calendar-alt me-2 text-info"></i>
                                Vencimento
                            </h5>
                            <div class="mb-3">
                                <label for="expiration_date" class="form-label">
                                    Data de Vencimento
                                </label>
                                <?php
                                $defaultDate = date('Y-m-d', strtotime('+30 days'));
                                ?>
                                <input type="date"
                                       class="form-control"
                                       id="expiration_date"
                                       name="expiration_date"
                                       value="<?= $defaultDate ?>"
                                       required>
                                <div class="form-text">
                                    <small>Data de vencimento das opções</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label mb-2">Vencimentos Sugeridos:</label>
                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setExpiration(7)">
                                        7 dias
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setExpiration(30)">
                                        30 dias
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="setExpiration(60)">
                                        60 dias
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Settings -->
                        <div class="param-card">
                            <h5 class="card-title">
                                <i class="fas fa-filter me-2 text-secondary"></i>
                                Filtros
                            </h5>
                            <div class="mb-3">
                                <label for="min_profit" class="form-label">
                                    Lucro Mínimo (%)
                                </label>
                                <input type="range"
                                       class="form-range"
                                       id="min_profit"
                                       name="min_profit"
                                       min="0"
                                       max="50"
                                       value="0"
                                       oninput="updateProfitValue(this.value)">
                                <div class="d-flex justify-content-between">
                                    <small>0%</small>
                                    <small id="profitValue">0%</small>
                                    <small>50%</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="filter_liquidity"
                                           name="filter_liquidity"
                                           checked>
                                    <label class="form-check-label" for="filter_liquidity">
                                        Filtro de Liquidez
                                    </label>
                                    <div class="form-text">
                                        <small>Spread máximo: R$ 0,05</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="filter_recency"
                                           name="filter_recency"
                                           checked>
                                    <label class="form-check-label" for="filter_recency">
                                        Filtro de Recência
                                    </label>
                                    <div class="form-text">
                                        <small>Último negócio: 5 minutos</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="param-card">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="scanButton">
                                    <i class="fas fa-play me-2"></i>
                                    Executar Scanner
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                    <i class="fas fa-redo me-2"></i>
                                    Limpar Formulário
                                </button>
                            </div>

                            <!-- Progress Bar (hidden by default) -->
                            <div class="scanner-progress mt-3 d-none" id="progressContainer">
                                <div class="scanner-progress-bar" id="progressBar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Quick Start Guide -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-rocket me-2"></i>
                                Como Começar
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center p-3">
                                        <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-key fa-lg text-primary"></i>
                                        </div>
                                        <h6>1. Configure a API</h6>
                                        <p class="text-muted small">Insira seu token de acesso da OPLab</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3">
                                        <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-list fa-lg text-success"></i>
                                        </div>
                                        <h6>2. Selecione Ativos</h6>
                                        <p class="text-muted small">Adicione os tickers que deseja analisar</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3">
                                        <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-sliders-h fa-lg text-warning"></i>
                                        </div>
                                        <h6>3. Ajuste Parâmetros</h6>
                                        <p class="text-muted small">Configure capital, vencimento e filtros</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3">
                                        <div class="rounded-circle bg-info bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-chart-line fa-lg text-info"></i>
                                        </div>
                                        <h6>4. Analise Resultados</h6>
                                        <p class="text-muted small">Veja as melhores oportunidades encontradas</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
    // Toggle token visibility
    document.getElementById('toggleToken').addEventListener('click', function() {
        const tokenInput = document.getElementById('access_token');
        const icon = this.querySelector('i');

        if (tokenInput.type === 'password') {
            tokenInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            tokenInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

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
        }
    }

    // Set expiration date
    function setExpiration(days) {
        const date = new Date();
        date.setDate(date.getDate() + days);

        const formattedDate = date.toISOString().split('T')[0];
        document.getElementById('expiration_date').value = formattedDate;
    }

    // Update profit value display
    function updateProfitValue(value) {
        document.getElementById('profitValue').textContent = value + '%';
    }

    // Reset form
    function resetForm() {
        if (confirm('Tem certeza que deseja limpar todos os campos?')) {
            document.getElementById('scannerForm').reset();
            document.getElementById('profitValue').textContent = '0%';
        }
    }

    // Form submission with progress bar
    document.getElementById('scannerForm').addEventListener('submit', function(e) {
        const scanButton = document.getElementById('scanButton');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');

        // Show progress bar
        progressContainer.classList.remove('d-none');

        // Disable button and show loading state
        scanButton.disabled = true;
        scanButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Analisando...';

        // Simulate progress
        let progress = 0;
        const interval = setInterval(() => {
            progress += 5;
            progressBar.style.width = Math.min(progress, 90) + '%';

            if (progress >= 90) {
                clearInterval(interval);
            }
        }, 100);

        // Allow form submission
        return true;
    });
</script>
</body>
</html>