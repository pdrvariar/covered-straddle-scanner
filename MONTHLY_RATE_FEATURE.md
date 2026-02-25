# Feature: Taxa Mensal para Collar

## Descrição
Esta feature adiciona o cálculo e exibição da **Taxa Mensal** para os cenários de Alta e Queda na estratégia Collar, na tela de Detalhes da Operação.

## Implementação

### 1. Cálculos no Backend (PHP)
Arquivo: `src/Views/operation-details.php`

Foram adicionados os seguintes cálculos para a estratégia Collar:

```php
// Calcular taxa mensal para Alta e Queda
$daysToMaturity = $operation['days_to_maturity'];
$taxaMensalAltaPercent = $daysToMaturity > 0 ? ($lucroMaximoPercent * 30) / $daysToMaturity : 0;
$taxaMensalAltaReal = ($taxaMensalAltaPercent / 100) * $operation['initial_investment'];

$taxaMensalQuedaPercent = $daysToMaturity > 0 ? ($lucroMinimoPercent * 30) / $daysToMaturity : 0;
$taxaMensalQuedaReal = ($taxaMensalQuedaPercent / 100) * $operation['initial_investment'];
```

**Fórmula:**
- Taxa Mensal (%) = (Lucro Total % × 30 dias) / Dias até o Vencimento
- Taxa Mensal (R$) = (Taxa Mensal % / 100) × Investimento Inicial

### 2. Exibição no Frontend (HTML)
Foram adicionados novos campos na seção "Análise Financeira":

```
Lucro Máximo (Alta): R$ XXX,XX (X,XX%)
Taxa Mensal (Alta): R$ XXX,XX (X,XX%)  ← NOVO
Lucro Mínimo (Queda): R$ XXX,XX (X,XX%)
Taxa Mensal (Queda): R$ XXX,XX (X,XX%)  ← NOVO
Soma dos Lucros: R$ XXX,XX (X,XX%)
```

### 3. Atualização Dinâmica (JavaScript)
O JavaScript foi atualizado para recalcular as taxas mensais quando os valores das opções são alterados:

```javascript
// Calcular taxa mensal para Alta e Queda
const taxaMensalAltaPercent = daysToMaturity > 0 ? (lucroMaximoPercent * 30) / daysToMaturity : 0;
const taxaMensalAltaReal = (taxaMensalAltaPercent / 100) * initialInvestment;

const taxaMensalQuedaPercent = daysToMaturity > 0 ? (lucroMinimoPercent * 30) / daysToMaturity : 0;
const taxaMensalQuedaReal = (taxaMensalQuedaPercent / 100) * initialInvestment;
```

## Exemplo de Uso

### Cenário de Exemplo:
- **Investimento Inicial:** R$ 15.600,00
- **Dias até Vencimento:** 30 dias
- **Lucro Máximo (Alta):** R$ 109,50 (0,70%)
- **Lucro Mínimo (Queda):** R$ 1.409,50 (9,04%)

### Cálculos:
- **Taxa Mensal (Alta):** 
  - Percentual: (0,70% × 30) / 30 = 0,70%
  - Valor: (0,70% / 100) × R$ 15.600,00 = R$ 109,50

- **Taxa Mensal (Queda):** 
  - Percentual: (9,04% × 30) / 30 = 9,04%
  - Valor: (9,04% / 100) × R$ 15.600,00 = R$ 1.409,50

*Nota: Para operações com 30 dias, a taxa mensal é igual ao lucro total, pois é exatamente 1 mês.*

### Com Diferentes Prazos:
Se a operação tivesse **60 dias** até o vencimento:
- **Taxa Mensal (Alta):** (0,70% × 30) / 60 = 0,35% → R$ 54,75
- **Taxa Mensal (Queda):** (9,04% × 30) / 60 = 4,52% → R$ 704,75

## Benefícios
1. **Comparabilidade:** Permite comparar retornos de operações com diferentes prazos
2. **Padronização:** Normaliza os retornos para base mensal
3. **Análise:** Facilita a análise de rentabilidade por período
4. **Decisão:** Auxilia na tomada de decisão entre diferentes estratégias

## Cores e Estilos
- **Taxa Mensal (Alta):** Texto verde (text-success) - representa cenário otimista
- **Taxa Mensal (Queda):** Texto amarelo (text-warning) - representa cenário conservador

## Notas Técnicas
- A taxa mensal é calculada de forma proporcional aos dias
- Se `days_to_maturity = 0`, a taxa mensal retorna 0 para evitar divisão por zero
- Os valores são exibidos com 2 casas decimais no formato brasileiro (R$ X.XXX,XX)
- As atualizações são em tempo real quando o usuário ajusta os valores das opções

