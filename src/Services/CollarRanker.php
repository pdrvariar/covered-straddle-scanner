<?php

namespace App\Services;

class CollarRanker {
    public function ordenarOperacoes(array $operacoes): array {
        // Ordenar pelo maior profit_percent (Retorno Total)
        usort($operacoes, function($a, $b) {
            $retornoA = $a['profit_percent'] ?? 0;
            $retornoB = $b['profit_percent'] ?? 0;

            // Ordem decrescente (maior para menor)
            if ($retornoB > $retornoA) return 1;
            if ($retornoB < $retornoA) return -1;
            return 0;
        });

        //Retorna as operacoes
        return $operacoes;
    }
}