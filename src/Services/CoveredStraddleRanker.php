<?php

namespace App\Services;

/**
 * ALGORITMO DE ORDENAÇÃO PARA COVERED STRADDLE
 *
 * PERFIL: Investidor experiente que busca extrair prêmios altos com risco controlado
 * ESTRUTURA: Mesmo strike para CALL e PUT (Straddle clássico)
 * CORTESIA: Liquidez já filtrada na entrada (bid-ask < 0.05)
 */

class CoveredStraddleRanker
{
    private $config;

    public function __construct()
    {
        // Configuração otimizada para Covered Straddle com mesmo strike
        $this->config = [
            'peso_retorno' => 0.40,      // Maior peso: Retorno é o objetivo principal
            'peso_seguranca' => 0.35,    // Segurança importante mas não acima do retorno
            'peso_eficiencia' => 0.15,   // Eficiência da estrutura
            'peso_volatilidade' => 0.10, // Volatilidade importante mas já filtrada
            'dias_base' => 28,
            'mso_ideal_min' => 8.0,      // MSO ideal mínimo (%)
            'mso_ideal_max' => 15.0,     // MSO ideal máximo (%)
            'retorno_ideal_min' => 2.0,  // Retorno mensal ideal mínimo (%) - CORRIGIDO
            'retorno_ideal_max' => 8.0,  // Retorno mensal ideal máximo (%) - CORRIGIDO
            'iv_percentile_ideal' => 70, // IV Percentile ideal para venda
            'spread_max' => 0.05         // Spread máximo aceitável (já filtrado)
        ];
    }

    /**
     * Calcula score para uma operação de Covered Straddle (mesmo strike)
     */
    public function calcularScore(array $operacao): array
    {
        try {
            // VALIDAÇÃO BÁSICA DOS DADOS
            if (!$this->validarDadosMinimos($operacao)) {
                return $this->resultadoErro($operacao, 'Dados insuficientes para cálculo');
            }

            // EXTRAÇÃO DE DADOS (com nomes de campos ajustados para seu sistema)
            $dados = $this->extrairDados($operacao);

            // CÁLCULO DAS MÉTRICAS PRIMÁRIAS
            $metricas = $this->calcularMetricas($dados);

            // CÁLCULO DOS SCORES INDIVIDUAIS
            $scores = [
                'retorno' => $this->calcularScoreRetorno($metricas['retorno_mensal']),
                'seguranca' => $this->calcularScoreSeguranca($metricas['mso_percentual']),
                'eficiencia' => $this->calcularScoreEficiencia($dados, $metricas),
                'volatilidade' => $this->calcularScoreVolatilidade($dados)
            ];

            // SCORE FINAL PONDERADO
            $scoreFinal = $this->calcularScorePonderado($scores);

            // MODIFICADORES DE SCORE (bônus/penalidades)
            $scoreFinal = $this->aplicarModificadores($scoreFinal, $dados, $metricas);

            // DETERMINAR CLASSIFICAÇÃO
            $classificacao = $this->determinarClassificacao($scoreFinal);

            // PREPARAR RESULTADO FINAL
            return $this->prepararResultado($operacao, $dados, $metricas, $scores, $scoreFinal, $classificacao);

        } catch (\Exception $e) {
            return $this->resultadoErro($operacao, $e->getMessage());
        }
    }

    /**
     * Valida dados mínimos necessários
     */
    private function validarDadosMinimos(array $operacao): bool
    {
        $camposObrigatorios = [
            'current_price', 'strike_price', 'call_premium',
            'put_premium', 'bep', 'days_to_maturity',
            'profit_percent', 'max_profit', 'initial_investment' // ADICIONADOS
        ];

        foreach ($camposObrigatorios as $campo) {
            if (!isset($operacao[$campo]) || $operacao[$campo] === null || $operacao[$campo] === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Extrai e formata os dados da operação
     */
    private function extrairDados(array $operacao): array
    {
        return [
            'preco_atual' => floatval($operacao['current_price']),
            'strike' => floatval($operacao['strike_price']), // Mesmo strike para CALL e PUT
            'premio_call' => floatval($operacao['call_premium']),
            'premio_put' => floatval($operacao['put_premium']),
            'bep_inferior' => floatval($operacao['bep']),
            'dias_vencimento' => intval($operacao['days_to_maturity']),
            'iv_percentile' => floatval($operacao['iv_1y_percentile'] ?? 50),

            // DADOS DA CALCULADORA - USAR ESTES!
            'profit_percent' => floatval($operacao['profit_percent'] ?? 0), // RETORNO REAL
            'monthly_profit_percent' => floatval($operacao['monthly_profit_percent'] ?? 0),
            'max_profit' => floatval($operacao['max_profit'] ?? 0),
            'initial_investment' => floatval($operacao['initial_investment'] ?? 0),
            'stock_investment' => floatval($operacao['stock_investment'] ?? 0),
            'lfts11_investment' => floatval($operacao['lfts11_investment'] ?? 0),

            'ranking_sistema' => intval($operacao['ranking_sistema'] ?? 12),
            'ticker' => $operacao['ticker'] ?? '',
            'nome' => $operacao['name'] ?? ''
        ];
    }

    /**
     * Calcula métricas principais - CORRIGIDO
     */
    private function calcularMetricas(array $dados): array
    {
        $P = $dados['preco_atual'];
        $Sc = $dados['strike'];
        $premioTotal = $dados['premio_call'] + $dados['premio_put'];
        $dias = $dados['dias_vencimento'];

        // USAR O RETORNO JÁ CALCULADO PELA CALCULADORA - CORREÇÃO CRÍTICA
        $retornoPercentual = $dados['profit_percent']; // Já está em percentual

        // Retorno normalizado para 30 dias - CORRIGIDO: não é linear!
        // Para opções, usa-se aproximação raiz quadrada (devido ao theta decay)
        $retornoMensal = $dados['monthly_profit_percent']; // Já calculado pela calculadora

        // Se não tiver monthly_profit_percent, calcular de forma não-linear
        if ($retornoMensal <= 0 && $dias > 0) {
            $retornoMensal = $retornoPercentual * sqrt(30 / $dias); // Correção não-linear
        }

        // Retorno anualizado (também não-linear)
        $retornoAnualizado = $dias > 0 ? $retornoPercentual * sqrt(365 / $dias) : 0;

        // Margem de Segurança Operacional (MSO)
        $msoPercentual = $P > 0 ? (($P - $dados['bep_inferior']) / $P) * 100 : 0;

        // CALL OTM (Out of The Money) - MELHOR para venda de opções!
        // CORREÇÃO: Invertido! CALL OTM (strike > preço) = mais prêmio extrínseco = MELHOR
        $call_otm = $Sc > $P;

        // Distância do strike (percentual)
        $distancia_strike_percentual = $P > 0 ? abs($P - $Sc) / $P * 100 : 0;

        return [
            'premio_total' => $premioTotal,
            'retorno_percentual' => $retornoPercentual,
            'retorno_mensal' => $retornoMensal,
            'retorno_anualizado' => $retornoAnualizado,
            'mso_percentual' => $msoPercentual,
            'call_otm' => $call_otm, // CORRIGIDO: OTM é melhor
            'distancia_strike_percentual' => $distancia_strike_percentual,
            'relacao_call_put' => $dados['premio_call'] > 0 ? $dados['premio_put'] / $dados['premio_call'] : 0
        ];
    }

    /**
     * Score de RETORNO - CORRIGIDO (faixas realistas)
     * Covered Straddle: retorno mensal realista normalmente 2-8%
     * >10% = risco excessivo, <2% = não vale o risco
     */
    private function calcularScoreRetorno(float $retornoMensal): float
    {
        // ZONA IDEAL: 4-6% mensal (equilíbrio perfeito risco/retorno) - CORRIGIDO
        if ($retornoMensal >= 4 && $retornoMensal <= 6) {
            return 95 + ($retornoMensal - 4); // 95 a 97 pontos
        }

        // ZONA BOA: 3-4% ou 6-7% (ainda atrativo)
        if (($retornoMensal >= 3 && $retornoMensal < 4) ||
            ($retornoMensal > 6 && $retornoMensal <= 7)) {
            if ($retornoMensal >= 3 && $retornoMensal < 4) {
                return 85 + ($retornoMensal - 3) * 10; // 85 a 95
            } else {
                return 95 - ($retornoMensal - 6) * 10; // 95 a 85
            }
        }

        // ZONA ACEITÁVEL: 2-3% ou 7-8%
        if (($retornoMensal >= 2 && $retornoMensal < 3) ||
            ($retornoMensal > 7 && $retornoMensal <= 8)) {
            if ($retornoMensal >= 2 && $retornoMensal < 3) {
                return 70 + ($retornoMensal - 2) * 15; // 70 a 85
            } else {
                return 85 - ($retornoMensal - 7) * 15; // 85 a 70
            }
        }

        // ZONA RUIM: 1-2% ou 8-10%
        if (($retornoMensal >= 1 && $retornoMensal < 2) ||
            ($retornoMensal > 8 && $retornoMensal <= 10)) {
            return 50 + ($retornoMensal * 10); // 50 a 70 pontos
        }

        // ZONA MUITO RUIM: <1% ou >10% - retorno suspeito ou muito baixo
        return max(0, 30 - abs($retornoMensal - 5.5) * 10);
    }

    /**
     * Score de SEGURANÇA (MSO) - Proteção contra prejuízo
     * Prefere MSO entre 8-15% (suficiente mas não excessivo)
     */
    private function calcularScoreSeguranca(float $msoPercentual): float
    {
        // ZONA IDEAL: 8-15% (proteção adequada sem sacrificar retorno)
        if ($msoPercentual >= 8 && $msoPercentual <= 15) {
            return 98 + min(2, ($msoPercentual - 8) * 0.3); // 98 a 100
        }

        // ZONA BOA: 5-8% ou 15-20%
        if (($msoPercentual >= 5 && $msoPercentual < 8) ||
            ($msoPercentual > 15 && $msoPercentual <= 20)) {
            if ($msoPercentual >= 5 && $msoPercentual < 8) {
                return 85 + ($msoPercentual - 5) * 4.33; // 85 a 98
            } else {
                return 98 - ($msoPercentual - 15) * 3.6; // 98 a 85
            }
        }

        // ZONA DE RISCO: <5% (proteção insuficiente)
        if ($msoPercentual < 5) {
            // Penalidade progressiva
            return max(0, $msoPercentual * 17); // 0 a 85
        }

        // >20% (proteção excessiva, retorno comprometido)
        return max(0, 85 - ($msoPercentual - 20) * 4);
    }

    /**
     * Score de EFICIÊNCIA - CORRIGIDO (divisão por zero)
     */
    private function calcularScoreEficiencia(array $dados, array $metricas): float
    {
        $score = 0;
        $P = $dados['preco_atual'];
        $Sc = $dados['strike'];
        $premioCall = $dados['premio_call'];
        $premioPut = $dados['premio_put'];

        // 1. Eficiência do prêmio CALL (0-40 pontos)
        $distanciaCall = abs($P - $Sc);
        if ($distanciaCall > 0.01) { // Evita divisão por zero
            $eficienciaCall = $premioCall / $distanciaCall;
            // Normalizar: eficiência ideal ~0.1 (prêmio = 10% da distância)
            $score += min(40, $eficienciaCall * 400); // 0.1 * 400 = 40
        } else {
            // Exatamente no strike (raro mas eficiente)
            $score += 40;
        }

        // 2. Eficiência do prêmio PUT (0-40 pontos)
        $distanciaPut = $distanciaCall; // Mesmo strike
        if ($distanciaPut > 0.01) {
            $eficienciaPut = $premioPut / $distanciaPut;
            $score += min(40, $eficienciaPut * 400);
        } else {
            $score += 40;
        }

        // 3. Balanceamento CALL/PUT (0-20 pontos)
        // Straddle balanceado tem prêmios similares
        if ($premioCall > 0) {
            $relacao = $premioPut / $premioCall;

            if ($relacao >= 0.7 && $relacao <= 1.3) {
                // Relação balanceada (entre 0.7 e 1.3)
                $score += 20;
            } elseif ($relacao >= 0.5 && $relacao < 0.7) {
                $score += 15;
            } elseif ($relacao > 1.3 && $relacao <= 2.0) {
                $score += 15;
            } elseif ($relacao >= 0.3 && $relacao < 0.5) {
                $score += 10;
            } elseif ($relacao > 2.0 && $relacao <= 3.0) {
                $score += 10;
            } else {
                $score += 5; // Muito desbalanceado
            }
        } else {
            $score += 10; // Valor neutro se não houver prêmio
        }

        return min(100, $score);
    }

    /**
     * Score de VOLATILIDADE - Timing para venda de opções
     * IV Percentile alto = bom momento para VENDER opções
     */
    private function calcularScoreVolatilidade(array $dados): float
    {
        $ivPercentile = $dados['iv_percentile'];

        // EXCELENTE: IV Percentile > 80 (volatilidade muito alta - prêmios caros)
        if ($ivPercentile >= 80) {
            return 100;
        }

        // MUITO BOM: 70-80 (ótimo momento para vender)
        if ($ivPercentile >= 70) {
            return 90 + ($ivPercentile - 70) * 1; // 90 a 100
        }

        // BOM: 60-70 (bom momento)
        if ($ivPercentile >= 60) {
            return 80 + ($ivPercentile - 60) * 1; // 80 a 90
        }

        // REGULAR: 50-60 (momento neutro)
        if ($ivPercentile >= 50) {
            return 70 + ($ivPercentile - 50) * 1; // 70 a 80
        }

        // RUIM: 40-50 (volatilidade abaixo da média)
        if ($ivPercentile >= 40) {
            return 60 + ($ivPercentile - 40) * 1; // 60 a 70
        }

        // PÉSSIMO: 30-40 (prêmios baratos)
        if ($ivPercentile >= 30) {
            return 40 + ($ivPercentile - 30) * 2; // 40 a 60
        }

        // MUITO PÉSSIMO: <30 (evitar vender opções)
        return max(0, $ivPercentile * 1.33); // 0 a 40
    }

    /**
     * Calcula score final ponderado
     */
    private function calcularScorePonderado(array $scores): float
    {
        return (
            $scores['retorno'] * $this->config['peso_retorno'] +
            $scores['seguranca'] * $this->config['peso_seguranca'] +
            $scores['eficiencia'] * $this->config['peso_eficiencia'] +
            $scores['volatilidade'] * $this->config['peso_volatilidade']
        );
    }

    /**
     * Aplica modificadores ao score final - CORRIGIDO
     */
    private function aplicarModificadores(float $scoreFinal, array $dados, array $metricas): float
    {
        // BÔNUS 1: CALL OTM (strike acima do preço) - MELHOR para venda de opções!
        // CORREÇÃO: Invertido! CALL OTM = mais prêmio extrínseco = melhor
        if ($metricas['call_otm'] && $metricas['distancia_strike_percentual'] <= 10) {
            $scoreFinal *= 1.10; // +10% de bônus para CALL OTM até 10%
        }

        // BÔNUS 2: Ranking do sistema (se disponível)
        $ranking = $dados['ranking_sistema'];
        if ($ranking <= 3) {
            $scoreFinal *= 1.08; // Top 3: +8%
        } elseif ($ranking <= 6) {
            $scoreFinal *= 1.04; // Top 6: +4%
        }

        // PENALIDADE 1: MSO muito baixo (<5%)
        if ($metricas['mso_percentual'] < 5) {
            $scoreFinal *= 0.7; // -30%
        }

        // PENALIDADE 2: Retorno suspeitamente alto (>8% mensal) - CORRIGIDO
        if ($metricas['retorno_mensal'] > 8) {
            $scoreFinal *= 0.8; // -20% (mais brando, mas ainda penaliza)
        }

        // PENALIDADE 3: IV Percentile muito baixo (<20) - prêmios baratos demais
        if ($dados['iv_percentile'] < 20) {
            $scoreFinal *= 0.5; // -50%
        }

        return min(100, max(0, $scoreFinal));
    }

    /**
     * Determina classificação baseada no score
     */
    private function determinarClassificacao(float $score): string
    {
        if ($score >= 85) return "⭐ EXCELENTE";
        if ($score >= 75) return "✅ MUITO BOA";
        if ($score >= 60) return "👍 BOA";
        if ($score >= 45) return "⚠️ REGULAR";
        if ($score >= 30) return "⛔ FRACA";
        return "❌ EVITAR";
    }

    /**
     * Prepara resultado final formatado
     */
    private function prepararResultado(
        array $operacao,
        array $dados,
        array $metricas,
        array $scores,
        float $scoreFinal,
        string $classificacao
    ): array {
        // Gerar recomendação baseada na classificação
        $recomendacao = $this->gerarRecomendacao($scoreFinal, $classificacao, $metricas);

        // Calcular probabilidade aproximada de sucesso - CORRIGIDO
        $probabilidadeSucesso = $this->calcularProbabilidadeSucesso($metricas);

        return array_merge($operacao, [
            'score' => round($scoreFinal, 2),
            'classificacao' => $classificacao,
            'recomendacao' => $recomendacao,
            'probabilidade_sucesso' => $probabilidadeSucesso,
            'metricas_calculadas' => [
                'premio_total' => round($metricas['premio_total'], 2),
                'retorno_mensal' => round($metricas['retorno_mensal'], 2) . '%',
                'retorno_anualizado' => round($metricas['retorno_anualizado'], 2) . '%',
                'mso_percentual' => round($metricas['mso_percentual'], 2) . '%',
                'call_otm' => $metricas['call_otm'] ? 'SIM' : 'NÃO', // CORRIGIDO
                'distancia_strike' => round($metricas['distancia_strike_percentual'], 2) . '%',
                'bep_inferior' => $dados['bep_inferior']
            ],
            'score_detalhado' => [
                'retorno' => round($scores['retorno'], 2),
                'seguranca' => round($scores['seguranca'], 2),
                'eficiencia' => round($scores['eficiencia'], 2),
                'volatilidade' => round($scores['volatilidade'], 2)
            ],
            'alerta' => $this->verificarAlertas($dados, $metricas)
        ]);
    }

    /**
     * Gera recomendação de ação
     */
    private function gerarRecomendacao(float $score, string $classificacao, array $metricas): string
    {
        if ($score >= 75) {
            return "EXECUTAR - Excelente oportunidade com bom risco/retorno";
        }

        if ($score >= 60) {
            return "CONSIDERAR - Boa oportunidade, monitorar de perto";
        }

        if ($score >= 45) {
            return "ANALISAR - Apenas se ajustar parâmetros (aumentar MSO ou reduzir tamanho)";
        }

        if ($score >= 30) {
            return "AGUARDAR - Esperar melhores condições";
        }

        return "EVITAR - Muito risco ou retorno insuficiente";
    }

    /**
     * Calcula probabilidade aproximada de sucesso - CORRIGIDO
     * Covered Straddle tem probabilidade BAIXA (ganha em faixa estreita)
     */
    private function calcularProbabilidadeSucesso(array $metricas): string
    {
        $mso = $metricas['mso_percentual'];
        $distancia = $metricas['distancia_strike_percentual'];

        // Fórmula corrigida: Covered Straddle tem probabilidade baixa
        // Base 30% (para MSO=0, distância=0) e aumenta com MSO, diminui com distância
        $probabilidade = min(70, max(10, 30 + ($mso * 1.0) - ($distancia * 1.5)));

        if ($probabilidade >= 60) return "ALTA (" . round($probabilidade) . "%)";
        if ($probabilidade >= 45) return "MÉDIA-ALTA (" . round($probabilidade) . "%)";
        if ($probabilidade >= 30) return "MÉDIA (" . round($probabilidade) . "%)";
        if ($probabilidade >= 15) return "BAIXA (" . round($probabilidade) . "%)";
        return "MUITO BAIXA (" . round($probabilidade) . "%)";
    }

    /**
     * Verifica alertas específicos - CORRIGIDO
     */
    private function verificarAlertas(array $dados, array $metricas): array
    {
        $alertas = [];

        // Alerta 1: IV Percentile muito baixo
        if ($dados['iv_percentile'] < 30) {
            $alertas[] = "IV Percentile baixo (" . $dados['iv_percentile'] . "%) - Prêmios podem estar baratos";
        }

        // Alerta 2: MSO insuficiente
        if ($metricas['mso_percentual'] < 3) {
            $alertas[] = "MSO muito baixo (" . round($metricas['mso_percentual'], 2) . "%) - Risco elevado";
        }

        // Alerta 3: Retorno muito alto (pode indicar risco oculto) - CORRIGIDO
        if ($metricas['retorno_mensal'] > 8) {
            $alertas[] = "Retorno muito alto (" . round($metricas['retorno_mensal'], 2) . "% mensal) - Verificar risco";
        }

        // Alerta 4: CALL muito ITM (strike muito abaixo do preço) - PIOR para venda
        // CORREÇÃO: Invertido! CALL ITM = ruim
        //if (!$metricas['call_otm'] && $metricas['distancia_strike_percentual'] > 10) {
        //    $alertas[] = "CALL muito no dinheiro (" . round($metricas['distancia_strike_percentual'], 2) . "%) - Prêmio principalmente intrínseco";
        //}

        return $alertas;
    }

    /**
     * Retorna resultado de erro
     */
    private function resultadoErro(array $operacao, string $mensagem): array
    {
        return array_merge($operacao, [
            'score' => 0,
            'classificacao' => '❌ ERRO',
            'recomendacao' => 'Verificar dados da operação',
            'erro' => $mensagem
        ]);
    }

    /**
     * Ordena várias operações pelo score
     */
    public function ordenarOperacoes(array $operacoes): array
    {
        $resultados = [];

        foreach ($operacoes as $operacao) {
            $resultado = $this->calcularScore($operacao);
            $resultados[] = $resultado;
        }

        // Ordena por score (decrescente)
        usort($resultados, function($a, $b) {
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });

        // Adiciona posição no ranking
        foreach ($resultados as $index => &$resultado) {
            $resultado['ranking_final'] = $index + 1;
        }

        return $resultados;
    }

    /**
     * Filtra operações por critérios mínimos - CORRIGIDO (string check)
     */
    public function filtrarOperacoesQualificadas(array $operacoes): array
    {
        $qualificadas = [];

        foreach ($operacoes as $operacao) {
            $resultado = $this->calcularScore($operacao);

            // Critérios mínimos para operação qualificada:
            // 1. Score mínimo de 60 (classificação "BOA" ou superior)
            // 2. MSO mínimo de 3%
            // 3. IV Percentile mínimo de 30%
            // 4. Sem classificação "EVITAR"

            $metricas = $resultado['metricas_calculadas'] ?? [];
            $mso = floatval(str_replace('%', '', $metricas['mso_percentual'] ?? '0'));
            $ivPercentile = $resultado['iv_percentile'] ?? 0;

            // CORREÇÃO: Verificar string sem emoji
            $classificacao = $resultado['classificacao'] ?? '';
            $evitar = strpos($classificacao, 'EVITAR') !== false;

            if ($resultado['score'] >= 60 &&
                $mso >= 3.0 &&
                $ivPercentile >= 30 &&
                !$evitar) {
                $qualificadas[] = $resultado;
            }
        }

        return $qualificadas;
    }

    /**
     * Gera relatório resumido das melhores operações
     */
    public function gerarRelatorioTopOperacoes(array $operacoesOrdenadas, int $limite = 5): string
    {
        if (empty($operacoesOrdenadas)) {
            return "Nenhuma operação qualificada encontrada.";
        }

        $relatorio = "📊 TOP " . min($limite, count($operacoesOrdenadas)) . " OPERAÇÕES - COVERED STRADDLE\n";
        $relatorio .= "===============================================\n\n";

        for ($i = 0; $i < min($limite, count($operacoesOrdenadas)); $i++) {
            $op = $operacoesOrdenadas[$i];

            $relatorio .= ($i + 1) . "º - " . ($op['ticker'] ?? $op['ativo'] ?? 'N/A') . "\n";
            $relatorio .= "   Score: " . ($op['score'] ?? 0) . " - " . ($op['classificacao'] ?? '') . "\n";
            $relatorio .= "   Retorno Mensal: " . ($op['metricas_calculadas']['retorno_mensal'] ?? 'N/A') . "\n";
            $relatorio .= "   MSO: " . ($op['metricas_calculadas']['mso_percentual'] ?? 'N/A') . "\n";
            $relatorio .= "   Prêmio Total: R$ " . ($op['metricas_calculadas']['premio_total'] ?? '0.00') . "\n";
            $relatorio .= "   Recomendação: " . ($op['recomendacao'] ?? '') . "\n";

            if (!empty($op['alerta'])) {
                $relatorio .= "   ⚠️ Alertas: " . implode("; ", $op['alerta']) . "\n";
            }

            $relatorio .= "\n";
        }

        return $relatorio;
    }
}
