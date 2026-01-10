<?php

namespace App\Controllers;

use App\Config\Database;
use App\Services\OPLabAPIClient;
use App\Services\StrategyEngine;
use App\Models\Operation;

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
            $accessToken = $_POST['access_token'] ?? '';
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

                foreach ($tickers as $ticker) {
                    $result = $strategyEngine->evaluateStraddles(
                        $ticker,
                        $expirationDate,
                        $selicAnnual
                    );

                    if ($result) {
                        $results[] = $result;
                    }
                }

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
        } else {
            // Load from session (temporary analysis)
            $results = $_SESSION['scan_results'] ?? [];
            $index = $_GET['index'] ?? 0;

            if (isset($results[$index])) {
                $operation = $results[$index];
            } else {
                header('Location: /?action=scan');
                exit;
            }
        }

        include __DIR__ . '/../Views/operation-details.php';
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $operationData = $_POST['operation'] ?? [];

            if (!empty($operationData)) {
                $operationModel = new Operation($this->db);
                $operationId = $operationModel->save($operationData);

                if ($operationId) {
                    $_SESSION['success'] = 'Operação salva com sucesso!';
                    header('Location: /?action=details&id=' . $operationId);
                    exit;
                }
            }
        }

        $_SESSION['error'] = 'Erro ao salvar operação';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}
?>