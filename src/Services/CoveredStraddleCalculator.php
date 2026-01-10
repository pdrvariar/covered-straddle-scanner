<?php

namespace App\Services;

class CoveredStraddleCalculator {
    private $quantity;

    public function __construct(int $quantity = 1000) {
        $this->quantity = $quantity;
    }

    public function calculatePayoffVector(
        float $currentPrice,
        float $callPremium,
        float $putPremium,
        float $strike,
        array $priceRange,
        float $selicReturn
    ): array {
        $payoffs = [];

        foreach ($priceRange as $price) {
            // Stock payoff
            $stockPayoff = ($price - $currentPrice) * $this->quantity;

            // Call payoff
            $callIntrinsic = max($price - $strike, 0);
            $callPayoff = ($callPremium - $callIntrinsic) * $this->quantity;

            // Put payoff
            $putIntrinsic = max($strike - $price, 0);
            $putPayoff = ($putPremium - $putIntrinsic) * $this->quantity;

            // Total payoff with SELIC
            $totalPayoff = $stockPayoff + $callPayoff + $putPayoff + $selicReturn;
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
        float $selicAnnual
    ): array {
        // SELIC return for the period
        $selicPeriodReturn = $selicAnnual * ($daysToMaturity / 365);
        $lfts11Return = $strike * $this->quantity * $selicPeriodReturn;

        // Initial investment
        $initialInvestment = ($currentPrice * $this->quantity) -
            ($callPremium * $this->quantity) -
            ($putPremium * $this->quantity) +
            ($strike * $this->quantity);

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
            'initial_investment' => $initialInvestment,
            'max_profit' => $maxProfit,
            'max_loss' => $maxLoss,
            'profit_percent' => $profitPercent,
            'monthly_profit' => $monthlyProfit,
            'monthly_profit_percent' => $monthlyProfitPercent,
            'selic_annual' => $selicAnnual,
            'selic_monthly' => $selicAnnual / 12,
            'selic_period_return' => $selicPeriodReturn,
            'lfts11_return' => $lfts11Return,
            'breakevens' => $breakevens,
            'payoff_data' => [
                'prices' => $priceRange,
                'payoff' => $payoff
            ]
        ];
    }
}
?>