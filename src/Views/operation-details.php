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
$operation['quantity'] = $operation['quantity'] ?? ($operation['strategy_type'] === 'collar' ? 100 : 1000);
$operation['strategy_type'] = $operation['strategy_type'] ?? 'covered_straddle';

// Definir strikes específicos para Collar
$isCollar = $operation['strategy_type'] === 'collar';
$isCoveredStraddle = !$isCollar;

if ($isCollar) {
    $operation['call_strike'] = $operation['call_strike'] ?? $operation['strike_price'];
    $operation['put_strike'] = $operation['put_strike'] ?? $operation['strike_price'];
}

// Mapeamento de nomes de estratégia
$strategyNames = [
        'covered_straddle' => 'Covered Straddle',
        'collar' => 'Collar'
];
$strategyName = $strategyNames[$operation['strategy_type']] ?? 'Estratégia Desconhecida';

// Calcular valores adicionais baseados na estratégia
if ($isCoveredStraddle) {
    $totalPremiums = ($operation['call_premium'] + $operation['put_premium']) * $operation['quantity'];
    $stockInvestment = $operation['current_price'] * $operation['quantity'];
    $totalGuaranteeNeeded = $operation['strike_price'] * $operation['quantity'];
    $callPremiumTotal = $operation['call_premium'] * $operation['quantity'];
    $putPremiumTotal = $operation['put_premium'] * $operation['quantity'];
} else {
    // Cálculo para Collar
    $callPremiumTotal = $operation['call_premium'] * $operation['quantity'];
    $putPremiumTotal = $operation['put_premium'] * $operation['quantity'];
    $totalPremiums = $callPremiumTotal - $putPremiumTotal; // Líquido (pode ser negativo)
    $stockInvestment = $operation['current_price'] * $operation['quantity'];
    $totalGuaranteeNeeded = 0; // Collar não precisa de garantia LFTS11
}

// LFTS11 data (apenas para Covered Straddle)
$lfts11Data = [];
if ($isCoveredStraddle) {
    $lfts11Data = $operation['lfts11_data'] ?? [
            'price' => $operation['lfts11_price'] ?? 146.00,
            'symbol' => 'LFTS11',
            'name' => 'ETF Tesouro Selic',
            'has_data' => isset($operation['lfts11_price']) && $operation['lfts11_price'] > 0
    ];

    // Re-calcular lfts11_investment e return se vierem do banco mas forem nulos
    if (!isset($operation['lfts11_investment']) || $operation['lfts11_investment'] == 0) {
        $totalGuaranteeNeeded = $operation['strike_price'] * $operation['quantity'];
        $lfts11Price = $lfts11Data['price'] > 0 ? $lfts11Data['price'] : 146.00;
        $operation['lfts11_quantity'] = ceil($totalGuaranteeNeeded / $lfts11Price);
        $operation['lfts11_investment'] = $operation['lfts11_quantity'] * $lfts11Price;

        $selicPeriodReturn = ($operation['selic_annual'] ?? 0.13) * (($operation['days_to_maturity'] ?? 30) / 365);
        $operation['lfts11_return'] = $operation['lfts11_investment'] * $selicPeriodReturn;
    }
} else {
    // Para Collar, não há LFTS11
    $operation['lfts11_investment'] = 0;
    $operation['lfts11_return'] = 0;
    $operation['lfts11_quantity'] = 0;
}

?>

<?php
// Set page title
$page_title = "Detalhes da Operação ({$strategyName}) - Options Strategy";

include __DIR__ . '/layout/header.php';
?>
    <div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1050; pointer-events: none;"></div>
    <div class="content-wrapper">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="/?action=results">Resultados</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalhes (<?= htmlspecialchars($strategyName) ?>)</li>
            </ol>
        </nav>

        <div class="page-header-gradient d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Detalhes da Operação: <?= htmlspecialchars($strategyName) ?></h1>
                <h3 class="text-white-50 h4 mb-2"><?= htmlspecialchars($operation['symbol'] ?? 'Desconhecido') ?></h3>
                <p class="mb-0 opacity-75">
                    <i class="fas fa-calendar me-1"></i>
                    Vencimento: <?= htmlspecialchars($operation['expiration_date'] ?? 'N/A') ?>
                    (<?= $operation['days_to_maturity'] ?? 0 ?> dias)
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success shadow-sm" onclick="saveOperation(event)">
                    <i class="fas fa-save me-2"></i>Salvar
                </button>
                <a href="/?action=results" class="btn btn-light shadow-sm">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>

        <!-- Resumo da Operação -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white detail-card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Resumo da Operação: <?= htmlspecialchars($strategyName) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center border-end">
                                <div class="py-2 px-3">
                                    <div class="row g-2 align-items-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block text-uppercase mb-1">Quantidade</small>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="<?= $isCollar ? '100' : '100' ?>" class="form-control text-center fw-bold" id="input-quantity" value="<?= $operation['quantity'] ?>" oninput="updateCalculations()">
                                                <span class="input-group-text p-1" style="font-size: 0.7rem;">Ações</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block text-uppercase mb-1">Total Aplicar</small>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text p-1" style="font-size: 0.7rem;">R$</span>
                                                <input type="number" step="100" class="form-control text-center fw-bold" id="input-total-invest" value="<?= round($stockInvestment + ($operation['lfts11_investment'] ?? 0)) ?>" oninput="updateQuantityFromTotal()">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 text-center border-end">
                                <div class="py-2">
                                    <small class="text-muted d-block text-uppercase mb-1">Retorno</small>
                                    <h2 class="h3 fw-bold text-success mb-0">
                                        <span id="resumo-retorno"><?= number_format($operation['profit_percent'], 2, ',', '.') ?></span>%
                                    </h2>
                                    <small class="text-muted">Total período</small>
                                </div>
                            </div>
                            <div class="col-md-2 text-center border-end">
                                <div class="py-2">
                                    <small class="text-muted d-block text-uppercase mb-1">Mensal</small>
                                    <h2 class="h3 fw-bold text-info mb-0">
                                        <span id="resumo-mensal"><?= number_format($operation['monthly_profit_percent'] ?? 0, 2, ',', '.') ?></span>%
                                    </h2>
                                    <small class="text-muted">Projeção mensal</small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="py-2">
                                    <small class="text-muted d-block text-uppercase mb-1">MSO</small>
                                    <?php
                                    $mso_resumo = $operation['mso'] ?? 0;
                                    if ($mso_resumo == 0 && isset($operation['breakevens'])) {
                                        $bep_min_resumo = !empty($operation['breakevens']) ? min($operation['breakevens']) : $operation['current_price'];
                                        $mso_resumo = (($operation['current_price'] - $bep_min_resumo) / $operation['current_price']) * 100;
                                    }
                                    $msoClass_resumo = $mso_resumo > 0 ? 'text-info' : 'text-danger';
                                    ?>
                                    <div class="d-flex align-items-baseline justify-content-center gap-2">
                                        <h2 class="h3 fw-bold <?= $msoClass_resumo ?> mb-0">
                                            MSO: <span id="resumo-mso"><?= number_format($mso_resumo, 2, ',', '.') ?></span>%
                                        </h2>
                                    </div>
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
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-arrow-up me-2"></i>
                            Compra da Ação
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted d-block text-uppercase small fw-bold">Ação</small>
                            <h4 class="mb-0"><?= htmlspecialchars($operation['symbol'] ?? 'N/A') ?></h4>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block text-uppercase small fw-bold">Preço por Ação</small>
                            <div class="input-group input-group-sm mt-1" style="max-width: 150px;">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" class="form-control fw-bold" id="input-current-price" value="<?= $operation['current_price'] ?>" oninput="updateCalculations()">
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block text-uppercase small fw-bold">Quantidade Total</small>
                            <p class="mb-0">
                                <strong id="display-quantity"><?= number_format($operation['quantity'], 0, ',', '.') ?></strong> ações
                            </p>
                        </div>
                        <div class="p-3 bg-light rounded border">
                            <small class="text-muted d-block text-uppercase small fw-bold">Investimento em Ações</small>
                            <h5 class="mb-0 text-success fw-bold">
                                R$ <span id="display-stock-investment"><?= number_format($stockInvestment, 2, ',', '.') ?></span>
                            </h5>
                            <small id="display-lotes" class="text-muted"><?= number_format($operation['quantity'] / 100, 1, ',', '.') ?> lotes padrão</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Estrutura de Opções -->
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-hand-holding-usd me-2"></i>
                            <?php if ($isCoveredStraddle): ?>
                                Venda das Opções
                            <?php else: ?>
                                Estrutura do Collar
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- CALL -->
                        <div class="mb-3">
                            <small class="text-muted d-block">CALL Vendida</small>
                            <p class="mb-1">
                                <strong><?= htmlspecialchars($operation['call_symbol'] ?? 'N/A') ?></strong>
                            </p>
                            <div class="row align-items-center">
                                <div class="col-7">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control fw-bold" id="input-call-premium" value="<?= $operation['call_premium'] ?>" oninput="updateCalculations()">
                                    </div>
                                </div>
                                <div class="col-5">
                                    <small>Strike: R$ <?= number_format($isCollar ? $operation['call_strike'] : $operation['strike_price'], 2, ',', '.') ?></small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>Receita Total:
                                    <strong>R$ <span id="call-total-revenue"><?= number_format($operation['call_premium'] * $operation['quantity'], 2, ',', '.') ?></span></strong>
                                </small>
                            </div>
                        </div>

                        <hr>

                        <!-- PUT -->
                        <div class="mb-3">
                            <?php if ($isCoveredStraddle): ?>
                                <small class="text-muted d-block">PUT Vendida</small>
                            <?php else: ?>
                                <small class="text-muted d-block">PUT Comprada</small>
                            <?php endif; ?>
                            <p class="mb-1">
                                <strong><?= htmlspecialchars($operation['put_symbol'] ?? 'N/A') ?></strong>
                            </p>
                            <div class="row align-items-center">
                                <div class="col-7">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control fw-bold" id="input-put-premium" value="<?= $operation['put_premium'] ?>" oninput="updateCalculations()">
                                    </div>
                                </div>
                                <div class="col-5">
                                    <small>Strike: R$ <?= number_format($isCollar ? $operation['put_strike'] : $operation['strike_price'], 2, ',', '.') ?></small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <?php if ($isCoveredStraddle): ?>
                                    <small>Receita Total:
                                        <strong>R$ <span id="put-total-revenue"><?= number_format($operation['put_premium'] * $operation['quantity'], 2, ',', '.') ?></span></strong>
                                    </small>
                                <?php else: ?>
                                    <small>Custo Total:
                                        <strong>R$ <span id="put-total-cost"><?= number_format($operation['put_premium'] * $operation['quantity'], 2, ',', '.') ?></span></strong>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded mt-3">
                            <?php if ($isCoveredStraddle): ?>
                                <small class="text-muted d-block">Prêmios Recebidos</small>
                                <h5 class="mb-0 text-warning">
                                    R$ <span id="total-premiums-badge"><?= number_format($totalPremiums, 2, ',', '.') ?></span>
                                </h5>
                                <small>Reduz o investimento inicial</small>
                            <?php else: ?>
                                <small class="text-muted d-block">Prêmios Líquidos (Call - Put)</small>
                                <h5 class="mb-0 <?= $totalPremiums >= 0 ? 'text-warning' : 'text-danger' ?>">
                                    R$ <span id="total-premiums-badge"><?= number_format($totalPremiums, 2, ',', '.') ?></span>
                                </h5>
                                <small><?= $totalPremiums >= 0 ? 'Receita líquida' : 'Custo líquido' ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isCoveredStraddle): ?>
                <!-- Card 3: Garantias com LFTS11 (apenas para Covered Straddle) -->
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
                                    Para cobrir <span id="display-quantity-guarantee"><?= number_format($operation['quantity'], 0, ',', '.') ?></span> PUTs de strike R$ <?= number_format($operation['strike_price'], 2, ',', '.') ?>
                                </p>
                                <h5 class="text-primary mt-1">
                                    R$ <span id="display-guarantee-needed"><?= number_format($totalGuaranteeNeeded, 2, ',', '.') ?></span>
                                </h5>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Cotas de LFTS11 Necessárias</small>
                                <p class="mb-0">
                                    <strong id="display-lfts-quantity"><?= number_format($operation['lfts11_quantity'] ?? 0, 0, ',', '.') ?></strong> cotas
                                </p>
                            </div>

                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Investimento em Garantias</small>
                                <h5 class="mb-0 text-info">
                                    R$ <span id="display-lfts-investment"><?= number_format($operation['lfts11_investment'] ?? 0, 2, ',', '.') ?></span>
                                </h5>
                                <small>Rende <?= number_format($operation['selic_annual'] * 100, 2, ',', '.') ?>% a.a.</small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
                                <span class="text-danger">- R$ <span id="financeira-stock-investment"><?= number_format($stockInvestment, 2, ',', '.') ?></span></span>
                            </div>

                            <?php if ($isCoveredStraddle): ?>
                                <div class="investment-item d-flex justify-content-between">
                                    <span>Investimento em LFTS11:</span>
                                    <span class="text-danger">- R$ <span id="financeira-lfts-investment"><?= number_format($operation['lfts11_investment'] ?? 0, 2, ',', '.') ?></span></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($isCoveredStraddle): ?>
                                <div class="investment-item d-flex justify-content-between">
                                    <span>Prêmios Recebidos (CALL + PUT):</span>
                                    <span class="text-success">+ R$ <span id="total-premiums-financeira"><?= number_format($totalPremiums, 2, ',', '.') ?></span></span>
                                </div>
                            <?php else: ?>
                                <div class="investment-item d-flex justify-content-between">
                                    <span>Receita da CALL Vendida:</span>
                                    <span class="text-success">+ R$ <span id="call-premium-financeira"><?= number_format($callPremiumTotal, 2, ',', '.') ?></span></span>
                                </div>
                                <div class="investment-item d-flex justify-content-between">
                                    <span>Custo da PUT Comprada:</span>
                                    <span class="text-danger">- R$ <span id="put-premium-financeira"><?= number_format($putPremiumTotal, 2, ',', '.') ?></span></span>
                                </div>
                                <div class="investment-item d-flex justify-content-between">
                                    <span>Prêmios Líquidos:</span>
                                    <span class="<?= $totalPremiums >= 0 ? 'text-success' : 'text-danger' ?>"><?= $totalPremiums >= 0 ? '+' : '-' ?> R$ <span id="net-premiums-financeira"><?= number_format(abs($totalPremiums), 2, ',', '.') ?></span></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($isCoveredStraddle): ?>
                                <div class="investment-item d-flex justify-content-between">
                                    <span>Retorno do LFTS11 (<?= $operation['days_to_maturity'] ?> dias):</span>
                                    <span class="text-success">+ R$ <span id="financeira-lfts-return"><?= number_format($operation['lfts11_return'] ?? 0, 2, ',', '.') ?></span></span>
                                </div>
                            <?php endif; ?>

                            <hr>

                            <div class="investment-item d-flex justify-content-between">
                                <strong>Investimento Líquido Inicial:</strong>
                                <strong class="text-primary">R$ <span id="initial-investment"><?= number_format($operation['initial_investment'], 2, ',', '.') ?></span></strong>
                            </div>

                            <div class="investment-item d-flex justify-content-between">
                                <strong>Lucro Máximo:</strong>
                                <strong class="text-success">R$ <span id="max-profit"><?= number_format($operation['max_profit'], 2, ',', '.') ?></span></strong>
                            </div>

                            <div class="investment-item d-flex justify-content-between">
                                <strong>Lucro Mínimo:</strong>
                                <strong class="text-danger">R$ <span id="max-loss"><?= number_format($operation['max_loss'], 2, ',', '.') ?></span></strong>
                            </div>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                <?php if ($isCoveredStraddle): ?>
                                    O investimento em LFTS11 garante que você terá recursos para honrar a PUT vendida caso seja exercida.
                                <?php else: ?>
                                    No Collar, a PUT comprada oferece proteção contra quedas, enquanto a CALL vendida limita os ganhos.
                                <?php endif; ?>
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
                                    if ($isCoveredStraddle) {
                                        $marginSafety = (($operation['strike_price'] - $operation['current_price']) / $operation['current_price']) * 100;
                                    } else {
                                        $marginSafety = (($operation['call_strike'] - $operation['current_price']) / $operation['current_price']) * 100;
                                    }
                                    $marginClass = $marginSafety > 0 ? 'text-success' : 'text-danger';
                                    ?>
                                    <h4 class="<?= $marginClass ?> mb-0">
                                        <?= number_format($marginSafety, 2, ',', '.') ?>%
                                    </h4>
                                    <small><?= $isCoveredStraddle ? 'Strike vs Preço Atual' : 'CALL Strike vs Preço Atual' ?></small>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block"><?= $isCoveredStraddle ? 'Yield dos Prêmios' : 'Receita da CALL' ?></small>
                                    <?php
                                    if ($isCoveredStraddle) {
                                        $premiumYield = ($totalPremiums / ($stockInvestment + ($operation['lfts11_investment'] ?? 0))) * 100;
                                    } else {
                                        $premiumYield = ($callPremiumTotal / $stockInvestment) * 100;
                                    }
                                    ?>
                                    <h4 class="text-warning mb-0">
                                        <span id="premium-yield"><?= number_format($premiumYield, 2, ',', '.') ?></span>%
                                    </h4>
                                    <small><?= $isCoveredStraddle ? 'Retorno dos prêmios' : 'Receita sobre ação' ?></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Retorno Anualizado</small>
                                    <h4 class="text-primary mb-0">
                                        <span id="annual-profit"><?= number_format($operation['annual_profit_percent'] ?? 0, 2, ',', '.') ?></span>%
                                    </h4>
                                    <small>Projeção anual</small>
                                </div>

                                <?php if ($isCollar): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Proteção de Queda (PUT)</small>
                                        <?php
                                        $downsideProtection = (($operation['current_price'] - $operation['put_strike']) / $operation['current_price']) * 100;
                                        $protectionClass = $downsideProtection > 0 ? 'text-success' : 'text-danger';
                                        ?>
                                        <h4 class="<?= $protectionClass ?> mb-0">
                                            <?= number_format($downsideProtection, 2, ',', '.') ?>%
                                        </h4>
                                        <small>Proteção até o strike PUT</small>
                                    </div>
                                <?php else: ?>
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
                                <?php endif; ?>

                                <div class="mb-3">
                                    <small class="text-muted d-block">Margem de Segurança (MSO)</small>
                                    <?php
                                    $mso = $operation['mso'] ?? 0;
                                    if ($mso == 0 && isset($operation['breakevens'])) {
                                        $bep_min = !empty($operation['breakevens']) ? min($operation['breakevens']) : $operation['current_price'];
                                        $mso = (($operation['current_price'] - $bep_min) / $operation['current_price']) * 100;
                                    }
                                    $msoClass = $mso > 0 ? 'text-info' : 'text-danger';
                                    ?>
                                    <h4 class="<?= $msoClass ?> mb-0" id="indicador-mso-container">
                                        <span id="indicador-mso"><?= number_format($mso, 2, ',', '.') ?></span>%
                                    </h4>
                                    <small>Até o Ponto de Equilíbrio</small>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <h6>Pontos de Equilíbrio (BEP):</h6>
                            <div id="breakevens-list" class="d-flex flex-wrap gap-2">
                                <?php if (!empty($operation['breakevens'])): ?>
                                    <?php foreach ($operation['breakevens'] as $bep_val): ?>
                                        <span class="badge bg-info">
                                                    R$ <?= number_format($bep_val, 2, ',', '.') ?>
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
                                <?php if ($isCoveredStraddle): ?>
                                    A operação é lucrativa se a ação terminar entre os pontos de equilíbrio.
                                <?php else: ?>
                                    O Collar é lucrativo dentro da faixa entre os strikes CALL e PUT.
                                <?php endif; ?>
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
                            <?php if ($isCoveredStraddle): ?>
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
                                                <br>• Lucro Máximo realizado
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
                                                <br>• Perda limitada ao <strong>Lucro Mínimo</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-md-4">
                                    <div class="card border-success mb-3">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-thumbs-up me-1"></i>
                                                Ação sobe
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                <strong>Ação acima do CALL strike:</strong>
                                                <br>• CALL é exercida - você vende as ações
                                                <br>• PUT expira sem valor
                                                <br>• Lucro limitado ao CALL strike
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
                                                Ação estável
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                <strong>Ação entre os strikes:</strong>
                                                <br>• Ambas as opções expiram sem valor
                                                <br>• Você mantém as ações
                                                <br>• Lucro com prêmio líquido
                                                <br>• Retorno: <strong>Prêmio líquido</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card border-danger mb-3">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-thumbs-down me-1"></i>
                                                Ação cai
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text">
                                                <strong>Ação abaixo do PUT strike:</strong>
                                                <br>• PUT é exercida - você vende as ações
                                                <br>• CALL expira sem valor
                                                <br>• Lucro Mínimo garantido pelo PUT strike
                                                <br>• Proteção: <strong>até <?= number_format((($operation['current_price'] - $operation['put_strike']) / $operation['current_price']) * 100, 1, ',', '.') ?>%</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Payoff -->
        <div class="row mt-4">
            <div class="col-md-7">
                <div class="card detail-card">
                    <div class="card-header bg-primary text-white detail-card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-area me-2"></i>
                            Gráfico de Payoff no Vencimento
                        </h5>
                    </div>
                    <div class="card-body">
                        <div style="height: 400px; position: relative;">
                            <canvas id="operationPayoffChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Cenários e SELIC (Apenas para Collar) -->
            <?php if ($isCollar && !empty($operation['detailed_scenarios'])): ?>
                <div class="col-md-5">
                    <div class="card detail-card h-100">
                        <div class="card-header bg-dark text-white detail-card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list-alt me-2"></i>
                                Cenários Detalhados
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cenário</th>
                                            <th>Preço Ação</th>
                                            <th class="text-end">Retorno %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($operation['detailed_scenarios'] as $scenario): 
                                            $isPositive = $scenario['profit_percent'] >= 0;
                                            $isNeutral = abs($scenario['price'] - $operation['current_price']) < 0.01;
                                        ?>
                                            <tr class="<?= $isNeutral ? 'table-info fw-bold' : '' ?>">
                                                <td><?= htmlspecialchars($scenario['name']) ?></td>
                                                <td>R$ <?= number_format($scenario['price'], 2, ',', '.') ?></td>
                                                <td class="text-end <?= $isPositive ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format($scenario['profit_percent'], 2, ',', '.') ?>%
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Comparação SELIC -->
                            <?php if (!empty($operation['selic_comparison'])): 
                                $selicComp = $operation['selic_comparison'];
                                $isBetterThanSelic = $selicComp['advantage_over_selic'] > 0;
                            ?>
                                <div class="p-3 bg-light border-top">
                                    <h6 class="fw-bold mb-2">Comparação com SELIC</h6>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>SELIC Bruta (<?= $selicComp['days_to_maturity'] ?> dias):</span>
                                        <span><?= number_format($selicComp['selic_period_gross'], 3, ',', '.') ?>%</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>SELIC Líquida (-22,5% IR):</span>
                                        <span class="fw-bold"><?= number_format($selicComp['selic_period_net'], 3, ',', '.') ?>%</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Lucro Garantido Collar:</span>
                                        <span class="fw-bold text-success"><?= number_format($selicComp['collar_min_profit'], 3, ',', '.') ?>%</span>
                                    </div>
                                    <div class="alert <?= $isBetterThanSelic ? 'alert-success' : 'alert-warning' ?> py-2 px-3 mb-0 small">
                                        <i class="fas <?= $isBetterThanSelic ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-1"></i>
                                        Vantagem sobre SELIC: <strong><?= number_format($selicComp['advantage_over_selic'], 3, ',', '.') ?>%</strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Notas para Covered Straddle se necessário, ou placeholder -->
            <?php endif; ?>
        </div>

        <!-- Notas Importantes -->
        <div class="alert alert-warning mt-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <h5 class="alert-heading mb-2">Considerações Importantes</h5>
                    <ul class="mb-0">
                        <?php if ($isCoveredStraddle): ?>
                            <li>O investimento em <strong>LFTS11</strong> é necessário para garantir a venda da PUT e rende a taxa SELIC durante o período</li>
                            <li>O retorno total considera os prêmios recebidos + rendimento do LFTS11 + variação da ação</li>
                            <li>A operação é mais adequada para ações estáveis com baixa volatilidade esperada</li>
                        <?php else: ?>
                            <li>O <strong>Collar</strong> oferece proteção contra quedas através da PUT comprada, mas limita os ganhos com a CALL vendida</li>
                            <li>Ideal para momentos de incerteza no mercado ou para proteger posições existentes</li>
                            <li>O custo da PUT é compensado pela receita da CALL, podendo resultar em custo líquido zero</li>
                        <?php endif; ?>
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
                <button class="btn btn-outline-info ms-2" onclick="exportOperation(event)">
                    <i class="fas fa-download me-2"></i>Exportar Dados
                </button>
            </div>
            <div>
                <a href="/?action=scan" class="btn btn-primary">
                    <i class="fas fa-redo me-2"></i>Nova Análise
                </a>
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Scripts Específicos -->
    <script>
        /**
         * Dados da operação injetados pelo PHP
         */
        const rawData = <?= json_encode($operation) ?>;
        // Determinar a estratégia
        const isCollar = <?= $isCollar ? 'true' : 'false' ?>;
        const isCoveredStraddle = <?= $isCoveredStraddle ? 'true' : 'false' ?>;

        // Garantir que campos numéricos sejam realmente números
        const operationData = {
            ...rawData,
            strategy_type: '<?= $operation['strategy_type'] ?>',
            quantity: parseFloat(rawData.quantity) || (isCollar ? 100 : 1000),
            strike_price: parseFloat(rawData.strike_price || rawData.strike) || 0,
            current_price: parseFloat(rawData.current_price) || 0,
            call_premium: parseFloat(rawData.call_premium) || 0,
            put_premium: parseFloat(rawData.put_premium) || 0,
            lfts11_investment: parseFloat(rawData.lfts11_investment) || 0,
            lfts11_return: parseFloat(rawData.lfts11_return) || 0,
            days_to_maturity: parseInt(rawData.days_to_maturity) || 30
        };

        // Adicionar strikes específicos para Collar
        if (isCollar) {
            operationData.call_strike = parseFloat(rawData.call_strike || rawData.strike_price) || 0;
            operationData.put_strike = parseFloat(rawData.put_strike || rawData.strike_price) || 0;
        }

        /**
         * Variável global para o gráfico
         */
        let payoffChart = null;

        /**
         * Formata número para moeda/decimal brasileiro
         */
        function formatBR(val, decimals = 2) {
            if (val === null || val === undefined || isNaN(val)) {
                return '0,' + '0'.repeat(decimals);
            }
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(val);
        }

        /**
         * Atualiza a quantidade baseada no valor total que o usuário quer aplicar
         */
        function updateQuantityFromTotal() {
            const totalInvestInput = document.getElementById('input-total-invest');
            const currentPriceInput = document.getElementById('input-current-price');
            const quantityInput = document.getElementById('input-quantity');

            if (!totalInvestInput || !currentPriceInput || !quantityInput) return;

            const totalToApply = parseFloat(totalInvestInput.value) || 0;
            const currentPrice = parseFloat(currentPriceInput.value) || 0;

            if (currentPrice > 0) {
                if (isCoveredStraddle) {
                    const strike = operationData.strike_price;
                    const lftsPrice = <?= $isCoveredStraddle ? ($lfts11Data['price'] ?? 146.00) : 0 ?>;

                    // Cálculo do valor por lote de 100:
                    // (100 * preço_ação) + (ceil(100 * strike / lfts_price) * lfts_price)
                    const lftsQtyPer100 = Math.ceil((100 * strike) / lftsPrice);
                    const costPer100 = (100 * currentPrice) + (lftsQtyPer100 * lftsPrice);

                    const numLotes = Math.floor(totalToApply / costPer100);
                    const newQuantity = Math.max(100, numLotes * 100);

                    quantityInput.value = newQuantity;
                } else {
                    // Para Collar, cálculo mais simples
                    const costPer100 = 100 * currentPrice;
                    const numLotes = Math.floor(totalToApply / costPer100);
                    const newQuantity = Math.max(100, numLotes * 100);

                    quantityInput.value = newQuantity;
                }

                updateCalculations(false); // Não reseta o campo de investimento total
            }
        }

        /**
         * Calcula o payoff para Covered Straddle
         */
        function calculateCoveredStraddlePayoff(s, currentPrice, callPremium, putPremium, strike, quantity, lftsReturn) {
            // CORREÇÃO: Incluir cálculo correto do payoff total
            const stockPayoff = (s - currentPrice) * quantity;
            const callPayoff = (callPremium - Math.max(0, s - strike)) * quantity;
            const putPayoff = (putPremium - Math.max(0, strike - s)) * quantity;
            const totalPayoff = stockPayoff + callPayoff + putPayoff + lftsReturn;

            // CORREÇÃO: Retornar payoff por ação para gráfico mais limpo
            return totalPayoff / quantity;
        }

        /**
         * Calcula o payoff para Collar
         */
        function calculateCollarPayoff(s, currentPrice, callPremium, putPremium, callStrike, putStrike, quantity) {
            let payoff;
            
            if (s <= putStrike) {
                // PUT é exercida: vende pelo strike da PUT
                payoff = (putStrike - currentPrice) * quantity;
            } else if (s >= callStrike) {
                // CALL é exercida: vende pelo strike da CALL
                payoff = (callStrike - currentPrice) * quantity;
            } else {
                // Entre os strikes: permanece com a ação pelo preço s
                payoff = (s - currentPrice) * quantity;
            }

            // Prêmios: recebe da CALL e paga pela PUT
            payoff += (callPremium - putPremium) * quantity;

            return payoff;
        }

        /**
         * Atualiza todos os cálculos quando os prêmios mudam
         */
        function updateCalculations(updateTotalInvestInput = true) {
            try {
                const callPremiumInput = document.getElementById('input-call-premium');
                const putPremiumInput = document.getElementById('input-put-premium');
                const currentPriceInput = document.getElementById('input-current-price');
                const quantityInput = document.getElementById('input-quantity');
                const totalInvestInput = document.getElementById('input-total-invest');

                if (!callPremiumInput || !putPremiumInput || !currentPriceInput || !quantityInput) return;

                const callPremium = parseFloat(callPremiumInput.value) || 0;
                const putPremium = parseFloat(putPremiumInput.value) || 0;
                const currentPrice = parseFloat(currentPriceInput.value) || 0;
                const quantity = parseFloat(quantityInput.value) || 0;

                const selicAnnual = <?= $operation['selic_annual'] ?? 0.13 ?>;
                const daysToMaturity = parseInt(operationData.days_to_maturity) || 30;

                // Atualizar objeto operationData
                operationData.call_premium = callPremium;
                operationData.put_premium = putPremium;
                operationData.current_price = currentPrice;
                operationData.quantity = quantity;

                if (isCoveredStraddle) {
                    const strike = operationData.strike_price;
                    const lftsPrice = <?= $isCoveredStraddle ? ($lfts11Data['price'] ?? 146.00) : 0 ?>;

                    // Recalcular valores de LFTS11
                    const guaranteeNeeded = strike * quantity;
                    const lftsQuantity = Math.ceil(guaranteeNeeded / lftsPrice);
                    const lftsInvestment = lftsQuantity * lftsPrice;
                    const selicPeriodReturn = selicAnnual * (daysToMaturity / 365);
                    const lftsReturn = lftsInvestment * selicPeriodReturn;

                    operationData.lfts11_investment = lftsInvestment;
                    operationData.lfts11_return = lftsReturn;
                    operationData.lfts11_quantity = lftsQuantity;

                    const stockInvestment = currentPrice * quantity;
                    const callTotalRevenue = callPremium * quantity;
                    const putTotalRevenue = putPremium * quantity;
                    const totalPremiums = (callPremium + putPremium) * quantity;

                    // Cálculos para Covered Straddle
                    const initialInvestment = stockInvestment + lftsInvestment - totalPremiums;
                    const maxProfit = ((strike - currentPrice) * quantity) + totalPremiums + lftsReturn;
                    const profitPercent = initialInvestment > 0 ? (maxProfit / initialInvestment) * 100 : 0;

                    const monthlyProfitPercent = $profitPercent * Math.sqrt(30 / daysToMaturity);
                    const annualProfit = $profitPercent * Math.sqrt(365 / daysToMaturity);

                    // BEP para Covered Straddle
                    let bep = 0;
                    if (quantity > 0) {
                        bep = (currentPrice + strike - (totalPremiums / quantity) - (lftsReturn / quantity)) / 2;
                    }

                    // MSO
                    const mso = currentPrice > 0 ? ((currentPrice - bep) / currentPrice) * 100 : 0;

                    // Max Loss (ação a zero)
                    const maxLoss = ((-currentPrice - strike) * quantity) + totalPremiums + lftsReturn;

                    // Atualizar DOM
                    updateDOMForCoveredStraddle(
                        callTotalRevenue, putTotalRevenue, totalPremiums, quantity,
                        stockInvestment, lftsQuantity, lftsInvestment, lftsReturn,
                        initialInvestment, maxProfit, profitPercent, bep, mso, maxLoss,
                        daysToMaturity
                    );

                } else if (isCollar) {
                    const callStrike = operationData.call_strike;
                    const putStrike = operationData.put_strike;

                    // Cálculos para Collar
                    const stockInvestment = currentPrice * quantity;
                    const callTotalRevenue = callPremium * quantity;
                    const putTotalCost = putPremium * quantity;
                    const netPremiums = callTotalRevenue - putTotalCost;

                    // Fórmula do Collar: initialInvestment = stockInvestment + putPremiumTotal - callPremiumTotal
                    const initialInvestment = stockInvestment + putTotalCost - callTotalRevenue;

                    // Max Profit para Collar (quando S_T >= callStrike)
                    const maxProfit = ((callStrike - currentPrice) * quantity) + (callPremium - putPremium) * quantity;

                    // Max Loss para Collar (quando S_T <= putStrike)
                    const maxLoss = ((putStrike - currentPrice) * quantity) + (callPremium - putPremium) * quantity;

                    const profitPercent = initialInvestment > 0 ? (maxProfit / initialInvestment) * 100 : 0;

                    // BEP para Collar: currentPrice - callPremium + putPremium
                    // Pois Lucro = (S - currentPrice) + callPremium - putPremium = 0 => S = currentPrice - callPremium + putPremium
                    // Isso vale se o BEP estiver entre os strikes.
                    let bep = currentPrice - callPremium + putPremium;
                    
                    // Se o lucro no strike da put já for positivo, não há BEP (investidor ganha mesmo na queda)
                    // X = putStrike - currentPrice + callPremium - putPremium
                    const X = (putStrike - currentPrice) + (callPremium - putPremium);
                    if (X > 0) {
                        bep = 0; // Ou algum indicador de que não há BEP / lucro garantido
                    }

                    // MSO para Collar
                    const mso = currentPrice > 0 ? ((currentPrice - bep) / currentPrice) * 100 : 0;

                    // Atualizar DOM
                    updateDOMForCollar(
                        callTotalRevenue, putTotalCost, netPremiums, quantity,
                        stockInvestment, initialInvestment, maxProfit, profitPercent,
                        bep, mso, maxLoss, daysToMaturity, callStrike, putStrike
                    );
                }

                if (updateTotalInvestInput && totalInvestInput) {
                    const stockInvestment = currentPrice * quantity;
                    const lftsInvestment = isCoveredStraddle ? operationData.lfts11_investment : 0;
                    totalInvestInput.value = Math.round(stockInvestment + lftsInvestment);
                }

                // Renderizar Gráfico
                renderPayoffChart();

            } catch (e) {
                console.error("Erro ao atualizar cálculos:", e);
            }
        }

        /**
         * Atualiza o DOM para Covered Straddle
         */
        function updateDOMForCoveredStraddle(
            callTotalRevenue, putTotalRevenue, totalPremiums, quantity,
            stockInvestment, lftsQuantity, lftsInvestment, lftsReturn,
            initialInvestment, maxProfit, profitPercent, bep, mso, maxLoss,
            daysToMaturity
        ) {
            // Helper para atualizar texto do elemento com segurança
            const setElText = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.innerText = text;
            };

            // Atualizar no DOM
            setElText('call-total-revenue', formatBR(callTotalRevenue));
            setElText('put-total-revenue', formatBR(putTotalRevenue));
            setElText('total-premiums-badge', formatBR(totalPremiums));
            setElText('total-premiums-financeira', formatBR(totalPremiums));

            // Atualizar displays de quantidade e investimento
            setElText('display-quantity', formatBR(quantity, 0));
            setElText('display-quantity-guarantee', formatBR(quantity, 0));
            setElText('display-stock-investment', formatBR(stockInvestment));
            setElText('display-lotes', formatBR(quantity / 100, 1) + ' lotes padrão');

            // Atualizar displays de LFTS11
            setElText('display-guarantee-needed', formatBR(operationData.strike_price * quantity));
            setElText('display-lfts-quantity', formatBR(lftsQuantity, 0));
            setElText('display-lfts-investment', formatBR(lftsInvestment));

            // Atualizar Análise Financeira
            setElText('financeira-stock-investment', formatBR(stockInvestment));
            setElText('financeira-lfts-investment', formatBR(lftsInvestment));
            setElText('financeira-lfts-return', formatBR(lftsReturn));

            // Investimento Líquido Inicial
            setElText('initial-investment', formatBR(initialInvestment));

            // Lucro Máximo
            setElText('max-profit', formatBR(maxProfit));

            // Lucro %
            setElText('resumo-retorno', formatBR(profitPercent));

            // Mensal e Anual (Proporcional)
            const monthlyProfit = (profitPercent / daysToMaturity) * 30;
            const annualProfit = (profitPercent / daysToMaturity) * 365;

            setElText('resumo-mensal', formatBR(monthlyProfit));
            setElText('annual-profit', formatBR(annualProfit));

            // Yield dos Prêmios
            const totalCapitalInvested = stockInvestment + lftsInvestment;
            const premiumYield = totalCapitalInvested > 0 ? (totalPremiums / totalCapitalInvested) * 100 : 0;
            setElText('premium-yield', formatBR(premiumYield));

            // Atualizar BEP e MSO
            setElText('resumo-mso', formatBR(mso));
            setElText('indicador-mso', formatBR(mso));

            // Atualizar cor do MSO
            const msoEl = document.getElementById('resumo-mso');
            if (msoEl && msoEl.closest('h2')) {
                msoEl.closest('h2').className = `h3 fw-bold ${mso > 0 ? 'text-info' : 'text-danger'} mb-0`;
            }

            const msoInd = document.getElementById('indicador-mso-container');
            if (msoInd) {
                msoInd.className = `${mso > 0 ? 'text-info' : 'text-danger'} mb-0`;
            }

            // Atualizar lista de BEPs
            const breakevensList = document.getElementById('breakevens-list');
            if (breakevensList) {
                breakevensList.innerHTML = `<span class="badge bg-info">R$ ${formatBR(bep)}</span>`;
            }

            // Perda Máxima
            setElText('max-loss', formatBR(Math.abs(maxLoss)));
        }

        /**
         * Atualiza o DOM para Collar
         */
        function updateDOMForCollar(
            callTotalRevenue, putTotalCost, netPremiums, quantity,
            stockInvestment, initialInvestment, maxProfit, profitPercent,
            bep, mso, maxLoss, daysToMaturity, callStrike, putStrike
        ) {
            // Helper para atualizar texto do elemento com segurança
            const setElText = (id, text) => {
                const el = document.getElementById(id);
                if (el) el.innerText = text;
            };

            // Atualizar no DOM
            setElText('call-total-revenue', formatBR(callTotalRevenue));
            setElText('put-total-cost', formatBR(putTotalCost));
            setElText('total-premiums-badge', formatBR(netPremiums));
            setElText('call-premium-financeira', formatBR(callTotalRevenue));
            setElText('put-premium-financeira', formatBR(putTotalCost));
            setElText('net-premiums-financeira', formatBR(Math.abs(netPremiums)));

            // Atualizar displays de quantidade e investimento
            setElText('display-quantity', formatBR(quantity, 0));
            setElText('display-stock-investment', formatBR(stockInvestment));
            setElText('display-lotes', formatBR(quantity / 100, 1) + ' lotes padrão');

            // Atualizar Análise Financeira
            setElText('financeira-stock-investment', formatBR(stockInvestment));

            // Investimento Líquido Inicial
            setElText('initial-investment', formatBR(initialInvestment));

            // Lucro Máximo
            setElText('max-profit', formatBR(maxProfit));

            // Lucro %
            setElText('resumo-retorno', formatBR(profitPercent));

            // Mensal e Anual (Proporcional)
            const monthlyProfit = (profitPercent / daysToMaturity) * 30;
            const annualProfit = (profitPercent / daysToMaturity) * 365;

            setElText('resumo-mensal', formatBR(monthlyProfit));
            setElText('annual-profit', formatBR(annualProfit));

            // Yield da CALL
            const callYield = stockInvestment > 0 ? (callTotalRevenue / stockInvestment) * 100 : 0;
            setElText('premium-yield', formatBR(callYield));

            // Atualizar BEP e MSO
            setElText('resumo-mso', formatBR(mso));
            setElText('indicador-mso', formatBR(mso));

            // Atualizar cor do MSO
            const msoEl = document.getElementById('resumo-mso');
            if (msoEl && msoEl.closest('h2')) {
                msoEl.closest('h2').className = `h3 fw-bold ${mso > 0 ? 'text-info' : 'text-danger'} mb-0`;
            }

            const msoInd = document.getElementById('indicador-mso-container');
            if (msoInd) {
                msoInd.className = `${mso > 0 ? 'text-info' : 'text-danger'} mb-0`;
            }

            // Atualizar lista de BEPs
            const breakevensList = document.getElementById('breakevens-list');
            if (breakevensList) {
                if (bep > 0) {
                    breakevensList.innerHTML = `<span class="badge bg-info">R$ ${formatBR(bep)}</span>`;
                } else {
                    breakevensList.innerHTML = `<span class="badge bg-success">Lucro Garantido (Sem BEP)</span>`;
                }
            }

            // Perda Máxima
            setElText('max-loss', formatBR(Math.abs(maxLoss)));
        }

        /**
         * Renderiza o gráfico de payoff usando Chart.js
         */
        function renderPayoffChart() {
            const canvas = document.getElementById('operationPayoffChart');
            if (!canvas) {
                console.warn('Canvas do gráfico não encontrado: operationPayoffChart');
                return;
            }

            // Verificar se Chart.js está carregado
            if (typeof Chart === 'undefined') {
                console.error('Erro: Chart.js não está carregado!');
                return;
            }

            const ctx = canvas.getContext('2d');

            // Garantir que os dados sejam numéricos
            const currentPrice = parseFloat(operationData.current_price) || 0;
            const quantity = parseFloat(operationData.quantity) || (isCollar ? 100 : 1000);
            const callPremium = parseFloat(operationData.call_premium) || 0;
            const putPremium = parseFloat(operationData.put_premium) || 0;

            if (currentPrice === 0) {
                console.warn('Preço atual é 0, não é possível renderizar o gráfico adequadamente.');
                return;
            }

            // Definir faixa de preço
            let minPrice, maxPrice;
            const callStrike = parseFloat(operationData.call_strike) || 0;
            const putStrike = parseFloat(operationData.put_strike) || 0;

            if (isCoveredStraddle) {
                minPrice = Math.max(0, currentPrice * 0.7);
                maxPrice = currentPrice * 1.3;
            } else {
                // Para o Collar, garantir que a faixa cubra os strikes com margem
                minPrice = Math.max(0, Math.min(putStrike, currentPrice) * 0.8);
                maxPrice = Math.max(callStrike, currentPrice) * 1.2;
            }

            const labels = [];
            const data = [];
            
            // Gerar pontos de interesse para garantir o formato correto (especialmente as "quinas" nos strikes)
            let points = [];
            
            // Pontos básicos (distribuídos linearmente)
            let step = (maxPrice - minPrice) / 60;
            for (let s = minPrice; s <= maxPrice; s += step) {
                points.push(s);
            }
            
            // Adicionar pontos críticos (strikes e preço atual) para garantir precisão visual
            if (isCollar) {
                points.push(putStrike);
                points.push(callStrike);
                // Adicionar pontos ligeiramente antes e depois dos strikes para reforçar a mudança de inclinação
                points.push(putStrike - 0.01);
                points.push(putStrike + 0.01);
                points.push(callStrike - 0.01);
                points.push(callStrike + 0.01);
            } else {
                const strike = operationData.strike_price;
                points.push(strike);
                points.push(strike - 0.01);
                points.push(strike + 0.01);
            }
            points.push(currentPrice);
            
            // Ordenar e remover duplicados/pontos fora da faixa
            points = [...new Set(points.filter(p => p >= minPrice && p <= maxPrice))].sort((a, b) => a - b);

            points.forEach(s => {
                labels.push('R$ ' + formatBR(s, 2));

                let payoff;
                if (isCoveredStraddle) {
                    const strike = operationData.strike_price;
                    const lftsReturn = parseFloat(operationData.lfts11_return) || 0;
                    payoff = calculateCoveredStraddlePayoff(s, currentPrice, callPremium, putPremium, strike, quantity, lftsReturn);
                } else {
                    payoff = calculateCollarPayoff(s, currentPrice, callPremium, putPremium, callStrike, putStrike, quantity);
                }

                data.push(payoff);
            });

            if (payoffChart) {
                payoffChart.destroy();
            }

            try {
                payoffChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Lucro/Prejuízo (R$)',
                            data: data,
                            borderColor: isCollar ? '#0dcaf0' : '#0d6efd',
                            backgroundColor: isCollar ? 'rgba(13, 202, 240, 0.1)' : 'rgba(13, 110, 253, 0.1)',
                            fill: true,
                            tension: 0, // Removido suavização para mostrar as "quinas" exatas da estratégia
                            pointRadius: function(context) {
                                // Destacar pontos críticos
                                const val = points[context.dataIndex];
                                if (Math.abs(val - putStrike) < 0.02 || Math.abs(val - callStrike) < 0.02 || Math.abs(val - currentPrice) < 0.02) {
                                    return 4;
                                }
                                return 0;
                            },
                            borderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        return 'Preço: ' + context[0].label;
                                    },
                                    label: function(context) {
                                        return 'Resultado: R$ ' + formatBR(context.raw);
                                    }
                                }
                            },
                            // Adicionar anotações visuais se possível (depende do plugin, mas vamos tentar via desenho simples ou apenas legendas)
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Preço da Ação no Vencimento'
                                },
                                grid: {
                                    display: true,
                                    color: 'rgba(0, 0, 0, 0.05)'
                                },
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45,
                                    callback: function(value, index, values) {
                                        // Mostrar apenas alguns labels para não poluir, mas garantir os strikes
                                        const val = points[index];
                                        if (Math.abs(val - putStrike) < 0.05 || Math.abs(val - callStrike) < 0.05 || Math.abs(val - currentPrice) < 0.05) {
                                            return 'R$ ' + formatBR(val, 2);
                                        }
                                        // Retornar label normal a cada X pontos
                                        if (index % 10 === 0) return 'R$ ' + formatBR(val, 2);
                                        return null;
                                    }
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Lucro / Prejuízo (R$)'
                                },
                                grid: {
                                    color: function(context) {
                                        if (context.tick.value === 0) return 'rgba(0, 0, 0, 0.5)'; // Linha de zero bem visível
                                        return 'rgba(0, 0, 0, 0.05)';
                                    },
                                    lineWidth: function(context) {
                                        if (context.tick.value === 0) return 2;
                                        return 1;
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Gráfico de Payoff renderizado com sucesso.');
            } catch (chartError) {
                console.error('Erro ao instanciar Chart.js:', chartError);
            }
        }

        // Inicializar quando o DOM estiver pronto
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            updateCalculations();
        } else {
            document.addEventListener('DOMContentLoaded', updateCalculations);
        }

        /**
         * Salva a operação no banco de dados via API
         */
        async function saveOperation(event) {
            const btn = event ? event.currentTarget : null;
            const originalHtml = btn ? btn.innerHTML : '';

            try {
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...';
                }

                // Preparar dados para envio
                const dataToSave = {
                    ...operationData,
                    strategy_type: operationData.strategy_type || (isCollar ? 'collar' : 'covered_straddle'),
                    // Para Covered Straddle, strike_price é o mesmo para call e put
                    // Para Collar, call_strike e put_strike são diferentes
                    strike_price: isCoveredStraddle ? operationData.strike_price : (operationData.call_strike || 0),
                    call_strike: isCollar ? operationData.call_strike : operationData.strike_price,
                    put_strike: isCollar ? operationData.put_strike : operationData.strike_price,
                    quantity: operationData.quantity || (isCollar ? 100 : 1000),
                    status: 'active',
                    risk_level: 'medium'
                };

                // Garantir que os campos de LFTS11 existam (apenas para Covered Straddle)
                if (isCoveredStraddle) {
                    dataToSave.lfts11_price = operationData.lfts11_price || <?= $lfts11Data['price'] ?? 146.00 ?>;
                    dataToSave.lfts11_quantity = operationData.lfts11_quantity || 0;
                    dataToSave.lfts11_investment = operationData.lfts11_investment || 0;
                    dataToSave.lfts11_return = operationData.lfts11_return || 0;
                } else {
                    // Para Collar, definir como null ou 0
                    dataToSave.lfts11_price = null;
                    dataToSave.lfts11_quantity = 0;
                    dataToSave.lfts11_investment = 0;
                    dataToSave.lfts11_return = 0;
                }

                // Remover campos desnecessários que podem causar problemas no banco
                delete dataToSave.payoff_data;
                delete dataToSave.breakevens;
                delete dataToSave.payoff;
                delete dataToSave.mso;
                delete dataToSave.bep;
                delete dataToSave.call_intrinsic_value;
                delete dataToSave.call_extrinsic_value;
                delete dataToSave.put_intrinsic_value;
                delete dataToSave.put_extrinsic_value;
                delete dataToSave.total_extrinsic_value;
                delete dataToSave.extrinsic_yield;
                delete dataToSave.stock_investment;
                delete dataToSave.total_premiums;
                delete dataToSave.total_guarantee_needed;
                delete dataToSave.selic_monthly;
                delete dataToSave.selic_period_return;
                delete dataToSave.margin_safety;
                delete dataToSave.downside_protection;
                delete dataToSave.premium_yield;
                delete dataToSave.payoff_data;
                delete dataToSave.lfts11_data;
                delete dataToSave.analysis_date;
                delete dataToSave.annual_profit_percent;

                // Log para debug (remover em produção)
                console.log("Enviando dados para salvar:", {
                    symbol: dataToSave.symbol,
                    strategy: dataToSave.strategy_type,
                    strike_price: dataToSave.strike_price,
                    call_strike: dataToSave.call_strike,
                    put_strike: dataToSave.put_strike,
                    quantity: dataToSave.quantity
                });

                // Corrigir a URL para a ação de salvar
                const response = await fetch('/?action=save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(dataToSave)
                });

                const result = await response.json();

                if (result.success) {
                    // Se existir a função global showNotification (de notifications.js)
                    if (typeof showNotification === 'function') {
                        showNotification('Operação salva com sucesso! ID: ' + result.id, 'success');
                    } else if (typeof showSuccess === 'function') {
                        showSuccess('Operação salva com sucesso! ID: ' + result.id);
                    } else {
                        alert('Operação salva com sucesso! ID: ' + result.id);
                    }

                    if (btn) {
                        btn.innerHTML = '<i class="fas fa-check me-2"></i>Salvo!';
                        btn.className = 'btn btn-success shadow-sm';

                        // Redirecionar para a página da operação salva
                        setTimeout(() => {
                            window.location.href = '/?action=details&id=' + result.id;
                        }, 2000);
                    }
                } else {
                    throw new Error(result.error || 'Erro desconhecido ao salvar');
                }
            } catch (error) {
                console.error('Erro ao salvar operação:', error);

                // Mostrar mensagem de erro detalhada
                let errorMessage = 'Erro ao salvar operação: ' + error.message;

                // Tentar extrair mais informações do erro
                if (error.message.includes('SQLSTATE')) {
                    errorMessage += '\nErro de banco de dados. Verifique se a tabela operations tem todas as colunas necessárias.';
                    errorMessage += '\nColunas necessárias: call_strike, put_strike';
                }

                if (typeof showNotification === 'function') {
                    showNotification(errorMessage, 'error');
                } else {
                    alert(errorMessage);
                }

                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        }

        /**
         * Exporta os dados da operação para JSON
         */
        function exportOperation(event) {
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(operationData, null, 2));
            const downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "operacao_" + (operationData.symbol || 'detalhes') + "_" + (isCollar ? 'collar' : 'straddle') + ".json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        }
    </script>

<?php
// O sidebar agora é incluído pelo header.php
include __DIR__ . '/layout/footer.php';
?>