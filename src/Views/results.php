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
                                <span class="badge bg-info filter-badge" onclick="filterAndSortCollar('menor_lucro')">
                                <i class="fas fa-sort-amount-down me-1"></i> Collar: Menor Lucro
                            </span>
                                <span class="badge bg-info filter-badge" onclick="filterAndSortCollar('soma_lucros')">
                                <i class="fas fa-sort-amount-down-alt me-1"></i> Collar: Soma Lucros
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
                <!-- Os resultados serão renderizados aqui pelo JavaScript -->
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
                                    <ol class="list-group list-group-numbered" id="topOperationsList">
                                        <!-- Top 5 será atualizado pelo JavaScript -->
                                    </ol>
                                </div>
                                <div class="col-md-6">
                                    <h6>Estatísticas Gerais</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Total de Operações:</td>
                                            <td class="text-end"><strong id="statsTotal"><?= count($results) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Covered Straddles:</td>
                                            <td class="text-end">
                                                <strong id="statsCovered"><?= $coveredStraddleCount ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Collars:</td>
                                            <td class="text-end">
                                                <strong id="statsCollars"><?= $collarCount ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Operações Lucrativas:</td>
                                            <td class="text-end">
                                            <span class="text-success">
                                                <strong id="statsProfitable"><?= $profitableCount ?></strong>
                                                (<?= $totalResults > 0 ? number_format(($profitableCount / $totalResults) * 100, 1) : 0 ?>%)
                                            </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Lucro Médio:</td>
                                            <td class="text-end">
                                            <span class="text-success">
                                                <strong id="statsAvgProfit"><?= number_format($avgProfit, 2, ',', '.') ?>%</strong>
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
                                                <strong id="statsAvgScore"><?= number_format($avgScore, 1) ?></strong>
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
                                                <strong id="statsAvgMso"><?= number_format($avgMso, 2, ',', '.') ?>%</strong>
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
            overflow: visible !important;
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .result-card .position-absolute.top-0 {
            z-index: 10;
        }

        .result-card .card-body {
            padding-top: 1.5rem;
        }

        .mt-n2 {
            margin-top: -12px !important;
        }
    </style>

    <script>
        // Dados originais do PHP convertidos para JavaScript
        const originalResults = <?= json_encode($results) ?>;
        let currentResults = [...originalResults];

        // Função para formatar números no padrão brasileiro
        function formatBR(num, decimals = 2) {
            if (num === null || num === undefined || isNaN(num)) {
                return '0,' + '0'.repeat(decimals);
            }
            return num.toLocaleString('pt-BR', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        }

        // Função para formatar moeda brasileira
        function formatCurrency(value) {
            return 'R$ ' + formatBR(value);
        }

        // Função para determinar a classe CSS baseada no score
        function getScoreClasses(score) {
            if (score >= 85) {
                return {
                    border: 'border-success border-2',
                    badge: 'bg-success',
                    text: 'text-success'
                };
            } else if (score >= 75) {
                return {
                    border: 'border-primary border-2',
                    badge: 'bg-primary',
                    text: 'text-primary'
                };
            } else if (score >= 60) {
                return {
                    border: 'border-info border-2',
                    badge: 'bg-info',
                    text: 'text-info'
                };
            } else if (score >= 45) {
                return {
                    border: 'border-warning border-2',
                    badge: 'bg-warning text-dark',
                    text: 'text-warning'
                };
            } else {
                return {
                    border: 'border-danger border-2',
                    badge: 'bg-danger',
                    text: 'text-danger'
                };
            }
        }

        // Função para determinar a classe CSS do MSO
        function getMsoClass(mso) {
            return mso > 10 ? 'bg-success' : (mso > 5 ? 'bg-warning' : 'bg-danger');
        }

        // Função para determinar a classe CSS do IV Percentile
        function getIvClass(iv) {
            return iv > 70 ? 'bg-success' : (iv > 40 ? 'bg-primary' : 'bg-warning');
        }

        // Função para renderizar um card individual
        function renderOperationCard(res, index) {
            const isCollar = res.strategy_type === 'collar';
            const isCoveredStraddle = !isCollar;

            // Dados básicos
            const symbol = res.symbol || 'N/A';
            const currentPrice = res.current_price || 0;
            const profitPercent = res.profit_percent || 0;
            const monthlyProfit = res.monthly_profit_percent || 0;
            const mso = res.mso || 0;
            const score = res.score || 0;
            const classification = res.classificacao || '';
            const quantity = res.quantity || (isCollar ? 100 : 1000);
            const expirationDate = res.expiration_date || 'N/A';
            const daysToMaturity = res.days_to_maturity || 0;

            // Calcular investimentos
            let totalInvestment;
            if (isCoveredStraddle) {
                const totalPremiums = ((res.call_premium || 0) + (res.put_premium || 0)) * quantity;
                const stockInvestment = currentPrice * quantity;
                const lftsInvestment = res.lfts11_investment || 0;
                totalInvestment = stockInvestment + lftsInvestment;
            } else {
                totalInvestment = currentPrice * quantity;
            }

            // Calcular lucros para Collar
            let lucroMaximoPercent = 0;
            let lucroMinimoPercent = 0;
            let lucroMaximoReal = 0;
            let lucroMinimoReal = 0;
            let somaLucros = 0;

            if (isCollar) {
                lucroMaximoPercent = res.profit_if_rise_percent || 0;
                lucroMinimoPercent = res.profit_if_fall_percent || 0;
                lucroMaximoReal = (lucroMaximoPercent / 100) * totalInvestment;
                lucroMinimoReal = (lucroMinimoPercent / 100) * totalInvestment;
                somaLucros = lucroMaximoPercent + lucroMinimoPercent;
            }

            // Classes CSS
            const scoreClasses = getScoreClasses(score);
            const msoClass = getMsoClass(mso);
            const ivClass = getIvClass(res.iv_1y_percentile || 0);

            // Determinar o menor lucro para Collar
            const menorLucro = isCollar ? Math.min(lucroMaximoPercent, lucroMinimoPercent) : profitPercent;

            // Construir o HTML do card
            return `
        <div class="col result-item"
             data-strategy="${res.strategy_type || 'covered_straddle'}"
             data-score="${score}"
             data-profit="${profitPercent}"
             data-mso="${mso}"
             data-iv="${res.iv_1y_percentile || 50}"
             ${isCollar ? `data-profit-minimo="${lucroMinimoPercent}" data-profit-maximo="${lucroMaximoPercent}" data-soma-lucros="${somaLucros}"` : ''}>

            <div class="card h-100 result-card ${scoreClasses.border}">
                <!-- Badge de Estratégia -->
                <div class="position-absolute top-0 end-0 mt-n2 me-2">
                    <span class="badge ${isCollar ? 'bg-info' : 'bg-primary'} shadow-sm">
                        ${isCollar ? 'Collar' : 'Covered Straddle'}
                    </span>
                </div>

                <!-- Badge de Ranking -->
                <div class="position-absolute top-0 start-0 mt-n2 ms-2">
                    <span class="badge bg-dark shadow-sm">#${index + 1}</span>
                </div>

                <!-- Badge de Classificação -->
                ${classification ? `
                    <div class="position-absolute top-0 start-50 translate-middle-x mt-n2">
                        <span class="badge ${score >= 85 ? 'bg-success' : (score >= 75 ? 'bg-primary' : 'bg-secondary')} shadow-sm">
                            ${classification}
                        </span>
                    </div>
                ` : ''}

                <div class="card-body">
                    <!-- Cabeçalho -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2 ${isCollar ? 'text-info' : 'text-primary'}"></i>
                                ${symbol}
                            </h5>
                            <small class="text-muted">
                                Score: <strong>${formatBR(score, 1)}</strong>
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-secondary">${expirationDate}</span>
                            <br>
                            <small class="text-muted">${daysToMaturity} dias</small>
                        </div>
                    </div>

                    <!-- Preço e Strikes -->
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Preço Atual</small>
                            <strong>${formatCurrency(currentPrice)}</strong>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted d-block">Strike(s)</small>
                            ${isCollar ? `
                                <small>CALL: ${formatCurrency(res.call_strike || res.strike_price || 0)}</small><br>
                                <small>PUT: ${formatCurrency(res.put_strike || res.strike_price || 0)}</small>
                            ` : `
                                <strong>${formatCurrency(res.strike_price || 0)}</strong>
                            `}
                        </div>
                    </div>

                    <!-- IV Percentile -->
                    ${res.iv_1y_percentile ? `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center p-2 rounded
                                    ${ivClass} bg-opacity-10">
                                <span><i class="fas fa-wind me-1"></i> IV Percentile:</span>
                                <span class="fw-bold">${formatBR(res.iv_1y_percentile, 1)}%</span>
                            </div>
                        </div>
                    ` : ''}

                    <!-- Retorno Principal -->
                    <div class="p-3 bg-light rounded mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold">Retorno Total</span>
                            <span class="badge ${scoreClasses.badge}">
                                ${formatBR(menorLucro)}%
                            </span>
                        </div>

                        ${isCollar ? `
                            <div class="d-flex justify-content-between mb-1">
                                <small>Lucro Máximo</small>
                                <small class="text-success fw-bold">
                                    ${formatCurrency(lucroMaximoReal)}
                                    (${formatBR(lucroMaximoPercent)}%)
                                </small>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small>Lucro Mínimo</small>
                                <small class="text-warning fw-bold">
                                    ${formatCurrency(lucroMinimoReal)}
                                    (${formatBR(lucroMinimoPercent)}%)
                                </small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small>Soma dos Lucros</small>
                                <small class="text-info fw-bold">
                                    ${formatBR(somaLucros)}%
                                </small>
                            </div>
                        ` : `
                            <div class="d-flex justify-content-between mb-1">
                                <small>Investimento Total</small>
                                <small class="fw-bold">${formatCurrency(totalInvestment)}</small>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <small>Retorno Mensal</small>
                                <small class="fw-bold">${formatBR(monthlyProfit)}%</small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small>Lucro Máximo</small>
                                <small class="text-success fw-bold">${formatCurrency(res.max_profit || 0)}</small>
                            </div>
                        `}
                    </div>

                    <!-- MSO e BEP -->
                    <div class="p-2 mb-3 rounded border border-info-subtle bg-info-light">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-bold">BEP / MSO</span>
                            <span class="badge ${msoClass}">
                                MSO: ${formatBR(mso)}%
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Ponto de Equilíbrio:</small>
                            <small class="fw-bold">${formatCurrency(res.bep || 0)}</small>
                        </div>
                    </div>

                    <!-- Opções -->
                    <div class="row small mb-3">
                        <div class="col-6">
                            <span class="d-block text-muted">CALL ${isCollar ? 'Vendida' : 'Vendida'}</span>
                            <span class="fw-bold">${res.call_symbol || 'N/A'}</span><br>
                            <span>Prêmio: ${formatCurrency(res.call_premium || 0)}</span>
                        </div>
                        <div class="col-6 text-end">
                            <span class="d-block text-muted">PUT ${isCollar ? 'Comprada' : 'Vendida'}</span>
                            <span class="fw-bold">${res.put_symbol || 'N/A'}</span><br>
                            <span>Prêmio: ${formatCurrency(res.put_premium || 0)}</span>
                        </div>
                    </div>

                    <!-- Informações Adicionais -->
                    <div class="row small text-muted">
                        <div class="col-6">
                            <i class="fas fa-balance-scale me-1"></i>
                            ${isCollar ?
                `CALL: ${formatBR(((res.call_strike || res.strike_price || 0) - currentPrice) / currentPrice * 100, 1)}%` :
                `Margem: ${formatBR(((res.strike_price || 0) - currentPrice) / currentPrice * 100, 1)}%`
            }
                        </div>
                        <div class="col-6 text-end">
                            <i class="fas fa-chart-pie me-1"></i>
                            Lotes: ${formatBR(quantity / 100, 1)}
                        </div>
                    </div>

                    <!-- LFTS11 (apenas Covered Straddle) -->
                    ${isCoveredStraddle && res.lfts11_investment && res.lfts11_investment > 0 ? `
                        <div class="mt-3 pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Garantias LFTS11
                                </small>
                                <span class="badge bg-info">
                                    ${formatCurrency(res.lfts11_investment)}
                                </span>
                            </div>
                        </div>
                    ` : ''}
                </div>

                <div class="card-footer bg-transparent border-top-0 d-grid">
                    <a href="/?action=details&index=${getOriginalIndex(res)}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-chart-bar me-1"></i> Análise Detalhada
                    </a>
                </div>
            </div>
        </div>
    `;
        }

        // Função para obter o índice original no array de resultados
        function getOriginalIndex(res) {
            for (let i = 0; i < originalResults.length; i++) {
                if (originalResults[i] === res) {
                    return i;
                }
            }
            return 0;
        }

        // Função para calcular estatísticas
        function calculateStats(results) {
            const stats = {
                total: results.length,
                coveredStraddleCount: 0,
                collarCount: 0,
                profitableCount: 0,
                totalProfit: 0,
                totalScore: 0,
                totalMso: 0
            };

            results.forEach(res => {
                const isCollar = res.strategy_type === 'collar';
                if (isCollar) {
                    stats.collarCount++;
                } else {
                    stats.coveredStraddleCount++;
                }

                if ((res.profit_percent || 0) > 0) {
                    stats.profitableCount++;
                }

                stats.totalProfit += res.profit_percent || 0;
                stats.totalScore += res.score || 0;
                stats.totalMso += res.mso || 0;
            });

            stats.avgProfit = stats.total > 0 ? stats.totalProfit / stats.total : 0;
            stats.avgScore = stats.total > 0 ? stats.totalScore / stats.total : 0;
            stats.avgMso = stats.total > 0 ? stats.totalMso / stats.total : 0;
            stats.profitablePercent = stats.total > 0 ? (stats.profitableCount / stats.total) * 100 : 0;

            return stats;
        }

        // Função para atualizar as estatísticas na página
        function updateStats(stats) {
            document.getElementById('statsTotal').textContent = stats.total;
            document.getElementById('statsCovered').textContent = stats.coveredStraddleCount;
            document.getElementById('statsCollars').textContent = stats.collarCount;
            document.getElementById('statsProfitable').textContent = stats.profitableCount;
            document.getElementById('statsAvgProfit').textContent = formatBR(stats.avgProfit) + '%';
            document.getElementById('statsAvgScore').textContent = formatBR(stats.avgScore, 1);
            document.getElementById('statsAvgMso').textContent = formatBR(stats.avgMso) + '%';

            // Atualizar o percentual de lucrativas
            const profitableElement = document.querySelector('.text-success strong');
            if (profitableElement) {
                profitableElement.textContent = stats.profitableCount;
            }
        }

        // Função para atualizar a lista top 5
        function updateTop5(results) {
            const top5List = document.getElementById('topOperationsList');
            if (!top5List) return;

            top5List.innerHTML = '';

            const top5 = results.slice(0, 5);

            top5.forEach((op, index) => {
                const isOpCollar = op.strategy_type === 'collar';
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-start';
                listItem.innerHTML = `
            <div class="ms-2 me-auto">
                <div class="fw-bold">${op.symbol || 'N/A'}</div>
                <span class="badge ${isOpCollar ? 'bg-info' : 'bg-primary'}">
                    ${isOpCollar ? 'Collar' : 'Covered Straddle'}
                </span>
                <div class="mt-1">
                    <small>Score: ${formatBR(op.score || 0, 1)}</small>
                </div>
            </div>
            <span class="badge bg-success rounded-pill">
                ${formatBR(op.profit_percent || 0)}%
            </span>
        `;
                top5List.appendChild(listItem);
            });
        }

        // Função para renderizar todos os resultados
        function renderResults(results) {
            const resultsGrid = document.getElementById('resultsGrid');
            if (!resultsGrid) return;

            resultsGrid.innerHTML = '';

            results.forEach((res, index) => {
                resultsGrid.innerHTML += renderOperationCard(res, index);
            });

            // Atualizar estatísticas
            const stats = calculateStats(results);
            updateStats(stats);
            updateTop5(results);
        }

        // Função para ordenar Collars
        function filterAndSortCollar(criterio) {
            // Filtrar apenas Collars
            const collarResults = originalResults.filter(r => r.strategy_type === 'collar');

            if (collarResults.length === 0) {
                showNotification('Não há operações do tipo Collar para ordenar.', 'warning');
                return;
            }

            // Ordenar conforme o critério
            if (criterio === 'menor_lucro') {
                collarResults.sort((a, b) => {
                    const menorA = Math.min(a.profit_if_rise_percent || 0, a.profit_if_fall_percent || 0);
                    const menorB = Math.min(b.profit_if_rise_percent || 0, b.profit_if_fall_percent || 0);
                    return menorB - menorA; // Ordem decrescente
                });
            } else if (criterio === 'soma_lucros') {
                collarResults.sort((a, b) => {
                    const somaA = (a.profit_if_rise_percent || 0) + (a.profit_if_fall_percent || 0);
                    const somaB = (b.profit_if_rise_percent || 0) + (b.profit_if_fall_percent || 0);
                    return somaB - somaA; // Ordem decrescente
                });
            }

            // Atualizar os resultados atuais
            currentResults = collarResults;

            // Renderizar
            renderResults(currentResults);

            showNotification(`Mostrando Collars ordenados por: ${criterio === 'menor_lucro' ? 'Menor Lucro' : 'Soma dos Lucros'}`, 'info');
        }

        // Função para filtrar resultados
        function filterResults(filterType) {
            let filteredResults = [...originalResults];

            switch(filterType) {
                case 'all':
                    // Mostrar tudo
                    break;
                case 'covered_straddle':
                    filteredResults = originalResults.filter(r => r.strategy_type === 'covered_straddle');
                    break;
                case 'collar':
                    filteredResults = originalResults.filter(r => r.strategy_type === 'collar');
                    break;
                case 'high_profit':
                    filteredResults = originalResults.filter(r => (r.profit_percent || 0) > 5);
                    break;
                case 'high_score':
                    filteredResults = originalResults.filter(r => (r.score || 0) >= 75);
                    break;
                case 'high_mso':
                    filteredResults = originalResults.filter(r => (r.mso || 0) > 10);
                    break;
                case 'high_iv':
                    filteredResults = originalResults.filter(r => (r.iv_1y_percentile || 0) > 70);
                    break;
            }

            currentResults = filteredResults;
            renderResults(currentResults);

            showNotification(`Filtro aplicado: ${getFilterName(filterType)} - Mostrando ${filteredResults.length} operações`, 'info');
        }

        // Função para obter nome do filtro
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

        // Função para limpar filtros
        function clearFilters() {
            currentResults = [...originalResults];
            renderResults(currentResults);
            showNotification('Filtros removidos - Mostrando todas as operações', 'info');
        }

        // Função para exportar resultados
        function exportAllResults() {
            const results = currentResults;

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
                    const isCollar = (res.strategy_type || 'covered_straddle') === 'collar';

                    return [
                        index + 1,
                        res.score || 0,
                        res.classificacao || '-',
                        isCollar ? 'Collar' : 'Covered Straddle',
                        res.symbol,
                        (res.current_price || 0).toFixed(2).replace('.', ','),
                        (res.strike_price || 0).toFixed(2).replace('.', ',') || '0,00',
                        isCollar ? ((res.call_strike || 0).toFixed(2).replace('.', ',') || '0,00') : (res.strike_price || 0).toFixed(2).replace('.', ',') || '0,00',
                        isCollar ? ((res.put_strike || 0).toFixed(2).replace('.', ',') || '0,00') : (res.strike_price || 0).toFixed(2).replace('.', ',') || '0,00',
                        res.expiration_date || '',
                        res.days_to_maturity || 0,
                        res.call_symbol || '',
                        (res.call_premium || 0).toFixed(2).replace('.', ',') || '0,00',
                        res.put_symbol || '',
                        (res.put_premium || 0).toFixed(2).replace('.', ',') || '0,00',
                        (res.profit_percent || 0).toFixed(2).replace('.', ',') || '0,00',
                        (res.monthly_profit_percent || 0).toFixed(2).replace('.', ',') || '0,00',
                        (res.max_profit || 0).toFixed(2).replace('.', ',') || '0,00',
                        (res.initial_investment || 0).toFixed(2).replace('.', ',') || '0,00',
                        (res.mso || 0).toFixed(2).replace('.', ',') || '0,00',
                        (res.bep || 0).toFixed(2).replace('.', ',') || '0,00',
                        (res.iv_1y_percentile || 0).toFixed(2).replace('.', ',') || '0,00',
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
            const alertContainer = document.getElementById('alertContainer') || (() => {
                const container = document.createElement('div');
                container.id = 'alertContainer';
                container.style.position = 'fixed';
                container.style.top = '20px';
                container.style.right = '20px';
                container.style.zIndex = '9999';
                container.style.pointerEvents = 'none';
                document.body.appendChild(container);
                return container;
            })();

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.style.pointerEvents = 'auto';
            alertDiv.style.marginBottom = '10px';
            alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

            alertContainer.appendChild(alertDiv);

            // Remover automaticamente após 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode === alertContainer) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Renderizar resultados iniciais
            renderResults(currentResults);

            // Inicializar tooltips do Bootstrap se existirem
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                tooltips.forEach(el => new bootstrap.Tooltip(el));
            }
        });
    </script>

<?php include __DIR__ . '/layout/footer.php'; ?>