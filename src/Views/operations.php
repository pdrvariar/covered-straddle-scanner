<div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-history me-2"></i>
            Operações Salvas
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="/?action=operations&sub=export" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-download me-1"></i> Exportar CSV
                </a>
                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="/" class="row g-3">
                <input type="hidden" name="action" value="operations">

                <div class="col-md-3">
                    <label for="status" class="form-label">Status:</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="active" <?= isset($_GET['status']) && $_GET['status'] == 'active' ? 'selected' : '' ?>>Ativas</option>
                        <option value="closed" <?= isset($_GET['status']) && $_GET['status'] == 'closed' ? 'selected' : '' ?>>Fechadas</option>
                        <option value="expired" <?= isset($_GET['status']) && $_GET['status'] == 'expired' ? 'selected' : '' ?>>Expiradas</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="symbol" class="form-label">Ativo:</label>
                    <input type="text" name="symbol" id="symbol" class="form-control form-control-sm"
                           placeholder="Ex: PETR4"
                           value="<?= htmlspecialchars($_GET['symbol'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="start_date" class="form-label">Data Início:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="end_date" class="form-label">Data Fim:</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <a href="/?action=operations" class="btn btn-secondary btn-sm">
                        Limpar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total'] ?></h3>
                    <p>Total de Operações</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['active'] ?></h3>
                    <p>Operações Ativas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['closed'] ?></h3>
                    <p>Operações Fechadas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['expired'] ?></h3>
                    <p>Operações Expiradas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Operações -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($operations)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h4>Nenhuma operação encontrada</h4>
                    <p class="text-muted">Comece salvando operações do scanner para vê-las aqui.</p>
                    <a href="/?action=scan" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Ir para Scanner
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ativo</th>
                            <th>Preço Atual</th>
                            <th>Strike</th>
                            <th>Expiração</th>
                            <th>Lucro %</th>
                            <th>Lucro Mensal %</th>
                            <th>Status</th>
                            <th>Data Entrada</th>
                            <th>Ações</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($operations as $op): ?>
                            <?php
                            // Determinar cor do status
                            $statusClass = [
                                'active' => 'bg-success',
                                'closed' => 'bg-primary',
                                'expired' => 'bg-danger'
                            ][$op['status']] ?? 'bg-secondary';

                            // Formatar valores monetários
                            $currentPrice = number_format($op['current_price'], 2, ',', '.');
                            $strikePrice = number_format($op['strike_price'], 2, ',', '.');
                            $profitPercent = number_format($op['profit_percent'], 2, ',', '.');
                            $monthlyProfit = number_format($op['monthly_profit_percent'], 2, ',', '.');
                            ?>
                            <tr>
                                <td><strong>#<?= $op['id'] ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($op['symbol']) ?></strong>
                                    <?php if ($op['call_symbol']): ?>
                                        <br>
                                        <small class="text-muted">
                                            Call: <?= htmlspecialchars($op['call_symbol']) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>R$ <?= $currentPrice ?></td>
                                <td>R$ <?= $strikePrice ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($op['expiration_date'])) ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= $op['days_to_maturity'] ?> dias
                                    </small>
                                </td>
                                <td>
                                    <span class="badge <?= $op['profit_percent'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $profitPercent ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $monthlyProfit ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst($op['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($op['entry_date'] ?? $op['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="/?action=operations&sub=show&id=<?= $op['id'] ?>"
                                           class="btn btn-outline-info"
                                           title="Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger delete-operation"
                                                data-id="<?= $op['id'] ?>"
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
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir esta operação? Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_operation">
                    <input type="hidden" name="operation_id" id="deleteOperationId">
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar modal de exclusão
        const deleteButtons = document.querySelectorAll('.delete-operation');
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        const deleteOperationIdInput = document.getElementById('deleteOperationId');
        const deleteForm = document.getElementById('deleteForm');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const operationId = this.getAttribute('data-id');
                deleteOperationIdInput.value = operationId;
                deleteModal.show();
            });
        });

        // Configurar ação do formulário de exclusão
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const operationId = deleteOperationIdInput.value;

            fetch('/api/save', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_operation',
                    operation_id: operationId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro ao excluir operação: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao excluir operação.');
                });
        });
    });
</script>