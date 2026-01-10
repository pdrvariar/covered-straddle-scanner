<?php

namespace App\Models;

use PDO;

class Operation {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM operations 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function save(array $data): int {
        $sql = "
            INSERT INTO operations (
                symbol, current_price, strike_price, call_symbol, call_premium,
                put_symbol, put_premium, expiration_date, days_to_maturity,
                initial_investment, max_profit, max_loss, profit_percent,
                monthly_profit_percent, selic_annual, created_at
            ) VALUES (
                :symbol, :current_price, :strike_price, :call_symbol, :call_premium,
                :put_symbol, :put_premium, :expiration_date, :days_to_maturity,
                :initial_investment, :max_profit, :max_loss, :profit_percent,
                :monthly_profit_percent, :selic_annual, NOW()
            )
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':symbol' => $data['symbol'],
            ':current_price' => $data['current_price'],
            ':strike_price' => $data['strike'],
            ':call_symbol' => $data['call_symbol'],
            ':call_premium' => $data['call_premium'],
            ':put_symbol' => $data['put_symbol'],
            ':put_premium' => $data['put_premium'],
            ':expiration_date' => $data['expiration_date'],
            ':days_to_maturity' => $data['days_to_maturity'],
            ':initial_investment' => $data['initial_investment'],
            ':max_profit' => $data['max_profit'],
            ':max_loss' => $data['max_loss'],
            ':profit_percent' => $data['profit_percent'],
            ':monthly_profit_percent' => $data['monthly_profit_percent'],
            ':selic_annual' => $data['selic_annual']
        ]);

        return $this->db->lastInsertId();
    }

    public function getRecent(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT * FROM operations 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(): array {
        $stats = [];

        // Today's operations count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM operations 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stmt->execute();
        $stats['today_ops'] = $stmt->fetchColumn();

        // Average profit
        $stmt = $this->db->prepare("
            SELECT AVG(profit_percent) as avg_profit 
            FROM operations 
            WHERE profit_percent IS NOT NULL
        ");
        $stmt->execute();
        $stats['avg_profit'] = round($stmt->fetchColumn() ?? 0, 2);

        // Best profit
        $stmt = $this->db->prepare("
            SELECT MAX(profit_percent) as best_profit 
            FROM operations
        ");
        $stmt->execute();
        $stats['best_profit'] = round($stmt->fetchColumn() ?? 0, 2);

        // Average days to maturity
        $stmt = $this->db->prepare("
            SELECT AVG(days_to_maturity) as avg_days 
            FROM operations 
            WHERE days_to_maturity > 0
        ");
        $stmt->execute();
        $stats['avg_days'] = round($stmt->fetchColumn() ?? 0, 0);

        return $stats;
    }

    public function search(array $filters): array {
        $where = [];
        $params = [];

        if (!empty($filters['symbol'])) {
            $where[] = "symbol LIKE :symbol";
            $params[':symbol'] = '%' . $filters['symbol'] . '%';
        }

        if (!empty($filters['min_profit'])) {
            $where[] = "profit_percent >= :min_profit";
            $params[':min_profit'] = $filters['min_profit'];
        }

        if (!empty($filters['max_days'])) {
            $where[] = "days_to_maturity <= :max_days";
            $params[':max_days'] = $filters['max_days'];
        }

        $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $this->db->prepare("
            SELECT * FROM operations 
            {$whereClause}
            ORDER BY profit_percent DESC
            LIMIT 50
        ");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>