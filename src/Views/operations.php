<?php
// Views/operations.php - Template para exibir operações
$operations = $operations ?? [];
$stats = $stats ?? [];
$filters = $filters ?? [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operações - Covered Straddle Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .operations-header {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .filter-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .operation-card {
            border-left: 4px solid #1f77b4;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .operation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
        }
        .profit-positive {
            color: #28a745;
        }
        .profit-negative {
            color: #dc3545;
        }
        .export-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/layout/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <!-- Cabeçalho -->
            <div class="operations-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-2">
                            <i class="fas fa-history me-2"></i>
                            Operações Salvas
                        </h1>
                        <p class="mb-0 opacity-75">
                            Histórico completo de todas as operações analisadas e salvas
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-info fs-6">
                            <i class="fas fa-database me-1"></i>
                            <?= count($operations) ?> operações
                        </span>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filter-card">
                <h5 class="mb-3">
                    <i class="fas fa-filter me-2"></i>
                    Filtros
                </h5>
                <form method="GET" action="" class="row g-3">
                    <input type="hidden" name="action" value="operations">

                    <div class="col-md-4">
                        <label for="symbol" class="form-label">Símbolo</label>
                        <input type="text"
                               class="form-control"
                               id="symbol"
                               name="symbol"
                               value="<?= htmlspecialchars($filters['symbol'] ?? '') ?>"
                               placeholder="Ex: PETR4">
                    </div>

                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="active" <?= ($filters['status'] ?? '') == 'active' ? 'selected' : '' ?>>Ativas</option>
                            <option value="closed" <?= ($filters['status'] ?? '') == 'closed' ? 'selected' : '' ?>>Encerradas</option>
                            <option value="expired" <?= ($filters['status'] ?? '') == 'expired' ? 'selected' : '' ?>>Expiradas</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="min_profit" class="form-label">Lucro Mínimo (%)</label>
                        <input type="number"
                               class="form-control"
                               id="min_profit"
                               name="min_profit"
                               value="<?= htmlspecialchars($filters['min_profit'] ?? '0') ?>"
                               min="0"
                               max="100"
                               step="0.1">
                    </div>

                    <div class="col-12">
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Aplicar Filtros
                            </button>
                            <a href="/?action=operations" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Limpar Filtros
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Estatísticas -->
            <?php if (!empty($operations)): ?>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Estatísticas
                                </h5>
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Total de Operações</small>
                                            <h4 class="mb-0"><?= $stats['total'] ?? 0 ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Ativas</small>
                                            <h4 class="text-success mb-0"><?= $stats['active'] ?? 0 ?></h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Lucro Médio</small>
                                            <h4 class="profit-positive mb-0"><?= number_format($stats['avg_profit'] ?? 0, 2, ',', '.') ?>%</h4>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <div class="mb-3">
                                            <small class="text-muted d-block">Melhor Lucro</small>
                                            <h4 class="profit-positive mb-0"><?= number_format($stats['best_profit'] ?? 0, 2, ',', '.') ?>%</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Lista de Operações -->
            <div class="row">
                <div class="col-md-12">
                    <?php if (!empty($operations)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Símbolo</th>
                                    <th>Preço Atual</th>
                                    <th>Strike</th>
                                    <th>Vencimento</th>
                                    <th>Dias</th>
                                    <th>Investimento</th>
                                    <th>Lucro %</th>
                                    <th>Status</th>
                                    <th>Data Entrada</th>
                                    <th>Ações</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($operations as $op): ?>
                                    <tr>
                                        <td>#<?= $op['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($op['symbol']) ?></strong>
                                        </td>
                                        <td>R$ <?= number_format($op['current_price'], 2, ',', '.') ?></td>
                                        <td>R$ <?= number_format($op['strike_price'], 2, ',', '.') ?></td>
                                        <td><?= date('d/m/Y', strtotime($op['expiration_date'])) ?></td>
                                        <td>
                                                <span class="badge bg-info">
                                                    <?= $op['days_to_maturity'] ?> dias
                                                </span>
                                        </td>
                                        <td>R$ <?= number_format($op['initial_investment'], 2, ',', '.') ?></td>
                                        <td>
                                                <span class="badge bg-<?= $op['profit_percent'] >= 0 ? 'success' : 'danger' ?>">
                                                    <?= ($op['profit_percent'] >= 0 ? '+' : '') . number_format($op['profit_percent'], 2, ',', '.') ?>%
                                                </span>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                    'active' => 'success',
                                                    'closed' => 'secondary',
                                                    'expired' => 'warning'
                                            ][$op['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                    <?= ucfirst($op['status']) ?>
                                                </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($op['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/?action=details&id=<?= $op['id'] ?>"
                                                   class="btn btn-outline-primary"
                                                   title="Ver detalhes">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button"
                                                        class="btn btn-outline-danger"
                                                        onclick="deleteOperation(<?= $op['id'] ?>)"
                                                        title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center py-5">
                            <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                            <h5>Nenhuma operação encontrada</h5>
                            <p class="text-muted">Execute uma análise e salve as operações para vê-las aqui.</p>
                            <a href="/?action=scan" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Executar Scanner
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Botão de Exportação -->
            <?php if (!empty($operations)): ?>
                <button class="btn btn-success export-btn" onclick="exportOperations()"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        title="Exportar para CSV">
                    <i class="fas fa-download me-2"></i> Exportar CSV
                </button>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Função para excluir operação
    function deleteOperation(id) {
        if (confirm('Tem certeza que deseja excluir esta operação?')) {
            fetch('/api/operations/delete', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ operation_id: id })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Operação excluída com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao excluir operação: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao excluir operação.');
                });
        }
    }

    // Função para exportar operações
    function exportOperations() {
        window.location.href = '/?action=export';
    }

    // Inicializar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => new bootstrap.Tooltip(el));
    });
</script>
</body>
</html>