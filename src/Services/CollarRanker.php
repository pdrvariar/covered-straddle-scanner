<?php

namespace App\Services;

class CollarRanker {
    public function ordenarOperacoes(array $operacoes, string $criterio = 'menor_lucro'): array {
        if ($criterio === 'soma_lucros') {
            // Ordenar pela soma dos lucros (máximo + mínimo)
            usort($operacoes, function($a, $b) {
                $somaA = ($a['profit_if_rise_percent'] ?? 0) + ($a['profit_if_fall_percent'] ?? 0);
                $somaB = ($b['profit_if_rise_percent'] ?? 0) + ($b['profit_if_fall_percent'] ?? 0);
                return $somaB <=> $somaA; // Ordem decrescente
            });
        } else {
            // Ordenar pelo menor lucro (padrão)
            usort($operacoes, function($a, $b) {
                $menorA = min($a['profit_if_rise_percent'] ?? 0, $a['profit_if_fall_percent'] ?? 0);
                $menorB = min($b['profit_if_rise_percent'] ?? 0, $b['profit_if_fall_percent'] ?? 0);

                // Ordem decrescente (maior para menor)
                if ($menorB > $menorA) return 1;
                if ($menorB < $menorA) return -1;
                return 0;
            });
        }

        return $operacoes;
    }
}
