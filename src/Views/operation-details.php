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

<?php
include __DIR__ . '/layout/header.php';
?>
<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1050;"></div>
<div class="py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/?action=results">Resultados</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detalhes</li>
                </ol>
            </nav>

            <div class="page-header-gradient d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-1">Detalhes da Operação</h1>
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
                                Resumo da Operação de Straddle Coberto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center border-end">
                                    <div class="py-2">
                                        <small class="text-muted d-block text-uppercase mb-1">Quantidade</small>
                                        <h2 class="h3 fw-bold mb-0"><?= number_format($operation['quantity'] / 100, 1, ',', '.') ?></h2>
                                        <small class="text-muted"><?= number_format($operation['quantity'], 0, ',', '.') ?> ações</small>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center border-end">
                                    <div class="py-2">
                                        <small class="text-muted d-block text-uppercase mb-1">Retorno</small>
                                        <h2 class="h3 fw-bold text-success mb-0">
                                            <?= number_format($operation['profit_percent'], 2, ',', '.') ?>%
                                        </h2>
                                        <small class="text-muted">Total período</small>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center border-end">
                                    <div class="py-2">
                                        <small class="text-muted d-block text-uppercase mb-1">Mensal</small>
                                        <h2 class="h3 fw-bold text-info mb-0">
                                            <?= number_format($operation['monthly_profit_percent'] ?? 0, 2, ',', '.') ?>%
                                        </h2>
                                        <small class="text-muted">Projeção mensal</small>
                                    </div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <div class="py-2">
                                        <small class="text-muted d-block text-uppercase mb-1">Break-even</small>
                                        <h2 class="h3 fw-bold text-warning mb-0">
                                            R$ <?= number_format($bep, 2, ',', '.') ?>
                                        </h2>
                                        <small class="text-muted">Preço da ação</small>
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
                                <p class="h4 fw-bold mb-0">
                                    R$ <?= number_format($operation['current_price'], 2, ',', '.') ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block text-uppercase small fw-bold">Quantidade Total</small>
                                <p class="mb-0">
                                    <strong><?= number_format($operation['quantity'], 0, ',', '.') ?></strong> ações
                                </p>
                            </div>
                            <div class="p-3 bg-light rounded border">
                                <small class="text-muted d-block text-uppercase small fw-bold">Investimento Total</small>
                                <h5 class="mb-0 text-success fw-bold">
                                    R$ <?= number_format($stockInvestment, 2, ',', '.') ?>
                                </h5>
                                <small class="text-muted"><?= number_format($operation['quantity'] / 100, 1, ',', '.') ?> lotes padrão</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Venda das Opções -->
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-warning text-dark">
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

<script>
/**
 * Dados da operação injetados pelo PHP
 */
const operationData = <?= json_encode($operation) ?>;

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

        const response = await fetch('/api/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(operationData)
        });

        const result = await response.json();

        if (result.success) {
            if (typeof showNotification === 'function') {
                showNotification('Operação salva com sucesso!', 'success');
            } else {
                alert('Operação salva com sucesso!');
            }

            if (btn) {
                btn.innerHTML = '<i class="fas fa-check me-2"></i>Salvo!';
                btn.className = 'btn btn-outline-success shadow-sm';
            }
        } else {
            throw new Error(result.error || 'Erro desconhecido ao salvar');
        }
    } catch (error) {
        console.error('Erro ao salvar operação:', error);
        
        if (typeof showNotification === 'function') {
            showNotification('Erro: ' + error.message, 'error');
        } else {
            alert('Erro ao salvar operação: ' + error.message);
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
    downloadAnchorNode.setAttribute("download", "operacao_" + (operationData.symbol || 'detalhes') + ".json");
    document.body.appendChild(downloadAnchorNode);
    downloadAnchorNode.click();
    downloadAnchorNode.remove();
}
</script>

<?php
// O sidebar agora é incluído pelo header.php
include __DIR__ . '/layout/footer.php';
?>