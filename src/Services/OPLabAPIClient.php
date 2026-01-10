<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OPLabAPIClient {
    private $client;
    private $accessToken;
    private $baseUrl = "https://api.oplab.com.br/v3";

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
            $response = $this->client->get("/market/stocks/{$symbol}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            error_log("API Error fetching stock {$symbol}: " . $e->getMessage());
            return null;
        }
    }

    public function getOptionsChain(string $symbol): array {
        try {
            $response = $this->client->get("/market/options/{$symbol}");
            $data = json_decode($response->getBody()->getContents(), true);
            return is_array($data) ? $data : [];
        } catch (RequestException $e) {
            error_log("API Error fetching options for {$symbol}: " . $e->getMessage());
            return [];
        }
    }

    public function getInterestRate(string $rateId = "SELIC"): ?float {
        try {
            $response = $this->client->get("/market/interest_rates/{$rateId}");
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
}
?>