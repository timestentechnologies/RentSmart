<?php

namespace App\Controllers;

use App\Database\Connection;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;

class DemoController
{
    private function demoLog(string $message): void
    {
        try {
            $root = dirname(__DIR__, 2);
            $path = $root . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'demo.log';
            $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
            @file_put_contents($path, $line, FILE_APPEND);
        } catch (\Throwable $e) {
        }
    }

    public function start()
    {
        $role = strtolower(trim((string)($_GET['role'] ?? '')));

        // Backward/typo compatibility
        if ($role === 'realator') {
            $role = 'realtor';
        }
        if (!in_array($role, ['landlord', 'manager', 'agent', 'realtor'], true)) {
            $_SESSION['flash_message'] = 'Invalid demo role selected';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        try {
            $this->demoLog('start requested role=' . $role . ' uri=' . (string)($_SERVER['REQUEST_URI'] ?? ''));
            error_log('Demo start requested. role=' . $role . ' uri=' . (string)($_SERVER['REQUEST_URI'] ?? ''));
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

            unset($_SESSION['flash_message'], $_SESSION['flash_type']);

            $redirectPath = ($role === 'realtor') ? '/realtor/dashboard' : '/dashboard';
            $this->demoLog('start success user_id=' . (int)$user['id'] . ' role=' . $role . ' redirect=' . BASE_URL . $redirectPath);
            error_log('Demo start success. user_id=' . (int)$user['id'] . ' role=' . $role . ' redirect=' . BASE_URL . $redirectPath);
            header('Location: ' . BASE_URL . $redirectPath);
            exit;
        } catch (\Throwable $e) {
            error_log('Demo start failed: ' . $e->getMessage());
            error_log('Demo start failed trace: ' . $e->getTraceAsString());
            $this->demoLog('start failed role=' . $role . ' error=' . $e->getMessage());
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

    private function ensureDemoUtilities($db, Setting $settings, int $userId, int $propertyId, int $unitId, int $tenantId): void
    {
        try {
            $stmt = $db->prepare("SELECT id FROM leases WHERE unit_id = ? AND tenant_id = ? AND status = 'active' ORDER BY id DESC LIMIT 1");
            $stmt->execute([(int)$unitId, (int)$tenantId]);
            $leaseId = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
            if ($leaseId <= 0) {
                return;
            }

            $types = [
                ['utility_type' => 'water', 'flat_rate' => 1200.00],
                ['utility_type' => 'electricity', 'flat_rate' => 1800.00],
            ];

            foreach ($types as $t) {
                $ut = (string)$t['utility_type'];
                $flat = (float)$t['flat_rate'];

                $stmtU = $db->prepare('SELECT id, flat_rate FROM utilities WHERE unit_id = ? AND utility_type = ? ORDER BY id DESC LIMIT 1');
                $stmtU->execute([(int)$unitId, $ut]);
                $rowU = $stmtU->fetch(\PDO::FETCH_ASSOC);
                $utilityId = (int)($rowU['id'] ?? 0);

                if ($utilityId <= 0) {
                    $ins = $db->prepare('INSERT INTO utilities (unit_id, utility_type, meter_number, is_metered, flat_rate) VALUES (?,?,NULL,0,?)');
                    $ins->execute([(int)$unitId, $ut, $flat]);
                    $utilityId = (int)$db->lastInsertId();
                    $this->protectId($settings, 'utility', $utilityId);
                } else {
                    $this->protectId($settings, 'utility', $utilityId);
                    try {
                        $db->prepare('UPDATE utilities SET flat_rate = ? WHERE id = ?')->execute([$flat, (int)$utilityId]);
                    } catch (\Throwable $e) {
                    }
                }

                // Ensure at least one utility payment exists, but less than cost so balance_due appears
                $stmtP = $db->prepare("SELECT id FROM payments WHERE lease_id = ? AND utility_id = ? AND payment_type = 'utility' LIMIT 1");
                $stmtP->execute([(int)$leaseId, (int)$utilityId]);
                $payId = (int)($stmtP->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
                if ($payId <= 0) {
                    $amount = max(0.0, round($flat * 0.5, 2));
                    $insP = $db->prepare("INSERT INTO payments (user_id, tenant_id, lease_id, property_id, unit_id, utility_id, amount, payment_date, payment_method, payment_type, status, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?, 'utility', 'completed', ?, NOW(), NOW())");
                    $insP->execute([
                        (int)$userId,
                        (int)$tenantId,
                        (int)$leaseId,
                        (int)$propertyId,
                        (int)$unitId,
                        (int)$utilityId,
                        $amount,
                        date('Y-m-d'),
                        'mpesa',
                        'Demo utility payment',
                    ]);
                    $newPayId = (int)$db->lastInsertId();
                    $this->protectId($settings, 'payment', $newPayId);
                } else {
                    $this->protectId($settings, 'payment', $payId);
                }
            }
        } catch (\Throwable $e) {
        }
    }

    private function ensureDemoRealtorData($db, Setting $settings, int $userId): void
    {
        try {
            $demoContractTotal = 120000.00;

            // Ensure tables/columns exist
            try {
                new \App\Models\RealtorListing();
                new \App\Models\RealtorClient();
                new \App\Models\RealtorContract();
                new Payment();
            } catch (\Throwable $e) {
            }

            $stmtL = $db->prepare('SELECT id FROM realtor_listings WHERE user_id = ? ORDER BY id DESC LIMIT 1');
            $stmtL->execute([(int)$userId]);
            $listingId = (int)($stmtL->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
            if ($listingId <= 0) {
                $ins = $db->prepare("INSERT INTO realtor_listings (user_id, title, listing_type, location, price, status, description, created_at, updated_at) VALUES (?,?,?,?,?, 'active', ?, NOW(), NOW())");
                $ins->execute([
                    (int)$userId,
                    'Demo Listing - 2BR Apartment',
                    'residential_apartment',
                    'Kilimani, Nairobi',
                    (float)$demoContractTotal,
                    'Sample demo listing with photos, inquiries, and contracts',
                ]);
                $listingId = (int)$db->lastInsertId();
            } else {
                try {
                    $db->prepare('UPDATE realtor_listings SET price = ? WHERE id = ? AND user_id = ?')->execute([(float)$demoContractTotal, (int)$listingId, (int)$userId]);
                } catch (\Throwable $e) {
                }
            }

            try {
                $this->protectId($settings, 'realtor_listing', (int)$listingId);
            } catch (\Throwable $e) {
            }

            $stmtC = $db->prepare('SELECT id FROM realtor_clients WHERE user_id = ? ORDER BY id DESC LIMIT 1');
            $stmtC->execute([(int)$userId]);
            $clientId = (int)($stmtC->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
            if ($clientId <= 0) {
                $ins = $db->prepare('INSERT INTO realtor_clients (user_id, realtor_listing_id, name, phone, email, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
                $ins->execute([
                    (int)$userId,
                    $listingId > 0 ? (int)$listingId : null,
                    'Demo Client',
                    '+254700000111',
                    'demo.client@rentsmart.local',
                    'Sample client interested in the demo listing',
                ]);
                $clientId = (int)$db->lastInsertId();
            }

            try {
                $this->protectId($settings, 'realtor_client', (int)$clientId);
            } catch (\Throwable $e) {
            }

            $stmtK = $db->prepare('SELECT id FROM realtor_contracts WHERE user_id = ? AND realtor_client_id = ? ORDER BY id DESC LIMIT 1');
            $stmtK->execute([(int)$userId, (int)$clientId]);
            $contractId = (int)($stmtK->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
            if ($contractId <= 0) {
                $ins = $db->prepare("INSERT INTO realtor_contracts (user_id, realtor_client_id, realtor_listing_id, terms_type, total_amount, monthly_amount, duration_months, start_month, instructions, status, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?, 'active', NOW(), NOW())");
                $ins->execute([
                    (int)$userId,
                    (int)$clientId,
                    $listingId > 0 ? (int)$listingId : null,
                    'one_time',
                    (float)$demoContractTotal,
                    null,
                    null,
                    date('Y-m-01'),
                    'Demo contract instructions',
                ]);
                $contractId = (int)$db->lastInsertId();
            } else {
                try {
                    $db->prepare('UPDATE realtor_contracts SET total_amount = ? WHERE id = ? AND user_id = ?')->execute([(float)$demoContractTotal, (int)$contractId, (int)$userId]);
                } catch (\Throwable $e) {
                }
            }

            try {
                $this->protectId($settings, 'realtor_contract', (int)$contractId);
            } catch (\Throwable $e) {
            }

            // Seed exactly one demo realtor payment (idempotent by reference_number)
            try {
                $stmtCnt = $db->prepare(
                    "SELECT COUNT(*) AS c FROM payments\n"
                    . "WHERE realtor_user_id = ?\n"
                    . "  AND payment_type = 'realtor'\n"
                    . "  AND reference_number = 'DEMO-REALTOR-001'"
                );
                $stmtCnt->execute([(int)$userId]);
                $cnt = (int)($stmtCnt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
                $this->demoLog('realtor payment pre-check user_id=' . (int)$userId . ' ref=DEMO-REALTOR-001 count=' . $cnt);
            } catch (\Throwable $e) {
            }
            $stmtRP = $db->prepare(
                "SELECT id FROM payments\n"
                . "WHERE realtor_user_id = ?\n"
                . "  AND payment_type = 'realtor'\n"
                . "  AND reference_number = 'DEMO-REALTOR-001'\n"
                . "ORDER BY id DESC\n"
                . "LIMIT 1"
            );
            $stmtRP->execute([(int)$userId]);
            $rpId = (int)($stmtRP->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
            $this->demoLog('realtor payment selected user_id=' . (int)$userId . ' rp_id=' . (int)$rpId);

            // Normalize any older demo rows from previous seeding versions.
            // Ensure they point to the current demo contract and have the correct amount.
            try {
                $stmtNorm = $db->prepare(
                    "UPDATE payments\n"
                    . "SET realtor_contract_id = ?, realtor_client_id = ?, realtor_listing_id = ?, amount = ?\n"
                    . "WHERE realtor_user_id = ?\n"
                    . "  AND payment_type = 'realtor'\n"
                    . "  AND (reference_number = 'DEMO-REALTOR-001' OR notes = 'Demo realtor payment')"
                );
                $stmtNorm->execute([
                    (int)$contractId,
                    (int)$clientId,
                    $listingId > 0 ? (int)$listingId : null,
                    (float)$demoContractTotal,
                    (int)$userId,
                ]);
            } catch (\Throwable $e) {
            }

            // Re-evaluate after normalization
            try {
                $stmtRP->execute([(int)$userId]);
                $rpId = (int)($stmtRP->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
            } catch (\Throwable $e) {
            }
            $this->demoLog('realtor payment after-normalize user_id=' . (int)$userId . ' rp_id=' . (int)$rpId);
            if ($rpId <= 0) {
                $paymentModel = new Payment();
                $this->demoLog('realtor payment inserting user_id=' . (int)$userId . ' contract_id=' . (int)$contractId);
                $pid = $paymentModel->createRealtorPayment([
                    'realtor_user_id' => (int)$userId,
                    'realtor_client_id' => (int)$clientId,
                    'realtor_listing_id' => $listingId > 0 ? (int)$listingId : null,
                    'realtor_contract_id' => (int)$contractId,
                    'amount' => (float)$demoContractTotal,
                    'payment_date' => date('Y-m-d'),
                    'applies_to_month' => date('Y-m-01'),
                    'payment_type' => 'realtor',
                    'payment_method' => 'mpesa',
                    'reference_number' => 'DEMO-REALTOR-001',
                    'status' => 'completed',
                    'notes' => 'Demo realtor payment',
                ]);
                $this->demoLog('realtor payment inserted user_id=' . (int)$userId . ' payment_id=' . (int)$pid);
                $this->protectId($settings, 'payment', (int)$pid);
            } else {
                $this->protectId($settings, 'payment', (int)$rpId);
                try {
                    $db->prepare(
                        "UPDATE payments\n"
                        . "SET realtor_contract_id = ?, realtor_client_id = ?, realtor_listing_id = ?, amount = ?, notes = 'Demo realtor payment'\n"
                        . "WHERE id = ?"
                    )->execute([
                        (int)$contractId,
                        (int)$clientId,
                        $listingId > 0 ? (int)$listingId : null,
                        (float)$demoContractTotal,
                        (int)$rpId,
                    ]);
                } catch (\Throwable $e) {
                }
            }

            // Protect the baseline contract invoice (if it exists) so demo cleanup won't remove it.
            try {
                $tag = 'REALTOR_CONTRACT#' . (int)$contractId;
                $stmtInv = $db->prepare("SELECT id FROM invoices WHERE user_id = ? AND notes LIKE ? ORDER BY id DESC LIMIT 1");
                $stmtInv->execute([(int)$userId, '%' . $tag . '%']);
                $invId = (int)($stmtInv->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
                if ($invId > 0) {
                    $this->protectId($settings, 'invoice', (int)$invId);
                }
            } catch (\Throwable $e) {
            }

            // Clean up any old duplicates that may have been created in earlier versions of demo seeding.
            try {
                $keepId = $rpId > 0 ? $rpId : (int)($pid ?? 0);
                if ($keepId > 0) {
                    $stmtAll = $db->prepare(
                        "SELECT id FROM payments\n"
                        . "WHERE realtor_user_id = ?\n"
                        . "  AND payment_type = 'realtor'\n"
                        . "  AND (reference_number = 'DEMO-REALTOR-001' OR notes = 'Demo realtor payment')\n"
                        . "ORDER BY id DESC"
                    );
                    $stmtAll->execute([(int)$userId]);
                    $rows = $stmtAll->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $r) {
                        $delId = (int)($r['id'] ?? 0);
                        if ($delId > 0 && $delId !== $keepId) {
                            try {
                                $db->prepare('DELETE FROM payments WHERE id = ?')->execute([(int)$delId]);
                            } catch (\Throwable $e) {
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
            }
        } catch (\Throwable $e) {
        }
    }

    private function ensureDemoData($db, Setting $settings, int $userId, string $role): void
    {
        // Realtor seeding touches schema (DDL) via models, which can implicitly commit in MySQL.
        // Do not wrap realtor demo seeding in a transaction to avoid "There is no active transaction" on commit.
        if ($role === 'realtor') {
            $this->ensureDemoRealtorData($db, $settings, $userId);
            return;
        }

        $db->beginTransaction();
        try {
            $propId = $this->ensureDemoProperty($db, $settings, $userId, $role);
            $unitIds = $this->ensureDemoUnits($db, $settings, $userId, $propId);
            $tenantId = $this->ensureDemoTenant($db, $settings, $userId, $propId, $unitIds[0] ?? null);
            if (!empty($unitIds[0]) && !empty($tenantId)) {
                $this->ensureDemoLeaseAndPayment($db, $settings, $userId, (int)$unitIds[0], (int)$tenantId, (int)$propId);
            }

            if (in_array($role, ['landlord', 'manager', 'agent'], true) && !empty($unitIds[0]) && !empty($tenantId)) {
                $this->ensureDemoUtilities($db, $settings, $userId, (int)$propId, (int)$unitIds[0], (int)$tenantId);
            }

            if ($db->inTransaction()) {
                $db->commit();
            }
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

        $email = 'demo.tenant+' . (int)$propertyId . '@rentsmart.local';
        try {
            $stmt = $db->prepare('SELECT id FROM tenants WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $found = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['id'] ?? 0);
            if ($found > 0) {
                $settings->updateByKey($key, (string)$found);
                $this->protectId($settings, 'tenant', $found);
                return $found;
            }
        } catch (\Throwable $e) {
        }

        $stmt = $db->prepare('INSERT INTO tenants (name, first_name, last_name, email, phone, unit_id, property_id, notes, registered_on, created_at, updated_at, rent_amount) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW(),?)');
        $stmt->execute([
            'Demo Tenant',
            'Demo',
            'Tenant',
            $email,
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
