# 🎨 Guia Visual - Jornada do Usuário no Perfil

## 📍 Como Acessar

```
┌─────────────────────────────────────────────┐
│  NAVBAR                        [👤 Usuario] │ ← 1. Clique aqui
└─────────────────────────────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────┐
                    │ 👤 Meu Perfil        │ ← 2. Clique em "Meu Perfil"
                    │ ⚙️  Configurações    │
                    │ 💰 Minha Carteira    │
                    │ 📜 Histórico         │
                    │ ────────────────     │
                    │ 🚪 Sair              │
                    └───────────────────────┘
```

---

## 🖼️ Layout da Página de Perfil

```
╔═══════════════════════════════════════════════════════════════╗
║  👤 MEU PERFIL                                                ║
║  Gerencie suas informações pessoais e configurações de conta  ║
╚═══════════════════════════════════════════════════════════════╝

┌──────────────────────────┬────────────────────────────────────┐
│                          │                                    │
│   ┌─────────────────┐    │  ╔══════════════════════════════╗ │
│   │                 │    │  ║ ✏️ INFORMAÇÕES DO PERFIL    ║ │
│   │      👤         │    │  ╚══════════════════════════════╝ │
│   │   Avatar        │    │                                    │
│   │                 │    │  Nome de Usuário:                  │
│   └─────────────────┘    │  ┌──────────────────────────────┐ │
│                          │  │ [usuario_atual]              │ │
│   JP                     │  └──────────────────────────────┘ │
│   Trader Premium         │                                    │
│   ✅ active             │  [💾 Salvar Alterações]           │
│                          │                                    │
│   📅 Membro desde:       │  ╔══════════════════════════════╗ │
│      25/01/2026          │  ║ 🔒 ALTERAR SENHA            ║ │
│                          │  ╚══════════════════════════════╝ │
│   🕐 Última atualização:  │                                    │
│      25/02/2026 14:30    │  Senha Atual:                      │
│                          │  ┌──────────────────────────────┐ │
│   ╔════════════════════╗ │  │ [••••••••]          [👁️]   │ │
│   ║ ESTATÍSTICAS       ║ │  └──────────────────────────────┘ │
│   ╚════════════════════╝ │                                    │
│                          │  Nova Senha:                       │
│   📋 Total Operações     │  ┌──────────────────────────────┐ │
│      15                  │  │ [••••••••]          [👁️]   │ │
│   ──────────────────     │  └──────────────────────────────┘ │
│   ✅ Completadas         │                                    │
│      10                  │  Confirmar Senha:                  │
│   ──────────────────     │  ┌──────────────────────────────┐ │
│   ⏳ Ativas              │  │ [••••••••]          [👁️]   │ │
│      5                   │  └──────────────────────────────┘ │
│   ──────────────────     │                                    │
│   💰 Lucro Total         │  ℹ️ Use senha forte com letras,   │
│      R$ 12.458,90        │     números e caracteres especiais │
│                          │                                    │
│                          │  [🛡️ Alterar Senha]               │
│                          │                                    │
│                          │  ╔══════════════════════════════╗ │
│                          │  ║ ⚠️ ZONA DE PERIGO           ║ │
│                          │  ╚══════════════════════════════╝ │
│                          │                                    │
│                          │  [🗑️ Excluir Conta]               │
│                          │                                    │
└──────────────────────────┴────────────────────────────────────┘
```

---

## 🔄 Fluxo: Alterar Username

```
1️⃣ USUÁRIO                      2️⃣ FRONTEND                    3️⃣ BACKEND
   
   [Edita campo]     ──────►    [JavaScript]      ──────►      ProfileController
   "novo_username"               captura evento                 ↓
                                 ↓                              updateProfile()
                                 valida no browser              ↓
                                 ↓                              User::updateProfile()
   [Clica Salvar]    ──────►    fetch('/api/profile/update')  ↓
                                 POST + JSON                    Validações:
                                                               - Min 3 chars
                                                               - Já existe?
                                                               ↓
   [Recebe feedback] ◄──────    [Mostra alerta]   ◄──────     UPDATE users
   ✅ "Sucesso!"                 setTimeout()                   ↓
                                 reload()                       $_SESSION atualizada
                                                               ↓
                                                               JSON response
```

---

## 🔐 Fluxo: Alterar Senha

```
1️⃣ USUÁRIO                      2️⃣ FRONTEND                    3️⃣ BACKEND

   [Preenche form]   ──────►    [JavaScript]      ──────►      ProfileController
   - Senha atual                 captura submit                 ↓
   - Nova senha                  ↓                              updatePassword()
   - Confirma                    valida match                   ↓
                                 ↓                              User::updatePassword()
   [Clica Alterar]   ──────►    fetch('/api/profile/password') ↓
                                 POST + JSON                    Validações:
                                                               ↓
                                                               1. Senha atual OK?
                                                               │  password_verify()
                                                               ↓
                                                               2. Min 6 chars?
   [Toggle 👁️]       ──────►    type = "text"                 ↓
   Mostra/Esconde               type = "password"              3. Senhas match?
                                                               ↓
                                                               UPDATE users
   [Recebe feedback] ◄──────    [Mostra alerta]   ◄──────     SET password = hash
   ✅ "Senha alterada!"          limpa form                    ↓
                                                               JSON response
```

---

## 🎨 Estados Visuais

### ✅ Sucesso
```
┌────────────────────────────────────────────┐
│ ✅ Perfil atualizado com sucesso!     [×] │
└────────────────────────────────────────────┘
[Cor: Verde #00c853]
[Auto-fecha em 5s]
```

### ❌ Erro
```
┌────────────────────────────────────────────┐
│ ⚠️ Nome de usuário já está em uso     [×] │
└────────────────────────────────────────────┘
[Cor: Vermelho #f44336]
[Auto-fecha em 5s]
```

### ℹ️ Info
```
┌────────────────────────────────────────────┐
│ ℹ️ Use senha forte com letras e números   │
└────────────────────────────────────────────┘
[Cor: Azul #2196f3]
[Permanece visível]
```

---

## 👁️ Funcionalidade: Toggle de Senha

```
ESTADO INICIAL:
┌──────────────────────────────┐
│ [••••••••]          [👁️]   │
└──────────────────────────────┘

        │
        │ [Usuário clica no ícone]
        ▼

ESTADO REVELADO:
┌──────────────────────────────┐
│ [senha123]          [👁️/]  │
└──────────────────────────────┘

Código:
- Alterna: type="password" ↔️ type="text"
- Alterna: fa-eye ↔️ fa-eye-slash
```

---

## 📊 Estatísticas em Tempo Real

```
Quando o usuário acessa o perfil:

1. ProfileController::index()
   ↓
2. User::getStats($userId)
   ↓
3. SQL Query:
   SELECT 
     COUNT(*) as total_operations,
     SUM(CASE status='completed') as completed,
     SUM(CASE status='active') as active,
     SUM(profit) as total_profit
   FROM operations 
   WHERE user_id = ?
   ↓
4. Retorna array com dados
   ↓
5. View renderiza:
   
   ╔════════════════════╗
   ║ ESTATÍSTICAS       ║
   ╚════════════════════╝
   
   📋 15   Total
   ✅ 10   Completadas
   ⏳ 5    Ativas
   💰 R$ 12.458,90
```

---

## 🔒 Validações em Camadas

```
┌─────────────────────────────────────────────┐
│         VALIDAÇÃO EM MÚLTIPLAS CAMADAS      │
└─────────────────────────────────────────────┘

🔷 CAMADA 1: HTML5
   <input minlength="6" required>
   ↓ [Validação básica do navegador]

🔷 CAMADA 2: JavaScript
   if (newPassword !== confirmPassword) {
     alert('Senhas não coincidem');
     return;
   }
   ↓ [Validação antes de enviar]

🔷 CAMADA 3: Backend Controller
   if (empty($newPassword)) {
     return error('Campo obrigatório');
   }
   ↓ [Validação de dados recebidos]

🔷 CAMADA 4: Model
   if (strlen($newPassword) < 6) {
     return ['success' => false];
   }
   ↓ [Validação de regras de negócio]

🔷 CAMADA 5: Database
   UNIQUE constraint on username
   ↓ [Integridade referencial]

✅ DADOS SEGUROS E VÁLIDOS
```

---

## 🎯 Casos de Uso

### Caso 1: Novo usuário altera senha padrão
```
1. Login com "mudar123"
2. Vai ao perfil
3. Altera senha para "minhaSenha2026!"
4. ✅ Sucesso - próximo login usa nova senha
```

### Caso 2: Usuário troca username
```
1. Username atual: "jp"
2. Edita para: "joao_pedro"
3. Salva
4. ✅ Navbar atualiza automaticamente
5. ✅ Próximo login usa "joao_pedro"
```

### Caso 3: Erro ao usar username existente
```
1. Tenta mudar para: "pablo"
2. Sistema verifica banco
3. ❌ "Username já existe"
4. Mantém username original
```

### Caso 4: Erro ao digitar senha errada
```
1. Senha atual: "errada"
2. Sistema verifica hash
3. ❌ "Senha atual incorreta"
4. Não altera nada
```

### Caso 5: Visualizar estatísticas
```
1. Acessa perfil
2. Sistema busca operações do user_id
3. ✅ Mostra:
   - 15 operações totais
   - 10 completadas
   - 5 ativas
   - R$ 12.458,90 lucro
```

---

## 🚦 Indicadores de Estado

### 🔵 Loading
```
[💾 Salvando...]
[Botão desabilitado]
[Spinner animado]
```

### ✅ Sucesso
```
[✅ Salvo!]
[Alert verde]
[Redirect em 1.5s]
```

### ❌ Erro
```
[⚠️ Erro!]
[Alert vermelho]
[Mantém no formulário]
```

---

## 📱 Responsividade

### 🖥️ Desktop (> 992px)
```
┌────────────┬──────────────────┐
│  Sidebar   │  Perfil (2 cols) │
│  (fixo)    │  Info + Forms    │
└────────────┴──────────────────┘
```

### 📱 Tablet (768px - 992px)
```
┌────────────┬──────────┐
│  Sidebar   │  Perfil  │
│  (menor)   │  (1 col) │
└────────────┴──────────┘
```

### 📱 Mobile (< 768px)
```
┌────────────────────┐
│  Sidebar (hidden)  │
│  [≡] Menu Toggle   │
├────────────────────┤
│     Perfil         │
│     (stacked)      │
│                    │
│  ┌──────────────┐  │
│  │  Info Card   │  │
│  └──────────────┘  │
│  ┌──────────────┐  │
│  │  Stats Card  │  │
│  └──────────────┘  │
│  ┌──────────────┐  │
│  │  Edit Form   │  │
│  └──────────────┘  │
│  ┌──────────────┐  │
│  │  Pass Form   │  │
│  └──────────────┘  │
└────────────────────┘
```

---

## 🎨 Paleta de Cores

```
🔵 Primária:  #667eea → #764ba2 (gradiente)
🟢 Sucesso:   #00c853 → #00b248
🔴 Erro:      #f44336 → #d32f2f  
🟡 Aviso:     #ffc107 → #ff9800
⚪ Background: #f8fafc
⬛ Texto:      #333333
```

---

## 💡 Dicas de UX

✨ **Feedback Imediato**: Alertas aparecem instantaneamente
✨ **Auto-close**: Alertas desaparecem após 5 segundos
✨ **Animações**: Transições suaves em todos os elementos
✨ **Icons**: Font Awesome para comunicação visual
✨ **Tooltips**: Dicas contextuais nos campos
✨ **Validation**: Mensagens claras de erro
✨ **Loading**: Indicadores durante processamento

---

**Este é o guia visual completo da funcionalidade de perfil! 🎉**

Todas as telas, fluxos e interações estão implementados e funcionando perfeitamente.

