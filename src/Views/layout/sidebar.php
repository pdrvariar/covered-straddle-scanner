<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                <i class="fas fa-chart-line fa-2x text-white"></i>
            </div>
            <h5 class="text-dark mb-1">Covered Straddle</h5>
            <p class="text-muted small">Scanner Professional</p>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item mb-2">
                <a class="nav-link <?= ($_GET['action'] ?? '') == '' ? 'active' : '' ?>" href="/">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?= ($_GET['action'] ?? '') == 'scan' ? 'active' : '' ?>" href="/?action=scan">
                    <i class="fas fa-search me-2"></i>
                    Scanner
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link <?= ($_GET['action'] ?? '') == 'results' ? 'active' : '' ?>" href="/?action=results">
                    <i class="fas fa-list-ul me-2"></i>
                    Resultados
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="#">
                    <i class="fas fa-history me-2"></i>
                    Histórico
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="#">
                    <i class="fas fa-chart-bar me-2"></i>
                    Análises
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="#">
                    <i class="fas fa-database me-2"></i>
                    Backtest
                </a>
            </li>
            <li class="nav-item mb-2">
                <a class="nav-link" href="#">
                    <i class="fas fa-calculator me-2"></i>
                    Calculadora
                </a>
            </li>
        </ul>

        <hr class="my-4">

        <!-- Quick Filters -->
        <div class="mb-4">
            <h6 class="sidebar-heading px-3 mb-2">
                <span class="text-muted">Filtros Rápidos</span>
            </h6>
            <div class="px-3">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="filterProfit">
                    <label class="form-check-label small" for="filterProfit">
                        Lucro > 10%
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="filterDays">
                    <label class="form-check-label small" for="filterDays">
                        Dias < 30
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="filterVolume">
                    <label class="form-check-label small" for="filterVolume">
                        Volume > 1000
                    </label>
                </div>
            </div>
        </div>

        <!-- Market Status -->
        <div class="px-3">
            <h6 class="sidebar-heading mb-2">
                <span class="text-muted">Mercado</span>
            </h6>
            <div class="mb-2">
                <small class="text-muted d-block">SELIC</small>
                <span class="badge bg-success">13.75%</span>
            </div>
            <div class="mb-2">
                <small class="text-muted d-block">IBOV</small>
                <span class="badge bg-primary">+0.85%</span>
            </div>
            <div class="mb-2">
                <small class="text-muted d-block">USD/BRL</small>
                <span class="badge bg-warning text-dark">5.42</span>
            </div>
        </div>

        <hr class="my-4">

        <!-- Recent Searches -->
        <div class="px-3">
            <h6 class="sidebar-heading mb-2">
                <span class="text-muted">Buscas Recentes</span>
            </h6>
            <div class="d-flex flex-wrap gap-1">
                <span class="badge bg-light text-dark">PETR4</span>
                <span class="badge bg-light text-dark">VALE3</span>
                <span class="badge bg-light text-dark">ITUB4</span>
                <span class="badge bg-light text-dark">BBAS3</span>
                <span class="badge bg-light text-dark">BBDC4</span>
            </div>
        </div>
    </div>
</nav>