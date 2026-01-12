<?php
$operation = $operation ?? null;
if (!$operation) {
    header('Location: /?action=scan');
    exit;
}

// Garantir que todas as chaves necessárias existam
$operation['strike_price'] = $operation['strike_price'] ?? $operation['strike'] ?? 0;
$operation['call_premium'] = $operation['call_premium'] ?? 0;
$operation['put_premium'] = $operation['put_premium'] ?? 0;
$operation['current_price'] = $operation['current_price'] ?? 0;
$operation['max_profit'] = $operation['max_profit'] ?? 0;
$operation['profit_percent'] = $operation['profit_percent'] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Operação - <?= htmlspecialchars($operation['symbol'] ?? 'Desconhecido') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .detail-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .detail-card-header {
            border-radius: 10px 10px 0 0 !important;
            padding: 1rem 1.5rem;
        }
        .metric-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .profit-positive {
            color: #28a745;
        }
        .profit-negative {
            color: #dc3545;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/layout/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/?action=results">Resultados</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detalhes</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Detalhes: <?= htmlspecialchars($operation['symbol'] ?? 'Desconhecido') ?></h1>
                <div>
                    <button class="btn btn-success" onclick="saveOperation()">
                        <i class="fas fa-save me-2"></i>Salvar Operação
                    </button>
                    <a href="/?action=results" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Card 1: Ativo e Vencimento -->
                <div class="col-md-4">
                    <div class="card detail-card">
                        <div class="card-header bg-primary text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>
                                Ativo e Vencimento
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Símbolo</small>
                                <h4 class="mb-0"><?= htmlspecialchars($operation['symbol'] ?? 'N/A') ?></h4>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Preço Atual</small>
                                <p class="metric-value mb-0">
                                    R$ <?= number_format($operation['current_price'], 2, ',', '.') ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Data de Vencimento</small>
                                <p class="mb-0">
                                    <strong><?= htmlspecialchars($operation['expiration_date'] ?? 'N/A') ?></strong>
                                </p>
                            </div>
                            <div>
                                <small class="text-muted d-block">Dias até o Vencimento</small>
                                <span class="badge bg-info">
                                        <?= $operation['days_to_maturity'] ?? 0 ?> dias
                                    </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Estrutura do Straddle -->
                <div class="col-md-4">
                    <div class="card detail-card">
                        <div class="card-header bg-success text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-sitemap me-2"></i>
                                Estrutura do Straddle
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Strike Price</small>
                                <p class="metric-value mb-0">
                                    R$ <?= number_format($operation['strike_price'], 2, ',', '.') ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">CALL (Venda)</small>
                                <p class="mb-1">
                                    <strong><?= htmlspecialchars($operation['call_symbol'] ?? 'N/A') ?></strong>
                                </p>
                                <p class="mb-0">
                                    Prêmio: R$ <?= number_format($operation['call_premium'], 2, ',', '.') ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">PUT (Venda)</small>
                                <p class="mb-1">
                                    <strong><?= htmlspecialchars($operation['put_symbol'] ?? 'N/A') ?></strong>
                                </p>
                                <p class="mb-0">
                                    Prêmio: R$ <?= number_format($operation['put_premium'], 2, ',', '.') ?>
                                </p>
                            </div>
                            <div>
                                <small class="text-muted d-block">Prêmio Total</small>
                                <p class="mb-0">
                                    <strong>R$ <?= number_format(($operation['call_premium'] + $operation['put_premium']), 2, ',', '.') ?></strong>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Métricas de Desempenho -->
                <div class="col-md-4">
                    <div class="card detail-card">
                        <div class="card-header bg-info text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calculator me-2"></i>
                                Métricas de Desempenho
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Lucro Máximo</small>
                                <p class="metric-value mb-0 text-success">
                                    R$ <?= number_format($operation['max_profit'], 2, ',', '.') ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Retorno Percentual</small>
                                <p class="metric-value mb-0 <?= ($operation['profit_percent'] >= 0) ? 'profit-positive' : 'profit-negative' ?>">
                                    <?= number_format($operation['profit_percent'], 2, ',', '.') ?>%
                                </p>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Ponto de Equilíbrio (BEP)</small>
                                <p class="metric-value mb-0">
                                    <?php
                                    $bep = $operation['strike_price'] - ($operation['call_premium'] + $operation['put_premium']);
                                    ?>
                                    R$ <?= number_format($bep, 2, ',', '.') ?>
                                </p>
                            </div>
                            <div>
                                <small class="text-muted d-block">Margem de Proteção</small>
                                <p class="mb-0">
                                    <?php
                                    $protection = (($operation['strike_price'] / $operation['current_price']) - 1) * 100;
                                    ?>
                                    <span class="badge bg-<?= $protection > 0 ? 'success' : 'danger' ?>">
                                            <?= number_format($protection, 2, ',', '.') ?>%
                                        </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Informações Adicionais -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card detail-card">
                        <div class="card-header bg-warning text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Informações Adicionais
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Investimento Inicial</small>
                                    <p class="mb-0">
                                        R$ <?= number_format($operation['initial_investment'] ?? 0, 2, ',', '.') ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Retorno Mensal</small>
                                    <p class="mb-0">
                                        <?= number_format($operation['monthly_profit_percent'] ?? 0, 2, ',', '.') ?>%
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Taxa SELIC</small>
                                    <p class="mb-0">
                                        <?= number_format(($operation['selic_annual'] ?? 0) * 100, 2, ',', '.') ?>%
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Data da Análise</small>
                                    <p class="mb-0">
                                        <?= htmlspecialchars($operation['analysis_date'] ?? date('Y-m-d H:i:s')) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nota Importante -->
            <div class="alert alert-warning mt-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-2">Nota Importante</h5>
                        <p class="mb-0">
                            Esta é uma simulação baseada em dados de mercado. O lucro real pode variar dependendo da execução,
                            taxas e condições de mercado. Recomenda-se consultar um profissional qualificado antes de realizar
                            qualquer operação.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                    <button class="btn btn-outline-info ms-2" onclick="exportOperation()">
                        <i class="fas fa-download me-2"></i>Exportar
                    </button>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="runNewAnalysis()">
                        <i class="fas fa-redo me-2"></i>Nova Análise
                    </button>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function saveOperation() {
        const data = <?= json_encode($operation) ?>;
        fetch('/?action=save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'operation': JSON.stringify(data)
            })
        }).then(response => {
            if (response.ok) {
                alert('Operação salva com sucesso!');
            } else {
                alert('Erro ao salvar operação.');
            }
        }).catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar operação.');
        });
    }

    function exportOperation() {
        const data = <?= json_encode($operation) ?>;
        const jsonStr = JSON.stringify(data, null, 2);
        const blob = new Blob([jsonStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `straddle_${data.symbol}_${data.expiration_date}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function runNewAnalysis() {
        window.location.href = '/?action=scan';
    }
</script>
</body>
</html>