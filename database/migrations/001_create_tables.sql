-- Tabela para armazenar resultados de operações
CREATE TABLE operations (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            symbol VARCHAR(10) NOT NULL,
                            current_price DECIMAL(10,2) NOT NULL,
                            strike_price DECIMAL(10,2) NOT NULL,
                            call_symbol VARCHAR(20),
                            call_premium DECIMAL(10,2),
                            put_symbol VARCHAR(20),
                            put_premium DECIMAL(10,2),
                            expiration_date DATE NOT NULL,
                            days_to_maturity INT,
                            initial_investment DECIMAL(12,2),
                            max_profit DECIMAL(12,2),
                            max_loss DECIMAL(12,2),
                            profit_percent DECIMAL(8,2),
                            monthly_profit_percent DECIMAL(8,2),
                            selic_annual DECIMAL(8,4),
                            status ENUM('active', 'closed', 'expired') DEFAULT 'active',
                            notes TEXT,
                            strategy_type VARCHAR(50),
                            risk_level VARCHAR(20),
                            entry_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            exit_date TIMESTAMP NULL,
                            exit_price DECIMAL(10,2),
                            exit_reason VARCHAR(100),
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            INDEX idx_symbol (symbol),
                            INDEX idx_expiration (expiration_date),
                            INDEX idx_status (status),
                            INDEX idx_entry_date (entry_date)
);

-- Tabela para configurações de usuário
CREATE TABLE user_settings (
                               id INT AUTO_INCREMENT PRIMARY KEY,
                               user_id INT DEFAULT 1,
                               access_token VARCHAR(255),
                               total_capital DECIMAL(12,2) DEFAULT 50000.00,
                               default_tickers TEXT,
                               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                               updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela para histórico de análises
CREATE TABLE analysis_history (
                                  id INT AUTO_INCREMENT PRIMARY KEY,
                                  operation_id INT,
                                  metric_type VARCHAR(50),
                                  metric_value DECIMAL(12,2),
                                  analysis_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                  FOREIGN KEY (operation_id) REFERENCES operations(id) ON DELETE CASCADE,
                                  INDEX idx_analysis_date (analysis_date)
);