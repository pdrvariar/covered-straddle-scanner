<?php
namespace App\Models;

use PDO;
use Exception;
use App\Config\Database;

class Operation
{
    private static $connection = null;

    private static function getConnection()
    {
        if (self::$connection === null) {
            $database = new Database();
            self::$connection = $database->connect();
        }
        return self::$connection;
    }

    // Método para uso do DashboardController (não estático)
    public function getStats()
    {
        $db = self::getConnection();

        $stats = [
            'total' => 0,
            'active' => 0,
            'closed' => 0,
            'expired' => 0,
            'today_ops' => 0,
            'best_profit' => 0,
            'avg_profit' => 0,
            'protection' => 0,
            'avg_volume' => 0,
            'avg_days' => 0,
            'selic' => 13.75,
            'success_rate' => 0
        ];

        try {
            // Total de operações
            $sql = "SELECT COUNT(*) as total, 
                           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                           SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                           SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired
                    FROM operations";

            $stmt = $db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $stats = array_merge($stats, $result);
            }

            // Operações de hoje
            $sql = "SELECT COUNT(*) as today_ops FROM operations 
                    WHERE DATE(created_at) = CURDATE()";
            $stmt = $db->query($sql);
            $todayResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['today_ops'] = $todayResult['today_ops'] ?? 0;

            // Melhor lucro
            $sql = "SELECT MAX(profit_percent) as best_profit FROM operations 
                    WHERE profit_percent IS NOT NULL";
            $stmt = $db->query($sql);
            $bestResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['best_profit'] = $bestResult['best_profit'] ?? 0;

            // Lucro médio
            $sql = "SELECT AVG(profit_percent) as avg_profit FROM operations 
                    WHERE profit_percent IS NOT NULL";
            $stmt = $db->query($sql);
            $avgResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_profit'] = $avgResult['avg_profit'] ?? 0;

            // Dias médios até vencimento
            $sql = "SELECT AVG(days_to_maturity) as avg_days FROM operations 
                    WHERE days_to_maturity > 0";
            $stmt = $db->query($sql);
            $daysResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_days'] = $daysResult['avg_days'] ?? 0;

        } catch (Exception $e) {
            // Log error but don't crash
            error_log("Error getting stats: " . $e->getMessage());
        }

        return $stats;
    }

    public function getRecent($limit = 10)
    {
        try {
            $db = self::getConnection();
            $sql = "SELECT * FROM operations 
                    ORDER BY created_at DESC 
                    LIMIT :limit";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting recent operations: " . $e->getMessage());
            return [];
        }
    }

    // Método estático para salvar operação
    public static function save($data)
    {
        try {
            $db = self::getConnection();

            $sql = "INSERT INTO operations (
                symbol, current_price, strike_price, call_symbol, call_premium,
                put_symbol, put_premium, expiration_date, days_to_maturity,
                initial_investment, max_profit, max_loss, profit_percent,
                monthly_profit_percent, selic_annual, status, strategy_type,
                risk_level, notes, quantity, lfts11_price, lfts11_quantity,
                lfts11_investment, lfts11_return
            ) VALUES (
                :symbol, :current_price, :strike_price, :call_symbol, :call_premium,
                :put_symbol, :put_premium, :expiration_date, :days_to_maturity,
                :initial_investment, :max_profit, :max_loss, :profit_percent,
                :monthly_profit_percent, :selic_annual, :status, :strategy_type,
                :risk_level, :notes, :quantity, :lfts11_price, :lfts11_quantity,
                :lfts11_investment, :lfts11_return
            )";

            $stmt = $db->prepare($sql);

            $params = [
                ':symbol' => $data['symbol'] ?? null,
                ':current_price' => $data['current_price'] ?? null,
                ':strike_price' => $data['strike_price'] ?? null,
                ':call_symbol' => $data['call_symbol'] ?? null,
                ':call_premium' => $data['call_premium'] ?? null,
                ':put_symbol' => $data['put_symbol'] ?? null,
                ':put_premium' => $data['put_premium'] ?? null,
                ':expiration_date' => $data['expiration_date'] ?? null,
                ':days_to_maturity' => $data['days_to_maturity'] ?? null,
                ':initial_investment' => $data['initial_investment'] ?? null,
                ':max_profit' => $data['max_profit'] ?? null,
                ':max_loss' => $data['max_loss'] ?? null,
                ':profit_percent' => $data['profit_percent'] ?? null,
                ':monthly_profit_percent' => $data['monthly_profit_percent'] ?? null,
                ':selic_annual' => $data['selic_annual'] ?? null,
                ':status' => $data['status'] ?? 'active',
                ':strategy_type' => $data['strategy_type'] ?? 'covered_straddle',
                ':risk_level' => $data['risk_level'] ?? 'medium',
                ':notes' => $data['notes'] ?? null,
                ':quantity' => $data['quantity'] ?? 1000,
                ':lfts11_price' => $data['lfts11_price'] ?? ($data['lfts11_data']['price'] ?? null),
                ':lfts11_quantity' => $data['lfts11_quantity'] ?? ($data['lfts11_data']['quantity'] ?? null),
                ':lfts11_investment' => $data['lfts11_investment'] ?? null,
                ':lfts11_return' => $data['lfts11_return'] ?? null
            ];

            if ($stmt->execute($params)) {
                return $db->lastInsertId();
            }

            throw new Exception('Erro ao salvar operação');
        } catch (Exception $e) {
            error_log("Error saving operation: " . $e->getMessage());
            throw $e;
        }
    }

    // Método estático para listar todas as operações
    public static function getAll($filters = [])
    {
        try {
            $db = self::getConnection();

            $sql = "SELECT * FROM operations WHERE 1=1";
            $params = [];

            if (!empty($filters['status'])) {
                $sql .= " AND status = :status";
                $params[':status'] = $filters['status'];
            }

            if (!empty($filters['symbol'])) {
                $sql .= " AND symbol LIKE :symbol";
                $params[':symbol'] = '%' . $filters['symbol'] . '%';
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting all operations: " . $e->getMessage());
            return [];
        }
    }

    // Método estático para obter operação por ID
    public static function getById($id)
    {
        try {
            $db = self::getConnection();
            $sql = "SELECT * FROM operations WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting operation by ID: " . $e->getMessage());
            return null;
        }
    }

    // Método estático para excluir operação
    public static function delete($id)
    {
        try {
            $db = self::getConnection();
            $sql = "DELETE FROM operations WHERE id = :id";
            $stmt = $db->prepare($sql);

            return $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            error_log("Error deleting operation: " . $e->getMessage());
            return false;
        }
    }
}