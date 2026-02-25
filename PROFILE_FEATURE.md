# Funcionalidade de Perfil do Usuário

## 📝 Visão Geral

Implementação completa da funcionalidade de perfil do usuário, incluindo:
- Visualização de informações pessoais
- Edição do nome de usuário
- Alteração de senha com validação de segurança
- Estatísticas do usuário (operações, lucros, etc.)

## 🎯 Funcionalidades Implementadas

### 1. Página de Perfil (`/src/Views/profile.php`)
- **Informações do Perfil**: Exibe avatar, nome de usuário, status e datas
- **Estatísticas**: Mostra total de operações, completadas, ativas e lucro total
- **Edição de Perfil**: Permite alterar o nome de usuário
- **Alteração de Senha**: Formulário completo com validações
- **Zona de Perigo**: Opção para exclusão de conta (placeholder)

### 2. Controller (`/src/Controllers/ProfileController.php`)
- `index()`: Exibe a página de perfil com dados do usuário
- `updateProfile()`: API para atualizar informações do perfil
- `updatePassword()`: API para alterar senha com validações

### 3. Model (`/src/Models/User.php`)
Novos métodos adicionados:
- `updatePassword()`: Atualiza senha com verificação da senha atual
- `updateProfile()`: Atualiza informações do usuário
- `getStats()`: Retorna estatísticas do usuário

## 🔧 Rotas Implementadas

### Rotas Web
- `/?action=profile` - Página de perfil do usuário

### Rotas API
- `POST /api/profile/update` - Atualizar informações do perfil
- `POST /api/profile/password` - Alterar senha

## 🔒 Segurança

### Validações de Senha
- Senha atual obrigatória para alteração
- Mínimo de 6 caracteres
- Confirmação de senha
- Hash bcrypt para armazenamento

### Validações de Perfil
- Username único no sistema
- Mínimo de 3 caracteres
- Verificação de autenticação em todas as rotas

## 📱 Interface do Usuário

### Design
- Layout responsivo com Bootstrap 5
- Cards organizados em grid
- Ícones Font Awesome
- Gradientes e sombras modernas
- Botões de toggle para mostrar/ocultar senhas

### Feedback ao Usuário
- Alertas de sucesso/erro
- Validação em tempo real
- Mensagens claras e intuitivas

## 🚀 Como Usar

### Acessar o Perfil
1. Faça login no sistema
2. Clique no ícone do usuário no canto superior direito
3. Selecione "Meu Perfil" no menu dropdown

### Alterar Nome de Usuário
1. Na seção "Informações do Perfil"
2. Edite o campo "Nome de Usuário"
3. Clique em "Salvar Alterações"

### Alterar Senha
1. Na seção "Alterar Senha"
2. Digite a senha atual
3. Digite a nova senha (mínimo 6 caracteres)
4. Confirme a nova senha
5. Clique em "Alterar Senha"

## 📊 Estatísticas Exibidas

- **Total de Operações**: Quantidade de operações criadas
- **Fechadas**: Operações com status "closed"
- **Ativas**: Operações com status "active"
- **Expiradas**: Operações com status "expired"
- **Lucro Potencial**: Soma de max_profit de todas as operações

## 🔄 Fluxo de Dados

```
Usuário → View (profile.php) → JavaScript → API Controller
                                                ↓
                                            User Model
                                                ↓
                                            Database
                                                ↓
                                            Response JSON
                                                ↓
                                            View Update
```

## 🎨 Customização

### Adicionar Novos Campos ao Perfil

1. Adicione colunas na tabela `users` (migration SQL)
2. Atualize o formulário em `profile.php`
3. Modifique `updateProfile()` no `User.php`
4. Atualize o controller para processar novos dados

### Exemplo: Adicionar Email

```sql
ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE;
```

```php
// No User.php
if (isset($data['email'])) {
    // Validar email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Email inválido'];
    }
    // Atualizar...
}
```

## 📝 Próximas Melhorias Sugeridas

1. **Upload de Avatar**: Permitir que usuários façam upload de foto
2. **Autenticação de Dois Fatores**: Adicionar 2FA
3. **Histórico de Alterações**: Log de mudanças no perfil
4. **Preferências**: Configurações personalizadas (tema, idioma, etc.)
5. **Exclusão de Conta**: Implementar funcionalidade completa
6. **Verificação de Email**: Enviar email de confirmação
7. **Recuperação de Senha**: Sistema de reset via email
8. **Sessões Ativas**: Mostrar dispositivos conectados

## 🐛 Troubleshooting

### Erro: "Senha atual incorreta"
- Verifique se está digitando a senha corretamente
- Certifique-se de que não há espaços extras

### Erro: "Nome de usuário já está em uso"
- Escolha um username diferente
- Verifique se há caracteres especiais

### Estatísticas não aparecem
- Verifique se há operações criadas pelo usuário
- Confira a conexão com o banco de dados

## 📄 Arquivos Modificados/Criados

### Novos Arquivos
- `/src/Controllers/ProfileController.php`
- `/src/Views/profile.php`

### Arquivos Modificados
- `/src/Models/User.php` - Adicionados métodos de perfil
- `/src/public/index.php` - Adicionadas rotas de perfil
- `/src/Views/layout/header.php` - Link para perfil atualizado

## ✅ Checklist de Implementação

- [x] Criar ProfileController
- [x] Criar View de perfil
- [x] Adicionar métodos no User Model
- [x] Configurar rotas (web + API)
- [x] Atualizar link no header
- [x] Validações de segurança
- [x] Interface responsiva
- [x] Feedback visual ao usuário
- [x] Toggle de senha
- [x] Estatísticas do usuário

## 🎯 Conclusão

A funcionalidade de perfil está completamente implementada e pronta para uso. Os usuários podem agora:
- Visualizar suas informações
- Atualizar seu nome de usuário
- Alterar sua senha de forma segura
- Ver estatísticas de suas operações

Todas as funcionalidades incluem validações adequadas e feedback visual para melhor experiência do usuário.

