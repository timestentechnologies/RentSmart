<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;

class DemoController
{
    public function start()
    {
        $role = strtolower(trim((string)($_GET['role'] ?? '')));
        if (!in_array($role, ['landlord', 'manager', 'agent', 'realtor'], true)) {
            $_SESSION['flash_message'] = 'Invalid demo role selected';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        try {
            $db = Connection::getInstance()->getConnection();
            $settings = new Setting();
            $userModel = new User();

            $demoEnabled = (string)$settings->get('demo_enabled');
            if ($demoEnabled !== '1') {
                $_SESSION['flash_message'] = 'Demo is currently disabled';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            $user = $this->getOrCreateDemoUser($db, $userModel, $role);
            try {
                $this->protectId($settings, 'user', (int)($user['id'] ?? 0));
            } catch (\Throwable $e) {
            }
            $this->ensureDemoSubscription($user['id']);
            $this->ensureDemoData($db, $settings, (int)$user['id'], $role);

            unset($_SESSION['tenant_id'], $_SESSION['impersonating'], $_SESSION['original_user']);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = (string)$user['name'];
            $_SESSION['user_email'] = (string)$user['email'];
            $_SESSION['user_role'] = (string)$role;
            $_SESSION['is_admin'] = false;
            $_SESSION['demo_mode'] = true;

            $redirectPath = ($role === 'realtor') ? '/realtor/dashboard' : '/dashboard';
            header('Location: ' . BASE_URL . $redirectPath);
            exit;
        } catch (\Throwable $e) {
            error_log('Demo start failed: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Failed to start demo: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }

    private function getOrCreateDemoUser($db, User $userModel, string $role): array
    {
        $email = 'demo+' . $role . '@rentsmart.local';
        $existing = $userModel->findByEmail($email);
        if ($existing) {
            return $existing;
        }

        $nameMap = [
            'landlord' => 'Demo Landlord',
            'manager' => 'Demo Manager',
            'agent' => 'Demo Agent',
            'realtor' => 'Demo Realtor',
        ];

        $password = bin2hex(random_bytes(16));

        $newId = $userModel->createUser([
            'name' => $nameMap[$role] ?? ('Demo ' . ucfirst($role)),
            'email' => $email,
            'phone' => '0700000000',
            'address' => 'Nairobi, Kenya',
            'password' => $password,
            'role' => $role,
            'is_subscribed' => 1,
            'trial_ends_at' => date('Y-m-d H:i:s', strtotime('+10 years')),
            'manager_id' => null,
        ]);

        $user = $userModel->find((int)$newId);

        // Agent self-manages if needed
        if ($role === 'agent' && $user) {
            try {
                $userModel->update((int)$newId, ['manager_id' => (int)$newId]);
                $user = $userModel->find((int)$newId);
            } catch (\Throwable $e) {
            }
        }

        return $user ?: ['id' => (int)$newId, 'name' => $nameMap[$role] ?? ('Demo ' . ucfirst($role)), 'email' => $email, 'role' => $role];
    }

    private function ensureDemoSubscription(int $userId): void
    {
        try {
            $sub = new Subscription();
            $existing = $sub->findByUserId($userId);
            if ($existing) {
                try {
                    $db = $sub->getDb();
                    $stmt = $db->prepare('UPDATE subscriptions SET status = \'active\', current_period_ends_at = ? WHERE user_id = ? ORDER BY id DESC LIMIT 1');
                    $stmt->execute([date('Y-m-d H:i:s', strtotime('+10 years')), (int)$userId]);
                } catch (\Throwable $e) {
                }
                return;
            }

            $planId = 1;
            try {
                $plan = $sub->getPlan($planId);
                if (!$plan) {
                    return;
                }
            } catch (\Throwable $e) {
                return;
            }

            $now = date('Y-m-d H:i:s');
            $ends = date('Y-m-d H:i:s', strtotime('+10 years'));
            $sub->create([
                'user_id' => (int)$userId,
                'plan_id' => (int)$planId,
                'plan_type' => 'Basic',
                'status' => 'active',
                'trial_ends_at' => $now,
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $ends,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
        }
    }

    private function ensureDemoData($db, Setting $settings, int $userId, string $role): void
    {
        $db->beginTransaction();
        try {
            $propId = $this->ensureDemoProperty($db, $settings, $userId, $role);
            $unitIds = $this->ensureDemoUnits($db, $settings, $userId, $propId);
            $tenantId = $this->ensureDemoTenant($db, $settings, $userId, $propId, $unitIds[0] ?? null);
            if (!empty($unitIds[0]) && !empty($tenantId)) {
                $this->ensureDemoLeaseAndPayment($db, $settings, $userId, (int)$unitIds[0], (int)$tenantId, (int)$propId);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function ensureDemoProperty($db, Setting $settings, int $userId, string $role): int
    {
        $key = 'demo_property_id_' . $role;
        $existingId = (int)($settings->get($key) ?? 0);
        if ($existingId > 0) {
            try {
                $stmt = $db->prepare('SELECT id FROM properties WHERE id = ? LIMIT 1');
                $stmt->execute([(int)$existingId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row && !empty($row['id'])) {
                    $this->protectId($settings, 'property', (int)$existingId);
                    return (int)$existingId;
                }
            } catch (\Throwable $e) {
            }
        }

        $name = 'Demo Property (' . ucfirst($role) . ')';
        $stmt = $db->prepare('INSERT INTO properties (name, address, city, state, zip_code, property_type, description, owner_id, manager_id, agent_id, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');

        $ownerId = null;
        $managerId = null;
        $agentId = null;
        if ($role === 'landlord') {
            $ownerId = $userId;
        } elseif ($role === 'manager') {
            $managerId = $userId;
        } elseif ($role === 'agent') {
            $agentId = $userId;
        }

        $stmt->execute([
            $name,
            'Demo Avenue 1',
            'Nairobi',
            'Nairobi',
            '00100',
            'apartment',
            'Sample demo property data',
            $ownerId,
            $managerId,
            $agentId,
        ]);

        $propId = (int)$db->lastInsertId();
        $settings->updateByKey($key, (string)$propId);
        $this->protectId($settings, 'property', $propId);
        return $propId;
    }

    private function ensureDemoUnits($db, Setting $settings, int $userId, int $propertyId): array
    {
        $stmt = $db->prepare('SELECT id FROM units WHERE property_id = ? ORDER BY id ASC');
        $stmt->execute([(int)$propertyId]);
        $existing = array_values(array_filter(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [])));

        if (count($existing) >= 2) {
            foreach ($existing as $uId) {
                $this->protectId($settings, 'unit', (int)$uId);
            }
            return $existing;
        }

        $toCreate = 2 - count($existing);
        for ($i = 0; $i < $toCreate; $i++) {
            $unitNumber = 'D-' . str_pad((string)(count($existing) + $i + 1), 2, '0', STR_PAD_LEFT);
            $stmtIns = $db->prepare('INSERT INTO units (property_id, unit_number, type, size, rent_amount, status, created_at, updated_at) VALUES (?,?,?,?,?,\'vacant\',NOW(),NOW())');
            $stmtIns->execute([(int)$propertyId, $unitNumber, '2bhk', 80.00, 45000.00]);
            $newUnitId = (int)$db->lastInsertId();
            $existing[] = $newUnitId;
            $this->protectId($settings, 'unit', $newUnitId);
        }

        return $existing;
    }

    private function ensureDemoTenant($db, Setting $settings, int $userId, int $propertyId, ?int $unitId): int
    {
        $key = 'demo_tenant_id_' . $propertyId;
        $existingId = (int)($settings->get($key) ?? 0);
        if ($existingId > 0) {
            try {
                $stmt = $db->prepare('SELECT id FROM tenants WHERE id = ? LIMIT 1');
                $stmt->execute([(int)$existingId]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row && !empty($row['id'])) {
                    $this->protectId($settings, 'tenant', (int)$existingId);
                    return (int)$existingId;
                }
            } catch (\Throwable $e) {
            }
        }

        $stmt = $db->prepare('INSERT INTO tenants (name, first_name, last_name, email, phone, unit_id, property_id, notes, registered_on, created_at, updated_at, rent_amount) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW(),?)');
        $stmt->execute([
            'Demo Tenant',
            'Demo',
            'Tenant',
            'demo.tenant@rentsmart.local',
            '0711111111',
            $unitId ? (int)$unitId : null,
            (int)$propertyId,
            'Sample demo tenant',
            date('Y-m-d'),
            45000.00,
        ]);
        $tenantId = (int)$db->lastInsertId();
        $settings->updateByKey($key, (string)$tenantId);
        $this->protectId($settings, 'tenant', $tenantId);
        return $tenantId;
    }

    private function ensureDemoLeaseAndPayment($db, Setting $settings, int $userId, int $unitId, int $tenantId, int $propertyId): void
    {
        // Ensure a lease exists
        $stmt = $db->prepare('SELECT id FROM leases WHERE unit_id = ? AND tenant_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([(int)$unitId, (int)$tenantId]);
        $leaseId = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);

        if ($leaseId <= 0) {
            $start = date('Y-m-d', strtotime('-1 month'));
            $end = date('Y-m-d', strtotime('+11 months'));
            $stmtL = $db->prepare('INSERT INTO leases (unit_id, tenant_id, start_date, end_date, rent_amount, payment_day, status, created_at, updated_at) VALUES (?,?,?,?,?, ?, \'active\', NOW(), NOW())');
            $stmtL->execute([(int)$unitId, (int)$tenantId, $start, $end, 45000.00, 5]);
            $leaseId = (int)$db->lastInsertId();
            $this->protectId($settings, 'lease', $leaseId);
        } else {
            $this->protectId($settings, 'lease', $leaseId);
        }

        // Update unit occupancy
        try {
            $db->prepare('UPDATE units SET status = \'occupied\', tenant_id = ? WHERE id = ?')->execute([(int)$tenantId, (int)$unitId]);
            $this->protectId($settings, 'unit', $unitId);
        } catch (\Throwable $e) {
        }

        // Create a sample payment if none exists
        try {
            $stmtP = $db->prepare("SELECT id FROM payments WHERE lease_id = ? AND payment_type = 'rent' LIMIT 1");
            $stmtP->execute([(int)$leaseId]);
            $paymentId = (int)($stmtP->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
            if ($paymentId <= 0) {
                $stmtIns = $db->prepare("INSERT INTO payments (user_id, tenant_id, lease_id, property_id, unit_id, amount, payment_date, payment_method, payment_type, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?, 'completed', ?, NOW(), NOW())");
                $stmtIns->execute([
                    (int)$userId,
                    (int)$tenantId,
                    (int)$leaseId,
                    (int)$propertyId,
                    (int)$unitId,
                    45000.00,
                    date('Y-m-d'),
                    'mpesa',
                    'rent',
                    'Demo payment',
                ]);
                $newPaymentId = (int)$db->lastInsertId();
                $this->protectId($settings, 'payment', $newPaymentId);
            } else {
                $this->protectId($settings, 'payment', $paymentId);
            }
        } catch (\Throwable $e) {
        }
    }

    private function protectId(Setting $settings, string $type, int $id): void
    {
        if ($id <= 0) {
            return;
        }

        $key = 'demo_protected_' . $type . '_ids_json';
        $raw = (string)($settings->get($key) ?? '[]');
        $ids = json_decode($raw, true);
        if (!is_array($ids)) {
            $ids = [];
        }

        $ids = array_values(array_unique(array_merge(array_map('intval', $ids), [(int)$id])));
        $settings->updateByKey($key, json_encode($ids));
    }
}
