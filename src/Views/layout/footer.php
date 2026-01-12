        </main>
    </div>
</div> <!-- End container-fluid -->

<!-- Footer -->
<footer class="footer py-3 border-top">
    <div class="container-fluid h-100">
        <div class="row align-items-center h-100">
            <div class="col-md-6">
                <p class="mb-0 text-muted small">
                    <i class="fas fa-copyright me-1"></i>
                    <?= date('Y') ?> <strong>Covered Straddle Scanner</strong>. Todos os direitos reservados.
                </p>
            </div>
            <div class="col-md-6 text-end">
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