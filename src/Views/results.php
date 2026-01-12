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
    </style>
</head>
<body class="bg-light">
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/layout/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Resultados do Escaneamento</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="/?action=scan" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Novo Scan
                    </a>
                    <?php if (!empty($results)): ?>
                        <button class="btn btn-sm btn-outline-success" onclick="exportToCSV()">
                            <i class="fas fa-download"></i> Exportar CSV
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (empty($results)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum resultado encontrado para os critérios selecionados.
                    Tente ajustar os tickers, a data de vencimento ou verifique se o mercado está aberto.
                </div>
            <?php else: ?>
                <div class="alert alert-success d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-sort-amount-down me-2"></i>
                        <strong><?= count($results) ?></strong> operações encontradas, <strong>ordenadas da mais lucrativa para a menos lucrativa</strong>.
                    </div>
                    <div>
                        <small class="text-muted">Vencimento: <?= htmlspecialchars($params['expiration_date'] ?? '') ?></small>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
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
                        ?>
                        <div class="col">
                            <div class="card h-100 result-card <?= $profitClass ?>">
                                <div class="ranking-badge">
                                    #<?= $index + 1 ?>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h5 class="card-title mb-0"><?= htmlspecialchars($res['symbol']) ?></h5>
                                            <small class="text-muted">Ranking: <?= $index + 1 ?> de <?= count($results) ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary"><?= htmlspecialchars($res['expiration_date']) ?></span>
                                            <br>
                                            <small class="text-muted"><?= $res['days_to_maturity'] ?> dias</small>
                                        </div>
                                    </div>

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

                                    <div class="p-3 bg-light rounded mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold">Retorno Total</span>
                                            <span class="badge <?= $profitBadgeClass ?> profit-badge">
                                                    <?= number_format($res['profit_percent'], 2, ',', '.') ?>%
                                                </span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>Lucro Máximo</small>
                                            <small class="text-success fw-bold">R$ <?= number_format($res['max_profit'], 2, ',', '.') ?></small>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>Retorno Mensal</small>
                                            <small class="fw-bold"><?= number_format($res['monthly_profit_percent'], 2, ',', '.') ?>%</small>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small>Taxa Anualizada</small>
                                            <small class="fw-bold"><?= number_format($res['annual_profit_percent'], 2, ',', '.') ?>%</small>
                                        </div>
                                    </div>

                                    <div class="row small mb-3">
                                        <div class="col-6">
                                            <span class="d-block text-muted">CALL:</span>
                                            <span class="fw-bold"><?= htmlspecialchars($res['call_symbol']) ?></span><br>
                                            <span>Prêmio: R$ <?= number_format($res['call_premium'], 2, ',', '.') ?></span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span class="d-block text-muted">PUT:</span>
                                            <span class="fw-bold"><?= htmlspecialchars($res['put_symbol']) ?></span><br>
                                            <span>Prêmio: R$ <?= number_format($res['put_premium'], 2, ',', '.') ?></span>
                                        </div>
                                    </div>

                                    <!-- Informações adicionais -->
                                    <div class="row small text-muted">
                                        <div class="col-6">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            Investimento: R$ <?= number_format($res['initial_investment'], 2, ',', '.') ?>
                                        </div>
                                        <div class="col-6 text-end">
                                            <i class="fas fa-chart-line me-1"></i>
                                            SELIC: <?= number_format($res['selic_annual'] * 100, 2, ',', '.') ?>%
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent border-top-0 d-grid">
                                    <a href="/?action=details&index=<?= $index ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i> Ver Detalhes Completos
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Resumo estatístico -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Resumo dos Resultados (Ordenados por Lucro)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">Maior Lucro (1º)</small>
                                        <h4 class="text-success"><?= isset($results[0]) ? number_format($results[0]['profit_percent'], 2, ',', '.') . '%' : 'N/A' ?></h4>
                                        <small><?= isset($results[0]) ? htmlspecialchars($results[0]['symbol']) : '' ?></small>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">Lucro Médio</small>
                                        <h4 class="text-primary">
                                            <?= number_format(array_sum(array_column($results, 'profit_percent')) / count($results), 2, ',', '.') ?>%
                                        </h4>
                                        <small>entre <?= count($results) ?> operações</small>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">Menor Lucro (<?= count($results) ?>º)</small>
                                        <h4 class="text-warning">
                                            <?= isset($results[count($results)-1]) ? number_format($results[count($results)-1]['profit_percent'], 2, ',', '.') . '%' : 'N/A' ?>
                                        </h4>
                                        <small><?= isset($results[count($results)-1]) ? htmlspecialchars($results[count($results)-1]['symbol']) : '' ?></small>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">Operações Rentáveis</small>
                                        <?php
                                        $profitable = array_filter($results, fn($op) => $op['profit_percent'] > 0);
                                        $profitablePercent = count($results) > 0 ? round(count($profitable) / count($results) * 100, 1) : 0;
                                        ?>
                                        <h4 class="<?= $profitablePercent >= 50 ? 'text-success' : 'text-danger' ?>">
                                            <?= $profitablePercent ?>%
                                        </h4>
                                        <small><?= count($profitable) ?> de <?= count($results) ?> operações</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function exportToCSV() {
        // Implementar exportação para CSV
        alert('Funcionalidade de exportação em desenvolvimento.');
    }
</script>
</body>
</html>