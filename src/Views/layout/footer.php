</div> <!-- Fecha .content-card -->
</main>
</div> <!-- Fecha .app-container -->

<!-- Footer -->
<footer class="footer py-3 border-top bg-white">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0 text-muted small">
                    <i class="fas fa-copyright me-1"></i>
                    <?= date('Y') ?> <strong>Covered Straddle Scanner</strong>. Todos os direitos reservados.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-inline-flex align-items-center text-muted small">
                    <span class="me-3">
                        <i class="fas fa-server me-1 opacity-50"></i>
                        v1.2.0
                    </span>
                    <span class="badge bg-light text-dark border fw-normal">
                        <i class="fas fa-circle text-success me-1 small"></i>
                        Sistema Online
                    </span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Layout Enhancement -->
<script>
    // Toggle Sidebar Mobile
    function toggleSidebar() {
        const sidebar = document.getElementById('mobileSidebar');
        const offcanvas = new bootstrap.Offcanvas(sidebar);
        offcanvas.show();
    }

    // Inicializar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    });

    // Preloader handler
    window.addEventListener('load', function() {
        const pageLoader = document.getElementById('pageLoader');
        if (pageLoader) {
            setTimeout(() => {
                pageLoader.classList.add('hidden');
                setTimeout(() => {
                    pageLoader.style.display = 'none';
                }, 500);
            }, 500);
        }
    });

    // Sticky sidebar on scroll
    window.addEventListener('scroll', function() {
        const sidebar = document.getElementById('desktopSidebar');
        if (sidebar && window.innerWidth >= 992) {
            if (window.scrollY > 40) {
                sidebar.style.top = '10px';
            } else {
                sidebar.style.top = '60px';
            }
        }
    });
</script>

<?php if (isset($page_js)): ?>
    <script>
        <?= $page_js ?>
    </script>
<?php endif; ?>

</body>
</html>