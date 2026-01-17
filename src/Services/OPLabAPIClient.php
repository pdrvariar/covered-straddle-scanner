<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OPLabAPIClient {
    private $client;
    private $accessToken;
    private $baseUrl = "https://api.oplab.com.br/v3/";

    public function __construct(string $accessToken) {
        $this->accessToken = $accessToken;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30.0,
            'headers' => [
                'Access-Token' => $accessToken,
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function getStockData(string $symbol): ?array {
        try {
            $response = $this->client->get("market/stocks/{$symbol}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $msg = "Erro API ({$symbol}): " . $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= " - Resposta: " . $e->getResponse()->getBody()->getContents();
            }
            error_log($msg);
            return null;
        }
    }

    /**
     * Busca opções filtrando por vencimento específico
     */
    public function getOptionsByExpiration(string $symbol, string $expirationDate): array {
        try {
            $response = $this->client->get("market/options/{$symbol}", [
                'query' => [
                    'due_date' => $expirationDate,
                    'category' => 'CALL,PUT' // Filtra apenas CALL e PUT
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return is_array($data) ? $data : [];
        } catch (RequestException $e) {
            $msg = "Erro API ({$symbol} - venc {$expirationDate}): " . $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= " - Resposta: " . $e->getResponse()->getBody()->getContents();
            }
            error_log($msg);
            return [];
        }
    }

    /**
     * Método original mantido para compatibilidade
     */
    public function getOptionsChain(string $symbol): array {
        try {
            $response = $this->client->get("market/options/{$symbol}");
            $data = json_decode($response->getBody()->getContents(), true);
            return is_array($data) ? $data : [];
        } catch (RequestException $e) {
            $msg = "Erro API ({$symbol}): " . $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= " - Resposta: " . $e->getResponse()->getBody()->getContents();
            }
            error_log($msg);
            return [];
        }
    }

    public function getInterestRate(string $rateId = "SELIC"): ?float {
        try {
            $response = $this->client->get("market/interest_rates/{$rateId}");
            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['value'])) {
                return $data['value'] / 100.0;
            }
            return null;
        } catch (RequestException $e) {
            error_log("API Error fetching interest rate: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Novo método para otimizar a busca de opções ATM
     */
    public function getAtmOptions(string $symbol, string $expirationDate, float $currentPrice, array $filters = []): array {
        try {
            // Buscar opções filtradas por vencimento
            $options = $this->getOptionsByExpiration($symbol, $expirationDate);

            if (empty($options)) {
                error_log("Nenhuma opção encontrada para $symbol no vencimento $expirationDate");
                return [];
            }

            // Define o limite de strike (percentual dinâmico ou default 2%)
            $strikeRangePercent = (float)($filters['strike_range'] ?? 2.0);
            $strikeLimit = $currentPrice * ($strikeRangePercent / 100.0); 
            
            $maxAgeMs = 300000; // 5 minutos
            $nowMs = time() * 1000;

            $applyRecencyFilter = $filters['filter_recency'] ?? true;
            $applyLiquidityFilter = $filters['filter_liquidity'] ?? true;

            $atmOptions = [];

            foreach ($options as $option) {
                // Verificar explicitamente o vencimento (filtro de segurança)
                $optionDueDate = $option['due_date'] ?? '';
                if ($optionDueDate !== $expirationDate) {
                    continue;
                }

                // Verificar se é CALL ou PUT
                $category = $option['category'] ?? '';
                if (!in_array($category, ['CALL', 'PUT'])) {
                    continue;
                }

                // Verificar strike (ATM ±10%)
                $strike = $option['strike'] ?? 0;
                if (abs($strike - $currentPrice) > $strikeLimit) {
                    continue;
                }

                // Filtro de recência (opcional)
                if ($applyRecencyFilter) {
                    $lastTrade = $option['last_trade_at'] ?? 0;
                    if ($lastTrade > 0 && ($nowMs - $lastTrade) > $maxAgeMs) {
                        continue;
                    }
                }

                // Filtro de liquidez (opcional)
                if ($applyLiquidityFilter) {
                    $bid = $option['bid'] ?? 0;
                    $ask = $option['ask'] ?? 0;

                    // Verificar se há bid/ask válidos e spread aceitável
                    if ($bid <= 0 || $ask <= 0 || abs($ask - $bid) > 0.05) {
                        continue;
                    }
                }

                $atmOptions[] = $option;
            }

            error_log("Opções ATM encontradas para $symbol: " . count($atmOptions));
            return $atmOptions;

        } catch (\Exception $e) {
            error_log("Erro ao buscar opções ATM para $symbol: " . $e->getMessage());
            return [];
        }
    }
}