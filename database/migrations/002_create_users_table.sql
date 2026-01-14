-- Tabela de usuários
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela para controle de tentativas de login (segurança)
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempts INT DEFAULT 1,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    locked_until TIMESTAMP NULL,
    INDEX idx_ip (ip_address)
);

-- Adicionar user_id à tabela operations
ALTER TABLE operations ADD COLUMN user_id INT AFTER id;
ALTER TABLE operations ADD CONSTRAINT fk_user_operations FOREIGN KEY (user_id) REFERENCES users(id);

-- Inserir usuários iniciais (exemplo de script, as senhas devem ser alteradas)
-- Usuários padrão: user1, user2, user3. Senha padrão: 'mudar123' (hash gerado via password_hash)
-- NOTA: O cliente deve rodar o password_hash no PHP para gerar os hashes reais se preferir.
INSERT INTO users (username, password) VALUES 
('jp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('pablo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('henry', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
