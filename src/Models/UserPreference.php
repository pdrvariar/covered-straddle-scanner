<?php

namespace App\Models;

use PDO;

class UserPreference {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getSettings(int $userId = 1): array {
        $stmt = $this->db->prepare("
            SELECT * FROM user_settings 
            WHERE user_id = :user_id 
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            // Create default settings
            return $this->createDefaultSettings($userId);
        }

        return $result;
    }

    public function updateSettings(int $userId, array $data): bool {
        $sql = "
            INSERT INTO user_settings 
            (user_id, access_token, total_capital, default_tickers, updated_at) 
            VALUES (:user_id, :access_token, :total_capital, :default_tickers, NOW())
            ON DUPLICATE KEY UPDATE
            access_token = VALUES(access_token),
            total_capital = VALUES(total_capital),
            default_tickers = VALUES(default_tickers),
            updated_at = NOW()
        ";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':access_token' => $data['access_token'] ?? '',
            ':total_capital' => $data['total_capital'] ?? 50000.00,
            ':default_tickers' => $data['default_tickers'] ?? ''
        ]);
    }

    private function createDefaultSettings(int $userId): array {
        $defaultTickers = "BBAS3,PETR4,BBSE3,VALE3,ITSA4,CMIG4,TAEE11,CXSE3,ISAE4,WEGE3,CSMG3,EGIE3,ITUB4,GOAU4,BBDC3,KLBN11,AURE3,CMIN3,RANI3,LEVE3,SAPR11,B3SA3,ABEV3,KEPL3,BMGB4,JHSF3,AGRO3,ABCB4,CSAN3,BRAP4,CPFE3,PRIO3,PSSA3,FIQE3";

        $settings = [
            'user_id' => $userId,
            'access_token' => '',
            'total_capital' => 50000.00,
            'default_tickers' => $defaultTickers,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Insert default settings
        $sql = "
            INSERT INTO user_settings 
            (user_id, access_token, total_capital, default_tickers, created_at, updated_at)
            VALUES (:user_id, :access_token, :total_capital, :default_tickers, :created_at, :updated_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $settings['user_id'],
            ':access_token' => $settings['access_token'],
            ':total_capital' => $settings['total_capital'],
            ':default_tickers' => $settings['default_tickers'],
            ':created_at' => $settings['created_at'],
            ':updated_at' => $settings['updated_at']
        ]);

        return $settings;
    }
}