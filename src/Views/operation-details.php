<?php
$operation = $operation ?? null;
if (!$operation) {
    header('Location: /?action=scan');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Operação - <?= htmlspecialchars($operation['symbol']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/layout/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="/?action=results">Resultados</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Detalhes</li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Detalhes: <?= htmlspecialchars($operation['symbol']) ?></h1>
                    <div>
                        <button class="btn btn-success" onclick="saveOperation()">
                            <i class="fas fa-save me-2"></i>Salvar Operação
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Ativo e Vencimento</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Preço Atual:</strong> R$ <?= number_format($operation['current_price'], 2, ',', '.') ?></p>
                                <p><strong>Vencimento:</strong> <?= htmlspecialchars($operation['expiration_date']) ?></p>
                                <p><strong>Dias até Vencimento:</strong> <?= $operation['days_to_maturity'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">Estrutura (Straddle)</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Strike:</strong> R$ <?= number_format($operation['strike'], 2, ',', '.') ?></p>
                                <p><strong>CALL (Venda):</strong> <?= htmlspecialchars($operation['call_symbol']) ?> (R$ <?= number_format($operation['call_premium'], 2, ',', '.') ?>)</p>
                                <p><strong>PUT (Venda):</strong> <?= htmlspecialchars($operation['put_symbol']) ?> (R$ <?= number_format($operation['put_premium'], 2, ',', '.') ?>)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="card-title mb-0">Métricas Esperadas</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Lucro Máximo:</strong> R$ <?= number_format($operation['max_profit'], 2, ',', '.') ?></p>
                                <p><strong>Retorno:</strong> <?= number_format($operation['profit_percent'], 2, ',', '.') ?>%</p>
                                <p><strong>BEP (Ponto de Equilíbrio):</strong> R$ <?= number_format($operation['strike'] - ($operation['call_premium'] + $operation['put_premium']), 2, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Nota:</strong> Esta é uma simulação baseada em dados de mercado. O lucro real pode variar dependendo da execução e taxas.
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function saveOperation() {
            const data = <?= json_encode($operation) ?>;
            fetch('/?action=save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'operation': JSON.stringify(data)
                })
            }).then(response => {
                if (response.ok) {
                    alert('Operação salva com sucesso!');
                } else {
                    alert('Erro ao salvar operação.');
                }
            });
        }
    </script>
</body>
</html>
