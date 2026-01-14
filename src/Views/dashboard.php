<!-- Conteúdo do Dashboard -->
<div class="pt-3 pb-2 mb-3 border-bottom">
    <!-- Cabeçalho -->
    <div class="page-header-gradient mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h2 mb-2">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </h1>
                <p class="mb-0 opacity-75">
                    Visão geral de suas operações e indicadores do mercado
                </p>
            </div>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <button type="button" class="btn btn-light shadow-sm" onclick="location.href='/?action=scan'">
                        <i class="fas fa-plus me-1"></i> Nova Análise
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon profit-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h5 class="mb-1">Melhor Lucro</h5>
                <p class="h3 text-success mb-0">+<?= $stats['best_profit'] ?? '0.00' ?>%</p>
                <small class="text-muted">Última análise</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon volume-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h5 class="mb-1">Volume Médio</h5>
                <p class="h3 text-primary mb-0"><?= $stats['avg_volume'] ?? '0' ?></p>
                <small class="text-muted">Contratos/dia</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon time-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h5 class="mb-1">Tempo Médio</h5>
                <p class="h3 text-warning mb-0"><?= $stats['avg_days'] ?? '0' ?>d</p>
                <small class="text-muted">Até vencimento</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon loss-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h5 class="mb-1">Proteção</h5>
                <p class="h3 text-danger mb-0"><?= $stats['protection'] ?? '0.00' ?>%</p>
                <small class="text-muted">Margem de segurança</small>
            </div>
        </div>
    </div>

    <!-- Recent Operations -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Operações Recentes
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_operations)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-custom">
                                <thead>
                                <tr>
                                    <th>Ticker</th>
                                    <th>Strike</th>
                                    <th>Vencimento</th>
                                    <th>Lucro</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recent_operations as $op): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($op['symbol']) ?></strong>
                                        </td>
                                        <td>R$ <?= number_format($op['strike_price'], 2, ',', '.') ?></td>
                                        <td><?= date('d/m/Y', strtotime($op['expiration_date'])) ?></td>
                                        <td>
                                                <span class="badge bg-<?= $op['profit_percent'] >= 0 ? 'success' : 'danger' ?>">
                                                    <?= ($op['profit_percent'] >= 0 ? '+' : '') . number_format($op['profit_percent'], 2, ',', '.') ?>%
                                                </span>
                                        </td>
                                        <td>
                                                <span class="badge bg-info">
                                                    <?= $op['days_to_maturity'] ?? 0 ?> dias
                                                </span>
                                        </td>
                                        <td>
                                            <a href="/?action=details&id=<?= $op['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <h5>Nenhuma operação encontrada</h5>
                            <p class="text-muted">Execute uma análise para ver os resultados</p>
                            <a href="/?action=scan" class="btn btn-primary">
                                <i class="fas fa-play me-2"></i> Iniciar Análise
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ações Rápidas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="location.href='/?action=scan'">
                            <i class="fas fa-bolt me-2"></i>Scanner Rápido
                        </button>
                        <button class="btn btn-outline-success">
                            <i class="fas fa-download me-2"></i>Exportar Dados
                        </button>
                        <button class="btn btn-outline-info">
                            <i class="fas fa-chart-bar me-2"></i>Relatórios
                        </button>
                        <button class="btn btn-outline-warning">
                            <i class="fas fa-cog me-2"></i>Configurar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Market Overview -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Mercado</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">SELIC (Anual)</small>
                        <h4 class="text-success mb-0"><?= $stats['selic'] ?? '13,75' ?>%</h4>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?= min(100, ($stats['success_rate'] ?? 0)) ?>%"></div>
                    </div>
                    <small class="text-muted">Taxa de sucesso: <?= $stats['success_rate'] ?? 0 ?>%</small>
                </div>
            </div>
        </div>
    </div>

<?php
// O sidebar agora é incluído pelo header.php
?>