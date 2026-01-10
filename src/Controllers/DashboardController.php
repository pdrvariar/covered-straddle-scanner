<?php

namespace App\Controllers;

use App\Config\Database;
use App\Models\Operation;
use App\Models\UserPreference;

class DashboardController {
    private $db;
    private $operationModel;
    private $userPrefModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        $this->operationModel = new Operation($this->db);
        $this->userPrefModel = new UserPreference($this->db);
    }

    public function index(): void {
        // Get dashboard statistics
        $stats = $this->operationModel->getStats();
        $recentOperations = $this->operationModel->getRecent(10);
        $userSettings = $this->userPrefModel->getSettings();

        // Mock market data (in production, fetch from API)
        $market = [
            'ibov' => 127850,
            'ibov_change' => 0.85,
            'selic' => 13.75
        ];

        // Calculate success rate
        $successRate = 0;
        if (!empty($recentOperations)) {
            $profitable = array_filter($recentOperations, fn($op) => $op['profit_percent'] > 0);
            $successRate = round(count($profitable) / count($recentOperations) * 100, 1);
        }

        $stats['success_rate'] = $successRate;

        // Calculate protection margin (average)
        $avgProtection = 0;
        if (!empty($recentOperations)) {
            $protections = array_map(function($op) {
                // Simplified protection calculation
                return ($op['strike_price'] / $op['current_price'] * 100) - 100;
            }, $recentOperations);
            $avgProtection = round(array_sum($protections) / count($protections), 2);
        }

        $stats['protection'] = $avgProtection;

        // Include view
        include __DIR__ . '/../Views/dashboard.php';
    }
}
?>