<?php

namespace App\Services;

use PDO;
use Exception;

class QueryBuilderService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public function buildDynamicQuery(string $table, array $columns, array $getParams, string $orderBy = 'id DESC'): array
    {
        $filters = [];
        $params = [];
        
        foreach ($columns as $column) {
            if (!empty($getParams[$column])) {
                $filters[] = "$column LIKE :$column";
                $params[$column] = '%' . $getParams[$column] . '%';
            }
        }
        
        $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';
        $query = "SELECT * FROM $table $whereClause ORDER BY $orderBy";
        $countQuery = "SELECT COUNT(*) FROM $table $whereClause";
        
        return [
            'query' => $query,
            'countQuery' => $countQuery,
            'params' => $params
        ];
    }

    public function executeCountQuery(string $countQuery, array $params): int
    {
        try {
            $stmt = $this->db->prepare($countQuery);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw new Exception("Chyba při počítání záznamů: " . $e->getMessage());
        }
    }

    public function executePaginatedQuery(string $query, array $params, int $offset, int $limit): array
    {
        try {
            $stmt = $this->db->prepare($query . ' LIMIT ? OFFSET ?');
            
            $allParams = array_merge($params, [$limit, $offset]);
            $stmt->execute($allParams);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new Exception("Chyba při načítání dat: " . $e->getMessage());
        }
    }
}