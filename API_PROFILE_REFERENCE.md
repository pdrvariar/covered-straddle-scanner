# API de Perfil - Referência Rápida

## 📡 Endpoints Disponíveis

### 1. Atualizar Perfil
```http
POST /api/profile/update
Content-Type: application/json
```

**Body:**
```json
{
  "username": "novo_username"
}
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Perfil atualizado com sucesso"
}
```

**Resposta de Erro:**
```json
{
  "success": false,
  "message": "Nome de usuário já está em uso"
}
```

---

### 2. Alterar Senha
```http
POST /api/profile/password
Content-Type: application/json
```

**Body:**
```json
{
  "current_password": "senha_atual",
  "new_password": "nova_senha",
  "confirm_password": "nova_senha"
}
```

**Resposta de Sucesso:**
```json
{
  "success": true,
  "message": "Senha alterada com sucesso"
}
```

**Resposta de Erro:**
```json
{
  "success": false,
  "message": "Senha atual incorreta"
}
```

---

## 🔒 Autenticação

Todas as rotas de API requerem que o usuário esteja autenticado via sessão PHP.

**Verificação:**
```php
if (!isset($_SESSION['user_id'])) {
    // Retorna 401 Unauthorized
}
```

---

## ✅ Validações

### Username
- Mínimo 3 caracteres
- Deve ser único no sistema
- Não pode estar vazio

### Senha
- Mínimo 6 caracteres
- Senha atual deve ser verificada
- Nova senha deve ser confirmada
- Senhas devem coincidir

---

## 📝 Exemplos de Uso

### JavaScript Fetch API

**Atualizar Perfil:**
```javascript
async function updateProfile(username) {
  const response = await fetch('/api/profile/update', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ username })
  });
  
  const result = await response.json();
  
  if (result.success) {
    console.log('Perfil atualizado!');
    // Recarregar página ou atualizar UI
  } else {
    console.error(result.message);
  }
}
```

**Alterar Senha:**
```javascript
async function changePassword(currentPassword, newPassword, confirmPassword) {
  const response = await fetch('/api/profile/password', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      current_password: currentPassword,
      new_password: newPassword,
      confirm_password: confirmPassword
    })
  });
  
  const result = await response.json();
  
  if (result.success) {
    console.log('Senha alterada!');
    // Limpar formulário
  } else {
    console.error(result.message);
  }
}
```

### jQuery

**Atualizar Perfil:**
```javascript
$.ajax({
  url: '/api/profile/update',
  method: 'POST',
  contentType: 'application/json',
  data: JSON.stringify({ username: 'novo_username' }),
  success: function(result) {
    if (result.success) {
      alert(result.message);
    }
  }
});
```

**Alterar Senha:**
```javascript
$.ajax({
  url: '/api/profile/password',
  method: 'POST',
  contentType: 'application/json',
  data: JSON.stringify({
    current_password: 'senha123',
    new_password: 'novaSenha456',
    confirm_password: 'novaSenha456'
  }),
  success: function(result) {
    if (result.success) {
      alert(result.message);
    } else {
      alert(result.message);
    }
  }
});
```

---

## 🐛 Códigos de Erro HTTP

| Código | Descrição |
|--------|-----------|
| 200 | Sucesso |
| 400 | Requisição inválida (dados faltando ou inválidos) |
| 401 | Não autenticado |
| 405 | Método não permitido (apenas POST) |
| 500 | Erro interno do servidor |

---

## 📊 Respostas Comuns

### Erros de Validação

```json
{
  "success": false,
  "message": "Todos os campos são obrigatórios"
}
```

```json
{
  "success": false,
  "message": "As senhas não coincidem"
}
```

```json
{
  "success": false,
  "message": "A nova senha deve ter no mínimo 6 caracteres"
}
```

```json
{
  "success": false,
  "message": "Nome de usuário deve ter no mínimo 3 caracteres"
}
```

### Erros de Autenticação

```json
{
  "success": false,
  "message": "Usuário não autenticado"
}
```

```json
{
  "success": false,
  "message": "Senha atual incorreta"
}
```

### Erros de Negócio

```json
{
  "success": false,
  "message": "Nome de usuário já está em uso"
}
```

---

## 🧪 Testando com cURL

**Atualizar Perfil:**
```bash
curl -X POST http://localhost:8080/api/profile/update \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=seu_session_id" \
  -d '{"username":"novo_username"}'
```

**Alterar Senha:**
```bash
curl -X POST http://localhost:8080/api/profile/password \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=seu_session_id" \
  -d '{
    "current_password":"senha123",
    "new_password":"novaSenha456",
    "confirm_password":"novaSenha456"
  }'
```

---

## 📚 Documentação Adicional

Para mais informações, consulte:
- `PROFILE_FEATURE.md` - Documentação completa da funcionalidade
- `/src/Controllers/ProfileController.php` - Código fonte do controller
- `/src/Models/User.php` - Métodos do model

---

## 💡 Dicas

1. **Sempre valide no frontend E no backend**
2. **Use HTTPS em produção**
3. **Implemente rate limiting para evitar ataques**
4. **Log de alterações críticas (mudança de senha)**
5. **Considere adicionar 2FA para mais segurança**

---

**Última atualização:** 2026-02-25

