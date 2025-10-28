<?php

namespace App\Models;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    public function create($data)
    {
        return $this->insert($data);
    }

    public function update($id, $data)
    {
        return $this->updateById($id, $data);
    }

    public function delete($id)
    {
        return $this->deleteById($id);
    }

    public function getById($id)
    {
        return $this->find($id);
    }

    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActive()
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByType($type)
    {
        $sql = "SELECT * FROM {$this->table} WHERE type = ? AND is_active = 1 ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
