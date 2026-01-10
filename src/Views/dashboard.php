<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Covered Straddle Scanner</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Custom CSS -->
    <link href="/css/style.css" rel="stylesheet">

    <style>
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            min-height: 100vh;
        }

        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            margin-bottom: 0.25rem;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .main-content {
            background-color: #f5f7fa;
            padding: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .profit-icon { background-color: rgba(0, 170, 0, 0.1); color: var(--success-color); }
        .loss-icon { background-color: rgba(255, 0, 0, 0.1); color: var(--danger-color); }
        .volume-icon { background-color: rgba(31, 119, 180, 0.1); color: var(--primary-color); }
        .time-icon { background-color: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <h2 class="text-white mb-0">📊</h2>
                    <h4 class="text-white mt-2">Covered Straddle</h4>
                    <p class="text-muted small">Scanner de Opções</p>
                </div>

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="/">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/?action=scan">
                            <i class="fas fa-search me-2"></i>
                            Scanner
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-history me-2"></i>
                            Histórico
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-chart-line me-2"></i>
                            Análises
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-cog me-2"></i>
                            Configurações
                        </a>
                    </li>
                </ul>

                <hr class="text-white-50 my-4">

                <!-- Quick Stats -->
                <div class="text-white-50 small">
                    <h6 class="text-white mb-3">Estatísticas Rápidas</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Operações Hoje:</span>
                        <span class="text-white"><?= $stats['today_ops'] ?? 0 ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Lucro Médio:</span>
                        <span class="text-success"><?= ($stats['avg_profit'] ?? 0) > 0 ? '+' : '' ?><?= $stats['avg_profit'] ?? 0 ?>%</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Taxa SELIC:</span>
                        <span class="text-info"><?= $stats['selic'] ?? 'N/A' ?>%</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.href='/?action=scan'">
                            <i class="fas fa-plus me-1"></i> Nova Análise
                        </button>
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
                                                            <?= $op['days_to_maturity'] ?> dias
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
                                <h4 class="text-success mb-0"><?= $stats['selic'] ?? '13.75' ?>%</h4>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted">IBOVESPA</small>
                                <h4 class="<?= ($market['ibov_change'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?> mb-0">
                                    <?= number_format($market['ibov'] ?? 0, 0, ',', '.') ?>
                                    <small class="fs-6">(<?= ($market['ibov_change'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($market['ibov_change'] ?? 0, 2, ',', '.') ?>%)</small>
                                </h4>
                            </div>
                            <div class="progress mb-2" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: <?= min(100, ($stats['success_rate'] ?? 0)) ?>%"></div>
                            </div>
                            <small class="text-muted">Taxa de sucesso: <?= $stats['success_rate'] ?? 0 ?>%</small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="/js/dashboard.js"></script>
</body>
</html>