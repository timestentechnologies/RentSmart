<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

abstract class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function all()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$values})");
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }

    public function update($id, array $data)
    {
        $sets = implode(' = ?, ', array_keys($data)) . ' = ?';
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$sets} WHERE {$this->primaryKey} = ?");
        $values = array_merge(array_values($data), [$id]);
        
        return $stmt->execute($values);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    public function where($column, $value)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Allow custom queries with parameters
    public function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insert a new record
    public function insert(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        return $this->db->lastInsertId();
    }

    // Update a record by primary key
    public function updateById($id, array $data)
    {
        $sets = implode(' = ?, ', array_keys($data)) . ' = ?';
        $stmt = $this->db->prepare("UPDATE {$this->table} SET $sets WHERE {$this->primaryKey} = ?");
        $values = array_merge(array_values($data), [$id]);
        return $stmt->execute($values);
    }

    // Delete a record by primary key
    public function deleteById($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get the database connection instance
     */
    public function getDb()
    {
        return $this->db;
    }

    // Add transaction methods
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    public function commit()
    {
        return $this->db->commit();
    }

    public function rollback()
    {
        return $this->db->rollBack();
    }
} 