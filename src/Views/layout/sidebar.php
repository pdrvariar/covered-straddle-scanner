<nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky">
        <div class="px-3 mb-4 mt-2">
            <h6 class="sidebar-heading text-muted opacity-75">
                <span>Menu Principal</span>
            </h6>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= ($_GET['action'] ?? '') == '' || ($_GET['action'] ?? '') == 'dashboard' ? 'active' : '' ?>" href="/">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($_GET['action'] ?? '') == 'scan' ? 'active' : '' ?>" href="/?action=scan">
                    <i class="fas fa-search me-2"></i>
                    Scanner Rápido
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($_GET['action'] ?? '') == 'results' ? 'active' : '' ?>" href="/?action=results">
                    <i class="fas fa-list-ul me-2"></i>
                    Resultados
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($_GET['action'] ?? '') == 'operations' ? 'active' : '' ?>" href="/?action=operations">
                    <i class="fas fa-history me-2"></i>
                    Minhas Operações
                </a>
            </li>
        </ul>

        <hr class="my-4 mx-3 opacity-25">

        <h6 class="sidebar-heading px-3 mb-2 text-muted opacity-75">
            <span>Ferramentas</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-chart-bar me-2"></i>
                    Análise Técnica
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-calculator me-2"></i>
                    Calculadora Black-Scholes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-database me-2"></i>
                    Histórico de Prêmios
                </a>
            </li>
        </ul>

        <hr class="my-4 mx-3 opacity-25">

        <!-- Market Status -->
        <div class="px-3">
            <h6 class="sidebar-heading mb-3 text-muted opacity-75">
                <span>Mercado B3</span>
            </h6>
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded-3">
                <small class="text-muted fw-bold">SELIC</small>
                <span class="badge bg-success-subtle text-success border border-success-subtle">13.75%</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded-3">
                <small class="text-muted fw-bold">IBOV</small>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">+0.85%</span>
            </div>
        </div>
    </div>
</nav>