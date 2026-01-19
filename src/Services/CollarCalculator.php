<?php

namespace App\Services;

class CollarCalculator {
    private $quantity;

    public function __construct(int $quantity = 100) {
        $this->quantity = $quantity;
    }

    public function getQuantity(): int {
        return $this->quantity;
    }

    public function calculateMetrics(
        float $currentPrice,
        float $callPremium,
        float $putPremium,
        float $callStrike,
        float $putStrike,
        int $daysToMaturity,
        float $selicAnnual,
        bool $includePayoffData = false
    ): array {
        // Cálculo do investimento inicial
        $stockInvestment = $currentPrice * $this->quantity;
        $callPremiumTotal = $callPremium * $this->quantity;
        $putPremiumTotal = $putPremium * $this->quantity;

        // Collar: Compra ação + Vende CALL + Compra PUT
        // Investimento = Ações + Custo líquido das opções
        $netOptionCost = $putPremiumTotal - $callPremiumTotal; // Positivo se PUT mais cara, negativo se CALL mais cara
        $initialInvestment = $stockInvestment + $netOptionCost;

        // ========== CÁLCULO DOS TRÊS CENÁRIOS DE LUCRO (como no Python) ==========

        // 1. CENÁRIO DE ALTA (preço acima do strike da CALL)
        $profitIfRise = ($callStrike - $currentPrice) * $this->quantity - $putPremiumTotal + $callPremiumTotal;

        // 2. CENÁRIO DE QUEDA (preço abaixo do strike da PUT)
        $profitIfFall = ($putStrike - $currentPrice) * $this->quantity - $putPremiumTotal + $callPremiumTotal;

        // 3. CENÁRIO LATERAL (preço entre os strikes)
        $profitIfSideways = -$putPremiumTotal + $callPremiumTotal; // Simplesmente o prêmio líquido

        // Lucro garantido (pior cenário) = mínimo dos três
        $guaranteedProfit = min($profitIfRise, $profitIfFall, $profitIfSideways);

        // Melhor lucro = máximo dos três
        $bestProfit = max($profitIfRise, $profitIfFall, $profitIfSideways);

        // Ponto de equilíbrio (onde lucro = 0)
        // Lucro = (S - currentPrice) + callPremium - putPremium = 0
        // S = currentPrice - callPremium + putPremium
        $breakeven = $currentPrice - $callPremium + $putPremium;
        
        // Se no strike da PUT o lucro já é positivo, não há BEP
        // Lucro_Put = (putStrike - currentPrice) + callPremium - putPremium
        $lucroNoStrikePut = ($putStrike - $currentPrice) + $callPremium - $putPremium;
        if ($lucroNoStrikePut > 0) {
            $breakeven = 0; // Ou outro sinalizador
        }

        // Margem de Segurança da Operação (MSO)
        $mso = (($currentPrice - $breakeven) / $currentPrice) * 100;

        // Rentabilidades percentuais
        $profitIfRisePercent = $initialInvestment > 0 ? ($profitIfRise / $initialInvestment) * 100 : 0;
        $profitIfFallPercent = $initialInvestment > 0 ? ($profitIfFall / $initialInvestment) * 100 : 0;
        $profitIfSidewaysPercent = $initialInvestment > 0 ? ($profitIfSideways / $initialInvestment) * 100 : 0;
        $guaranteedProfitPercent = $initialInvestment > 0 ? ($guaranteedProfit / $initialInvestment) * 100 : 0;
        $bestProfitPercent = $initialInvestment > 0 ? ($bestProfit / $initialInvestment) * 100 : 0;

        // Rentabilidade mensal do cenário garantido
        $monthlyGuaranteedProfitPercent = $guaranteedProfitPercent * (30 / $daysToMaturity);

        // Taxa SELIC para o período
        $selicPeriodReturn = $selicAnnual * ($daysToMaturity / 365);

        // Cálculo de proteção
        $downsideProtection = (($currentPrice - $putStrike) / $currentPrice) * 100;
        $upsideCap = (($callStrike - $currentPrice) / $currentPrice) * 100;

        // ========== CÁLCULO DE CENÁRIOS DETALHADOS (IGUAL AO PYTHON) ==========
        $detailedScenarios = [
            'Queda_30%' => $currentPrice * 0.7,
            'Queda_Forte_(Put)' => $putStrike * 0.8,
            'Queda_Moderada' => $putStrike * 0.9,
            'No_Strike_Put' => $putStrike,
            'Preço_Atual' => $currentPrice,
            'No_Strike_Call' => $callStrike,
            'Alta_Moderada' => $callStrike * 1.1,
            'Alta_Forte_(Call)' => $callStrike * 1.2,
            'Alta_30%' => $currentPrice * 1.3
        ];

        $scenariosResults = [];
        foreach ($detailedScenarios as $name => $priceFinal) {
            if ($priceFinal <= $putStrike) {
                $lucroScenario = ($putStrike - $currentPrice) * $this->quantity - $netOptionCost;
            } elseif ($priceFinal >= $callStrike) {
                $lucroScenario = ($callStrike - $currentPrice) * $this->quantity - $netOptionCost;
            } else {
                $lucroScenario = ($priceFinal - $currentPrice) * $this->quantity - $netOptionCost;
            }

            $scenariosResults[] = [
                'name' => str_replace('_', ' ', $name),
                'price' => $priceFinal,
                'profit' => $lucroScenario,
                'profit_percent' => ($lucroScenario / $initialInvestment) * 100
            ];
        }

        // Ordenar cenários por preço
        usort($scenariosResults, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });

        // ========== COMPARAÇÃO COM SELIC LÍQUIDA (IGUAL AO PYTHON) ==========
        // SELIC Bruta no período
        $taxaSelicPeriodo = (pow(1 + $selicAnnual, $daysToMaturity / 365)) - 1;
        $taxaSelicPeriodoPercent = $taxaSelicPeriodo * 100;
        
        // SELIC Líquida (descontando 22.5% de IR - conservador)
        $taxaSelicPeriodoPercentNet = $taxaSelicPeriodoPercent * 0.775;
        
        $selicComparison = [
            'selic_annual' => $selicAnnual * 100,
            'days_to_maturity' => $daysToMaturity,
            'selic_period_gross' => $taxaSelicPeriodoPercent,
            'selic_period_net' => $taxaSelicPeriodoPercentNet,
            'collar_min_profit' => $guaranteedProfitPercent,
            'advantage_over_selic' => $guaranteedProfitPercent - $taxaSelicPeriodoPercentNet
        ];

        // Valor extrínseco
        $callExtrinsicValue = max($callPremium - max($currentPrice - $callStrike, 0), 0);
        $putExtrinsicValue = max($putPremium - max($putStrike - $currentPrice, 0), 0);
        $totalExtrinsicValue = $callExtrinsicValue + $putExtrinsicValue;
        $extrinsicYield = ($totalExtrinsicValue / $currentPrice) * 100;

        // Dados para gráfico de payoff (mantido igual)
        $payoffData = [];
        if ($includePayoffData) {
            $priceMin = max(0, $currentPrice * 0.7);
            $priceMax = $currentPrice * 1.3;
            $step = ($priceMax - $priceMin) / 100;

            $prices = [];
            $payoffs = [];

            for ($price = $priceMin; $price <= $priceMax; $price += $step) {
                $prices[] = $price;

                // Payoff do Collar
                if ($price <= $putStrike) {
                    // PUT é exercida
                    $payoff = ($putStrike - $currentPrice) * $this->quantity - $netOptionCost;
                } elseif ($price >= $callStrike) {
                    // CALL é exercida
                    $payoff = ($callStrike - $currentPrice) * $this->quantity - $netOptionCost;
                } else {
                    // Entre os strikes
                    $payoff = ($price - $currentPrice) * $this->quantity - $netOptionCost;
                }

                $payoffs[] = $payoff;
            }

            $payoffData = [
                'prices' => $prices,
                'payoff' => $payoffs
            ];
        }

        return [
            // Dados básicos
            'current_price' => $currentPrice,
            'call_premium' => $callPremium,
            'put_premium' => $putPremium,
            'call_strike' => $callStrike,
            'put_strike' => $putStrike,
            'quantity' => $this->quantity,

            // Investimentos
            'stock_investment' => $stockInvestment,
            'call_premium_total' => $callPremiumTotal,
            'put_premium_total' => $putPremiumTotal,
            'net_option_cost' => $netOptionCost,
            'initial_investment' => $initialInvestment,

            // Resultados por cenário (COMO NO PYTHON)
            'profit_if_rise' => $profitIfRise,
            'profit_if_fall' => $profitIfFall,
            'profit_if_sideways' => $profitIfSideways,
            'guaranteed_profit' => $guaranteedProfit,
            'best_profit' => $bestProfit,

            // Rentabilidades percentuais
            'profit_if_rise_percent' => $profitIfRisePercent,
            'profit_if_fall_percent' => $profitIfFallPercent,
            'profit_if_sideways_percent' => $profitIfSidewaysPercent,
            'guaranteed_profit_percent' => $guaranteedProfitPercent,
            'best_profit_percent' => $bestProfitPercent,
            'monthly_guaranteed_profit_percent' => $monthlyGuaranteedProfitPercent,

            // Análise de risco
            'breakeven' => $breakeven,
            'mso' => $mso,
            'downside_protection' => $downsideProtection,
            'upside_cap' => $upsideCap,

            // Valor extrínseco
            'call_extrinsic_value' => $callExtrinsicValue,
            'put_extrinsic_value' => $putExtrinsicValue,
            'total_extrinsic_value' => $totalExtrinsicValue,
            'extrinsic_yield' => $extrinsicYield,

            // SELIC
            'selic_annual' => $selicAnnual,
            'selic_period_return' => $selicPeriodReturn,
            'selic_comparison' => $selicComparison,

            // Cenários detalhados
            'detailed_scenarios' => $scenariosResults,

            // Dados para gráfico
            'payoff_data' => $payoffData,

            // Compatibilidade (mantendo campos antigos)
            'max_profit' => $profitIfRise, // para compatibilidade
            'max_loss' => $profitIfFall,   // para compatibilidade
            'profit_percent' => $profitIfRisePercent, // para compatibilidade
            'monthly_profit_percent' => $monthlyGuaranteedProfitPercent, // para compatibilidade
        ];
    }
    public function findBreakevens(array $payoff, array $priceRange): array {
        $breakevens = [];

        for ($i = 0; $i < count($payoff) - 1; $i++) {
            if (($payoff[$i] <= 0 && $payoff[$i + 1] >= 0) ||
                ($payoff[$i] >= 0 && $payoff[$i + 1] <= 0)) {

                // Interpolação linear
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
}