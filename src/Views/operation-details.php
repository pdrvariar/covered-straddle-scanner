<?php
$operation = $operation ?? null;
if (!$operation) {
    header('Location: /?action=scan');
    exit;
}

// Garantir que todas as chaves necessárias existam com valores padrão seguros
$operation['symbol'] = $operation['symbol'] ?? 'N/A';
$operation['strike_price'] = floatval($operation['strike_price'] ?? $operation['strike'] ?? 0);
$operation['call_premium'] = floatval($operation['call_premium'] ?? 0);
$operation['put_premium'] = floatval($operation['put_premium'] ?? 0);
$operation['current_price'] = floatval($operation['current_price'] ?? 0);
$operation['max_profit'] = floatval($operation['max_profit'] ?? 0);
$operation['profit_percent'] = floatval($operation['profit_percent'] ?? 0);
$operation['quantity'] = intval($operation['quantity'] ?? ($operation['strategy_type'] === 'collar' ? 100 : 1000));
$operation['strategy_type'] = $operation['strategy_type'] ?? 'covered_straddle';
$operation['days_to_maturity'] = intval($operation['days_to_maturity'] ?? 30);
$operation['selic_annual'] = floatval($operation['selic_annual'] ?? 0.13);

// Definir strikes específicos para Collar
$isCollar = $operation['strategy_type'] === 'collar';
$isCoveredStraddle = !$isCollar;

if ($isCollar) {
    $operation['call_strike'] = floatval($operation['call_strike'] ?? $operation['strike_price']);
    $operation['put_strike'] = floatval($operation['put_strike'] ?? $operation['strike_price']);
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

    // CORREÇÃO: Calcular monthly_profit_percent se não existir
    if (!isset($operation['monthly_profit_percent']) || $operation['monthly_profit_percent'] == 0) {
        if ($operation['days_to_maturity'] > 0) {
            $operation['monthly_profit_percent'] = $operation['profit_percent'] * sqrt(30 / $operation['days_to_maturity']);
        } else {
            $operation['monthly_profit_percent'] = 0;
        }
    }
} else {
    // Cálculo para Collar
    $callPremiumTotal = $operation['call_premium'] * $operation['quantity'];
    $putPremiumTotal = $operation['put_premium'] * $operation['quantity'];
    $totalPremiums = $callPremiumTotal - $putPremiumTotal; // Líquido
    $stockInvestment = $operation['current_price'] * $operation['quantity'];
    $totalGuaranteeNeeded = 0;

    // CORREÇÃO: Para Collar, o lucro mensal é proporcional
    if (!isset($operation['monthly_profit_percent']) || $operation['monthly_profit_percent'] == 0) {
        $operation['monthly_profit_percent'] = $operation['profit_percent'] * (30 / max(1, $operation['days_to_maturity']));
    }
}

// LFTS11 data (apenas para Covered Straddle)
$lfts11Data = [];
if ($isCoveredStraddle) {
    $lfts11Data = $operation['lfts11_data'] ?? [
            'price' => floatval($operation['lfts11_price'] ?? 146.00),
            'symbol' => 'LFTS11',
            'name' => 'ETF Tesouro Selic',
            'has_data' => isset($operation['lfts11_price']) && $operation['lfts11_price'] > 0
    ];

    // Re-calcular lfts11_investment e return se necessário
    if (!isset($operation['lfts11_investment']) || $operation['lfts11_investment'] == 0) {
        $totalGuaranteeNeeded = $operation['strike_price'] * $operation['quantity'];
        $lfts11Price = $lfts11Data['price'] > 0 ? $lfts11Data['price'] : 146.00;
        $operation['lfts11_quantity'] = ceil($totalGuaranteeNeeded / $lfts11Price);
        $operation['lfts11_investment'] = $operation['lfts11_quantity'] * $lfts11Price;

        $selicPeriodReturn = $operation['selic_annual'] * ($operation['days_to_maturity'] / 365);
        $operation['lfts11_return'] = $operation['lfts11_investment'] * $selicPeriodReturn;
    }

    // Garantir que lfts11_investment e return sejam float
    $operation['lfts11_investment'] = floatval($operation['lfts11_investment'] ?? 0);
    $operation['lfts11_return'] = floatval($operation['lfts11_return'] ?? 0);
    $operation['lfts11_quantity'] = intval($operation['lfts11_quantity'] ?? 0);
} else {
    $operation['lfts11_investment'] = 0;
    $operation['lfts11_return'] = 0;
    $operation['lfts11_quantity'] = 0;
}

// Calcular BEP e MSO de forma consistente
if ($isCoveredStraddle) {
    if (empty($operation['breakevens']) && $operation['quantity'] > 0) {
        // Fórmula analítica para BEP do Covered Straddle
        $bep = ($operation['current_price'] + $operation['strike_price'] -
                        (($operation['call_premium'] + $operation['put_premium'])) -
                        ($operation['lfts11_return'] / $operation['quantity'])) / 2;
        $operation['bep'] = max(0, $bep);
        $operation['breakevens'] = [$operation['bep']];
    }

    if (!isset($operation['mso']) || $operation['mso'] == 0) {
        $bep_min = !empty($operation['breakevens']) ? min($operation['breakevens']) : $operation['current_price'];
        $operation['mso'] = $operation['current_price'] > 0 ?
                (($operation['current_price'] - $bep_min) / $operation['current_price']) * 100 : 0;
    }
} else {
    // Para Collar, BEP é diferente
    if (!isset($operation['bep'])) {
        // BEP para Collar: currentPrice - callPremium + putPremium
        $operation['bep'] = $operation['current_price'] - $operation['call_premium'] + $operation['put_premium'];
    }

    if (!isset($operation['mso'])) {
        $operation['mso'] = $operation['current_price'] > 0 ?
                (($operation['current_price'] - $operation['bep']) / $operation['current_price']) * 100 : 0;
    }
}

// Calcular initial_investment se não existir
if (!isset($operation['initial_investment'])) {
    if ($isCoveredStraddle) {
        $operation['initial_investment'] = $stockInvestment + $operation['lfts11_investment'] - $totalPremiums;
    } else {
        $operation['initial_investment'] = $stockInvestment + $putPremiumTotal - $callPremiumTotal;
    }
}

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
                <h3 class="text-white-50 h4 mb-2"><?= htmlspecialchars($operation['symbol']) ?></h3>
                <p class="mb-0 opacity-75">
                    <i class="fas fa-calendar me-1"></i>
                    Vencimento: <?= htmlspecialchars($operation['expiration_date'] ?? 'N/A') ?>
                    (<?= $operation['days_to_maturity'] ?> dias)
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
                                                <input type="number" step="100" class="form-control text-center fw-bold"
                                                       id="input-quantity" value="<?= $operation['quantity'] ?>"
                                                       oninput="updateCalculations()">
                                                <span class="input-group-text p-1" style="font-size: 0.7rem;">Ações</span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block text-uppercase mb-1">Total Aplicar</small>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text p-1" style="font-size: 0.7rem;">R$</span>
                                                <input type="number" step="1000" class="form-control text-center fw-bold"
                                                       id="input-total-invest"
                                                       value="<?= round($stockInvestment + $operation['lfts11_investment']) ?>"
                                                       oninput="updateQuantityFromTotal()">
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
                                        <span id="resumo-mensal"><?= number_format($operation['monthly_profit_percent'], 2, ',', '.') ?></span>%
                                    </h2>
                                    <small class="text-muted">Projeção mensal</small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="py-2">
                                    <small class="text-muted d-block text-uppercase mb-1">MSO</small>
                                    <?php
                                    $msoClass = $operation['mso'] > 0 ? 'text-info' : 'text-danger';
                                    ?>
                                    <div class="d-flex align-items-baseline justify-content-center gap-2">
                                        <h2 class="h3 fw-bold <?= $msoClass ?> mb-0">
                                            MSO: <span id="resumo-mso"><?= number_format($operation['mso'], 2, ',', '.') ?></span>%
                                        </h2>
                                    </div>
                                    <small class="text-muted">Margem de Segurança Operacional</small>
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
                            <h4 class="mb-0"><?= htmlspecialchars($operation['symbol']) ?></h4>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted d-block text-uppercase small fw-bold">Preço por Ação</small>
                            <div class="input-group input-group-sm mt-1" style="max-width: 150px;">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" class="form-control fw-bold"
                                       id="input-current-price" value="<?= $operation['current_price'] ?>"
                                       oninput="updateCalculations()">
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
                            <?= $isCoveredStraddle ? 'Venda das Opções' : 'Estrutura do Collar' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- CALL -->
                        <div class="mb-3">
                            <small class="text-muted d-block">CALL <?= $isCoveredStraddle ? 'Vendida' : 'Vendida' ?></small>
                            <p class="mb-1">
                                <strong><?= htmlspecialchars($operation['call_symbol'] ?? 'N/A') ?></strong>
                            </p>
                            <div class="row align-items-center">
                                <div class="col-7">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control fw-bold"
                                               id="input-call-premium" value="<?= $operation['call_premium'] ?>"
                                               oninput="updateCalculations()">
                                    </div>
                                </div>
                                <div class="col-5">
                                    <small>Strike: R$ <?= number_format($isCollar ? $operation['call_strike'] : $operation['strike_price'], 2, ',', '.') ?></small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small>Receita Total:
                                    <strong>R$ <span id="call-total-revenue"><?= number_format($callPremiumTotal, 2, ',', '.') ?></span></strong>
                                </small>
                            </div>
                        </div>

                        <hr>

                        <!-- PUT -->
                        <div class="mb-3">
                            <small class="text-muted d-block">PUT <?= $isCoveredStraddle ? 'Vendida' : 'Comprada' ?></small>
                            <p class="mb-1">
                                <strong><?= htmlspecialchars($operation['put_symbol'] ?? 'N/A') ?></strong>
                            </p>
                            <div class="row align-items-center">
                                <div class="col-7">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" step="0.01" class="form-control fw-bold"
                                               id="input-put-premium" value="<?= $operation['put_premium'] ?>"
                                               oninput="updateCalculations()">
                                    </div>
                                </div>
                                <div class="col-5">
                                    <small>Strike: R$ <?= number_format($isCollar ? $operation['put_strike'] : $operation['strike_price'], 2, ',', '.') ?></small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <?php if ($isCoveredStraddle): ?>
                                    <small>Receita Total:
                                        <strong>R$ <span id="put-total-revenue"><?= number_format($putPremiumTotal, 2, ',', '.') ?></span></strong>
                                    </small>
                                <?php else: ?>
                                    <small>Custo Total:
                                        <strong>R$ <span id="put-total-cost"><?= number_format($putPremiumTotal, 2, ',', '.') ?></span></strong>
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
                                    Para cobrir <span id="display-quantity-guarantee"><?= number_format($operation['quantity'], 0, ',', '.') ?></span> PUTs
                                </p>
                                <h5 class="text-primary mt-1">
                                    R$ <span id="display-guarantee-needed"><?= number_format($totalGuaranteeNeeded, 2, ',', '.') ?></span>
                                </h5>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block">Cotas de LFTS11 Necessárias</small>
                                <p class="mb-0">
                                    <strong id="display-lfts-quantity"><?= number_format($operation['lfts11_quantity'], 0, ',', '.') ?></strong> cotas
                                </p>
                            </div>

                            <div class="p-3 bg-light rounded">
                                <small class="text-muted d-block">Investimento em Garantias</small>
                                <h5 class="mb-0 text-info">
                                    R$ <span id="display-lfts-investment"><?= number_format($operation['lfts11_investment'], 2, ',', '.') ?></span>
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
                                    <span class="text-danger">- R$ <span id="financeira-lfts-investment"><?= number_format($operation['lfts11_investment'], 2, ',', '.') ?></span></span>
                                </div>
                                <div class="investment-item d-flex justify-content-between">
                                    <span>Prêmios Recebidos (CALL + PUT):</span>
                                    <span class="text-success">+ R$ <span id="total-premiums-financeira"><?= number_format($totalPremiums, 2, ',', '.') ?></span></span>
                                </div>
                                <div class="investment-item d-flex justify-content-between">
                                    <span>Retorno do LFTS11 (<?= $operation['days_to_maturity'] ?> dias):</span>
                                    <span class="text-success">+ R$ <span id="financeira-lfts-return"><?= number_format($operation['lfts11_return'], 2, ',', '.') ?></span></span>
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
                                    <span class="<?= $totalPremiums >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $totalPremiums >= 0 ? '+' : '-' ?> R$ <span id="net-premiums-financeira"><?= number_format(abs($totalPremiums), 2, ',', '.') ?></span>
                                </span>
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
                                <strong>Prejuízo Máximo:</strong>
                                <strong class="text-danger">R$ <span id="max-loss"><?= number_format($operation['max_loss'] ?? 0, 2, ',', '.') ?></span></strong>
                            </div>
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
                                    <small class="text-muted d-block">Distância do Strike</small>
                                    <?php
                                    if ($isCoveredStraddle) {
                                        $distance = (($operation['strike_price'] - $operation['current_price']) / $operation['current_price']) * 100;
                                    } else {
                                        $distance = (($operation['call_strike'] - $operation['current_price']) / $operation['current_price']) * 100;
                                    }
                                    $distanceClass = $distance > 0 ? 'text-success' : 'text-danger';
                                    ?>
                                    <h4 class="<?= $distanceClass ?> mb-0">
                                        <?= number_format($distance, 2, ',', '.') ?>%
                                    </h4>
                                    <small><?= $isCoveredStraddle ? 'Strike vs Preço' : 'CALL Strike vs Preço' ?></small>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted d-block">Yield dos Prêmios</small>
                                    <?php
                                    if ($isCoveredStraddle) {
                                        $premiumYield = $totalPremiums / ($stockInvestment + $operation['lfts11_investment']) * 100;
                                    } else {
                                        $premiumYield = $callPremiumTotal / $stockInvestment * 100;
                                    }
                                    ?>
                                    <h4 class="text-warning mb-0">
                                        <span id="premium-yield"><?= number_format($premiumYield, 2, ',', '.') ?></span>%
                                    </h4>
                                    <small>Retorno dos prêmios</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <small class="text-muted d-block">Retorno Anualizado</small>
                                    <?php
                                    $annualProfit = $operation['profit_percent'] * (365 / max(1, $operation['days_to_maturity']));
                                    ?>
                                    <h4 class="text-primary mb-0">
                                        <span id="annual-profit"><?= number_format($annualProfit, 2, ',', '.') ?></span>%
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
                                    <?php $msoClass = $operation['mso'] > 0 ? 'text-info' : 'text-danger'; ?>
                                    <h4 class="<?= $msoClass ?> mb-0" id="indicador-mso-container">
                                        <span id="indicador-mso"><?= number_format($operation['mso'], 2, ',', '.') ?></span>%
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Payoff -->
        <div class="row mt-4">
            <div class="col-md-12">
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

    <script>
        // Dados da operação
        const operationData = <?= json_encode($operation) ?>;
        const isCollar = <?= $isCollar ? 'true' : 'false' ?>;
        const isCoveredStraddle = <?= $isCoveredStraddle ? 'true' : 'false' ?>;
        const lfts11Price = <?= $isCoveredStraddle ? $lfts11Data['price'] : 0 ?>;

        // Formatação
        function formatBR(val, decimals = 2) {
            if (val === null || val === undefined || isNaN(val)) {
                return '0,' + '0'.repeat(decimals);
            }
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(val);
        }

        // Cálculo do payoff para Covered Straddle
        function calculateCoveredStraddlePayoff(price, currentPrice, callPremium, putPremium, strike, quantity, lftsReturn) {
            const stockPayoff = (price - currentPrice) * quantity;
            const callPayoff = (callPremium - Math.max(0, price - strike)) * quantity;
            const putPayoff = (putPremium - Math.max(0, strike - price)) * quantity;
            return stockPayoff + callPayoff + putPayoff + lftsReturn;
        }

        // Cálculo do payoff para Collar
        function calculateCollarPayoff(price, currentPrice, callPremium, putPremium, callStrike, putStrike, quantity) {
            let payoff;
            if (price <= putStrike) {
                payoff = (putStrike - currentPrice) * quantity;
            } else if (price >= callStrike) {
                payoff = (callStrike - currentPrice) * quantity;
            } else {
                payoff = (price - currentPrice) * quantity;
            }
            payoff += (callPremium - putPremium) * quantity;
            return payoff;
        }

        // Atualizar quantidade baseada no total investido
        function updateQuantityFromTotal() {
            const totalInvest = parseFloat(document.getElementById('input-total-invest').value) || 0;
            const currentPrice = parseFloat(document.getElementById('input-current-price').value) || 0;

            if (currentPrice > 0) {
                let quantity;
                if (isCoveredStraddle) {
                    const strike = operationData.strike_price;
                    const lftsPerShare = Math.ceil(strike / lfts11Price) * lfts11Price;
                    const costPerShare = currentPrice + lftsPerShare;
                    quantity = Math.floor(totalInvest / costPerShare) * 100;
                } else {
                    quantity = Math.floor(totalInvest / currentPrice) * 100;
                }

                quantity = Math.max(100, quantity);
                document.getElementById('input-quantity').value = quantity;
                updateCalculations(false);
            }
        }

        // Função principal de atualização
        function updateCalculations(updateTotalInvest = true) {
            try {
                const currentPrice = parseFloat(document.getElementById('input-current-price').value) || 0;
                const callPremium = parseFloat(document.getElementById('input-call-premium').value) || 0;
                const putPremium = parseFloat(document.getElementById('input-put-premium').value) || 0;
                const quantity = parseFloat(document.getElementById('input-quantity').value) || 0;
                const daysToMaturity = operationData.days_to_maturity || 30;
                const selicAnnual = operationData.selic_annual || 0.13;

                // Atualizar displays básicos
                document.getElementById('display-quantity').textContent = formatBR(quantity, 0);
                document.getElementById('display-stock-investment').textContent = formatBR(currentPrice * quantity);
                document.getElementById('call-total-revenue').textContent = formatBR(callPremium * quantity);
                document.getElementById('call-premium-financeira').textContent = formatBR(callPremium * quantity);

                if (isCoveredStraddle) {
                    const strike = operationData.strike_price;

                    // Calcular LFTS11
                    const guaranteeNeeded = strike * quantity;
                    const lftsQuantity = Math.ceil(guaranteeNeeded / lfts11Price);
                    const lftsInvestment = lftsQuantity * lfts11Price;
                    const selicPeriodReturn = selicAnnual * (daysToMaturity / 365);
                    const lftsReturn = lftsInvestment * selicPeriodReturn;

                    // Atualizar displays LFTS11
                    document.getElementById('display-quantity-guarantee').textContent = formatBR(quantity, 0);
                    document.getElementById('display-guarantee-needed').textContent = formatBR(guaranteeNeeded);
                    document.getElementById('display-lfts-quantity').textContent = formatBR(lftsQuantity, 0);
                    document.getElementById('display-lfts-investment').textContent = formatBR(lftsInvestment);
                    document.getElementById('financeira-lfts-investment').textContent = formatBR(lftsInvestment);
                    document.getElementById('financeira-lfts-return').textContent = formatBR(lftsReturn);

                    // Calcular métricas
                    const stockInvestment = currentPrice * quantity;
                    const callTotalRevenue = callPremium * quantity;
                    const putTotalRevenue = putPremium * quantity;
                    const totalPremiums = callTotalRevenue + putTotalRevenue;

                    document.getElementById('put-total-revenue').textContent = formatBR(putTotalRevenue);
                    document.getElementById('total-premiums-badge').textContent = formatBR(totalPremiums);
                    document.getElementById('total-premiums-financeira').textContent = formatBR(totalPremiums);

                    const initialInvestment = stockInvestment + lftsInvestment - totalPremiums;
                    document.getElementById('initial-investment').textContent = formatBR(initialInvestment);

                    // Lucro máximo ocorre quando S = strike
                    const maxProfit = ((strike - currentPrice) * quantity) + totalPremiums + lftsReturn;
                    document.getElementById('max-profit').textContent = formatBR(maxProfit);

                    // Percentual de retorno
                    const profitPercent = initialInvestment > 0 ? (maxProfit / initialInvestment) * 100 : 0;
                    document.getElementById('resumo-retorno').textContent = formatBR(profitPercent);

                    // Retorno mensal proporcional (não-linear para opções)
                    const monthlyProfitPercent = profitPercent * Math.sqrt(30 / daysToMaturity);
                    document.getElementById('resumo-mensal').textContent = formatBR(monthlyProfitPercent);

                    // Retorno anualizado
                    const annualProfit = profitPercent * Math.sqrt(365 / daysToMaturity);
                    document.getElementById('annual-profit').textContent = formatBR(annualProfit);

                    // BEP analítico
                    const bep = (currentPrice + strike - (totalPremiums/quantity) - (lftsReturn/quantity)) / 2;
                    const mso = currentPrice > 0 ? ((currentPrice - bep) / currentPrice) * 100 : 0;

                    document.getElementById('resumo-mso').textContent = formatBR(mso);
                    document.getElementById('indicador-mso').textContent = formatBR(mso);

                    // Atualizar BEP display
                    document.getElementById('breakevens-list').innerHTML =
                        `<span class="badge bg-info">R$ ${formatBR(bep)}</span>`;

                    // Prejuízo máximo (quando ação vai a zero)
                    const maxLoss = ((-currentPrice - strike) * quantity) + totalPremiums + lftsReturn;
                    document.getElementById('max-loss').textContent = formatBR(Math.abs(maxLoss));

                    // Yield dos prêmios
                    const premiumYield = totalPremiums / (stockInvestment + lftsInvestment) * 100;
                    document.getElementById('premium-yield').textContent = formatBR(premiumYield);

                    // Distância do strike
                    const strikeDistance = ((strike - currentPrice) / currentPrice) * 100;

                    if (updateTotalInvest) {
                        document.getElementById('input-total-invest').value = Math.round(stockInvestment + lftsInvestment);
                    }

                } else if (isCollar) {
                    const callStrike = operationData.call_strike;
                    const putStrike = operationData.put_strike;

                    document.getElementById('put-total-cost').textContent = formatBR(putPremium * quantity);
                    document.getElementById('put-premium-financeira').textContent = formatBR(putPremium * quantity);

                    const netPremiums = (callPremium - putPremium) * quantity;
                    document.getElementById('total-premiums-badge').textContent = formatBR(netPremiums);
                    document.getElementById('net-premiums-financeira').textContent = formatBR(Math.abs(netPremiums));

                    const stockInvestment = currentPrice * quantity;
                    const initialInvestment = stockInvestment + (putPremium * quantity) - (callPremium * quantity);
                    document.getElementById('initial-investment').textContent = formatBR(initialInvestment);

                    // Lucro máximo (quando S >= callStrike)
                    const maxProfit = ((callStrike - currentPrice) * quantity) + netPremiums;
                    document.getElementById('max-profit').textContent = formatBR(maxProfit);

                    const profitPercent = initialInvestment > 0 ? (maxProfit / initialInvestment) * 100 : 0;
                    document.getElementById('resumo-retorno').textContent = formatBR(profitPercent);

                    // Retorno mensal proporcional
                    const monthlyProfitPercent = profitPercent * (30 / daysToMaturity);
                    document.getElementById('resumo-mensal').textContent = formatBR(monthlyProfitPercent);

                    // Retorno anualizado
                    const annualProfit = profitPercent * (365 / daysToMaturity);
                    document.getElementById('annual-profit').textContent = formatBR(annualProfit);

                    // BEP para Collar
                    const bep = currentPrice - callPremium + putPremium;
                    const mso = currentPrice > 0 ? ((currentPrice - bep) / currentPrice) * 100 : 0;

                    document.getElementById('resumo-mso').textContent = formatBR(mso);
                    document.getElementById('indicador-mso').textContent = formatBR(mso);

                    // Prejuízo máximo (quando S <= putStrike)
                    const maxLoss = ((putStrike - currentPrice) * quantity) + netPremiums;
                    document.getElementById('max-loss').textContent = formatBR(Math.abs(maxLoss));

                    // Yield da CALL
                    const callYield = (callPremium * quantity) / stockInvestment * 100;
                    document.getElementById('premium-yield').textContent = formatBR(callYield);

                    if (updateTotalInvest) {
                        document.getElementById('input-total-invest').value = Math.round(stockInvestment);
                    }
                }

                renderPayoffChart();

            } catch (error) {
                console.error('Erro nos cálculos:', error);
            }
        }

        // Renderizar gráfico
        function renderPayoffChart() {
            const canvas = document.getElementById('operationPayoffChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            if (window.payoffChart) {
                window.payoffChart.destroy();
            }

            const currentPrice = parseFloat(document.getElementById('input-current-price').value) || 0;
            const callPremium = parseFloat(document.getElementById('input-call-premium').value) || 0;
            const putPremium = parseFloat(document.getElementById('input-put-premium').value) || 0;
            const quantity = parseFloat(document.getElementById('input-quantity').value) || 0;

            if (currentPrice <= 0) return;

            // Definir faixa de preços
            let minPrice, maxPrice;
            if (isCoveredStraddle) {
                const strike = operationData.strike_price;
                minPrice = Math.max(0, currentPrice * 0.7);
                maxPrice = currentPrice * 1.3;
            } else {
                const callStrike = operationData.call_strike;
                const putStrike = operationData.put_strike;
                minPrice = Math.max(0, putStrike * 0.8);
                maxPrice = callStrike * 1.2;
            }

            // Gerar dados
            const labels = [];
            const data = [];
            const step = (maxPrice - minPrice) / 50;

            for (let price = minPrice; price <= maxPrice; price += step) {
                labels.push('R$ ' + formatBR(price));

                let payoff;
                if (isCoveredStraddle) {
                    const strike = operationData.strike_price;
                    const lftsReturn = parseFloat(document.getElementById('financeira-lfts-return')?.textContent?.replace(/\./g, '').replace(',', '.') || 0);
                    payoff = calculateCoveredStraddlePayoff(price, currentPrice, callPremium, putPremium, strike, quantity, lftsReturn);
                } else {
                    const callStrike = operationData.call_strike;
                    const putStrike = operationData.put_strike;
                    payoff = calculateCollarPayoff(price, currentPrice, callPremium, putPremium, callStrike, putStrike, quantity);
                }

                data.push(payoff);
            }

            window.payoffChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Lucro/Prejuízo (R$)',
                        data: data,
                        borderColor: isCollar ? '#0dcaf0' : '#0d6efd',
                        backgroundColor: isCollar ? 'rgba(13, 202, 240, 0.1)' : 'rgba(13, 110, 253, 0.1)',
                        fill: true,
                        tension: 0.1,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Resultado: R$ ' + formatBR(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Preço da Ação no Vencimento'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Lucro / Prejuízo (R$)'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            updateCalculations();
        });
    </script>

<?php include __DIR__ . '/layout/footer.php'; ?>