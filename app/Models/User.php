<?php

namespace App\Models;

class User extends Model
{
    protected $table = 'users';
    private $userData = null;

    public function find($id)
    {
        if ($id === null) {
            return false;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $this->userData = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $this->userData;
    }

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $this->userData = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $this->userData;
    }

    public function createUser($data)
    {
        // Hash password before saving
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        return $this->create($data);
    }

    public function updatePassword($id, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $id]);
    }

    // Role-based helper methods
    public function isAdmin()
    {
        return isset($this->userData['role']) && ($this->userData['role'] === 'admin' || $this->userData['role'] === 'administrator');
    }

    public function isLandlord()
    {
        return isset($this->userData['role']) && $this->userData['role'] === 'landlord';
    }

    public function isManager()
    {
        return isset($this->userData['role']) && $this->userData['role'] === 'manager';
    }

    public function isAgent()
    {
        return isset($this->userData['role']) && $this->userData['role'] === 'agent';
    }

    // Get properties based on user role
    public function getAccessibleProperties()
    {
        if ($this->isAdmin()) {
            // Admin can see all properties
            return $this->db->query("SELECT * FROM properties")->fetchAll(\PDO::FETCH_ASSOC);
        } elseif ($this->isLandlord()) {
            // Landlord can see properties they own
            $stmt = $this->db->prepare("SELECT * FROM properties WHERE owner_id = ?");
            $stmt->execute([$this->userData['id']]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } elseif ($this->isManager()) {
            // Manager can see properties they manage
            $stmt = $this->db->prepare("SELECT * FROM properties WHERE manager_id = ?");
            $stmt->execute([$this->userData['id']]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } elseif ($this->isAgent()) {
            // Agent can see properties where their manager is assigned
            $stmt = $this->db->prepare("
                SELECT p.* FROM properties p
                INNER JOIN users u ON p.manager_id = u.id
                WHERE u.id = (SELECT manager_id FROM users WHERE id = ?)
            ");
            $stmt->execute([$this->userData['id']]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get accessible property IDs for the user
    public function getAccessiblePropertyIds()
    {
        $properties = $this->getAccessibleProperties();
        return array_column($properties, 'id');
    }

    public function getAllUsers()
    {
        $sql = "SELECT u.*,
                s.status as subscription_status,
                s.trial_ends_at as subscription_trial_ends_at,
                s.current_period_ends_at as subscription_ends_at,
                s.plan_type as subscription_plan,
                (SELECT COUNT(*) FROM properties WHERE owner_id = u.id) as properties_owned,
                (SELECT COUNT(*) FROM properties WHERE manager_id = u.id) as properties_managed
                FROM users u
                LEFT JOIN subscriptions s ON u.id = s.user_id
                    AND s.id = (
                        SELECT id FROM subscriptions 
                        WHERE user_id = u.id 
                        ORDER BY created_at DESC 
                        LIMIT 1
                    )
                ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
} 