<?php

namespace Core;

use PDO;
use Core\Database;

class Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $pdo;
    protected $query;
    protected $bindings = [];
    protected $totalRecords;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function when($condition, callable $callback)
    {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    // Fetch the first result
    public function first()
    {
        if ($this->query) {
            $this->query .= " LIMIT 1";
        } else {
            $this->query = "SELECT * FROM $this->table LIMIT 1";
        }
        $stmt = $this->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // Fetch all results
    public function get()
    {
        if (!$this->query) {
            $this->query = "SELECT * FROM $this->table";
        }
        $stmt = $this->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Fetch all records from the table
    public function all()
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Find a record by ID
    public function find($id)
    {
        $this->query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $this->bindings = ['id' => $id];
        return $this->first();
    }

    // Insert a new record
    public function create(array $data)
    {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(',:', array_keys($data));
        $this->query = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        $this->bindings = $data;
        $this->execute();

        $id = $this->pdo->lastInsertId();
        return $this->find($id);
    }

    // Update a record by ID
    public function update($id, array $data)
    {
        $columns = implode(', ', array_map(fn($col) => "{$col} = :{$col}", array_keys($data)));
        $this->query = "UPDATE {$this->table} SET $columns WHERE {$this->primaryKey} = :id";

        $this->bindings = array_merge($data, ['id' => $id]);
        return $this->execute();
    }

    // Delete a record by ID
    public function delete($id)
    {
        $this->query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $this->bindings = ['id' => $id];
        return $this->execute();
    }

    // WHERE clause
    public function where($column, $operator = "=", $value)
    {
        $this->addCondition("{$column} {$operator} :{$column}");
        $this->bindings[$column] = $value;
        return $this;
    }

    // WHERE IN clause
    public function whereIn($column, array $values)
    {
        $placeholders = implode(',', array_map(fn($key) => ":{$column}_{$key}", array_keys($values)));
        $this->addCondition("{$column} IN ({$placeholders})");

        foreach ($values as $key => $value) {
            $this->bindings["{$column}_{$key}"] = $value;
        }

        return $this;
    }

    // WHERE BETWEEN clause
    public function whereBetween($column, array $values)
    {
        if (count($values) !== 2) {
            throw new \InvalidArgumentException("The whereBetween method requires exactly two values.");
        }

        $this->addCondition("{$column} BETWEEN :{$column}_start AND :{$column}_end");
        $this->bindings["{$column}_start"] = $values[0];
        $this->bindings["{$column}_end"] = $values[1];

        return $this;
    }

    // WHERE LIKE clause
    public function whereLike($column, $value)
    {
        $this->addCondition("{$column} LIKE :{$column}");
        $this->bindings[$column] = "%{$value}%";
        return $this;
    }

    // Dynamic pagination
    public function paginate($limit = 10)
    {
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $offset = ($page - 1) * $limit;

        $countQuery = "SELECT COUNT(*) AS total FROM {$this->table}";
        $baseQuery = $this->query;

        if (strpos($baseQuery, 'WHERE') !== false) {
            $countQuery .= ' ' . strstr($baseQuery, 'WHERE');
        }

        $stmt = $this->pdo->prepare($countQuery);
        foreach ($this->bindings as $key => $value) {
            $stmt->bindValue(is_int($key) ? $key + 1 : ":$key", $value);
        }
        $stmt->execute();
        $totalRecords = $stmt->fetchColumn();

        if ($this->query) {
            $this->query .= " LIMIT :limit OFFSET :offset";
        } else {
            $this->query = "SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset";
        }

        $this->bindings['limit'] = (int)$limit;
        $this->bindings['offset'] = (int)$offset;

        $stmt = $this->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalRecords / $limit),
                'total_records' => $totalRecords,
            ],
        ];
    }

    // Add raw conditions to the query
    protected function addCondition($condition)
    {
        if (strpos($this->query, 'WHERE') === false) {
            $this->query = "SELECT * FROM {$this->table} WHERE {$condition}";
        } else {
            $this->query .= " AND {$condition}";
        }
    }

    // Execute the query with bindings
    protected function execute()
    {
        // dd($this->query);
        $stmt = $this->pdo->prepare($this->query);

        foreach ($this->bindings as $key => $value) {
            $stmt->bindValue(
                is_int($key) ? $key + 1 : ":$key",
                $value,
                is_bool($value)
                    ? PDO::PARAM_BOOL
                    : (is_numeric($value)
                        ? PDO::PARAM_INT
                        : PDO::PARAM_STR)
            );
        }

        $stmt->execute();
        $this->totalRecords = $stmt->rowCount();
        return $stmt;
    }

    protected function toSQL()
    {
        return $this->query;
    }
}
