</div> <!-- End container-fluid -->

<!-- Footer -->
<footer class="footer mt-auto py-3 bg-light border-top">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                    <span class="text-muted">
                        <i class="fas fa-code me-1"></i>
                        Covered Straddle Scanner v1.0.0
                    </span>
            </div>
            <div class="col-md-6 text-end">
                    <span class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        <?= date('d/m/Y H:i:s') ?> |
                        <i class="fas fa-server me-1 ms-2"></i>
                        <?= $_SERVER['SERVER_NAME'] ?? 'localhost' ?>
                    </span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= $base_url ?? '' ?>/js/main.js"></script>

<?php if (isset($additional_js)): ?>
    <?php foreach ($additional_js as $js): ?>
        <script src="<?= $js ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Page-specific JS -->
<?php if (isset($page_js)): ?>
    <script>
        <?= $page_js ?>
    </script>
<?php endif; ?>

<!-- Incluir utils.js se existir -->
<?php if (file_exists(__DIR__ . '/../../../public/js/utils.js')): ?>
    <script src="/js/utils.js"></script>
<?php endif; ?>

<!-- Sistema de Notificações (já incluso no header, mas garantindo) -->
<script>
    // Funções de compatibilidade para páginas antigas
    if (typeof showNotification === 'undefined') {
        window.showNotification = function(message, type = 'info') {
            if (typeof Notification !== 'undefined') {
                Notification.show(message, type);
            } else {
                alert(message);
            }
        };
    }

    if (typeof showLoading === 'undefined') {
        window.showLoading = function(message = 'Processando...') {
            if (typeof Loading !== 'undefined') {
                Loading.show(message);
            }
        };
    }

    if (typeof hideLoading === 'undefined') {
        window.hideLoading = function() {
            if (typeof Loading !== 'undefined') {
                Loading.hide();
            }
        };
    }
</script>

</body>
</html>