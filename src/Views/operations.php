<?php
// Views/operations.php - Template para exibir operações
$operations = $operations ?? [];
$stats = $stats ?? [];
$filters = $filters ?? [];

// Define variáveis para o header
$page_title = 'Operações - Covered Straddle Scanner';

// Incluir header MODERNO
include __DIR__ . '/layout/header.php';
?>

    <div class="content-wrapper mt-4 pt-3">
        <!-- Cabeçalho -->
        <div class="page-header-gradient mb-4">
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
                <span class="badge bg-white text-primary fs-6">
                    <i class="fas fa-database me-1"></i>
                    <?= count($operations) ?> operações
                </span>
                </div>
            </div>
        </div>

            <!-- Filtros -->
            <div class="filter-section">
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
    </div>

<?php
// O sidebar agora é incluído pelo header.php
?>

<?php
// Passar o JavaScript da página para o footer
ob_start();
?>
<script>
    // Função para excluir operação
    function deleteOperation(id) {
        if (typeof confirmAction === 'function') {
            confirmAction('Tem certeza que deseja excluir esta operação?', () => {
                executeDelete(id);
            });
        } else if (confirm('Tem certeza que deseja excluir esta operação?')) {
            executeDelete(id);
        }
    }

    function executeDelete(id) {
        if (typeof showLoading === 'function') showLoading('Excluindo operação...');

        fetch('/api/operations/delete', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ operation_id: id })
        })
            .then(response => response.json())
            .then(data => {
                if (typeof hideLoading === 'function') hideLoading();
                if (data.success) {
                    if (typeof showSuccess === 'function') showSuccess('Operação excluída com sucesso!');
                    else alert('Operação excluída com sucesso!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    if (typeof showError === 'function') showError('Erro ao excluir operação: ' + data.message);
                    else alert('Erro ao excluir operação: ' + data.message);
                }
            })
            .catch(error => {
                if (typeof hideLoading === 'function') hideLoading();
                console.error('Erro:', error);
                if (typeof showError === 'function') showError('Erro ao excluir operação.');
                else alert('Erro ao excluir operação.');
            });
    }

    // Função para exportar operações
    function exportOperations() {
        window.location.href = '/?action=export';
    }

    // Inicializar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            tooltips.forEach(el => new bootstrap.Tooltip(el));
        }
    });
</script>
<?php
$page_js = ob_get_clean();

// Incluir footer
include __DIR__ . '/layout/footer.php';
?>