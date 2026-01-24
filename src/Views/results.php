<?php
$results = $_SESSION['scan_results'] ?? [];
$params = $_SESSION['scan_params'] ?? [];

// Mapeamento de nomes de estratégia
$strategyNames = [
        'covered_straddle' => 'Covered Straddle',
        'collar' => 'Collar'
];
$strategyType = $params['strategy_type'] ?? 'covered_straddle';
$strategyName = $strategyNames[$strategyType] ?? 'Estratégia Desconhecida';

// Define variáveis para o header
$page_title = "Resultados do Scanner ({$strategyName}) - Options Strategy";

// Incluir header
include __DIR__ . '/layout/header.php';

// Calcular estatísticas
$totalResults = count($results);
$coveredStraddleCount = 0;
$collarCount = 0;
$profitableCount = 0;
$totalProfit = 0;
$totalInvestment = 0;

foreach ($results as $res) {
    $isCollar = ($res['strategy_type'] ?? 'covered_straddle') === 'collar';
    if ($isCollar) {
        $collarCount++;
    } else {
        $coveredStraddleCount++;
    }

    if (($res['profit_percent'] ?? 0) > 0) {
        $profitableCount++;
    }

    $totalProfit += $res['profit_percent'] ?? 0;
    $totalInvestment += $res['initial_investment'] ?? 0;
}

$avgProfit = $totalResults > 0 ? $totalProfit / $totalResults : 0;
?>

    <div class="content-wrapper">
        <!-- Cabeçalho -->
        <div class="page-header-gradient mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-2">
                        <i class="fas fa-chart-line me-2"></i>
                        Resultados do Scanner: <?= htmlspecialchars($strategyName) ?>
                    </h1>
                    <p class="mb-0 opacity-75">
                        Análise realizada em <?= date('d/m/Y H:i:s') ?>
                    </p>
                </div>
                <div class="text-end d-flex align-items-center gap-3">
                <span class="badge bg-white text-primary fs-6">
                    <i class="fas fa-sort-amount-down me-1"></i>
                    Ordenado por Score de Qualidade
                </span>
                    <a href="/?action=scan" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Voltar
                    </a>
                </div>
            </div>
        </div>

        <!-- Estatísticas Rápidas -->
        <?php if (!empty($results)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card summary-card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-chart-bar me-2"></i>
                                Resumo Geral
                            </h5>
                            <div class="quick-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?= $totalResults ?></div>
                                    <div class="stat-label">Operações</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value profit-up"><?= $profitableCount ?></div>
                                    <div class="stat-label">Lucrativas</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value profit-up"><?= number_format($avgProfit, 2, ',', '.') ?>%</div>
                                    <div class="stat-label">Lucro Médio</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $coveredStraddleCount ?></div>
                                    <div class="stat-label">Covered Straddle</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?= $collarCount ?></div>
                                    <div class="stat-label">Collars</div>
                                </div>
                            </div>

                            <!-- Filtros Rápidos -->
                            <div class="d-flex flex-wrap gap-2 mt-3">
                            <span class="badge bg-primary filter-badge" onclick="filterResults('all')">
                                <i class="fas fa-list me-1"></i> Todas (<?= $totalResults ?>)
                            </span>
                                <span class="badge bg-success filter-badge" onclick="filterResults('covered_straddle')">
                                <i class="fas fa-shield-alt me-1"></i> Covered Straddle (<?= $coveredStraddleCount ?>)
                            </span>
                                <span class="badge bg-info filter-badge" onclick="filterResults('collar')">
                                <i class="fas fa-layer-group me-1"></i> Collar (<?= $collarCount ?>)
                            </span>
                                <span class="badge bg-warning filter-badge" onclick="filterResults('high_profit')">
                                <i class="fas fa-trophy me-1"></i> Alto Lucro (>5%)
                            </span>
                                <span class="badge bg-danger filter-badge" onclick="filterResults('high_mso')">
                                <i class="fas fa-shield-alt me-1"></i> MSO Alto (>10%)
                            </span>
                                <span class="badge bg-secondary filter-badge" onclick="clearFilters()">
                                <i class="fas fa-times me-1"></i> Limpar Filtros
                            </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Mensagem de Erro -->
        <?php if (empty($results)): ?>
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <i class="fas fa-search fa-3x me-3 text-muted"></i>
                    <div>
                        <h5 class="alert-heading mb-2">Nenhum resultado encontrado</h5>
                        <p class="mb-0">
                            Não foram encontradas operações que atendam aos critérios selecionados.<br>
                            Tente ajustar os tickers, a data de vencimento ou reduzir o filtro de lucro mínimo.
                        </p>
                        <a href="/?action=scan" class="btn btn-primary mt-3">
                            <i class="fas fa-redo me-2"></i> Nova Análise
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Grid de Resultados -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4" id="resultsGrid">
                <?php foreach ($results as $index => $res): ?>
                    <?php
                    // Determinar a estratégia
                    $operationStrategy = $res['strategy_type'] ?? 'covered_straddle';
                    $isCollar = $operationStrategy === 'collar';
                    $isCoveredStraddle = !$isCollar;

                    // Score e classificação
                    $score = $res['score'] ?? 0;
                    $classification = $res['classificacao'] ?? '';

                    // Determinar cores baseadas no score
                    $cardBorderClass = '';
                    $profitBadgeClass = 'bg-secondary';

                    if ($score >= 85) {
                        $cardBorderClass = 'border-success border-2';
                        $profitBadgeClass = 'bg-success';
                    } elseif ($score >= 75) {
                        $cardBorderClass = 'border-primary border-2';
                        $profitBadgeClass = 'bg-primary';
                    } elseif ($score >= 60) {
                        $cardBorderClass = 'border-info border-2';
                        $profitBadgeClass = 'bg-info';
                    } elseif ($score >= 45) {
                        $cardBorderClass = 'border-warning border-2';
                        $profitBadgeClass = 'bg-warning text-dark';
                    } else {
                        $cardBorderClass = 'border-danger border-2';
                        $profitBadgeClass = 'bg-danger';
                    }

                    // Calcular valores
                    $quantity = $res['quantity'] ?? ($isCollar ? 100 : 1000);
                    $currentPrice = $res['current_price'] ?? 0;
                    $profitPercent = $res['profit_percent'] ?? 0;
                    $monthlyProfit = $res['monthly_profit_percent'] ?? 0;
                    $mso = $res['mso'] ?? 0;

                    if ($isCoveredStraddle) {
                        $totalPremiums = (($res['call_premium'] ?? 0) + ($res['put_premium'] ?? 0)) * $quantity;
                        $stockInvestment = $currentPrice * $quantity;
                        $lftsInvestment = $res['lfts11_investment'] ?? 0;
                        $totalInvestment = $stockInvestment + $lftsInvestment;
                    } else {
                        $callPremium = $res['call_premium'] ?? 0;
                        $putPremium = $res['put_premium'] ?? 0;
                        $callStrike = $res['call_strike'] ?? $res['strike_price'] ?? 0;
                        $putStrike = $res['put_strike'] ?? $res['strike_price'] ?? 0;
                        $stockInvestment = $currentPrice * $quantity;
                        $totalInvestment = $stockInvestment;
                    }
                    ?>

                    <div class="col result-item"
                         data-strategy="<?= $operationStrategy ?>"
                         data-score="<?= $score ?>"
                         data-profit="<?= $profitPercent ?>"
                         data-mso="<?= $mso ?>"
                         data-iv="<?= $res['iv_1y_percentile'] ?? 50 ?>">

                        <div class="card h-100 result-card <?= $cardBorderClass ?>">
                            <!-- Badge de Estratégia -->
                            <div class="position-absolute top-0 end-0 mt-n2 me-2">
                            <span class="badge <?= $isCollar ? 'bg-info' : 'bg-primary' ?> shadow-sm">
                                <?= $isCollar ? 'Collar' : 'Covered Straddle' ?>
                            </span>
                            </div>

                            <!-- Badge de Ranking -->
                            <div class="position-absolute top-0 start-0 mt-n2 ms-2">
                                <span class="badge bg-dark shadow-sm">#<?= $index + 1 ?></span>
                            </div>

                            <!-- Badge de Classificação -->
                            <?php if ($classification): ?>
                                <div class="position-absolute top-0 start-50 translate-middle-x mt-n2">
                                <span class="badge <?= $score >= 85 ? 'bg-success' : ($score >= 75 ? 'bg-primary' : 'bg-secondary') ?> shadow-sm">
                                    <?= $classification ?>
                                </span>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <!-- Cabeçalho -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-chart-line me-2 <?= $isCollar ? 'text-info' : 'text-primary' ?>"></i>
                                            <?= htmlspecialchars($res['symbol']) ?>
                                        </h5>
                                        <small class="text-muted">
                                            Score: <strong><?= number_format($score, 1) ?></strong>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary"><?= htmlspecialchars($res['expiration_date'] ?? 'N/A') ?></span>
                                        <br>
                                        <small class="text-muted"><?= $res['days_to_maturity'] ?? 0 ?> dias</small>
                                    </div>
                                </div>

                                <!-- Preço e Strikes -->
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Preço Atual</small>
                                        <strong>R$ <?= number_format($currentPrice, 2, ',', '.') ?></strong>
                                    </div>
                                    <div class="col-6 text-end">
                                        <small class="text-muted d-block">Strike(s)</small>
                                        <?php if ($isCollar): ?>
                                            <small>CALL: R$ <?= number_format($callStrike, 2, ',', '.') ?></small><br>
                                            <small>PUT: R$ <?= number_format($putStrike, 2, ',', '.') ?></small>
                                        <?php else: ?>
                                            <strong>R$ <?= number_format($res['strike_price'] ?? 0, 2, ',', '.') ?></strong>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- IV Percentile -->
                                <?php if (isset($res['iv_1y_percentile'])): ?>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center p-2 rounded
                                                <?= $res['iv_1y_percentile'] > 70 ? 'bg-success' :
                                                ($res['iv_1y_percentile'] > 40 ? 'bg-primary' : 'bg-warning') ?>
                                                bg-opacity-10">
                                            <span><i class="fas fa-wind me-1"></i> IV Percentile:</span>
                                            <span class="fw-bold"><?= number_format($res['iv_1y_percentile'], 1) ?>%</span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Retorno Principal -->
                                <div class="p-3 bg-light rounded mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold">Retorno Total</span>
                                        <span class="badge <?= $profitBadgeClass ?>">
                                        <?= number_format($profitPercent, 2, ',', '.') ?>%
                                    </span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Investimento Total</small>
                                        <small class="fw-bold">R$ <?= number_format($totalInvestment, 0, ',', '.') ?></small>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Retorno Mensal</small>
                                        <small class="fw-bold"><?= number_format($monthlyProfit, 2, ',', '.') ?>%</small>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small>Lucro Máximo</small>
                                        <small class="text-success fw-bold">R$ <?= number_format($res['max_profit'] ?? 0, 0, ',', '.') ?></small>
                                    </div>
                                </div>

                                <!-- MSO e BEP -->
                                <div class="p-2 mb-3 rounded border border-info-subtle bg-info-light">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-bold">BEP / MSO</span>
                                        <span class="badge <?= $mso > 10 ? 'bg-success' : ($mso > 5 ? 'bg-warning' : 'bg-danger') ?>">
                                        MSO: <?= number_format($mso, 2, ',', '.') ?>%
                                    </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Ponto de Equilíbrio:</small>
                                        <small class="fw-bold">R$ <?= number_format($res['bep'] ?? 0, 2, ',', '.') ?></small>
                                    </div>
                                </div>

                                <!-- Opções -->
                                <div class="row small mb-3">
                                    <div class="col-6">
                                        <span class="d-block text-muted">CALL <?= $isCollar ? 'Vendida' : 'Vendida' ?></span>
                                        <span class="fw-bold"><?= htmlspecialchars($res['call_symbol'] ?? 'N/A') ?></span><br>
                                        <span>Prêmio: R$ <?= number_format($res['call_premium'] ?? 0, 2, ',', '.') ?></span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <span class="d-block text-muted">PUT <?= $isCollar ? 'Comprada' : 'Vendida' ?></span>
                                        <span class="fw-bold"><?= htmlspecialchars($res['put_symbol'] ?? 'N/A') ?></span><br>
                                        <span>Prêmio: R$ <?= number_format($res['put_premium'] ?? 0, 2, ',', '.') ?></span>
                                    </div>
                                </div>

                                <!-- Informações Adicionais -->
                                <div class="row small text-muted">
                                    <div class="col-6">
                                        <i class="fas fa-balance-scale me-1"></i>
                                        <?php if ($isCollar): ?>
                                            CALL: <?= number_format((($callStrike - $currentPrice) / $currentPrice * 100), 1, ',', '.') ?>%
                                        <?php else: ?>
                                            Margem: <?= number_format((($res['strike_price'] ?? 0 - $currentPrice) / $currentPrice * 100), 1, ',', '.') ?>%
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6 text-end">
                                        <i class="fas fa-chart-pie me-1"></i>
                                        Lotes: <?= number_format($quantity / 100, 1, ',', '.') ?>
                                    </div>
                                </div>

                                <!-- LFTS11 (apenas Covered Straddle) -->
                                <?php if ($isCoveredStraddle && isset($res['lfts11_investment']) && $res['lfts11_investment'] > 0): ?>
                                    <div class="mt-3 pt-2 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-shield-alt me-1"></i>
                                                Garantias LFTS11
                                            </small>
                                            <span class="badge bg-info">
                                            R$ <?= number_format($res['lfts11_investment'], 0, ',', '.') ?>
                                        </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-footer bg-transparent border-top-0 d-grid">
                                <a href="/?action=details&index=<?= $index ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-chart-bar me-1"></i> Análise Detalhada
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Relatório Final -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>
                                Relatório de Análise
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Top 5 Operações por Score</h6>
                                    <ol class="list-group list-group-numbered">
                                        <?php for ($i = 0; $i < min(5, count($results)); $i++): ?>
                                            <?php
                                            $op = $results[$i];
                                            $isOpCollar = ($op['strategy_type'] ?? 'covered_straddle') === 'collar';
                                            ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div class="ms-2 me-auto">
                                                    <div class="fw-bold"><?= htmlspecialchars($op['symbol']) ?></div>
                                                    <span class="badge <?= $isOpCollar ? 'bg-info' : 'bg-primary' ?>">
                                                    <?= $isOpCollar ? 'Collar' : 'Covered Straddle' ?>
                                                </span>
                                                    <div class="mt-1">
                                                        <small>Score: <?= number_format($op['score'] ?? 0, 1) ?></small>
                                                    </div>
                                                </div>
                                                <span class="badge bg-success rounded-pill">
                                                <?= number_format($op['profit_percent'] ?? 0, 2, ',', '.') ?>%
                                            </span>
                                            </li>
                                        <?php endfor; ?>
                                    </ol>
                                </div>
                                <div class="col-md-6">
                                    <h6>Estatísticas Gerais</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Total de Operações:</td>
                                            <td class="text-end"><strong><?= count($results) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Covered Straddles:</td>
                                            <td class="text-end">
                                                <strong><?= $coveredStraddleCount ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Collars:</td>
                                            <td class="text-end">
                                                <strong><?= $collarCount ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Operações Lucrativas:</td>
                                            <td class="text-end">
                                            <span class="text-success">
                                                <strong><?= $profitableCount ?></strong>
                                                (<?= $totalResults > 0 ? number_format(($profitableCount / $totalResults) * 100, 1) : 0 ?>%)
                                            </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Lucro Médio:</td>
                                            <td class="text-end">
                                            <span class="text-success">
                                                <strong><?= number_format($avgProfit, 2, ',', '.') ?>%</strong>
                                            </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Score Médio:</td>
                                            <td class="text-end">
                                                <?php
                                                $avgScore = 0;
                                                if ($totalResults > 0) {
                                                    $totalScore = 0;
                                                    foreach ($results as $res) {
                                                        $totalScore += $res['score'] ?? 0;
                                                    }
                                                    $avgScore = $totalScore / $totalResults;
                                                }
                                                ?>
                                                <strong><?= number_format($avgScore, 1) ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>MSO Médio:</td>
                                            <td class="text-end">
                                                <?php
                                                $avgMso = 0;
                                                if ($totalResults > 0) {
                                                    $totalMso = 0;
                                                    foreach ($results as $res) {
                                                        $totalMso += $res['mso'] ?? 0;
                                                    }
                                                    $avgMso = $totalMso / $totalResults;
                                                }
                                                ?>
                                                <strong><?= number_format($avgMso, 2, ',', '.') ?>%</strong>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button class="btn btn-outline-success btn-sm" onclick="exportAllResults()">
                                    <i class="fas fa-download me-2"></i> Exportar para CSV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .filter-badge {
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-badge:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        .quick-stats {
            display: flex;
            justify-content: space-between;
            text-align: center;
        }

        .stat-item {
            flex: 1;
            padding: 10px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .stat-value.profit-up {
            color: #198754;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .result-card {
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: visible !important; /* Permitir que badges fiquem levemente para fora se necessário */
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        /* Ajuste para os badges não sobreporem o conteúdo */
        .result-card .position-absolute.top-0 {
            z-index: 10;
        }

        .result-card .card-body {
            padding-top: 1.5rem; /* Aumentar o padding superior para dar mais espaço */
        }

        .mt-n2 {
            margin-top: -12px !important;
        }
    </style>

    <script>
        // Filtros de resultados
        function filterResults(filterType) {
            const items = document.querySelectorAll('.result-item');
            let visibleCount = 0;

            items.forEach(item => {
                const strategy = item.dataset.strategy;
                const score = parseFloat(item.dataset.score);
                const profit = parseFloat(item.dataset.profit);
                const mso = parseFloat(item.dataset.mso);
                const iv = parseFloat(item.dataset.iv);

                let showItem = true;

                switch(filterType) {
                    case 'all':
                        showItem = true;
                        break;
                    case 'covered_straddle':
                        showItem = strategy === 'covered_straddle';
                        break;
                    case 'collar':
                        showItem = strategy === 'collar';
                        break;
                    case 'high_profit':
                        showItem = profit > 5;
                        break;
                    case 'high_score':
                        showItem = score >= 75;
                        break;
                    case 'high_mso':
                        showItem = mso > 10;
                        break;
                    case 'high_iv':
                        showItem = iv > 70;
                        break;
                    default:
                        showItem = true;
                }

                if (showItem) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            showNotification(`Filtro aplicado: ${getFilterName(filterType)} - Mostrando ${visibleCount} de ${items.length} operações`, 'info');
        }

        function getFilterName(filterType) {
            const filters = {
                'all': 'Todas as Operações',
                'covered_straddle': 'Covered Straddle',
                'collar': 'Collar',
                'high_profit': 'Alto Lucro (>5%)',
                'high_score': 'Alto Score (≥75)',
                'high_mso': 'Alto MSO (>10%)',
                'high_iv': 'Alta IV (>70%)'
            };
            return filters[filterType] || 'Filtro';
        }

        function clearFilters() {
            const items = document.querySelectorAll('.result-item');
            items.forEach(item => {
                item.style.display = 'block';
            });
            showNotification('Filtros removidos - Mostrando todas as operações', 'info');
        }

        // Exportação para CSV
        function exportAllResults() {
            const results = <?= json_encode($results) ?>;

            if (results.length === 0) {
                showNotification('Nenhum dado para exportar', 'warning');
                return;
            }

            // Definir cabeçalhos
            const headers = [
                'Ranking', 'Score', 'Classificação', 'Estratégia', 'Símbolo',
                'Preço Atual', 'Strike', 'CALL Strike', 'PUT Strike', 'Vencimento',
                'Dias', 'CALL', 'Prêmio CALL', 'PUT', 'Prêmio PUT', 'Retorno %',
                'Retorno Mensal %', 'Lucro Máximo', 'Investimento Total', 'MSO %',
                'BEP', 'IV Percentile', 'Data Análise'
            ];

            // Criar linhas de dados
            const csvRows = [
                headers.join(';'),
                ...results.map((res, index) => {
                    const isCollar = (res['strategy_type'] ?? 'covered_straddle') === 'collar';

                    return [
                        index + 1,
                        res.score || 0,
                        res.classificacao || '-',
                        isCollar ? 'Collar' : 'Covered Straddle',
                        res.symbol,
                        res.current_price.toFixed(2).replace('.', ','),
                        res.strike_price?.toFixed(2).replace('.', ',') || '0,00',
                        isCollar ? (res.call_strike?.toFixed(2).replace('.', ',') || '0,00') : res.strike_price?.toFixed(2).replace('.', ',') || '0,00',
                        isCollar ? (res.put_strike?.toFixed(2).replace('.', ',') || '0,00') : res.strike_price?.toFixed(2).replace('.', ',') || '0,00',
                        res.expiration_date || '',
                        res.days_to_maturity || 0,
                        res.call_symbol || '',
                        res.call_premium?.toFixed(2).replace('.', ',') || '0,00',
                        res.put_symbol || '',
                        res.put_premium?.toFixed(2).replace('.', ',') || '0,00',
                        res.profit_percent?.toFixed(2).replace('.', ',') || '0,00',
                        res.monthly_profit_percent?.toFixed(2).replace('.', ',') || '0,00',
                        res.max_profit?.toFixed(2).replace('.', ',') || '0,00',
                        res.initial_investment?.toFixed(2).replace('.', ',') || '0,00',
                        res.mso?.toFixed(2).replace('.', ',') || '0,00',
                        res.bep?.toFixed(2).replace('.', ',') || '0,00',
                        res.iv_1y_percentile?.toFixed(2).replace('.', ',') || '0,00',
                        res.analysis_date || new Date().toISOString()
                    ].join(';');
                })
            ];

            // Criar e baixar arquivo
            const csvContent = csvRows.join('\n');
            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            const now = new Date().toISOString().slice(0, 19).replace(/[:]/g, '-');
            a.href = url;
            a.download = `options_results_${now}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            showNotification('Arquivo CSV exportado com sucesso!', 'success');
        }

        // Função auxiliar para mostrar notificações
        function showNotification(message, type = 'info') {
            const alertContainer = document.createElement('div');
            alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
            alertContainer.style.pointerEvents = 'auto';
            alertContainer.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

            const container = document.getElementById('alertContainer') || document.body;
            container.appendChild(alertContainer);

            setTimeout(() => {
                alertContainer.remove();
            }, 5000);
        }
    </script>

<?php include __DIR__ . '/layout/footer.php'; ?>