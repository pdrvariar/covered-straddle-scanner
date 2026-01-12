<?php
namespace App\Models;

use PDO;
use App\Config\Database;

class Ticker
{
    private $db;

    public function __construct(PDO $db = null)
    {
        if ($db === null) {
            $database = new Database();
            $this->db = $database->connect();
        } else {
            $this->db = $db;
        }
    }

    public static function getRecentSearches($limit = 5)
    {
        $instance = new self();
        $db = $instance->db;

        $sql = "SELECT symbol FROM operations 
                WHERE symbol IS NOT NULL AND symbol != ''
                GROUP BY symbol 
                ORDER BY MAX(created_at) DESC 
                LIMIT :limit";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        // Se não houver operações, retorne símbolos padrão
        if (empty($results)) {
            return ['PETR4', 'VALE3', 'ITUB4', 'BBAS3', 'BBDC4'];
        }

        return $results;
    }
}