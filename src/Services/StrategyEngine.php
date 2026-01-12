<?php

namespace App\Services;

use DateTime;

class StrategyEngine {
    private $apiClient;
    private $calculator;

    public function __construct(OPLabAPIClient $apiClient) {
        $this->apiClient = $apiClient;
        $this->calculator = new CoveredStraddleCalculator();
    }

    public function evaluateStraddles(string $symbol, string $expirationDate, float $selicAnnual, array $filters = [], bool $includePayoffData = false): ?array {
        try {
            error_log("=== Iniciando análise para $symbol (venc: $expirationDate) ===");

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

            // 2. Buscar dados do LFTS11 para cálculo de garantias
            $lfts11Fetcher = new Lfts11PriceFetcher($this->apiClient);
            $lfts11Price = $lfts11Fetcher->getPrice();
            $lfts11Data = $lfts11Fetcher->getData();
            // Garantir que o preço de getData seja o mesmo que getPrice obteve
            $lfts11Data['price'] = $lfts11Price;

            if ($lfts11Price <= 0) {
                error_log("⚠️  Preço do LFTS11 inválido (R$ $lfts11Price). Usando fallback de R$ 146,00");
                $lfts11Price = 146.00;
                $lfts11Data['price'] = 146.00;
                $lfts11Data['source'] = 'forced_fallback';
            }

            error_log("💰 Preço do LFTS11 (fonte: {$lfts11Data['source']}): R$ " . number_format($lfts11Price, 2));

// Registrar no log se está usando valor padrão
            if ($lfts11Data['source'] === 'default') {
                error_log("⚠️  ATENÇÃO: Usando valor padrão para LFTS11. Verifique a conexão com as fontes de dados.");
            }

            // 3. Buscar opções ATM filtradas
            $atmOptions = $this->apiClient->getAtmOptions($symbol, $expirationDate, $currentPrice, $filters);

            if (empty($atmOptions)) {
                error_log("❌ Nenhuma opção ATM encontrada para $symbol no vencimento $expirationDate");
                return null;
            }

            // Separar calls e puts
            $calls = array_filter($atmOptions, function($opt) {
                return ($opt['category'] ?? '') === 'CALL';
            });

            $puts = array_filter($atmOptions, function($opt) {
                return ($opt['category'] ?? '') === 'PUT';
            });

            error_log("📈 Calls ATM: " . count($calls));
            error_log("📉 Puts ATM: " . count($puts));

            if (empty($calls) || empty($puts)) {
                error_log("❌ Faltam calls ou puts para formar straddle");
                return null;
            }

            $allStraddles = [];

            // 4. Agrupar por strike para formar straddles
            $strikes = array_unique(array_column($atmOptions, 'strike'));
            error_log("🎯 Strikes disponíveis: " . implode(', ', $strikes));

            foreach ($strikes as $strike) {
                // Buscar call com este strike (maior volume)
                $strikeCalls = array_filter($calls, function($call) use ($strike) {
                    return $call['strike'] == $strike;
                });

                // Buscar put com este strike (maior volume)
                $strikePuts = array_filter($puts, function($put) use ($strike) {
                    return $put['strike'] == $strike;
                });

                if (empty($strikeCalls) || empty($strikePuts)) {
                    continue;
                }

                $call = $this->getHighestVolumeOption($strikeCalls);
                $put = $this->getHighestVolumeOption($strikePuts);

                // Calcular prêmios
                $callPremium = $this->calculatePremium($call);
                $putPremium = $this->calculatePremium($put);

                if ($callPremium <= 0 || $putPremium <= 0) {
                    error_log("⚠️  Prêmio inválido para strike $strike: CALL=$callPremium, PUT=$putPremium");
                    continue;
                }

                // Calcular dias até vencimento
                $dueDate = DateTime::createFromFormat('Y-m-d', $expirationDate);
                $now = new DateTime('today');
                $daysToMaturity = max(1, $dueDate->diff($now)->days);

                // Calcular métricas com dados do LFTS11
                $metrics = $this->calculator->calculateMetrics(
                    $currentPrice,
                    $callPremium,
                    $putPremium,
                    $strike,
                    $daysToMaturity,
                    $selicAnnual,
                    $lfts11Price,
                    $includePayoffData
                );

                // Aplicar filtro de lucro mínimo
                $minProfit = $filters['min_profit'] ?? 0;
                if ($metrics['profit_percent'] < $minProfit) {
                    error_log("📉 Strike $strike não atinge lucro mínimo: {$metrics['profit_percent']}% < {$minProfit}%");
                    continue;
                }

                error_log("✅ Strike $strike: Lucro = {$metrics['profit_percent']}%, Retorno mensal = {$metrics['monthly_profit_percent']}%");

                $straddleData = [
                    'symbol' => $symbol,
                    'current_price' => $currentPrice,
                    'call_symbol' => $call['symbol'],
                    'call_premium' => $callPremium,
                    'put_symbol' => $put['symbol'],
                    'put_premium' => $putPremium,
                    'strike_price' => $strike,
                    'expiration_date' => $expirationDate,
                    'days_to_maturity' => $daysToMaturity,
                    'analysis_date' => $now->format('Y-m-d H:i:s'),
                    'annual_profit_percent' => $metrics['profit_percent'] * (365 / $daysToMaturity),
                    'lfts11_data' => $lfts11Data,
                    'quantity' => $this->calculator->getQuantity()
                ];

                $straddleData = array_merge($straddleData, $metrics);
                $allStraddles[] = $straddleData;
            }

            if (empty($allStraddles)) {
                error_log("❌ Nenhum straddle viável encontrado para $symbol");
                return null;
            }

            // Ordenar do MAIOR para o MENOR lucro percentual
            usort($allStraddles, function($a, $b) {
                return $b['profit_percent'] <=> $a['profit_percent'];
            });

            error_log("🎉 Encontrados " . count($allStraddles) . " straddles para $symbol");
            return $allStraddles;

        } catch (\Exception $e) {
            error_log("💥 ERRO na análise de $symbol: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            return null;
        }
    }
    private function getHighestVolumeOption(array $options): array {
        $maxVolume = -1;
        $bestOption = [];

        foreach ($options as $option) {
            $volume = $option['volume'] ?? 0;
            if ($volume > $maxVolume) {
                $maxVolume = $volume;
                $bestOption = $option;
            }
        }

        // Se nenhum tem volume, retorna o primeiro
        if (empty($bestOption) && !empty($options)) {
            return reset($options);
        }

        return $bestOption;
    }

    private function calculatePremium(array $option): float {
        // Prioridade: close > bid/ask médio > bid
        if (!empty($option['close']) && $option['close'] > 0) {
            return $option['close'];
        }

        $bid = $option['bid'] ?? 0;
        $ask = $option['ask'] ?? 0;

        if ($bid > 0 && $ask > 0) {
            return ($bid + $ask) / 2;
        } elseif ($bid > 0) {
            return $bid;
        } elseif ($ask > 0) {
            return $ask;
        }

        return 0;
    }

    private function getLfts11Data(): ?array {
        try {
            // Tentar buscar dados do LFTS11 (ETF de Tesouro Selic)
            $lfts11Data = $this->apiClient->getStockData('LFTS11');

            if ($lfts11Data) {
                return [
                    'price' => $lfts11Data['close'] ?? 0,
                    'symbol' => 'LFTS11',
                    'name' => 'ETF Tesouro Selic',
                    'has_data' => true
                ];
            }

            // Fallback: usar dados padrão se não encontrar
            return [
                'price' => 146.00, // Preço aproximado do LFTS11
                'symbol' => 'LFTS11',
                'name' => 'ETF Tesouro Selic',
                'has_data' => false
            ];

        } catch (\Exception $e) {
            error_log("⚠️  Erro ao buscar dados do LFTS11: " . $e->getMessage());
            return [
                'price' => 146.00,
                'symbol' => 'LFTS11',
                'name' => 'ETF Tesouro Selic',
                'has_data' => false
            ];
        }
    }



}