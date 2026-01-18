<?php

namespace App\Services;

use DateTime;

class CollarStrategy implements IOptionStrategy {
    private $apiClient;
    private $calculator;

    public function __construct(OPLabAPIClient $apiClient) {
        $this->apiClient = $apiClient;
        $this->calculator = new CollarCalculator();
    }

    public function getName(): string {
        return 'collar';
    }

    public function execute(string $symbol, string $expirationDate, float $selicAnnual, array $filters = [], bool $includePayoffData = false): ?array {
        try {
            error_log("=== Executando estratégia Collar para $symbol (venc: $expirationDate) ===");

            // 1. Buscar dados da ação
            $stockData = $this->apiClient->getStockData($symbol);
            if (!$stockData) {
                error_log("❌ Dados da ação $symbol não encontrados");
                return null;
            }

            $currentPrice = $stockData['close'];
            error_log("📊 Preço atual de $symbol: R$ " . number_format($currentPrice, 2));

            // Verificar se tem opções listadas
            if (!($stockData['has_options'] ?? false)) {
                error_log("⚠️  Ação $symbol não tem opções listadas");
                return null;
            }

            // 2. Buscar opções filtradas
            $atmOptions = $this->apiClient->getAtmOptions($symbol, $expirationDate, $currentPrice, $filters);

            if (empty($atmOptions)) {
                error_log("❌ Nenhuma opção encontrada para $symbol no vencimento $expirationDate");
                return null;
            }

            // Filtrar apenas opções com o vencimento exato
            $atmOptions = array_filter($atmOptions, function($opt) use ($expirationDate) {
                return ($opt['due_date'] ?? '') === $expirationDate;
            });

            if (empty($atmOptions)) {
                error_log("❌ Nenhuma opção com vencimento exato $expirationDate para $symbol");
                return null;
            }

            // Separar calls e puts
            $calls = array_filter($atmOptions, function($opt) {
                return ($opt['category'] ?? '') === 'CALL';
            });

            $puts = array_filter($atmOptions, function($opt) {
                return ($opt['category'] ?? '') === 'PUT';
            });

            error_log("📈 Calls encontradas: " . count($calls));
            error_log("📉 Puts encontradas: " . count($puts));

            if (empty($calls) || empty($puts)) {
                error_log("❌ Faltam calls ou puts para formar collar");
                return null;
            }

            $allCollars = [];

            // 3. Encontrar combinações viáveis de Collar
            // Collar: Compra ação + Vende CALL + Compra PUT

            // Usar o parâmetro de faixa ATM dos filtros (como no Python)
            $atmRange = $filters['strike_range'] ?? 10.0; // Default 10%

            // Calcular limites da faixa ATM (como no Python)
            $rangeMin = $currentPrice * (1 - $atmRange/100);
            $rangeMax = $currentPrice * (1 + $atmRange/100);

            error_log("🔍 Buscando opções ATM para {$symbol}: Preço R$ {$currentPrice}, Faixa: R$ {$rangeMin} - R$ {$rangeMax} (±{$atmRange}%)");

            // Filtrar opções dentro da faixa ATM (como no Python)
            $atmCalls = array_filter($calls, function($call) use ($rangeMin, $rangeMax) {
                $strike = $call['strike'] ?? 0;
                return $strike >= $rangeMin && $strike <= $rangeMax;
            });

            $atmPuts = array_filter($puts, function($put) use ($rangeMin, $rangeMax) {
                $strike = $put['strike'] ?? 0;
                return $strike >= $rangeMin && $strike <= $rangeMax;
            });

            error_log("📊 Resultado: " . count($atmCalls) . " calls ATM, " . count($atmPuts) . " puts ATM");

            if (empty($atmCalls) || empty($atmPuts)) {
                error_log("❌ Não foi possível encontrar opções ATM para formar collar");
                return null;
            }

            $dueDate = DateTime::createFromFormat('Y-m-d', $expirationDate);
            $now = new DateTime('today');
            $daysToMaturity = max(1, $dueDate->diff($now)->days);

            $minProfit = $filters['min_profit'] ?? 0;

            // Iterar sobre todas as combinações (Igual ao Python)
            foreach ($atmCalls as $call) {
                foreach ($atmPuts as $put) {
                    $callPremium = $this->calculatePremium($call);
                    $putPremium = $this->calculatePremium($put);

                    if ($callPremium <= 0 || $putPremium <= 0) {
                        continue;
                    }

                    // Calcular métricas usando o CollarCalculator
                    $metrics = $this->calculator->calculateMetrics(
                        $currentPrice,
                        $callPremium,
                        $putPremium,
                        $call['strike'],
                        $put['strike'],
                        $daysToMaturity,
                        $selicAnnual,
                        $includePayoffData
                    );

                    $profitRisePercent = $metrics['profit_if_rise_percent'] ?? 0;
                    $profitFallPercent = $metrics['profit_if_fall_percent'] ?? 0;
                    $guaranteedProfitPercent = $metrics['guaranteed_profit_percent'] ?? 0;

                    // Filtro de rentabilidade (Igual ao Python)
                    if ($profitRisePercent < $minProfit || $profitFallPercent < $minProfit) {
                        continue;
                    }

                    if ($guaranteedProfitPercent < 0) {
                        continue;
                    }

                    $menorRentabilidade = min($profitRisePercent, $profitFallPercent);

                    $collarData = [
                        'symbol' => $symbol,
                        'current_price' => $currentPrice,
                        'call_symbol' => $call['symbol'],
                        'call_premium' => $callPremium,
                        'call_strike' => $call['strike'],
                        'put_symbol' => $put['symbol'],
                        'put_premium' => $putPremium,
                        'put_strike' => $put['strike'],
                        'expiration_date' => $expirationDate,
                        'days_to_maturity' => $daysToMaturity,
                        'analysis_date' => $now->format('Y-m-d H:i:s'),

                        'profit_if_rise_percent' => $profitRisePercent,
                        'profit_if_fall_percent' => $profitFallPercent,
                        'profit_if_sideways_percent' => $metrics['profit_if_sideways_percent'] ?? 0,
                        'guaranteed_profit_percent' => $guaranteedProfitPercent,
                        'minimum_profit_percent' => $menorRentabilidade,

                        'annual_profit_percent' => $profitRisePercent * (365 / $daysToMaturity),
                        'quantity' => $this->calculator->getQuantity(),
                        'strategy_type' => 'collar',

                        // Manter compatibilidade
                        'max_loss_percent' => $profitFallPercent,
                        'worst_case_profit_percent' => $profitFallPercent,
                    ];

                    $collarData = array_merge($collarData, $metrics);
                    $allCollars[] = $collarData;
                }
            }

            return !empty($allCollars) ? $allCollars : null;

        } catch (\Exception $e) {
            error_log("💥 ERRO na estratégia Collar para $symbol: " . $e->getMessage());
            return null;
        }
    }

    private function calculatePremium(array $option): float {
        $bid = $option['bid'] ?? 0;
        $ask = $option['ask'] ?? 0;

        if ($bid > 0 && $ask > 0) {
            return ($bid + $ask) / 2;
        }

        if (!empty($option['close']) && $option['close'] > 0) {
            return $option['close'];
        }

        if ($bid > 0) {
            return $bid;
        } elseif ($ask > 0) {
            return $ask;
        }

        return 0;
    }
}