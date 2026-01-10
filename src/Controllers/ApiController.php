<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\Operation;
use App\Services\OPLabAPIClient;
use App\Services\StrategyEngine;

class ApiController {
    private $db;
    private $operationModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->operationModel = new Operation($this->db);
    }

    public function scan(): void {
        header('Content-Type: application/json');

        try {
            $accessToken = $_POST['access_token'] ?? $_GET['access_token'] ?? '';
            $symbols = $_POST['symbols'] ?? $_GET['symbols'] ?? '';
            $expiration = $_POST['expiration'] ?? $_GET['expiration'] ?? date('Y-m-d', strtotime('+30 days'));

            if (empty($accessToken) || empty($symbols)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Token de acesso e símbolos são obrigatórios'
                ]);
                return;
            }

            $apiClient = new OPLabAPIClient($accessToken);
            $strategyEngine = new StrategyEngine($apiClient);

            $selicAnnual = $apiClient->getInterestRate() ?? 0.1375;
            $symbolList = explode(',', $symbols);

            $results = [];
            foreach ($symbolList as $symbol) {
                $result = $strategyEngine->evaluateStraddles(
                    trim($symbol),
                    $expiration,
                    $selicAnnual
                );

                if ($result) {
                    $results[] = $result;
                }
            }

            // Sort by profit percentage
            usort($results, fn($a, $b) => $b['profit_percent'] <=> $a['profit_percent']);

            echo json_encode([
                'success' => true,
                'count' => count($results),
                'results' => $results,
                'metadata' => [
                    'expiration_date' => $expiration,
                    'selic_annual' => $selicAnnual,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => DEBUG_MODE ? $e->getTraceAsString() : null
            ]);
        }
    }

    public function saveOperation(): void {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Dados inválidos'
                ]);
                return;
            }

            $operationId = $this->operationModel->save($data);

            echo json_encode([
                'success' => true,
                'operation_id' => $operationId,
                'message' => 'Operação salva com sucesso'
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getOperation(int $id): void {
        header('Content-Type: application/json');

        try {
            $operation = $this->operationModel->findById($id);

            if (!$operation) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Operação não encontrada'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'operation' => $operation
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getOperations(): void {
        header('Content-Type: application/json');

        try {
            $filters = [
                'symbol' => $_GET['symbol'] ?? '',
                'min_profit' => $_GET['min_profit'] ?? 0,
                'max_days' => $_GET['max_days'] ?? 365
            ];

            $operations = $this->operationModel->search($filters);

            echo json_encode([
                'success' => true,
                'count' => count($operations),
                'operations' => $operations,
                'filters' => $filters
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
?>