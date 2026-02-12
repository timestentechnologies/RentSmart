<?php

namespace App\Models;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';

    private const SUBSCRIPTION_OWNER_USER_ID = 1;

    private function ensureScopeColumn()
    {
        try {
            $col = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'scope'")->fetch(\PDO::FETCH_ASSOC);
            if (!$col) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN scope VARCHAR(32) NULL DEFAULT 'tenant'");
            }
        } catch (\Exception $e) {
            // Swallow errors to avoid breaking existing installs
        }
    }

    public function create($data)
    {
        $this->ensureScopeColumn();
        return $this->insert($data);
    }

    public function update($id, $data)
    {
        $this->ensureScopeColumn();
        return $this->updateById($id, $data);
    }

    public function delete($id)
    {
        return $this->deleteById($id);
    }

    public function getById($id)
    {
        $this->ensureScopeColumn();
        return $this->find($id);
    }

    public function getAll()
    {
        $this->ensureScopeColumn();
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllForScope($scope)
    {
        $this->ensureScopeColumn();
        $scope = strtolower(trim((string)$scope));
        if ($scope === 'subscription') {
            $sql = "SELECT * FROM {$this->table} WHERE (COALESCE(scope, 'tenant') = 'subscription' OR owner_user_id = ?) ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([self::SUBSCRIPTION_OWNER_USER_ID]);
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE COALESCE(scope, 'tenant') = 'tenant' AND (owner_user_id IS NULL OR owner_user_id <> ?) ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([self::SUBSCRIPTION_OWNER_USER_ID]);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function getActive()
    {
        $this->ensureScopeColumn();
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActiveForScope($scope)
    {
        $this->ensureScopeColumn();
        $scope = strtolower(trim((string)$scope));
        if ($scope === 'subscription') {
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 AND (COALESCE(scope, 'tenant') = 'subscription' OR owner_user_id = ?) ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([self::SUBSCRIPTION_OWNER_USER_ID]);
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 AND COALESCE(scope, 'tenant') = 'tenant' AND (owner_user_id IS NULL OR owner_user_id <> ?) ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([self::SUBSCRIPTION_OWNER_USER_ID]);
        }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function getByType($type)
    {
        $this->ensureScopeColumn();
        $sql = "SELECT * FROM {$this->table} WHERE type = ? AND is_active = 1 ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$type]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Return active methods owned by any of the given user IDs (owner/manager/agent)
    public function getActiveForUsers(array $userIds)
    {
        $this->ensureScopeColumn();
        try {
            $userIds = array_values(array_filter(array_map('intval', $userIds)));
            if (empty($userIds)) {
                return [];
            }
            // Build placeholders e.g. IN (?, ?, ?)
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 AND owner_user_id IN ($placeholders) AND COALESCE(scope, 'tenant') = 'tenant' AND owner_user_id <> " . (int)self::SUBSCRIPTION_OWNER_USER_ID . " ORDER BY name ASC";
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
        $this->ensureScopeColumn();
        try {
            $sql = "SELECT * FROM {$this->table} WHERE owner_user_id = ? AND COALESCE(scope, 'tenant') = 'tenant' AND owner_user_id <> ? ORDER BY name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([intval($userId), (int)self::SUBSCRIPTION_OWNER_USER_ID]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    // Ensure the linking table exists
    private function ensureLinkTable()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS payment_method_properties (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        payment_method_id INT NOT NULL,
                        property_id INT NOT NULL,
                        UNIQUE KEY uniq_method_property (payment_method_id, property_id),
                        KEY idx_property (property_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->exec($sql);
        } catch (\Exception $e) {
            // Swallow errors to avoid breaking existing installs
        }
    }

    // Assign a payment method to multiple properties (replace existing links)
    public function assignProperties($paymentMethodId, array $propertyIds)
    {
        $this->ensureLinkTable();
        try {
            $paymentMethodId = (int)$paymentMethodId;
            $propertyIds = array_values(array_unique(array_map('intval', $propertyIds)));
            $this->db->beginTransaction();
            $del = $this->db->prepare("DELETE FROM payment_method_properties WHERE payment_method_id = ?");
            $del->execute([$paymentMethodId]);
            if (!empty($propertyIds)) {
                $ins = $this->db->prepare("INSERT INTO payment_method_properties (payment_method_id, property_id) VALUES (?, ?)");
                foreach ($propertyIds as $pid) {
                    if ($pid > 0) {
                        $ins->execute([$paymentMethodId, $pid]);
                    }
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) { $this->db->rollBack(); }
            return false;
        }
    }

    // Get property IDs linked to a payment method
    public function getPropertyIdsForMethod($paymentMethodId)
    {
        $this->ensureLinkTable();
        try {
            $stmt = $this->db->prepare("SELECT property_id FROM payment_method_properties WHERE payment_method_id = ?");
            $stmt->execute([(int)$paymentMethodId]);
            return array_map('intval', array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'property_id'));
        } catch (\Exception $e) {
            return [];
        }
    }

    // Return active methods linked to a property
    public function getActiveForProperty($propertyId)
    {
        $this->ensureLinkTable();
        $this->ensureScopeColumn();
        try {
            $sql = "SELECT pm.*
                    FROM {$this->table} pm
                    INNER JOIN payment_method_properties pmp ON pmp.payment_method_id = pm.id
                    WHERE pm.is_active = 1 AND pmp.property_id = ? AND COALESCE(pm.scope, 'tenant') = 'tenant' AND (pm.owner_user_id IS NULL OR pm.owner_user_id <> " . (int)self::SUBSCRIPTION_OWNER_USER_ID . ")
                    ORDER BY pm.name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$propertyId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function isLinkedToProperty($paymentMethodId, $propertyId)
    {
        $this->ensureLinkTable();
        try {
            $stmt = $this->db->prepare("SELECT 1 FROM payment_method_properties WHERE payment_method_id = ? AND property_id = ? LIMIT 1");
            $stmt->execute([(int)$paymentMethodId, (int)$propertyId]);
            return (bool)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return false;
        }
    }
}
