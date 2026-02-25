<?php include __DIR__ . '/layout/header.php'; ?>

<div class="page-header-gradient mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h2 class="mb-2">
                <i class="fas fa-user-circle me-2"></i>
                Meu Perfil
            </h2>
            <p class="mb-0 opacity-75">Gerencie suas informações pessoais e configurações de conta</p>
        </div>
        <div>
            <i class="fas fa-user-cog fa-3x opacity-25"></i>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer"></div>

<div class="row g-4">
    <!-- Informações do Perfil -->
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body text-center p-4">
                <div class="user-profile-avatar mb-3">
                    <div class="avatar-circle mx-auto">
                        <i class="fas fa-user fa-4x"></i>
                    </div>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($user['username']) ?></h4>
                <p class="text-muted mb-3">
                    <i class="fas fa-shield-alt me-1"></i>
                    Trader Premium
                </p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-success">
                        <i class="fas fa-check-circle me-1"></i>
                        <?= htmlspecialchars($user['status']) ?>
                    </span>
                </div>
                <hr>
                <div class="text-start">
                    <p class="mb-2">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        <strong>Membro desde:</strong>
                        <br>
                        <small class="text-muted ms-4">
                            <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                        </small>
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-clock text-primary me-2"></i>
                        <strong>Última atualização:</strong>
                        <br>
                        <small class="text-muted ms-4">
                            <?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?>
                        </small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-chart-line me-2"></i>
                Minhas Estatísticas
            </div>
            <div class="card-body">
                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-list-alt text-primary me-2"></i>
                            <span>Total de Operações</span>
                        </div>
                        <strong class="h5 mb-0"><?= $stats['total_operations'] ?? 0 ?></strong>
                    </div>
                </div>
                <hr>
                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Fechadas</span>
                        </div>
                        <strong class="h5 mb-0"><?= $stats['completed'] ?? 0 ?></strong>
                    </div>
                </div>
                <hr>
                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-spinner text-warning me-2"></i>
                            <span>Ativas</span>
                        </div>
                        <strong class="h5 mb-0"><?= $stats['active'] ?? 0 ?></strong>
                    </div>
                </div>
                <hr>
                <div class="stat-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-times-circle text-danger me-2"></i>
                            <span>Expiradas</span>
                        </div>
                        <strong class="h5 mb-0"><?= $stats['expired'] ?? 0 ?></strong>
                    </div>
                </div>
                <hr>
                <div class="stat-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-dollar-sign text-success me-2"></i>
                            <span>Lucro Potencial</span>
                        </div>
                        <strong class="h5 mb-0 text-success">
                            R$ <?= number_format($stats['total_profit'] ?? 0, 2, ',', '.') ?>
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulários de Edição -->
    <div class="col-lg-8">
        <!-- Editar Informações do Perfil -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Informações do Perfil
                </h5>
            </div>
            <div class="card-body">
                <form id="profileForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-user me-1"></i>
                            Nome de Usuário
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            value="<?= htmlspecialchars($user['username']) ?>"
                            required
                            minlength="3"
                        >
                        <div class="form-text">Mínimo de 3 caracteres</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Alterar Senha -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-lock me-2"></i>
                    Alterar Senha
                </h5>
            </div>
            <div class="card-body">
                <form id="passwordForm">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">
                            <i class="fas fa-key me-1"></i>
                            Senha Atual
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                class="form-control"
                                id="current_password"
                                name="current_password"
                                required
                            >
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">
                            <i class="fas fa-lock me-1"></i>
                            Nova Senha
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                class="form-control"
                                id="new_password"
                                name="new_password"
                                required
                                minlength="6"
                            >
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Mínimo de 6 caracteres</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock me-1"></i>
                            Confirmar Nova Senha
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                required
                                minlength="6"
                            >
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Dica de Segurança:</strong> Use uma senha forte com letras, números e caracteres especiais.
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-shield-alt me-2"></i>
                            Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Opções Adicionais -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Zona de Perigo
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Ações irreversíveis que afetam sua conta
                </p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-danger" onclick="confirmDeleteAccount()">
                        <i class="fas fa-trash-alt me-2"></i>
                        Excluir Conta
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.user-profile-avatar {
    position: relative;
}

.avatar-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
}

.stat-item {
    padding: 0.5rem 0;
}

.input-group .btn-outline-secondary {
    border-color: #ced4da;
}

.input-group .btn-outline-secondary:hover {
    background-color: #e9ecef;
    border-color: #ced4da;
    color: #495057;
}
</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const btn = field.nextElementSibling;
    const icon = btn.querySelector('i');

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function showAlert(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    alertContainer.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Profile Form
document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        username: document.getElementById('username').value
    };

    try {
        const response = await fetch('/api/profile/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Erro ao atualizar perfil', 'danger');
        console.error('Error:', error);
    }
});

// Password Form
document.getElementById('passwordForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        current_password: document.getElementById('current_password').value,
        new_password: document.getElementById('new_password').value,
        confirm_password: document.getElementById('confirm_password').value
    };

    try {
        const response = await fetch('/api/profile/password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message, 'success');
            document.getElementById('passwordForm').reset();
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Erro ao alterar senha', 'danger');
        console.error('Error:', error);
    }
});

function confirmDeleteAccount() {
    if (confirm('Tem certeza que deseja excluir sua conta? Esta ação não pode ser desfeita!')) {
        if (confirm('Última confirmação: Todos os seus dados serão permanentemente excluídos. Continuar?')) {
            showAlert('Funcionalidade de exclusão de conta ainda não implementada', 'warning');
        }
    }
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>

