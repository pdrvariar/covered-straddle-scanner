<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;

class Lfts11PriceFetcher {
    private $apiClient;
    private $httpClient;
    private $lastSource;

    public function __construct(OPLabAPIClient $apiClient = null) {
        $this->apiClient = $apiClient;
        $this->httpClient = new Client([
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ]
        ]);
        $this->lastSource = 'unknown';
    }

    public function getData(): array {
        $price = $this->getPrice();

        return [
            'price' => $price,
            'symbol' => 'LFTS11',
            'name' => 'iShares Tesouro Selic ETF',
            'source' => $this->lastSource,
            'has_data' => $price > 0,
            'fetch_time' => date('Y-m-d H:i:s'),
            'description' => 'ETF que replica a carteira do Tesouro Selic (LFT)'
        ];
    }

    public function getPrice(): float {
        $price = null;

        // Tentativa 1: OPLab API (se disponível)
        if ($this->apiClient) {
            $price = $this->tryOplab();
            if ($price > 0) {
                $this->lastSource = 'oplab';
                return $price;
            }
        }

        // Tentativa 2: Yahoo Finance (API não oficial)
        $price = $this->tryYahooFinance();
        if ($price > 0) {
            $this->lastSource = 'yahoo';
            return $price;
        }

        // Tentativa 3: B3 (Bolsa de Valores Brasileira)
        $price = $this->tryB3();
        if ($price > 0) {
            $this->lastSource = 'b3';
            return $price;
        }

        // Tentativa 4: Investing.com Brasil
        $price = $this->tryInvesting();
        if ($price > 0) {
            $this->lastSource = 'investing';
            return $price;
        }

        // Tentativa 5: Economatica/StatusInvest
        $price = $this->tryStatusInvest();
        if ($price > 0) {
            $this->lastSource = 'statusinvest';
            return $price;
        }

        // Fallback: Valor padrão com aviso
        $this->lastSource = 'default';
        error_log("⚠️  Não foi possível obter o preço do LFTS11. Usando valor padrão: R$ 146,00");
        return 146.00; // Valor padrão baseado em janeiro de 2026
    }

    private function tryOplab(): float {
        try {
            if (!$this->apiClient) {
                return 0;
            }
            // Tentar buscar como ação/ETF
            $data = $this->apiClient->getStockData('LFTS11');

            if ($data && isset($data['close']) && $data['close'] > 0) {
                $price = $data['close'];
                error_log("✅ LFTS11 via OPLab: R$ " . number_format($price, 2));
                return $price;
            }

            // Tentar buscar via símbolo completo
            $data = $this->apiClient->getStockData('LFTS11.SA');
            if ($data && isset($data['close']) && $data['close'] > 0) {
                $price = $data['close'];
                error_log("✅ LFTS11.SA via OPLab: R$ " . number_format($price, 2));
                return $price;
            }

        } catch (\Exception $e) {
            error_log("❌ Erro OPLab para LFTS11: " . $e->getMessage());
        }

        return 0;
    }

    private function tryYahooFinance(): float {
        try {
            // URL da API não oficial do Yahoo Finance
            $url = "https://query1.finance.yahoo.com/v8/finance/chart/LFTS11.SA";

            $response = $this->httpClient->get($url, [
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
                $price = $data['chart']['result'][0]['meta']['regularMarketPrice'];
                error_log("✅ LFTS11 via Yahoo Finance: R$ " . number_format($price, 2));
                return $price;
            }

            // Tentar formato alternativo
            if (isset($data['chart']['result'][0]['indicators']['quote'][0]['close'][0])) {
                $quotes = $data['chart']['result'][0]['indicators']['quote'][0]['close'];
                $price = array_filter($quotes);
                if (!empty($price)) {
                    $price = end($price);
                    error_log("✅ LFTS11 via Yahoo Finance (alternativo): R$ " . number_format($price, 2));
                    return $price;
                }
            }

        } catch (\Exception $e) {
            error_log("❌ Erro Yahoo Finance para LFTS11: " . $e->getMessage());
        }

        return 0;
    }

    private function tryB3(): float {
        try {
            // API da B3 (pode variar)
            $url = "https://sistemaswebb3-listados.b3.com.br/indexProxy/indexCall/GetPortfolioDay/eyJsYW5ndWFnZSI6InB0LWJyIiwicGFnZU51bWJlciI6MSwicGFnZVNpemUiOjEwMCwiaW5kZXgiOiJJRk5DIiwic2VnbWVudCI6IjEwIn0=";

            $response = $this->httpClient->get($url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Referer' => 'https://sistemaswebb3-listados.b3.com.br/'
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            // Procurar por LFTS11 nos resultados
            if (isset($data['results'])) {
                foreach ($data['results'] as $item) {
                    if (isset($item['cod']) && $item['cod'] === 'LFTS11') {
                        $price = $item['vl'] ?? 0;
                        if ($price > 0) {
                            error_log("✅ LFTS11 via B3 API: R$ " . number_format($price, 2));
                            return $price;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            error_log("❌ Erro B3 API para LFTS11: " . $e->getMessage());
        }

        // Tentar scraping do site da B3
        try {
            $url = "https://www.b3.com.br/pt_br/market-data-e-indices/servicos-de-dados/market-data/cotacoes/?tvwidgetsymbol=LFTS11";

            $response = $this->httpClient->get($url);
            $html = $response->getBody()->getContents();

            // Padrões comuns para preços na B3
            $patterns = [
                '/data-price="([0-9,]+)"/',
                '/"lastPrice":"([0-9,]+)"/',
                '/<span[^>]*class="[^"]*value[^"]*"[^>]*>R\$\s*([0-9,.]+)</',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    $priceStr = str_replace(',', '.', str_replace('.', '', $matches[1]));
                    $price = floatval($priceStr);
                    if ($price > 0) {
                        error_log("✅ LFTS11 via B3 Scraping: R$ " . number_format($price, 2));
                        return $price;
                    }
                }
            }

        } catch (\Exception $e) {
            error_log("❌ Erro B3 Scraping para LFTS11: " . $e->getMessage());
        }

        return 0;
    }

    private function tryInvesting(): float {
        try {
            $url = "https://br.investing.com/etfs/ishares-tesouro-selic-fund-cotacao";

            $response = $this->httpClient->get($url, [
                'headers' => [
                    'Accept' => 'text/html',
                    'Accept-Language' => 'pt-BR,pt;q=0.9',
                ]
            ]);

            $html = $response->getBody()->getContents();

            // Padrões do Investing.com
            $patterns = [
                '/data-test="instrument-price-last">([0-9.,]+)</',
                '/<span[^>]*class="text-2xl"[^>]*>([0-9.,]+)</',
                '/lastInst"([^>]*)>([0-9.,]+)</',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $html, $matches)) {
                    // Pegar o último grupo que contém números
                    foreach ($matches as $match) {
                        if (preg_match('/[0-9]/', $match)) {
                            $priceStr = preg_replace('/[^0-9,.]/', '', $match);
                            $priceStr = str_replace(',', '.', str_replace('.', '', $priceStr));
                            $price = floatval($priceStr);
                            if ($price > 0 && $price < 1000) { // Verificação razoável
                                error_log("✅ LFTS11 via Investing.com: R$ " . number_format($price, 2));
                                return $price;
                            }
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            error_log("❌ Erro Investing.com para LFTS11: " . $e->getMessage());
        }

        return 0;
    }

    private function tryStatusInvest(): float {
        try {
            // StatusInvest (Economatica)
            $url = "https://statusinvest.com.br/etfs/lfts11";

            $response = $this->httpClient->get($url);
            $html = $response->getBody()->getContents();

            // StatusInvest tem dados bem estruturados
            if (preg_match('/"price":\s*"([0-9,]+)"/', $html, $matches)) {
                $priceStr = str_replace(',', '.', str_replace('.', '', $matches[1]));
                $price = floatval($priceStr);
                if ($price > 0) {
                    error_log("✅ LFTS11 via StatusInvest: R$ " . number_format($price, 2));
                    return $price;
                }
            }

            // Tentar padrão alternativo
            if (preg_match('/<strong[^>]*>R\$\s*([0-9.,]+)</', $html, $matches)) {
                $priceStr = str_replace(',', '.', str_replace('.', '', $matches[1]));
                $price = floatval($priceStr);
                if ($price > 0) {
                    error_log("✅ LFTS11 via StatusInvest (alt): R$ " . number_format($price, 2));
                    return $price;
                }
            }

        } catch (\Exception $e) {
            error_log("❌ Erro StatusInvest para LFTS11: " . $e->getMessage());
        }

        return 0;
    }

    /**
     * Método para testar todas as fontes
     */
    public function testAllSources(): array {
        $results = [];

        // Testar OPLab
        $results['oplab'] = $this->tryOplab();

        // Testar Yahoo
        $results['yahoo'] = $this->tryYahooFinance();

        // Testar B3
        $results['b3'] = $this->tryB3();

        // Testar Investing
        $results['investing'] = $this->tryInvesting();

        // Testar StatusInvest
        $results['statusinvest'] = $this->tryStatusInvest();

        return $results;
    }
}