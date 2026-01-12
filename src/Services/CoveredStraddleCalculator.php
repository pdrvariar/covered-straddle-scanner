<?php

namespace App\Services;

class CoveredStraddleCalculator {
    private $quantity;
    private $lfts11Price;

    public function __construct(int $quantity = 1000, float $lfts11Price = 100.00) {
        $this->quantity = $quantity;
        $this->lfts11Price = $lfts11Price;
    }

    public function getQuantity(): int {
        return $this->quantity;
    }

    public function calculatePayoffVector(
        float $currentPrice,
        float $callPremium,
        float $putPremium,
        float $strike,
        array $priceRange,
        float $selicReturn,
        float $lfts11Investment,
        float $lfts11Return
    ): array {
        $payoffs = [];

        foreach ($priceRange as $price) {
            // Stock payoff
            $stockPayoff = ($price - $currentPrice) * $this->quantity;

            // Call payoff (vendida)
            $callIntrinsic = max($price - $strike, 0);
            $callPayoff = ($callPremium - $callIntrinsic) * $this->quantity;

            // Put payoff (vendida)
            $putIntrinsic = max($strike - $price, 0);
            $putPayoff = ($putPremium - $putIntrinsic) * $this->quantity;

            // Total payoff with SELIC return from LFTS11
            $totalPayoff = $stockPayoff + $callPayoff + $putPayoff + $lfts11Return;
            $payoffs[] = $totalPayoff;
        }

        return $payoffs;
    }

    public function findBreakevens(array $payoff, array $priceRange): array {
        $breakevens = [];

        for ($i = 0; $i < count($payoff) - 1; $i++) {
            if (($payoff[$i] <= 0 && $payoff[$i + 1] >= 0) ||
                ($payoff[$i] >= 0 && $payoff[$i + 1] <= 0)) {

                // Linear interpolation
                $x1 = $priceRange[$i];
                $x2 = $priceRange[$i + 1];
                $y1 = $payoff[$i];
                $y2 = $payoff[$i + 1];

                if ($y1 != $y2) {
                    $breakeven = $x1 - $y1 * ($x2 - $x1) / ($y2 - $y1);
                    $breakevens[] = round($breakeven, 2);
                }
            }
        }

        return $breakevens;
    }

    public function calculateMetrics(
        float $currentPrice,
        float $callPremium,
        float $putPremium,
        float $strike,
        int $daysToMaturity,
        float $selicAnnual,
        float $lfts11Price = null
    ): array {
        // Usar preço fornecido ou default
        $lfts11Price = $lfts11Price ?? $this->lfts11Price;

        // SELIC return for the period
        $selicPeriodReturn = $selicAnnual * ($daysToMaturity / 365);

        // Cálculo do investimento em LFTS11 para garantir a PUT
        $totalGuaranteeNeeded = $strike * $this->quantity;
        $lfts11Quantity = ceil($totalGuaranteeNeeded / $lfts11Price);
        $lfts11Investment = $lfts11Quantity * $lfts11Price;
        $lfts11Return = $lfts11Investment * $selicPeriodReturn;

        // Cálculos das garantias
        $stockInvestment = $currentPrice * $this->quantity;
        $totalPremiums = ($callPremium + $putPremium) * $this->quantity;

        // Initial investment breakdown
        $initialInvestment = $stockInvestment + $lfts11Investment - $totalPremiums;

        // Price range for simulation
        $priceMin = max(0, $currentPrice * 0.3);
        $priceMax = $currentPrice * 1.7;
        $step = ($priceMax - $priceMin) / 1000;

        $priceRange = [];
        $payoff = [];

        for ($i = 0; $i <= 1000; $i++) {
            $price = $priceMin + ($step * $i);
            $priceRange[] = $price;

            // Calculate payoff for this price
            $stockPayoff = ($price - $currentPrice) * $this->quantity;
            $callIntrinsic = max($price - $strike, 0);
            $callPayoff = ($callPremium - $callIntrinsic) * $this->quantity;
            $putIntrinsic = max($strike - $price, 0);
            $putPayoff = ($putPremium - $putIntrinsic) * $this->quantity;
            $totalPayoff = $stockPayoff + $callPayoff + $putPayoff + $lfts11Return;

            $payoff[] = $totalPayoff;
        }

        // Calculate metrics
        $maxProfit = max($payoff);
        $maxLoss = min($payoff);
        $breakevens = $this->findBreakevens($payoff, $priceRange);

        $profitPercent = ($maxProfit / $initialInvestment) * 100;
        $monthlyProfit = $maxProfit * (30 / $daysToMaturity);
        $monthlyProfitPercent = ($monthlyProfit / $initialInvestment) * 100;

        return [
            // Investimentos
            'stock_investment' => $stockInvestment,
            'lfts11_investment' => $lfts11Investment,
            'lfts11_quantity' => $lfts11Quantity,
            'lfts11_price' => $lfts11Price,
            'total_premiums' => $totalPremiums,
            'total_guarantee_needed' => $totalGuaranteeNeeded,

            // Retornos
            'initial_investment' => $initialInvestment,
            'max_profit' => $maxProfit,
            'max_loss' => $maxLoss,
            'profit_percent' => $profitPercent,
            'monthly_profit' => $monthlyProfit,
            'monthly_profit_percent' => $monthlyProfitPercent,

            // Taxas e retornos
            'selic_annual' => $selicAnnual,
            'selic_monthly' => $selicAnnual / 12,
            'selic_period_return' => $selicPeriodReturn,
            'lfts11_return' => $lfts11Return,

            // Pontos de equilíbrio
            'breakevens' => $breakevens,

            // Dados para gráficos
            'payoff_data' => [
                'prices' => $priceRange,
                'payoff' => $payoff
            ],

            // Análise de risco
            'margin_safety' => (($strike - $currentPrice) / $currentPrice) * 100,
            'downside_protection' => (($strike / $currentPrice) - 1) * 100,
            'premium_yield' => ($totalPremiums / ($stockInvestment + $lfts11Investment)) * 100
        ];
    }
}