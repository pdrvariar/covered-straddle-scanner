# Correção: Strike não sendo salvo nas operações

## Problema Identificado

O campo `strike_price` não estava sendo salvo corretamente no banco de dados ou não estava sendo exibido na tela de Operações Salvas.

## Diagnóstico Realizado

Verifiquei os seguintes componentes do sistema:

1. **Banco de Dados:** ✅ A coluna `strike_price` existe na tabela `operations`
2. **Model (Operation.php):** ✅ O código já salva e recupera o `strike_price` corretamente
3. **Views (operations.php, dashboard.php):** ✅ As telas já exibem o `strike_price` corretamente
4. **JavaScript (operation-details.php):** ❌ O `strike_price` não estava sendo explicitamente enviado ao salvar

## Causa Raiz

No arquivo `src/Views/operation-details.php`, na função JavaScript `saveOperation()`, o campo `strike_price` não estava sendo explicitamente incluído no objeto `operationToSave` enviado para o backend. 

O código dependia apenas do operador spread (`...operationData`), mas para operações de **Collar**, quando os valores de `call_strike` e `put_strike` eram diferentes, o `strike_price` poderia não estar sendo corretamente definido ou poderia estar sendo sobrescrito.

## Solução Implementada

### 1. Arquivo modificado: `src/Views/operation-details.php`

**Localização:** Linhas 1220-1250 (aproximadamente)

**Antes:**
```javascript
// Ajustes específicos por estratégia
if (isCollar) {
    operationToSave.call_strike = operationData.call_strike || operationData.strike_price;
    operationToSave.put_strike = operationData.put_strike || operationData.strike_price;
}
```

**Depois:**
```javascript
// Ajustes específicos por estratégia
if (isCollar) {
    operationToSave.strike_price = operationData.strike_price || operationData.call_strike || operationData.put_strike;
    operationToSave.call_strike = operationData.call_strike || operationData.strike_price;
    operationToSave.put_strike = operationData.put_strike || operationData.strike_price;
} else {
    // Para Covered Straddle, garantir que strike_price esteja definido
    operationToSave.strike_price = operationData.strike_price || operationData.call_strike || operationData.put_strike;
    operationToSave.call_strike = operationData.strike_price || operationData.call_strike;
    operationToSave.put_strike = operationData.strike_price || operationData.put_strike;
}
```

## Benefícios

1. **Collar Strategy:** O campo `strike_price` agora é explicitamente definido e salvo no banco de dados
2. **Covered Straddle Strategy:** O campo `strike_price` também é garantido para esta estratégia
3. **Fallback Robusto:** Implementado um sistema de fallback que tenta usar `call_strike` ou `put_strike` caso `strike_price` não esteja disponível
4. **Consistência:** Ambas as estratégias agora seguem o mesmo padrão de salvamento

## Taxa Mensal para Collar (Bônus)

Como solicitado, o cálculo da Taxa Mensal para os cenários de Alta e Queda no Collar **já estava implementado** no sistema:

### Análise Financeira - Collar
A tela de detalhes da operação agora exibe:

- **Lucro Máximo (Alta):** R$ XXX,XX (X,XX%)
  - **Taxa Mensal (Alta):** R$ XXX,XX (X,XX%)
  
- **Lucro Mínimo (Queda):** R$ XXX,XX (X,XX%)
  - **Taxa Mensal (Queda):** R$ XXX,XX (X,XX%)

### Fórmula Implementada

```javascript
// Taxa Mensal (Alta)
const taxaMensalAltaPercent = daysToMaturity > 0 ? (lucroMaximoPercent * 30) / daysToMaturity : 0;
const taxaMensalAltaReal = (taxaMensalAltaPercent / 100) * initialInvestment;

// Taxa Mensal (Queda)
const taxaMensalQuedaPercent = daysToMaturity > 0 ? (lucroMinimoPercent * 30) / daysToMaturity : 0;
const taxaMensalQuedaReal = (taxaMensalQuedaPercent / 100) * initialInvestment;
```

## Teste

Para testar a correção:

1. Execute um scan (Scanner) para qualquer estratégia (Collar ou Covered Straddle)
2. Abra os detalhes de uma operação
3. Clique em "Salvar Operação"
4. Vá para "Operações Salvas"
5. Verifique se o Strike está sendo exibido corretamente na coluna "Strike"
6. Abra os detalhes da operação salva e verifique se todos os valores estão corretos

## Arquivos Relacionados

- `src/Views/operation-details.php` - Corrigido o salvamento do strike_price
- `src/Views/operations.php` - Exibe o strike_price das operações salvas
- `src/Models/Operation.php` - Processa o salvamento no banco de dados
- `database/migrations/001_create_tables.sql` - Define a estrutura da tabela operations

## Data da Correção

2026-02-25

