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

    public function findAtmOptions(array $stockData, array $optionsChain, string $expirationDate): array {
        $currentPrice = $stockData['close'] ?? 0;
        $strikeLimit = $currentPrice * 0.15;
        $maxAgeMs = 300000; // 5 minutes
        $nowMs = time() * 1000;

        $atmOptions = [];

        foreach ($optionsChain as $option) {
            // Validate expiration date
            if (($option['due_date'] ?? '') !== $expirationDate) {
                continue;
            }

            // Check recency
            $lastTrade = $option['last_trade_at'] ?? 0;
            if (($nowMs - $lastTrade) > $maxAgeMs) {
                continue;
            }

            // Check liquidity spread
            $bid = $option['bid'] ?? 0;
            $ask = $option['ask'] ?? 0;
            if (abs($ask - $bid) > 0.05) {
                continue;
            }

            // Check strike range
            $strike = $option['strike'] ?? 0;
            if (abs($strike - $currentPrice) <= $strikeLimit) {
                $atmOptions[] = $option;
            }
        }

        return $atmOptions;
    }

    public function evaluateStraddles(string $symbol, string $expirationDate, float $selicAnnual): ?array {
        try {
            $stockData = $this->apiClient->getStockData($symbol);
            if (!$stockData) {
                return null;
            }

            $optionsChain = $this->apiClient->getOptionsChain($symbol);
            if (empty($optionsChain)) {
                return null;
            }

            $currentPrice = $stockData['close'];
            $atmOptions = $this->findAtmOptions($stockData, $optionsChain, $expirationDate);

            // Separate calls and puts
            $calls = array_filter($atmOptions, fn($opt) => ($opt['category'] ?? '') === 'CALL');
            $puts = array_filter($atmOptions, fn($opt) => ($opt['category'] ?? '') === 'PUT');

            $bestStraddle = null;
            $bestProfit = -INF;

            // Get unique strikes
            $strikes = array_unique(array_column($atmOptions, 'strike'));

            foreach ($strikes as $strike) {
                $strikeCalls = array_filter($calls, fn($call) => $call['strike'] == $strike);
                $strikePuts = array_filter($puts, fn($put) => $put['strike'] == $strike);

                if (empty($strikeCalls) || empty($strikePuts)) {
                    continue;
                }

                // Get highest volume options
                $call = $this->getHighestVolumeOption($strikeCalls);
                $put = $this->getHighestVolumeOption($strikePuts);

                // Calculate premiums
                $callPremium = $call['close'] ?? (($call['bid'] + $call['ask']) / 2);
                $putPremium = $put['close'] ?? (($put['bid'] + $put['ask']) / 2);

                if ($callPremium <= 0 || $putPremium <= 0) {
                    continue;
                }

                // Calculate days to maturity
                $dueDate = DateTime::createFromFormat('Y-m-d', $call['due_date']);
                $now = new DateTime();
                $daysToMaturity = $dueDate->diff($now)->days;

                if ($daysToMaturity <= 0) {
                    continue;
                }

                // Calculate metrics
                $metrics = $this->calculator->calculateMetrics(
                    $currentPrice,
                    $callPremium,
                    $putPremium,
                    $strike,
                    $daysToMaturity,
                    $selicAnnual
                );

                if ($metrics['max_profit'] > $bestProfit) {
                    $bestProfit = $metrics['max_profit'];
                    $bestStraddle = [
                        'symbol' => $symbol,
                        'current_price' => $currentPrice,
                        'call_symbol' => $call['symbol'],
                        'call_premium' => $callPremium,
                        'put_symbol' => $put['symbol'],
                        'put_premium' => $putPremium,
                        'strike' => $strike,
                        'expiration_date' => $expirationDate,
                        'days_to_maturity' => $daysToMaturity,
                        'analysis_date' => $now->format('Y-m-d H:i:s')
                    ];

                    $bestStraddle = array_merge($bestStraddle, $metrics);
                }
            }

            return $bestStraddle;

        } catch (\Exception $e) {
            error_log("Error evaluating {$symbol}: " . $e->getMessage());
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

        return $bestOption;
    }
}
?>