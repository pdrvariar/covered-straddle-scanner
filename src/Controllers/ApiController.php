<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\Operation;
use App\Services\OPLabAPIClient;
use App\Services\StrategyEngine;

class ApiController {
    public function __construct() {
    }

    public function scan(): void {
        header('Content-Type: application/json');

        try {
            $accessToken = $_ENV['OPLAB_TOKEN'] ?? '';
            $symbols = $_POST['symbols'] ?? $_GET['symbols'] ?? '';
            $expiration = $_POST['expiration'] ?? $_GET['expiration'] ?? date('Y-m-d', strtotime('+30 days'));

            if (empty($accessToken)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Token de acesso OPLab não configurado no servidor'
                ]);
                return;
            }

            if (empty($symbols)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Símbolos são obrigatórios'
                ]);
                return;
            }

            $apiClient = new OPLabAPIClient($accessToken);
            $strategyEngine = new StrategyEngine($apiClient);

            $selicAnnual = $apiClient->getInterestRate() ?? 0.1375;
            $symbolList = explode(',', $symbols);

            $results = [];
            foreach ($symbolList as $symbol) {
                $result = $strategyEngine->evaluate(
                    trim($symbol),
                    $expiration,
                    $selicAnnual
                );

                if ($result) {
                    // Se o método retornar um array de straddles (múltiplos strikes)
                    if (isset($result[0]) && is_array($result[0])) {
                        $results = array_merge($results, $result);
                    } else {
                        $results[] = $result;
                    }
                }
            }

            // Ordenar do MAIOR para o MENOR MSO (Margem de Segurança da Operação)
            usort($results, function($a, $b) {
                $msoA = $a['mso'] ?? 0;
                $msoB = $b['mso'] ?? 0;
                return $msoB <=> $msoA;
            });

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
                'error' => $e->getMessage()
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

            $operationId = Operation::save($data);

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
            $operation = Operation::getById($id);

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

            $operations = Operation::getAll($filters);

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

    public function deleteOperation()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['operation_id'])) {
                echo json_encode(['success' => false, 'message' => 'ID da operação não fornecido']);
                return;
            }

            try {
                $operationId = $data['operation_id'];
                $success = Operation::delete($operationId);

                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Operação excluída com sucesso']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Não foi possível excluir a operação. Verifique se ela existe e pertence ao seu usuário.']);
                }
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir operação: ' . $e->getMessage()]);
            }
        } else {
            header('HTTP/1.1 405 Method Not Allowed');
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        }
    }
}
?>