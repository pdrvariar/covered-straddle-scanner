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
            $atmCalls = array_filter($calls, function($call) use ($rangeMin, $rangeMax, $currentPrice) {
                $strike = $call['strike'] ?? 0;
                $isInRange = $strike >= $rangeMin && $strike <= $rangeMax;

                if ($isInRange) {
                    $type = $strike > $currentPrice ? 'OTM' : ($strike < $currentPrice ? 'ITM' : 'ATM');
                    error_log("   ✅ CALL {$call['symbol']}: Strike R$ {$strike} ({$type})");
                }

                return $isInRange;
            });

            $atmPuts = array_filter($puts, function($put) use ($rangeMin, $rangeMax, $currentPrice) {
                $strike = $put['strike'] ?? 0;
                $isInRange = $strike >= $rangeMin && $strike <= $rangeMax;

                if ($isInRange) {
                    $type = $strike < $currentPrice ? 'OTM' : ($strike > $currentPrice ? 'ITM' : 'ATM');
                    error_log("   ✅ PUT {$put['symbol']}: Strike R$ {$strike} ({$type})");
                }

                return $isInRange;
            });

            error_log("📊 Resultado: " . count($atmCalls) . " calls ATM, " . count($atmPuts) . " puts ATM");

            if (empty($atmCalls) || empty($atmPuts)) {
                error_log("❌ Não foi possível encontrar opções ATM para formar collar");
                return null;
            }

            // Ordenar por liquidez
            usort($atmCalls, function($a, $b) {
                return ($b['volume'] ?? 0) <=> ($a['volume'] ?? 0);
            });

            usort($atmPuts, function($a, $b) {
                return ($b['volume'] ?? 0) <=> ($a['volume'] ?? 0);
            });

            // Pegar as melhores opções de cada (pela liquidez)
            $bestCall = reset($atmCalls);
            $bestPut = reset($atmPuts);

            // Verificar se encontramos combinação válida
            $callStrike = $bestCall['strike'] ?? 0;
            $putStrike = $bestPut['strike'] ?? 0;

            error_log("🎯 Combinação selecionada:");
            error_log("   📈 CALL: {$bestCall['symbol']} - Strike R$ {$callStrike} " .
                ($callStrike > $currentPrice ? '(OTM)' : ($callStrike < $currentPrice ? '(ITM)' : '(ATM)')));
            error_log("   📉 PUT: {$bestPut['symbol']} - Strike R$ {$putStrike} " .
                ($putStrike < $currentPrice ? '(OTM)' : ($putStrike > $currentPrice ? '(ITM)' : '(ATM)')));

            $callPremium = $this->calculatePremium($bestCall);
            $putPremium = $this->calculatePremium($bestPut);

            if ($callPremium <= 0 || $putPremium <= 0) {
                error_log("⚠️  Prêmio inválido: CALL=$callPremium, PUT=$putPremium");
                return null;
            }

            $dueDate = DateTime::createFromFormat('Y-m-d', $expirationDate);
            $now = new DateTime('today');
            $daysToMaturity = max(1, $dueDate->diff($now)->days);

            // Calcular métricas usando o CollarCalculator (que agora tem os três cenários)
            $metrics = $this->calculator->calculateMetrics(
                $currentPrice,
                $callPremium,
                $putPremium,
                $bestCall['strike'],
                $bestPut['strike'],
                $daysToMaturity,
                $selicAnnual,
                $includePayoffData
            );

            $minProfit = $filters['min_profit'] ?? 0;

            // ========== LÓGICA DE FILTRO ATUALIZADA (IGUAL AO PYTHON) ==========
            // No Python, o filtro verifica se a rentabilidade MÍNIMA e MÁXIMA são >= filtro
            // Aqui, usamos os cenários de alta (profit_if_rise) e queda (profit_if_fall)

            $profitRisePercent = $metrics['profit_if_rise_percent'] ?? 0;
            $profitFallPercent = $metrics['profit_if_fall_percent'] ?? 0;
            $profitSidewaysPercent = $metrics['profit_if_sideways_percent'] ?? 0;
            $guaranteedProfitPercent = $metrics['guaranteed_profit_percent'] ?? 0;

            // Verificar se ambos os cenários (alta e queda) atendem ao filtro mínimo
            if ($profitRisePercent < $minProfit || $profitFallPercent < $minProfit) {
                error_log("📉 Collar descartado: rentabilidade insuficiente em um dos cenários. 
                  Alta: {$profitRisePercent}%, Queda: {$profitFallPercent}% < {$minProfit}% (filtro)");
                return null;
            }

            // Também descartar se o lucro garantido for negativo
            if ($guaranteedProfitPercent < 0) {
                error_log("📉 Collar descartado: lucro garantido negativo = {$guaranteedProfitPercent}%");
                return null;
            }

            // Determinar a menor rentabilidade entre os cenários de alta e queda
            $menorRentabilidade = min($profitRisePercent, $profitFallPercent);

            // Log de aprovação detalhado
            error_log("✅ Collar APROVADO: 
              - CALL strike R$ {$bestCall['strike']} 
              - PUT strike R$ {$bestPut['strike']} 
              - Rentabilidade na Alta = {$profitRisePercent}%
              - Rentabilidade na Queda = {$profitFallPercent}%
              - Rentabilidade Lateral = {$profitSidewaysPercent}%
              - Lucro Garantido = {$guaranteedProfitPercent}%
              - Menor Rentabilidade = {$menorRentabilidade}%
              - Retorno mensal garantido = {$metrics['monthly_guaranteed_profit_percent']}%
              - Filtro mínimo: {$minProfit}%");
            // ===========================================

            // Descartar collares com lucro garantido negativo (já verificado acima, mas mantemos para segurança)
            if ($metrics['guaranteed_profit'] <= 0) {
                error_log("📉 Collar descartado (lucro garantido negativo): R$ {$metrics['guaranteed_profit']}");
                return null;
            }

            $collarData = [
                'symbol' => $symbol,
                'current_price' => $currentPrice,
                'call_symbol' => $bestCall['symbol'],
                'call_premium' => $callPremium,
                'call_strike' => $bestCall['strike'],
                'put_symbol' => $bestPut['symbol'],
                'put_premium' => $putPremium,
                'put_strike' => $bestPut['strike'],
                'expiration_date' => $expirationDate,
                'days_to_maturity' => $daysToMaturity,
                'analysis_date' => $now->format('Y-m-d H:i:s'),

                // Adicionar métricas específicas (novas)
                'profit_if_rise_percent' => $profitRisePercent,
                'profit_if_fall_percent' => $profitFallPercent,
                'profit_if_sideways_percent' => $profitSidewaysPercent,
                'guaranteed_profit_percent' => $guaranteedProfitPercent,
                'minimum_profit_percent' => $menorRentabilidade,

                'annual_profit_percent' => $profitRisePercent * (365 / $daysToMaturity),
                'quantity' => $this->calculator->getQuantity(),
                'strategy_type' => 'collar',

                // Manter compatibilidade com código existente
                'max_loss_percent' => $profitFallPercent,
                'worst_case_profit_percent' => $profitFallPercent,
            ];

            // Mesclar com todas as métricas do calculator
            $collarData = array_merge($collarData, $metrics);
            $allCollars[] = $collarData;

            return $allCollars;

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