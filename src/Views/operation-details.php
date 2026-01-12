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
$operation['quantity'] = $operation['quantity'] ?? 1000;

// Calcular valores adicionais
$totalPremiums = ($operation['call_premium'] + $operation['put_premium']) * $operation['quantity'];
$stockInvestment = $operation['current_price'] * $operation['quantity'];
$totalGuaranteeNeeded = $operation['strike_price'] * $operation['quantity'];
$bep = $operation['strike_price'] - ($operation['call_premium'] + $operation['put_premium']);

// LFTS11 data
$lfts11Data = $operation['lfts11_data'] ?? [
        'price' => 100.00,
        'symbol' => 'LFTS11',
        'name' => 'ETF Tesouro Selic',
        'has_data' => false
];
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
        .investment-breakdown {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .investment-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .investment-item:last-child {
            border-bottom: none;
        }
        .guarantee-badge {
            background: linear-gradient(135deg, #1f77b4 0%, #2c3e50 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1050;"></div>
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
                <div>
                    <h1 class="mb-1">Detalhes da Operação</h1>
                    <h3 class="text-primary"><?= htmlspecialchars($operation['symbol'] ?? 'Desconhecido') ?></h3>
                    <p class="text-muted mb-0">
                        <i class="fas fa-calendar me-1"></i>
                        Vencimento: <?= htmlspecialchars($operation['expiration_date'] ?? 'N/A') ?>
                        (<?= $operation['days_to_maturity'] ?? 0 ?> dias)
                    </p>
                </div>
                <div>
                    <button class="btn btn-success" onclick="saveOperation()">
                        <i class="fas fa-save me-2"></i>Salvar Operação
                    </button>
                    <a href="/?action=results" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>

            <!-- Resumo da Operação -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card detail-card">
                        <div class="card-header bg-primary text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Resumo da Operação de Straddle Coberto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Quantidade de Lotes</small>
                                        <h2 class="metric-value mb-0"><?= number_format($operation['quantity'] / 100, 1, ',', '.') ?></h2>
                                        <small><?= number_format($operation['quantity'], 0, ',', '.') ?> ações</small>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Retorno Esperado</small>
                                        <h2 class="metric-value profit-positive mb-0">
                                            <?= number_format($operation['profit_percent'], 2, ',', '.') ?>%
                                        </h2>
                                        <small>Total do período</small>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Retorno Mensalizado</small>
                                        <h2 class="metric-value text-info mb-0">
                                            <?= number_format($operation['monthly_profit_percent'] ?? 0, 2, ',', '.') ?>%
                                        </h2>
                                        <small>Projeção mensal</small>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Ponto de Equilíbrio</small>
                                        <h2 class="metric-value text-warning mb-0">
                                            R$ <?= number_format($bep, 2, ',', '.') ?>
                                        </h2>
                                        <small>Preço da ação</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estrutura da Operação -->
            <div class="row">
                <!-- Card 1: Compra da Ação -->
                <div class="col-md-4">
                    <div class="card detail-card h-100">
                        <div class="card-header bg-success text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-arrow-up me-2"></i>
                                Compra da Ação
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Ação</small>
                                <h4 class="mb-0"><?= htmlspecialchars($operation['symbol'] ?? 'N/A') ?></h4>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Preço por Ação</small>
                                <p class="metric-value mb-0">
                                    R$ <?= number_format($operation['current_price'], 2, ',', '.') ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Quantidade Total</small>
                                <p class="mb-0">
                                    <strong><?= number_format($operation['quantity'], 0, ',', '.') ?></strong> ações
                                </p>
                            </div>
                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Investimento Total</small>
                                <h5 class="mb-0 text-success">
                                    R$ <?= number_format($stockInvestment, 2, ',', '.') ?>
                                </h5>
                                <small><?= number_format($operation['quantity'] / 100, 1, ',', '.') ?> lotes padrão</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Venda das Opções -->
                <div class="col-md-4">
                    <div class="card detail-card h-100">
                        <div class="card-header bg-warning text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-hand-holding-usd me-2"></i>
                                Venda das Opções
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- CALL -->
                            <div class="mb-3">
                                <small class="text-muted d-block">CALL Vendida</small>
                                <p class="mb-1">
                                    <strong><?= htmlspecialchars($operation['call_symbol'] ?? 'N/A') ?></strong>
                                </p>
                                <div class="row">
                                    <div class="col-6">
                                        <small>Prêmio: R$ <?= number_format($operation['call_premium'], 2, ',', '.') ?></small>
                                    </div>
                                    <div class="col-6">
                                        <small>Strike: R$ <?= number_format($operation['strike_price'], 2, ',', '.') ?></small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small>Receita Total:
                                        <strong>R$ <?= number_format($operation['call_premium'] * $operation['quantity'], 2, ',', '.') ?></strong>
                                    </small>
                                </div>
                            </div>

                            <hr>

                            <!-- PUT -->
                            <div class="mb-3">
                                <small class="text-muted d-block">PUT Vendida</small>
                                <p class="mb-1">
                                    <strong><?= htmlspecialchars($operation['put_symbol'] ?? 'N/A') ?></strong>
                                </p>
                                <div class="row">
                                    <div class="col-6">
                                        <small>Prêmio: R$ <?= number_format($operation['put_premium'], 2, ',', '.') ?></small>
                                    </div>
                                    <div class="col-6">
                                        <small>Strike: R$ <?= number_format($operation['strike_price'], 2, ',', '.') ?></small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small>Receita Total:
                                        <strong>R$ <?= number_format($operation['put_premium'] * $operation['quantity'], 2, ',', '.') ?></strong>
                                    </small>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded mt-3">
                                <small class="text-muted d-block">Prêmios Recebidos</small>
                                <h5 class="mb-0 text-warning">
                                    R$ <?= number_format($totalPremiums, 2, ',', '.') ?>
                                </h5>
                                <small>Reduz o investimento inicial</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Garantias com LFTS11 -->
                <div class="col-md-4">
                    <div class="card detail-card h-100">
                        <div class="card-header guarantee-badge detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt me-2"></i>
                                Garantias (LFTS11)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <small class="text-muted d-block">Título de Garantia</small>
                                <h5 class="mb-0"><?= htmlspecialchars($lfts11Data['name']) ?></h5>
                                <small class="text-muted"><?= htmlspecialchars($lfts11Data['symbol']) ?></small>
                                <?php if (!$lfts11Data['has_data']): ?>
                                    <span class="badge bg-warning text-dark ms-2">Dados estimados</span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Preço por Cota</small>
                                <p class="mb-0">
                                    <strong>R$ <?= number_format($lfts11Data['price'], 2, ',', '.') ?></strong>
                                </p>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Garantia Necessária</small>
                                <p class="mb-0">
                                    Para cobrir <?= number_format($operation['quantity'], 0, ',', '.') ?> PUTs de strike R$ <?= number_format($operation['strike_price'], 2, ',', '.') ?>
                                </p>
                                <h5 class="text-primary mt-1">
                                    R$ <?= number_format($totalGuaranteeNeeded, 2, ',', '.') ?>
                                </h5>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Cotias de LFTS11 Necessárias</small>
                                <p class="mb-0">
                                    <strong><?= number_format($operation['lfts11_quantity'] ?? 0, 0, ',', '.') ?></strong> cotas
                                </p>
                            </div>

                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Investimento em Garantias</small>
                                <h5 class="mb-0 text-info">
                                    R$ <?= number_format($operation['lfts11_investment'] ?? 0, 2, ',', '.') ?>
                                </h5>
                                <small>Rende <?= number_format($operation['selic_annual'] * 100, 2, ',', '.') ?>% a.a.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Análise Financeira Detalhada -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card detail-card">
                        <div class="card-header bg-info text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-calculator me-2"></i>
                                Análise Financeira
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="investment-breakdown">
                                <h6 class="mb-3">Detalhamento do Investimento</h6>

                                <div class="investment-item d-flex justify-content-between">
                                    <span>Compra de Ações:</span>
                                    <span class="text-danger">- R$ <?= number_format($stockInvestment, 2, ',', '.') ?></span>
                                </div>

                                <div class="investment-item d-flex justify-content-between">
                                    <span>Investimento em LFTS11:</span>
                                    <span class="text-danger">- R$ <?= number_format($operation['lfts11_investment'] ?? 0, 2, ',', '.') ?></span>
                                </div>

                                <div class="investment-item d-flex justify-content-between">
                                    <span>Prêmios Recebidos (CALL + PUT):</span>
                                    <span class="text-success">+ R$ <?= number_format($totalPremiums, 2, ',', '.') ?></span>
                                </div>

                                <div class="investment-item d-flex justify-content-between">
                                    <span>Retorno do LFTS11 (<?= $operation['days_to_maturity'] ?> dias):</span>
                                    <span class="text-success">+ R$ <?= number_format($operation['lfts11_return'] ?? 0, 2, ',', '.') ?></span>
                                </div>

                                <hr>

                                <div class="investment-item d-flex justify-content-between">
                                    <strong>Investimento Líquido Inicial:</strong>
                                    <strong class="text-primary">R$ <?= number_format($operation['initial_investment'], 2, ',', '.') ?></strong>
                                </div>

                                <div class="investment-item d-flex justify-content-between">
                                    <strong>Lucro Máximo Esperado:</strong>
                                    <strong class="text-success">R$ <?= number_format($operation['max_profit'], 2, ',', '.') ?></strong>
                                </div>

                                <div class="investment-item d-flex justify-content-between">
                                    <strong>Prejuízo Máximo:</strong>
                                    <strong class="text-danger">R$ <?= number_format($operation['max_loss'], 2, ',', '.') ?></strong>
                                </div>
                            </div>

                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    O investimento em LFTS11 garante que você terá recursos para honrar a PUT vendida caso seja exercida.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Análise de Risco -->
                <div class="col-md-6">
                    <div class="card detail-card">
                        <div class="card-header bg-dark text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>
                                Análise de Risco e Retorno
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Margem de Segurança</small>
                                        <?php
                                        $marginSafety = (($operation['strike_price'] - $operation['current_price']) / $operation['current_price']) * 100;
                                        $marginClass = $marginSafety > 0 ? 'text-success' : 'text-danger';
                                        ?>
                                        <h4 class="<?= $marginClass ?> mb-0">
                                            <?= number_format($marginSafety, 2, ',', '.') ?>%
                                        </h4>
                                        <small>Strike vs Preço Atual</small>
                                    </div>

                                    <div class="mb-3">
                                        <small class="text-muted d-block">Yield dos Prêmios</small>
                                        <?php
                                        $premiumYield = ($totalPremiums / ($stockInvestment + ($operation['lfts11_investment'] ?? 0))) * 100;
                                        ?>
                                        <h4 class="text-warning mb-0">
                                            <?= number_format($premiumYield, 2, ',', '.') ?>%
                                        </h4>
                                        <small>Retorno dos prêmios</small>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Retorno Anualizado</small>
                                        <h4 class="text-primary mb-0">
                                            <?= number_format($operation['annual_profit_percent'] ?? 0, 2, ',', '.') ?>%
                                        </h4>
                                        <small>Projeção anual</small>
                                    </div>

                                    <div class="mb-3">
                                        <small class="text-muted d-block">Proteção de Queda</small>
                                        <?php
                                        $downsideProtection = (($operation['strike_price'] / $operation['current_price']) - 1) * 100;
                                        $protectionClass = $downsideProtection > 0 ? 'text-success' : 'text-danger';
                                        ?>
                                        <h4 class="<?= $protectionClass ?> mb-0">
                                            <?= number_format($downsideProtection, 2, ',', '.') ?>%
                                        </h4>
                                        <small>Até o strike</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <h6>Pontos de Equilíbrio (BEP):</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if (!empty($operation['breakevens'])): ?>
                                        <?php foreach ($operation['breakevens'] as $bep): ?>
                                            <span class="badge bg-info">
                                                    R$ <?= number_format($bep, 2, ',', '.') ?>
                                                </span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não calculado</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    A operação é lucrativa se a ação terminar entre os pontos de equilíbrio.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cenários da Operação -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card detail-card">
                        <div class="card-header bg-secondary text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-project-diagram me-2"></i>
                                Cenários da Operação
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card border-success mb-3">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-thumbs-up me-1"></i>
                                                Cenário Otimista
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                <strong>Ação acima do strike:</strong>
                                                <br>• CALL é exercida - você vende as ações
                                                <br>• PUT expira sem valor
                                                <br>• Lucro máximo realizado
                                                <br>• Retorno: <strong><?= number_format($operation['profit_percent'], 2, ',', '.') ?>%</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card border-warning mb-3">
                                        <div class="card-header bg-warning text-white">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-balance-scale me-1"></i>
                                                Cenário Neutro
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                <strong>Ação próxima ao strike:</strong>
                                                <br>• Ambas as opções expiram sem valor
                                                <br>• Você mantém as ações
                                                <br>• Fica com os prêmios
                                                <br>• Retorno: <strong>Prêmios + SELIC</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card border-danger mb-3">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-thumbs-down me-1"></i>
                                                Cenário Pessimista
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                <strong>Ação abaixo do strike:</strong>
                                                <br>• PUT é exercida - você compra mais ações
                                                <br>• CALL expira sem valor
                                                <br>• Você fica com ações em baixa
                                                <br>• Perda limitada ao <strong>prejuízo máximo</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notas Importantes -->
            <div class="alert alert-warning mt-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-2">Considerações Importantes</h5>
                        <ul class="mb-0">
                            <li>O investimento em <strong>LFTS11</strong> é necessário para garantir a venda da PUT e rende a taxa SELIC durante o período</li>
                            <li>O retorno total considera os prêmios recebidos + rendimento do LFTS11 + variação da ação</li>
                            <li>A operação é mais adequada para ações estáveis com baixa volatilidade esperada</li>
                            <li>Taxas de corretagem, emolumentos e impostos não estão incluídos nos cálculos</li>
                            <li>Recomenda-se consultar um assessor de investimentos antes de realizar a operação</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Imprimir Análise
                    </button>
                    <button class="btn btn-outline-info ms-2" onclick="exportOperation()">
                        <i class="fas fa-download me-2"></i>Exportar Dados
                    </button>
                </div>
                <div>
                    <a href="/?action=scan" class="btn btn-primary">
                        <i class="fas fa-redo me-2"></i>Nova Análise
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert" style="min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                <div>${message}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
        alertContainer.insertAdjacentHTML('beforeend', alertHtml);

        // Auto-remover após 5 segundos
        setTimeout(() => {
            const alertElement = document.getElementById(alertId);
            if (alertElement) {
                alertElement.remove();
            }
        }, 5000);
    }

    function saveOperation() {
        // Fallback para funções de notificação caso não existam
        const _showLoading = typeof showLoading === 'function' ? showLoading : (msg) => console.log('Loading: ' + msg);
        const _hideLoading = typeof hideLoading === 'function' ? hideLoading : () => console.log('Hide loading');
        const _showSuccess = typeof showSuccess === 'function' ? showSuccess : (msg) => showAlert(msg, 'success');
        const _showError = typeof showError === 'function' ? showError : (msg) => showAlert(msg, 'danger');

        // Função auxiliar para formatar data para o MySQL (YYYY-MM-DD)
        const formatToMySQLDate = (dateStr) => {
            if (!dateStr) return '';
            // Se já estiver no formato YYYY-MM-DD, retorna como está
            if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) return dateStr;
            // Se estiver no formato DD/MM/YYYY
            const parts = dateStr.split('/');
            if (parts.length === 3) {
                return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
            }
            return dateStr;
        };

        const data = <?= json_encode($operation) ?>;

        // Prepara os dados para envio
        const operationData = {
            symbol: data.symbol || '',
            current_price: data.current_price || 0,
            strike_price: data.strike_price || data.strike || 0,
            call_symbol: data.call_symbol || '',
            call_premium: data.call_premium || 0,
            put_symbol: data.put_symbol || '',
            put_premium: data.put_premium || 0,
            expiration_date: formatToMySQLDate(data.expiration_date),
            days_to_maturity: data.days_to_maturity || 0,
            initial_investment: data.initial_investment || 0,
            max_profit: data.max_profit || 0,
            max_loss: data.max_loss || 0,
            profit_percent: data.profit_percent || 0,
            monthly_profit_percent: data.monthly_profit_percent || 0,
            selic_annual: data.selic_annual || 0.1375,
            status: 'active',
            strategy_type: 'covered_straddle',
            risk_level: 'medium',
            notes: 'Operação salva via scanner'
        };

        // Mostrar loading global
        _showLoading('Salvando operação...');

        // Usar a API via POST
        fetch('/?action=save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                'operation': JSON.stringify(operationData)
            })
        })
            .then(response => {
                if (response.redirected) {
                    _hideLoading();
                    _showSuccess('Operação salva com sucesso! Redirecionando...');
                    setTimeout(() => {
                        window.location.href = response.url;
                    }, 1500);
                    return null;
                }
                
                // Tenta ler como texto primeiro para lidar com erros de PHP que quebram o JSON
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Resposta não é um JSON válido:', text);
                        
                        // Em desenvolvimento, mostrar o erro bruto na tela
                        const debugInfo = text.substring(0, 1000) + (text.length > 1000 ? '...' : '');
                        throw new Error('O servidor retornou uma resposta inválida: ' + debugInfo);
                    }
                });
            })
            .then(result => {
                if (!result) return; // Caso de redirecionamento

                _hideLoading();

                if (result.success) {
                    _showSuccess(result.message || 'Operação salva com sucesso!');
                    
                    const id = result.id || (result.data && result.data.id);
                    if (id) {
                        setTimeout(() => {
                            window.location.href = '/?action=details&id=' + id;
                        }, 2000);
                    } else {
                        setTimeout(() => location.reload(), 2000);
                    }
                } else {
                    _showError(result.message || 'Erro ao salvar operação.');
                    console.log('Resposta do servidor:', result);
                }
            })
            .catch(error => {
                _hideLoading();
                console.error('Erro:', error);
                _showError(error.message || 'Erro de conexão ou resposta inválida do servidor.');
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
</script>
</body>
</html>