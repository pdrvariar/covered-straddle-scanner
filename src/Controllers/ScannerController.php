<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\Operation;
use App\Services\OPLabAPIClient;
use App\Services\StrategyEngine;

class ScannerController {
    private $db;

    public function __construct() {
        // Bootstrap já carregou o autoload
        $database = new Database();
        $this->db = $database->connect();
    }

    public function scan() {
        // Display scanning interface
        $defaultTickers = "BBAS3,PETR4,BBSE3,VALE3,ITSA4,CMIG4,TAEE11,CXSE3,ISAE4,WEGE3,CSMG3,EGIE3,ITUB4,GOAU4,BBDC3,KLBN11,AURE3,CMIN3,RANI3,LEVE3,SAPR11,B3SA3,ABEV3,KEPL3,BMGB4,JHSF3,AGRO3,ABCB4,CSAN3,BRAP4,CPFE3,PRIO3,PSSA3,FIQE3";

        include __DIR__ . '/../Views/scanner.php';
    }

    public function results() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accessToken = $_POST['access_token'] ?? ($_ENV['OPLAB_TOKEN'] ?? '');
            $tickersInput = $_POST['tickers'] ?? '';
            $expirationDate = $_POST['expiration_date'] ?? '';
            $totalCapital = $_POST['total_capital'] ?? 50000;

            if (empty($accessToken) || empty($tickersInput)) {
                $_SESSION['error'] = 'Token de acesso e lista de tickers são obrigatórios';
                header('Location: /?action=scan');
                exit;
            }

            try {
                // Agora o autoload já está carregado, Guzzle estará disponível
                $apiClient = new OPLabAPIClient($accessToken);
                $strategyEngine = new StrategyEngine($apiClient);

                // Get SELIC rate
                $selicAnnual = $apiClient->getInterestRate();
                if (!$selicAnnual) {
                    $selicAnnual = 0.1375; // Fallback
                }

                $tickers = array_map('trim', explode(',', $tickersInput));
                $results = [];

                // Capturar filtros
                $filters = [
                    'filter_liquidity' => isset($_POST['filter_liquidity']),
                    'filter_recency' => isset($_POST['filter_recency']),
                    'min_profit' => (float)($_POST['min_profit'] ?? 0)
                ];

                foreach ($tickers as $ticker) {
                    $result = $strategyEngine->evaluateStraddles(
                        $ticker,
                        $expirationDate,
                        $selicAnnual,
                        $filters
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

                // ORDENAÇÃO DO MAIOR PARA O MENOR LUCRO PERCENTUAL
                usort($results, function($a, $b) {
                    $profitA = $a['profit_percent'] ?? 0;
                    $profitB = $b['profit_percent'] ?? 0;
                    return $profitB <=> $profitA; // Ordem decrescente (maior para menor)
                });

                // Store in session for later use
                $_SESSION['scan_results'] = $results;
                $_SESSION['scan_params'] = [
                    'expiration_date' => $expirationDate,
                    'total_capital' => $totalCapital,
                    'selic_annual' => $selicAnnual
                ];

                // Display results
                include __DIR__ . '/../Views/results.php';

            } catch (\Exception $e) {
                $_SESSION['error'] = 'Erro na análise: ' . $e->getMessage();
                header('Location: /?action=scan');
                exit;
            }
        } else {
            header('Location: /?action=scan');
            exit;
        }
    }
    public function details() {
        $operationId = $_GET['id'] ?? null;
        $totalCapital = $_GET['capital'] ?? 50000;

        if ($operationId) {
            // Load from database
            $operationModel = new Operation($this->db);
            $operation = $operationModel->findById($operationId);

            // Verificar e padronizar as chaves
            if ($operation && isset($operation['strike_price'])) {
                $operation['strike'] = $operation['strike_price']; // Para compatibilidade
            }
        } else {
            // Load from session (temporary analysis)
            $results = $_SESSION['scan_results'] ?? [];
            $index = $_GET['index'] ?? 0;

            if (isset($results[$index])) {
                $operation = $results[$index];

                // Verificar e padronizar as chaves
                if (isset($operation['strike_price'])) {
                    $operation['strike'] = $operation['strike_price']; // Para compatibilidade
                }
            } else {
                header('Location: /?action=scan');
                exit;
            }
        }

        include __DIR__ . '/../Views/operation-details.php';
    }
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtém os dados do POST (que vêm como JSON string)
            $jsonData = $_POST['operation'] ?? '';

            if (!empty($jsonData)) {
                // Decodifica o JSON
                $operationData = json_decode($jsonData, true);

                if (is_array($operationData)) {
                    // Adiciona campos padrão se não existirem
                    $operationData['status'] = $operationData['status'] ?? 'active';
                    $operationData['strategy_type'] = $operationData['strategy_type'] ?? 'covered_straddle';
                    $operationData['risk_level'] = $operationData['risk_level'] ?? 'medium';
                    $operationData['notes'] = $operationData['notes'] ?? 'Salvo via Scanner';

                    try {
                        // Usa o método estático do modelo Operation
                        $operationId = Operation::save($operationData);

                        if ($operationId) {
                            // Usar notificação flash
                            add_flash_notification('Operação salva com sucesso! ID: ' . $operationId, 'success');

                            // Retornar JSON para AJAX
                            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                                json_response(['id' => $operationId], true, 'Operação salva com sucesso!');
                            } else {
                                // Redirecionamento normal
                                header('Location: /?action=details&id=' . $operationId);
                                exit;
                            }
                        } else {
                            throw new Exception('Não foi possível obter o ID da operação.');
                        }
                    } catch (\Exception $e) {
                        $errorMsg = 'Erro ao salvar operação: ' . $e->getMessage();
                        error_log($errorMsg);

                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            json_response([], false, $errorMsg);
                        } else {
                            add_flash_notification($errorMsg, 'error');
                            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/?action=scan'));
                            exit;
                        }
                    }
                }
            }

            $errorMsg = 'Dados da operação inválidos ou não recebidos.';
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                json_response([], false, $errorMsg);
            } else {
                add_flash_notification($errorMsg, 'error');
            }
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/?action=scan'));
        exit;
    }
}
?>