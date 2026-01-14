<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\Operation;
use App\Models\UserPreference;

class DashboardController {
    private $operationModel;
    private $userPrefModel;

    public function __construct() {
        // Inicializar os models
        $this->operationModel = new Operation();

        // UserPreference ainda precisa do PDO no construtor
        $database = new Database();
        $db = $database->connect();
        $this->userPrefModel = new UserPreference($db);
    }

    public function index(): void {
        try {
            // Get dashboard statistics
            $stats = $this->operationModel->getStats();
            $recentOperations = $this->operationModel->getRecent(10);
            $userSettings = $this->userPrefModel->getSettings();

            // Get real market data from API
            $accessToken = $_ENV['OPLAB_TOKEN'] ?? '';
            $selicAnnual = 13.75; // Default fallback

            if (!empty($accessToken)) {
                try {
                    $apiClient = new \App\Services\OPLabAPIClient($accessToken);
                    $apiSelic = $apiClient->getInterestRate();
                    if ($apiSelic !== null) {
                        $selicAnnual = $apiSelic * 100; // Convert to percentage
                    }
                } catch (\Exception $e) {
                    error_log("Erro ao buscar SELIC da API: " . $e->getMessage());
                }
            }

            // Mock market data (only what's still needed)
            $market = [
                'selic' => $selicAnnual
            ];

            // Calculate success rate
            $successRate = 0;
            if (!empty($recentOperations)) {
                $profitable = array_filter($recentOperations, fn($op) => ($op['profit_percent'] ?? 0) > 0);
                $successRate = round(count($profitable) / count($recentOperations) * 100, 1);
            }

            $stats['success_rate'] = $successRate;

            // Calculate protection margin (average)
            $avgProtection = 0;
            if (!empty($recentOperations)) {
                $protections = array_map(function($op) {
                    if (($op['current_price'] ?? 0) > 0 && ($op['strike_price'] ?? 0) > 0) {
                        return (($op['strike_price'] - $op['current_price']) / $op['current_price'] * 100);
                    }
                    return 0;
                }, $recentOperations);
                $avgProtection = round(array_sum($protections) / count($protections), 2);
            }

            $stats['protection'] = $avgProtection;

            // Formatar valores para exibição
            $stats['best_profit'] = number_format($stats['best_profit'] ?? 0, 2, ',', '.');
            $stats['avg_profit'] = number_format($stats['avg_profit'] ?? 0, 2, ',', '.');
            $stats['protection'] = number_format($stats['protection'] ?? 0, 2, ',', '.');
            $stats['selic'] = number_format($market['selic'], 2, ',', '.');
            $stats['avg_days'] = number_format($stats['avg_days'] ?? 0, 0, ',', '.');

            // Definir variáveis para o header
            $page_title = 'Dashboard - Covered Straddle Scanner';

            // Incluir header
            require __DIR__ . '/../Views/layout/header.php';

            // Passar variáveis para o dashboard
            $recent_operations = $recentOperations;

            require __DIR__ . '/../Views/dashboard.php';

            // Incluir footer
            require __DIR__ . '/../Views/layout/footer.php';

        } catch (\Exception $e) {
            echo "Erro no Dashboard: " . $e->getMessage();
            error_log("Dashboard error: " . $e->getMessage());
        }
    }
}