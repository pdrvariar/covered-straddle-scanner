<nav class="sidebar glass shadow-lg" style="min-height: calc(100vh - 120px);">
    <div class="position-sticky pt-4">
        <div class="px-3 mb-4 mt-2">
            <h6 class="sidebar-heading text-muted d-flex align-items-center">
                <i class="fas fa-bars me-2 fs-5"></i>
                <span class="fs-6 fw-bold">MENU PRINCIPAL</span>
            </h6>
        </div>

        <ul class="nav flex-column mb-4 px-2">
            <li class="nav-item mb-2">
                <a class="nav-link px-3 py-3 rounded-3 <?= ($_GET['action'] ?? '') == '' || ($_GET['action'] ?? '') == 'dashboard' ? 'active' : '' ?>" href="/">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-tachometer-alt me-3 fs-5"></i>
                        <span class="fw-medium">Dashboard</span>
                        <span class="badge bg-primary ms-auto"><?= $stats['total'] ?? 0 ?></span>
                    </div>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link px-3 py-3 rounded-3 <?= ($_GET['action'] ?? '') == 'scan' ? 'active' : '' ?>" href="/?action=scan">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-search me-3 fs-5"></i>
                        <span class="fw-medium">Scanner Rápido</span>
                        <span class="badge bg-success ms-auto">Novo</span>
                    </div>
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link px-3 py-3 rounded-3 <?= ($_GET['action'] ?? '') == 'operations' ? 'active' : '' ?>" href="/?action=operations">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-history me-3 fs-5"></i>
                        <span class="fw-medium">Minhas Operações</span>
                        <span class="badge bg-warning ms-auto"><?= $stats['active'] ?? 0 ?></span>
                    </div>
                </a>
            </li>
        </ul>

        <hr class="my-4 mx-3 opacity-25">

        <!-- Market Status -->
        <div class="px-3">
            <h6 class="sidebar-heading mb-3 text-muted d-flex align-items-center">
                <i class="fas fa-chart-line me-2"></i>
                <span class="fs-6 fw-bold">MERCADO B3</span>
            </h6>
            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-gradient rounded-3 hover-lift transition-all" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div>
                    <small class="text-muted fw-bold d-block">SELIC (Anual)</small>
                    <small class="text-muted">Taxa básica</small>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle fs-6"><?= $stats['selic'] ?? '13,75' ?>%</span>
            </div>
        </div>

        <!-- Sidebar Footer -->
        <div class="mt-auto px-3">
            <div class="text-center p-3 rounded-3" style="background: rgba(0, 0, 0, 0.05);">
                <small class="text-muted d-block mb-2">
                    <i class="fas fa-shield-alt me-1"></i>
                    Sistema Seguro
                </small>
                <div class="progress mb-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: 100%"></div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    Atualizado às <?= date('H:i') ?>
                </small>
            </div>
        </div>
    </div>
</nav>