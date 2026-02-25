# ✅ Checklist de Teste - Taxa Mensal Collar

## 📋 Testes de Verificação

### 1. Teste Visual - Interface
- [ ] Acessar http://localhost:8080
- [ ] Fazer login no sistema
- [ ] Ir para Scanner
- [ ] Selecionar estratégia "Collar"
- [ ] Executar um scan
- [ ] Clicar em "Detalhes" de uma operação
- [ ] Verificar seção "Análise Financeira"

### 2. Verificar Campos Exibidos
- [ ] **Lucro Máximo (Alta):** R$ XXX,XX (X,XX%)
- [ ] **Taxa Mensal (Alta):** R$ XXX,XX (X,XX%) ✨ **NOVO**
- [ ] **Lucro Mínimo (Queda):** R$ XXX,XX (X,XX%)
- [ ] **Taxa Mensal (Queda):** R$ XXX,XX (X,XX%) ✨ **NOVO**
- [ ] **Soma dos Lucros:** R$ XXX,XX (X,XX%)

### 3. Validar Cores
- [ ] Taxa Mensal (Alta) está em **verde** (text-success)
- [ ] Taxa Mensal (Queda) está em **amarelo** (text-warning)

### 4. Teste de Cálculos

#### Caso 1: Operação de 30 dias
```
Investimento: R$ 15.600,00
Prazo: 30 dias
Lucro Máximo: 0,70%
Lucro Mínimo: 9,04%

Esperado:
Taxa Mensal (Alta): 0,70% → R$ 109,20
Taxa Mensal (Queda): 9,04% → R$ 1.410,24
```

#### Caso 2: Operação de 60 dias
```
Investimento: R$ 15.600,00
Prazo: 60 dias
Lucro Máximo: 1,40%
Lucro Mínimo: 18,08%

Esperado:
Taxa Mensal (Alta): 0,70% → R$ 109,20
Taxa Mensal (Queda): 9,04% → R$ 1.410,24
```

### 5. Teste de Atualização Dinâmica
- [ ] Alterar o valor do **Prêmio da CALL**
- [ ] Verificar se Taxa Mensal (Alta) atualiza automaticamente
- [ ] Alterar o valor do **Prêmio da PUT**
- [ ] Verificar se Taxa Mensal (Queda) atualiza automaticamente
- [ ] Alterar o **Preço Atual da Ação**
- [ ] Verificar se ambas as taxas atualizam

### 6. Teste de Formatação
- [ ] Valores em Real usam formato brasileiro: R$ X.XXX,XX
- [ ] Percentuais usam vírgula: X,XX%
- [ ] Não há valores negativos visíveis (usar abs se necessário)
- [ ] Casas decimais consistentes (2 dígitos)

### 7. Teste de Casos Especiais

#### Caso: Dias = 0
```
Comportamento esperado:
Taxa Mensal (Alta): R$ 0,00 (0,00%)
Taxa Mensal (Queda): R$ 0,00 (0,00%)
```

#### Caso: Investimento = 0
```
Comportamento esperado:
Taxa Mensal (Alta): R$ 0,00 (0,00%)
Taxa Mensal (Queda): R$ 0,00 (0,00%)
```

### 8. Teste de Responsividade
- [ ] Abrir em desktop (largura > 768px)
- [ ] Verificar layout em 2 colunas
- [ ] Abrir em tablet (largura 768px - 992px)
- [ ] Verificar se mantém legibilidade
- [ ] Abrir em mobile (largura < 768px)
- [ ] Verificar se empilha verticalmente

### 9. Teste de Console do Browser
- [ ] Abrir DevTools (F12)
- [ ] Verificar aba Console
- [ ] Confirmar que **não há erros JavaScript**
- [ ] Verificar Network para erros HTTP

### 10. Teste com Covered Straddle
- [ ] Executar scan com estratégia "Covered Straddle"
- [ ] Abrir Detalhes
- [ ] Confirmar que **NÃO exibe** Taxa Mensal (só para Collar)
- [ ] Verificar que mostra "Lucro Máximo" e "Prejuízo Máximo" normais

## 📊 Validação de Fórmulas

### Fórmula da Taxa Mensal:
```
Taxa Mensal (%) = (Lucro Total % × 30) / Dias até Vencimento
Taxa Mensal (R$) = (Taxa Mensal % / 100) × Investimento Inicial
```

### Exemplos de Validação Manual:

**Exemplo 1: 30 dias**
- Lucro: 10%
- Dias: 30
- Taxa: (10 × 30) / 30 = 10%

**Exemplo 2: 60 dias**
- Lucro: 10%
- Dias: 60
- Taxa: (10 × 30) / 60 = 5%

**Exemplo 3: 90 dias**
- Lucro: 15%
- Dias: 90
- Taxa: (15 × 30) / 90 = 5%

## 🐛 Problemas Conhecidos a Verificar
- [ ] Divisão por zero quando dias = 0
- [ ] Valores NaN ou Infinity no JavaScript
- [ ] Arredondamento incorreto
- [ ] Formatação com símbolos inválidos
- [ ] Atualização não ocorrendo em tempo real

## ✅ Critérios de Aceitação

Para considerar a feature **APROVADA**, todos os itens devem estar OK:

1. ✅ Campos Taxa Mensal (Alta) e (Queda) são exibidos
2. ✅ Cálculos estão matematicamente corretos
3. ✅ Formatação brasileira está correta
4. ✅ Cores estão adequadas (verde/amarelo)
5. ✅ Atualização dinâmica funciona
6. ✅ Não há erros no console
7. ✅ Layout está responsivo
8. ✅ Não afeta estratégia Covered Straddle
9. ✅ Casos especiais (0 dias) tratados
10. ✅ Documentação está completa

## 📝 Notas de Teste

**Data:** _______________
**Testador:** _______________
**Versão:** 1.0

**Resultado Geral:**
- [ ] ✅ APROVADO
- [ ] ⚠️ APROVADO COM RESSALVAS
- [ ] ❌ REPROVADO

**Comentários:**
_____________________________________________
_____________________________________________
_____________________________________________

