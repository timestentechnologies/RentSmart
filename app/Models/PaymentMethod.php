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

    // Return active methods owned by any of the given user IDs (owner/manager/agent)
    public function getActiveForUsers(array $userIds)
    {
        try {
            $userIds = array_values(array_filter(array_map('intval', $userIds)));
            if (empty($userIds)) {
                return [];
            }
            // Build placeholders e.g. IN (?, ?, ?)
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 AND owner_user_id IN ($placeholders) ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($userIds);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $rows ?: [];
        } catch (\Exception $e) {
            // Fallback if owner_user_id column doesn't exist yet
            return [];
        }
    }

    // Return all methods for a single user (owner panel)
    public function getByUser($userId)
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE owner_user_id = ? ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([intval($userId)]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }
}
