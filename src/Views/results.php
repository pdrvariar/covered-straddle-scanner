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
        }
        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .profit-high { border-left-color: #28a745; }
        .profit-med { border-left-color: #ffc107; }
        .profit-low { border-left-color: #dc3545; }
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
                    </div>
                </div>

                <?php if (empty($results)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Nenhum resultado encontrado para os critérios selecionados. 
                        Tente ajustar os tickers, a data de vencimento ou verifique se o mercado está aberto.
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                        <?php foreach ($results as $index => $res): ?>
                            <?php 
                                $profitClass = '';
                                if ($res['profit_percent'] > 5) $profitClass = 'profit-high';
                                elseif ($res['profit_percent'] > 2) $profitClass = 'profit-med';
                                else $profitClass = 'profit-low';
                            ?>
                            <div class="col">
                                <div class="card h-100 result-card <?= $profitClass ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0"><?= htmlspecialchars($res['symbol']) ?></h5>
                                            <span class="badge bg-primary"><?= htmlspecialchars($res['expiration_date']) ?></span>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Preço Atual</small>
                                                <strong>R$ <?= number_format($res['current_price'], 2, ',', '.') ?></strong>
                                            </div>
                                            <div class="col-6 text-end">
                                                <small class="text-muted d-block">Strike</small>
                                                <strong>R$ <?= number_format($res['strike'], 2, ',', '.') ?></strong>
                                            </div>
                                        </div>

                                        <div class="p-3 bg-light rounded mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Lucro Máximo</span>
                                                <span class="text-success fw-bold">R$ <?= number_format($res['max_profit'], 2, ',', '.') ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Retorno</span>
                                                <span class="text-success fw-bold"><?= number_format($res['profit_percent'], 2, ',', '.') ?>%</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Taxa Anualizada</span>
                                                <span class="fw-bold"><?= number_format($res['annual_profit_percent'], 2, ',', '.') ?>%</span>
                                            </div>
                                        </div>

                                        <div class="row small mb-3">
                                            <div class="col-6">
                                                <span class="d-block text-muted">CALL: <?= htmlspecialchars($res['call_symbol']) ?></span>
                                                <span>Premio: R$ <?= number_format($res['call_premium'], 2, ',', '.') ?></span>
                                            </div>
                                            <div class="col-6 text-end">
                                                <span class="d-block text-muted">PUT: <?= htmlspecialchars($res['put_symbol']) ?></span>
                                                <span>Premio: R$ <?= number_format($res['put_premium'], 2, ',', '.') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0 d-grid">
                                        <a href="/?action=details&index=<?= $index ?>" class="btn btn-outline-primary btn-sm">
                                            Ver Detalhes
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
