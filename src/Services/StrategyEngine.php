<?php

namespace App\Services;

use DateTime;

class StrategyEngine {
    private $apiClient;
    private $strategies = [];

    public function __construct(OPLabAPIClient $apiClient) {
        $this->apiClient = $apiClient;

        // Registrar ambas as estratégias disponíveis
        $this->registerStrategy(new CoveredStraddleStrategy($apiClient));
        $this->registerStrategy(new CollarStrategy($apiClient));
    }

    public function registerStrategy(IOptionStrategy $strategy) {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    public function evaluate(string $symbol, string $expirationDate, float $selicAnnual, array $filters = [], bool $includePayoffData = false): ?array {
        $strategyType = $filters['strategy_type'] ?? 'covered_straddle';

        if (!isset($this->strategies[$strategyType])) {
            error_log("⚠️ Estratégia '$strategyType' não encontrada ou não implementada.");
            return null;
        }

        $strategy = $this->strategies[$strategyType];
        return $strategy->execute($symbol, $expirationDate, $selicAnnual, $filters, $includePayoffData);
    }
}