<?php
namespace App\Controllers;

use App\Models\Operation;

class OperationController
{
    public function index()
    {
        try {
            $filters = [];

            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }

            if (isset($_GET['symbol'])) {
                $filters['symbol'] = $_GET['symbol'];
            }

            $operations = Operation::getAll($filters);

            // Criar uma instância do Operation para usar getStats()
            $operationModel = new Operation();
            $stats = $operationModel->getStats();

            // Definir variáveis para o header
            $page_title = 'Operações - Covered Straddle Scanner';

            // Incluir header
            require __DIR__ . '/../Views/layout/header.php';

            // Incluir view operations.php
            require __DIR__ . '/../Views/operations.php';

            // Incluir footer
            require __DIR__ . '/../Views/layout/footer.php';

        } catch (\Exception $e) {
            echo "Erro ao carregar operações: " . $e->getMessage();
            error_log("Operations error: " . $e->getMessage());
        }
    }

    public function export()
    {
        try {
            $operations = Operation::getAll();

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="operacoes_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');

            // Cabeçalho
            fputcsv($output, [
                'ID', 'Ativo', 'Preço Atual', 'Strike', 'Call', 'Prêmio Call',
                'Put', 'Prêmio Put', 'Expiração', 'Dias', 'Investimento',
                'Lucro Máx', 'Perda Máx', 'Lucro %', 'Lucro Mensal %',
                'SELIC', 'Status', 'Data Entrada'
            ], ';');

            // Dados
            foreach ($operations as $op) {
                fputcsv($output, [
                    $op['id'] ?? '',
                    $op['symbol'] ?? '',
                    $op['current_price'] ?? 0,
                    $op['strike_price'] ?? 0,
                    $op['call_symbol'] ?? '',
                    $op['call_premium'] ?? 0,
                    $op['put_symbol'] ?? '',
                    $op['put_premium'] ?? 0,
                    $op['expiration_date'] ?? '',
                    $op['days_to_maturity'] ?? 0,
                    $op['initial_investment'] ?? 0,
                    $op['max_profit'] ?? 0,
                    $op['max_loss'] ?? 0,
                    $op['profit_percent'] ?? 0,
                    $op['monthly_profit_percent'] ?? 0,
                    $op['selic_annual'] ?? 0,
                    $op['status'] ?? 'active',
                    $op['created_at'] ?? ''
                ], ';');
            }

            fclose($output);
            exit;

        } catch (\Exception $e) {
            echo "Erro ao exportar: " . $e->getMessage();
        }
    }
}