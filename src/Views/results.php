<?php
$results = $_SESSION['scan_results'] ?? [];
$params = $_SESSION['scan_params'] ?? [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados do Scanner - Covered Straddle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .result-card {
            transition: transform 0.2s;
            border-left: 5px solid #1f77b4;
            position: relative;
            height: 100%;
        }
        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .profit-high { border-left-color: #28a745; }
        .profit-med { border-left-color: #ffc107; }
        .profit-low { border-left-color: #dc3545; }
        .ranking-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffc107;
            color: #000;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .profit-badge {
            font-size: 0.9rem;
            padding: 0.25rem 0.5rem;
        }
        .lfts11-badge {
            background: linear-gradient(135deg, #1f77b4 0%, #2c3e50 100%);
            color: white;
            font-size: 0.8rem;
        }
        .summary-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            border-radius: 10px;
        }
        .quick-stats {
            display: flex;
            justify-content: space-between;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .stat-item {
            text-align: center;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .profit-up { color: #28a745; }
        .profit-down { color: #dc3545; }
        .export-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .filter-badge {
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-badge:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }
        .result-header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/layout/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <!-- Cabeçalho -->
            <div class="result-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-2">
                            <i class="fas fa-chart-line me-2"></i>
                            Resultados do Scanner
                        </h1>
                        <p class="mb-0 opacity-75">
                            Análise realizada em <?= date('d/m/Y H:i:s') ?>
                        </p>
                    </div>
                    <div class="text-end">
                            <span class="badge bg-info fs-6">
                                <i class="fas fa-sort-amount-down me-1"></i>
                                Ordenado por Lucro
                            </span>
                    </div>
                </div>
            </div>

            <!-- Estatísticas Rápidas -->
            <?php if (!empty($results)): ?>
                <?php
                $totalResults = count($results);
                $profitableResults = array_filter($results, fn($op) => ($op['profit_percent'] ?? 0) > 0);
                $avgProfit = array_sum(array_column($results, 'profit_percent')) / $totalResults;
                $maxProfit = max(array_column($results, 'profit_percent'));
                $minProfit = min(array_column($results, 'profit_percent'));
                $avgDays = array_sum(array_column($results, 'days_to_maturity')) / $totalResults;
                $totalLfts11Investment = array_sum(array_column($results, 'lfts11_investment'));
                ?>

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
                                        <div class="stat-value profit-up"><?= count($profitableResults) ?></div>
                                        <div class="stat-label">Lucrativas</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value profit-up"><?= number_format($avgProfit, 2, ',', '.') ?>%</div>
                                        <div class="stat-label">Lucro Médio</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?= number_format($avgDays, 1, ',', '.') ?>d</div>
                                        <div class="stat-label">Dias Médios</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value">R$ <?= number_format($totalLfts11Investment, 0, ',', '.') ?></div>
                                        <div class="stat-label">Total LFTS11</div>
                                    </div>
                                </div>

                                <!-- Filtros Rápidos -->
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                        <span class="badge bg-primary filter-badge" onclick="filterResults('all')">
                                            <i class="fas fa-list me-1"></i> Todas (<?= $totalResults ?>)
                                        </span>
                                    <span class="badge bg-success filter-badge" onclick="filterResults('high-profit')">
                                            <i class="fas fa-trophy me-1"></i> Alto Lucro (>5%)
                                        </span>
                                    <span class="badge bg-info filter-badge" onclick="filterResults('short-term')">
                                            <i class="fas fa-bolt me-1"></i> Curto Prazo (<30d)
                                        </span>
                                    <span class="badge bg-warning filter-badge" onclick="filterResults('lfts11-low')">
                                            <i class="fas fa-shield-alt me-1"></i> Baixa Garantia
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
                        // Determinar classe de cor baseada no percentual de lucro
                        $profitClass = '';
                        if ($res['profit_percent'] > 8) $profitClass = 'profit-high';
                        elseif ($res['profit_percent'] > 4) $profitClass = 'profit-med';
                        else $profitClass = 'profit-low';

                        // Determinar badge de lucro
                        $profitBadgeClass = 'bg-secondary';
                        if ($res['profit_percent'] > 10) $profitBadgeClass = 'bg-success';
                        elseif ($res['profit_percent'] > 5) $profitBadgeClass = 'bg-primary';
                        elseif ($res['profit_percent'] > 2) $profitBadgeClass = 'bg-warning text-dark';
                        else $profitBadgeClass = 'bg-danger';

                        // Calcular valores adicionais
                        $totalPremiums = ($res['call_premium'] + $res['put_premium']) * ($res['quantity'] ?? 1000);
                        $stockInvestment = $res['current_price'] * ($res['quantity'] ?? 1000);
                        $totalInvestment = $res['initial_investment'] ?? 0;
                        $lfts11Percent = $totalInvestment > 0 ? ($res['lfts11_investment'] / $totalInvestment * 100) : 0;
                        ?>
                        <div class="col result-item"
                             data-profit="<?= $res['profit_percent'] ?>"
                             data-days="<?= $res['days_to_maturity'] ?>"
                             data-lfts11="<?= $lfts11Percent ?>">
                            <div class="card h-100 result-card <?= $profitClass ?>">
                                <div class="ranking-badge">
                                    #<?= $index + 1 ?>
                                </div>
                                <div class="card-body">
                                    <!-- Cabeçalho do Card -->
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-chart-line me-2 text-primary"></i>
                                                <?= htmlspecialchars($res['symbol']) ?>
                                            </h5>
                                            <small class="text-muted">
                                                Ranking: <?= $index + 1 ?> de <?= count($results) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary"><?= htmlspecialchars($res['expiration_date']) ?></span>
                                            <br>
                                            <small class="text-muted"><?= $res['days_to_maturity'] ?> dias</small>
                                        </div>
                                    </div>

                                    <!-- Informações de Preço -->
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Preço Atual</small>
                                            <strong>R$ <?= number_format($res['current_price'], 2, ',', '.') ?></strong>
                                        </div>
                                        <div class="col-6 text-end">
                                            <small class="text-muted d-block">Strike</small>
                                            <strong>R$ <?= number_format($res['strike_price'], 2, ',', '.') ?></strong>
                                        </div>
                                    </div>

                                    <!-- Retorno Principal -->
                                    <div class="p-3 bg-light rounded mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold">Retorno Total</span>
                                            <span class="badge <?= $profitBadgeClass ?> profit-badge">
                                                    <?= number_format($res['profit_percent'], 2, ',', '.') ?>%
                                                </span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>Investimento Total</small>
                                            <small class="fw-bold">R$ <?= number_format($totalInvestment, 0, ',', '.') ?></small>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>Lucro Máximo</small>
                                            <small class="text-success fw-bold">R$ <?= number_format($res['max_profit'], 0, ',', '.') ?></small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small>Retorno Mensal</small>
                                            <small class="fw-bold"><?= number_format($res['monthly_profit_percent'], 2, ',', '.') ?>%</small>
                                        </div>
                                    </div>

                                    <!-- Opções -->
                                    <div class="row small mb-3">
                                        <div class="col-6">
                                            <span class="d-block text-muted">CALL Vendida:</span>
                                            <span class="fw-bold"><?= htmlspecialchars($res['call_symbol']) ?></span><br>
                                            <span>Prêmio: R$ <?= number_format($res['call_premium'], 2, ',', '.') ?></span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span class="d-block text-muted">PUT Vendida:</span>
                                            <span class="fw-bold"><?= htmlspecialchars($res['put_symbol']) ?></span><br>
                                            <span>Prêmio: R$ <?= number_format($res['put_premium'], 2, ',', '.') ?></span>
                                        </div>
                                    </div>

                                    <!-- Garantias LFTS11 -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">
                                                <i class="fas fa-shield-alt me-1"></i>
                                                Garantias LFTS11
                                            </small>
                                            <span class="badge lfts11-badge">
                                                    R$ <?= number_format($res['lfts11_investment'] ?? 0, 0, ',', '.') ?>
                                                </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <?php
                                            $lfts11Percent = $totalInvestment > 0 ? ($res['lfts11_investment'] / $totalInvestment * 100) : 0;
                                            ?>
                                            <div class="progress-bar"
                                                 style="width: <?= min(100, $lfts11Percent) ?>%; background-color: #1f77b4;">
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small>
                                                <?= number_format($res['lfts11_quantity'] ?? 0, 0, ',', '.') ?> cotas
                                            </small>
                                            <small>
                                                <?= number_format($res['selic_annual'] * 100, 2, ',', '.') ?>% a.a.
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Informações de Risco -->
                                    <div class="row small text-muted">
                                        <div class="col-6">
                                            <i class="fas fa-balance-scale me-1"></i>
                                            Margem: <?= number_format((($res['strike_price'] - $res['current_price']) / $res['current_price'] * 100), 1, ',', '.') ?>%
                                        </div>
                                        <div class="col-6 text-end">
                                            <i class="fas fa-chart-pie me-1"></i>
                                            Lotes: <?= number_format(($res['quantity'] ?? 1000) / 100, 1, ',', '.') ?>
                                        </div>
                                    </div>
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
                                        <h6>Top 3 Operações</h6>
                                        <ol class="list-group list-group-numbered">
                                            <?php for ($i = 0; $i < min(3, count($results)); $i++): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                                    <div class="ms-2 me-auto">
                                                        <div class="fw-bold"><?= htmlspecialchars($results[$i]['symbol']) ?></div>
                                                        Strike: R$ <?= number_format($results[$i]['strike_price'], 2, ',', '.') ?>
                                                    </div>
                                                    <span class="badge bg-success rounded-pill">
                                                            <?= number_format($results[$i]['profit_percent'], 2, ',', '.') ?>%
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
                                                <td>Operações Lucrativas:</td>
                                                <td class="text-end">
                                                        <span class="text-success">
                                                            <strong><?= count($profitableResults) ?></strong>
                                                            (<?= round(count($profitableResults) / count($results) * 100, 1) ?>%)
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
                                                <td>Investimento LFTS11 Total:</td>
                                                <td class="text-end">
                                                    <strong>R$ <?= number_format($totalLfts11Investment, 0, ',', '.') ?></strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Período Médio:</td>
                                                <td class="text-end">
                                                    <strong><?= number_format($avgDays, 1, ',', '.') ?> dias</strong>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Relatório gerado automaticamente. As operações são ordenadas por retorno percentual esperado (maior para menor).
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Botão de Exportação Fixo -->
            <?php if (!empty($results)): ?>
                <button class="btn btn-success export-btn" onclick="exportAllResults()" data-bs-toggle="tooltip" data-bs-placement="left" title="Exportar todos os resultados">
                    <i class="fas fa-download me-2"></i> Exportar CSV
                </button>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Filtros de resultados
    function filterResults(filterType) {
        const items = document.querySelectorAll('.result-item');
        let visibleCount = 0;

        items.forEach(item => {
            const profit = parseFloat(item.dataset.profit);
            const days = parseInt(item.dataset.days);
            const lfts11Percent = parseFloat(item.dataset.lfts11);
            let showItem = true;

            switch(filterType) {
                case 'all':
                    showItem = true;
                    break;
                case 'high-profit':
                    showItem = profit > 5;
                    break;
                case 'short-term':
                    showItem = days < 30;
                    break;
                case 'lfts11-low':
                    showItem = lfts11Percent < 50;
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

        // Atualizar contador
        showAlert(`Filtro aplicado: ${getFilterName(filterType)} - Mostrando ${visibleCount} de ${items.length} operações`, 'info');
    }

    function getFilterName(filterType) {
        const filters = {
            'all': 'Todas as Operações',
            'high-profit': 'Alto Lucro (>5%)',
            'short-term': 'Curto Prazo (<30 dias)',
            'lfts11-low': 'Baixa Garantia LFTS11'
        };
        return filters[filterType] || 'Filtro';
    }

    function clearFilters() {
        const items = document.querySelectorAll('.result-item');
        items.forEach(item => {
            item.style.display = 'block';
        });
        showAlert('Filtros removidos - Mostrando todas as operações', 'info');
    }

    // Exportação para CSV
    function exportAllResults() {
        const results = <?= json_encode($results) ?>;

        if (results.length === 0) {
            showAlert('Nenhum dado para exportar', 'warning');
            return;
        }

        // Definir cabeçalhos
        const headers = [
            'Ranking', 'Símbolo', 'Preço Atual', 'Strike', 'Vencimento',
            'Dias', 'CALL', 'Prêmio CALL', 'PUT', 'Prêmio PUT',
            'Retorno %', 'Lucro Máximo', 'Investimento Total',
            'LFTS11 Investimento', 'LFTS11 Cotas', 'SELIC %', 'Data Análise'
        ];

        // Criar linhas de dados
        const csvRows = [
            headers.join(';'),
            ...results.map((res, index) => [
                index + 1,
                res.symbol,
                res.current_price.toFixed(2).replace('.', ','),
                res.strike_price.toFixed(2).replace('.', ','),
                res.expiration_date,
                res.days_to_maturity,
                res.call_symbol,
                res.call_premium.toFixed(2).replace('.', ','),
                res.put_symbol,
                res.put_premium.toFixed(2).replace('.', ','),
                res.profit_percent.toFixed(2).replace('.', ','),
                res.max_profit.toFixed(2).replace('.', ','),
                res.initial_investment.toFixed(2).replace('.', ','),
                (res.lfts11_investment || 0).toFixed(2).replace('.', ','),
                (res.lfts11_quantity || 0),
                (res.selic_annual * 100).toFixed(2).replace('.', ','),
                res.analysis_date || new Date().toISOString()
            ].join(';'))
        ];

        // Criar e baixar arquivo
        const csvContent = csvRows.join('\n');
        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const now = new Date().toISOString().slice(0, 19).replace(/[:]/g, '-');
        a.href = url;
        a.download = `straddle_results_${now}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showAlert('Arquivo CSV exportado com sucesso!', 'success');
    }

    // Função auxiliar para mostrar alertas
    function showAlert(message, type = 'info') {
        // Remover alertas existentes
        const existingAlerts = document.querySelectorAll('.custom-alert');
        existingAlerts.forEach(alert => alert.remove());

        // Criar novo alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} custom-alert alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = `
                top: 20px;
                right: 20px;
                z-index: 1050;
                min-width: 300px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
        alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

        document.body.appendChild(alertDiv);

        // Auto-remover após 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Inicializar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => new bootstrap.Tooltip(el));
    });

    // Adicionar funcionalidade de ordenação
    let currentSort = 'profit_desc';

    function sortResults(sortBy) {
        const grid = document.getElementById('resultsGrid');
        const items = Array.from(grid.querySelectorAll('.result-item'));

        items.sort((a, b) => {
            const aProfit = parseFloat(a.dataset.profit);
            const bProfit = parseFloat(b.dataset.profit);
            const aDays = parseInt(a.dataset.days);
            const bDays = parseInt(b.dataset.days);

            switch(sortBy) {
                case 'profit_desc':
                    return bProfit - aProfit;
                case 'profit_asc':
                    return aProfit - bProfit;
                case 'days_asc':
                    return aDays - bDays;
                case 'days_desc':
                    return bDays - aDays;
                default:
                    return 0;
            }
        });

        // Reordenar os itens no grid
        items.forEach(item => grid.appendChild(item));

        // Atualizar ranking
        const newItems = grid.querySelectorAll('.result-item');
        newItems.forEach((item, index) => {
            const badge = item.querySelector('.ranking-badge');
            if (badge) {
                badge.textContent = `#${index + 1}`;
            }
        });

        currentSort = sortBy;
        showAlert(`Resultados ordenados por ${getSortName(sortBy)}`, 'info');
    }

    function getSortName(sortBy) {
        const sorts = {
            'profit_desc': 'Maior Lucro',
            'profit_asc': 'Menor Lucro',
            'days_asc': 'Menor Prazo',
            'days_desc': 'Maior Prazo'
        };
        return sorts[sortBy] || 'Lucro';
    }
</script>
</body>
</html>