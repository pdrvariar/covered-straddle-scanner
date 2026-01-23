<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\Operation;
use App\Services\OPLabAPIClient;
use App\Services\StrategyEngine;
use App\Services\CoveredStraddleRanker;

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

        // Buscar vencimentos dinâmicos
        $accessToken = $_ENV['OPLAB_TOKEN'] ?? '';
        $dynamicExpirations = [];
        if (!empty($accessToken)) {
            $apiClient = new OPLabAPIClient($accessToken);
            $dynamicExpirations = $apiClient->getOptionExpirations("LEVE3", 3);
        }

        // Recuperar parâmetros da sessão se existirem
        $params = $_SESSION['scan_params'] ?? [];
        $filters = $_SESSION['scan_filters'] ?? [];

        include __DIR__ . '/../Views/scanner.php';
    }

    public function results() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accessToken = $_ENV['OPLAB_TOKEN'] ?? '';
            $tickersInput = $_POST['tickers'] ?? '';
            $expirationDate = $_POST['expiration_date'] ?? '';
            $totalCapital = (float)($_POST['total_capital'] ?? 50000);

            if (empty($accessToken)) {
                $_SESSION['error'] = 'Token de acesso OPLab não configurado no arquivo .env';
                header('Location: /?action=scan');
                exit;
            }

            if (empty($tickersInput)) {
                $_SESSION['error'] = 'A lista de tickers é obrigatória';
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
                    'max_spread' => (float)($_POST['max_spread'] ?? 0.05),
                    'max_recency' => (int)($_POST['max_recency'] ?? 5),
                    'min_profit' => (float)($_POST['min_profit'] ?? 0),
                    'strategy_type' => $_POST['strategy_type'] ?? 'covered_straddle',
                    'strike_range' => (float)($_POST['strike_range'] ?? 2.0)
                ];

                foreach ($tickers as $ticker) {
                    $result = $strategyEngine->evaluate(
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

                // 1. Ordenação inicial para definir o "ranking do sistema" baseado em rentabilidade
                usort($results, function($a, $b) {
                    $rentA = min($a['profit_if_rise_percent'] ?? 0, $a['profit_if_fall_percent'] ?? 0);
                    $rentB = min($b['profit_if_rise_percent'] ?? 0, $b['profit_if_fall_percent'] ?? 0);
                    return $rentB <=> $rentA;
                });

                // 2. Adicionar o ranking_sistema
                foreach ($results as $index => &$res) {
                    $res['ranking_sistema'] = $index + 1;
                }

                // 3. Aplicar o algoritmo de ordenação especializado (CoveredStraddleRanker)
                if (($filters['strategy_type'] ?? 'covered_straddle') === 'covered_straddle') {
                    $ranker = new CoveredStraddleRanker();
                    $results = $ranker->ordenarOperacoes($results);
                } else {
                    // Fallback para outras estratégias: manter ordenação por rentabilidade
                }

                // Store in session for later use
                $_SESSION['scan_results'] = $results;
                $_SESSION['scan_params'] = [
                    'tickers' => $tickersInput,
                    'expiration_date' => $expirationDate,
                    'total_capital' => $totalCapital,
                    'selic_annual' => $selicAnnual,
                    'strategy_type' => $filters['strategy_type'] ?? 'covered_straddle',
                    'strike_range' => $filters['strike_range'] ?? 2.0
                ];
                $_SESSION['scan_filters'] = $filters;

                // Redirect to avoid form resubmission and allow back button
                header('Location: /?action=results');
                exit;

            } catch (\Exception $e) {
                $_SESSION['error'] = 'Erro na análise: ' . $e->getMessage();
                header('Location: /?action=scan');
                exit;
            }
        } else {
            // Se já houver resultados na sessão, exibe-os
            if (isset($_SESSION['scan_results'])) {
                $results = $_SESSION['scan_results'];
                $params = $_SESSION['scan_params'] ?? [];
                include __DIR__ . '/../Views/results.php';
            } else {
                header('Location: /?action=scan');
                exit;
            }
        }
    }
    public function details() {
        $operationId = $_GET['id'] ?? null;
        $totalCapital = $_GET['capital'] ?? 50000;

        if ($operationId) {
            // Load from database
            $operationModel = new Operation($this->db);
            $operation = Operation::getById($operationId);

            // Verificar e padronizar as chaves
            if ($operation && isset($operation['strike_price'])) {
                $operation['strike'] = $operation['strike_price']; // Para compatibilidade
            }
        } else {
            // Load from session (temporary analysis)
            $index = $_GET['index'] ?? 0;

            if (isset($_SESSION['scan_results'][$index])) {
                $operation = $_SESSION['scan_results'][$index];

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
            // Obtém os dados do POST
            $jsonData = $_POST['operation'] ?? file_get_contents('php://input');

            error_log("Tentativa de salvar operação via AJAX. Dados recebidos: " . substr($jsonData, 0, 500));

            if (!empty($jsonData)) {
                // Decodifica o JSON
                $operationData = json_decode($jsonData, true);

                if (is_array($operationData)) {
                    try {
                        // Log para depuração
                        error_log("Salvando operação para o símbolo: " . ($operationData['symbol'] ?? 'N/A'));
                        error_log("Tipo de estratégia: " . ($operationData['strategy_type'] ?? 'covered_straddle'));

                        // Garantir que os campos necessários existam
                        if (!isset($operationData['strike_price']) && isset($operationData['call_strike'])) {
                            $operationData['strike_price'] = $operationData['call_strike'];
                        }

                        if (!isset($operationData['call_strike']) && isset($operationData['strike_price'])) {
                            $operationData['call_strike'] = $operationData['strike_price'];
                        }

                        if (!isset($operationData['put_strike']) && isset($operationData['strike_price'])) {
                            $operationData['put_strike'] = $operationData['strike_price'];
                        }

                        // Usa o método estático do modelo Operation
                        $operationId = Operation::save($operationData);

                        if ($operationId) {
                            error_log("Operação salva com sucesso! ID: " . $operationId);

                            // Retornar JSON para AJAX
                            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                                header('Content-Type: application/json');
                                echo json_encode([
                                    'success' => true,
                                    'id' => $operationId,
                                    'message' => 'Operação salva com sucesso!'
                                ]);
                                exit;
                            } else {
                                // Redirecionamento normal
                                header('Location: /?action=details&id=' . $operationId);
                                exit;
                            }
                        } else {
                            throw new \Exception('O banco de dados não retornou o ID da operação.');
                        }
                    } catch (\Exception $e) {
                        $errorMsg = 'Erro no processamento do banco de dados: ' . $e->getMessage();
                        error_log($errorMsg);

                        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                            header('Content-Type: application/json');
                            echo json_encode([
                                'success' => false,
                                'error' => $errorMsg
                            ]);
                            exit;
                        } else {
                            \add_flash_notification($errorMsg, 'error');
                            header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/?action=scan'));
                            exit;
                        }
                    }
                } else {
                    $errorMsg = 'O formato dos dados da operação é inválido (JSON malformado).';
                    error_log($errorMsg);
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'error' => $errorMsg
                        ]);
                        exit;
                    }
                }
            } else {
                $errorMsg = 'Nenhum dado de operação foi recebido no POST.';
                error_log($errorMsg);
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => $errorMsg
                    ]);
                    exit;
                }
            }
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/?action=scan'));
        exit;
    }
}
?>