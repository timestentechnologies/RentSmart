<?php

namespace App\Models;

use App\Database\Connection;
use PDO;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Invoice;

class Payment extends Model
{
    protected $table = 'payments';

    public function __construct()
    {
        parent::__construct();
        $this->ensureRealtorPaymentColumns();
    }

    private function ensureRealtorPaymentColumns(): void
    {
        // Realtor payments are contract-based and do not use leases; allow NULL lease_id.
        try {
            $this->db->exec("ALTER TABLE payments MODIFY lease_id INT NULL");
        } catch (\Exception $e) {
        }

        try {
            $this->db->exec("ALTER TABLE payments ADD COLUMN realtor_user_id INT NULL AFTER lease_id");
        } catch (\Exception $e) {
        }
        try {
            $this->db->exec("ALTER TABLE payments ADD COLUMN realtor_client_id INT NULL AFTER realtor_user_id");
        } catch (\Exception $e) {
        }
        try {
            $this->db->exec("ALTER TABLE payments ADD COLUMN realtor_listing_id INT NULL AFTER realtor_client_id");
        } catch (\Exception $e) {
        }
        try {
            $this->db->exec("ALTER TABLE payments ADD COLUMN realtor_contract_id INT NULL AFTER realtor_listing_id");
        } catch (\Exception $e) {
        }
        try {
            $this->db->exec("ALTER TABLE payments ADD INDEX idx_realtor_payment (realtor_user_id, realtor_client_id, realtor_listing_id)");
        } catch (\Exception $e) {
        }
        try {
            $this->db->exec("ALTER TABLE payments ADD INDEX idx_realtor_contract (realtor_contract_id)");
        } catch (\Exception $e) {
        }
    }

    public function createRealtorPayment(array $data)
    {
        $this->ensureAppliesToMonthColumn();

        $sql = "INSERT INTO payments (
                    lease_id, realtor_user_id, realtor_client_id, realtor_listing_id,
                    realtor_contract_id,
                    amount, payment_date, applies_to_month, payment_type, payment_method,
                    reference_number, status, notes
                ) VALUES (
                    NULL, :realtor_user_id, :realtor_client_id, :realtor_listing_id,
                    :realtor_contract_id,
                    :amount, :payment_date, :applies_to_month, :payment_type, :payment_method,
                    :reference_number, :status, :notes
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'realtor_user_id' => (int)($data['realtor_user_id'] ?? 0),
            'realtor_client_id' => (int)($data['realtor_client_id'] ?? 0),
            'realtor_listing_id' => (int)($data['realtor_listing_id'] ?? 0),
            'realtor_contract_id' => !empty($data['realtor_contract_id']) ? (int)$data['realtor_contract_id'] : null,
            'amount' => (float)($data['amount'] ?? 0),
            'payment_date' => (string)($data['payment_date'] ?? date('Y-m-d')),
            'applies_to_month' => $data['applies_to_month'] ?? null,
            'payment_type' => (string)($data['payment_type'] ?? 'realtor'),
            'payment_method' => (string)($data['payment_method'] ?? 'cash'),
            'reference_number' => $data['reference_number'] ?? null,
            'status' => (string)($data['status'] ?? 'completed'),
            'notes' => (string)($data['notes'] ?? ''),
        ]);

        $id = (int)$this->db->lastInsertId();
        // Auto-post to ledger + create an invoice (Realtor payments are contract-based).
        $this->postPaymentToLedgerIfNeeded($id, $data);
        $this->createInvoiceForRealtorPaymentIfNeeded($id, $data);
        return $id;
    }

    private function createInvoiceForRealtorPaymentIfNeeded(int $paymentId, array $data): void
    {
        try {
            $status = $data['status'] ?? 'completed';
            if (!$this->shouldPostToLedger($status)) return;

            $userId = (int)($data['realtor_user_id'] ?? 0);
            if ($userId <= 0) return;

            $amount = (float)($data['amount'] ?? 0);
            if ($amount <= 0) return;

            // Prevent duplicates if called twice.
            $tag = 'REALTOR_PAYMENT#' . (int)$paymentId;
            $chk = $this->db->prepare("SELECT id FROM invoices WHERE user_id = ? AND notes LIKE ? ORDER BY id DESC LIMIT 1");
            $chk->execute([$userId, '%' . $tag . '%']);
            $exists = $chk->fetch(\PDO::FETCH_ASSOC);
            if (!empty($exists)) return;

            $clientId = (int)($data['realtor_client_id'] ?? 0);
            $listingId = (int)($data['realtor_listing_id'] ?? 0);
            $contractId = (int)($data['realtor_contract_id'] ?? 0);
            $date = (string)($data['payment_date'] ?? date('Y-m-d'));

            $clientName = '';
            $listingTitle = '';
            try {
                if ($clientId > 0) {
                    $s = $this->db->prepare("SELECT name FROM realtor_clients WHERE id = ? LIMIT 1");
                    $s->execute([$clientId]);
                    $clientName = (string)($s->fetch(\PDO::FETCH_ASSOC)['name'] ?? '');
                }
            } catch (\Exception $e) {
            }
            try {
                if ($listingId > 0) {
                    $s = $this->db->prepare("SELECT title FROM realtor_listings WHERE id = ? LIMIT 1");
                    $s->execute([$listingId]);
                    $listingTitle = (string)($s->fetch(\PDO::FETCH_ASSOC)['title'] ?? '');
                }
            } catch (\Exception $e) {
            }

            $desc = 'Contract Payment';
            $suffixParts = [];
            if ($contractId > 0) $suffixParts[] = 'Contract #' . $contractId;
            if ($clientName !== '') $suffixParts[] = $clientName;
            if ($listingTitle !== '') $suffixParts[] = $listingTitle;
            if (!empty($suffixParts)) {
                $desc .= ' - ' . implode(' / ', $suffixParts);
            }

            $inv = new Invoice();
            $inv->createInvoice([
                'tenant_id' => null,
                'issue_date' => $date,
                'due_date' => null,
                'status' => 'sent',
                'notes' => trim($tag . ' ' . (string)($data['notes'] ?? '')),
                'user_id' => $userId,
            ], [
                [
                    'description' => $desc,
                    'quantity' => 1,
                    'unit_price' => $amount,
                ]
            ]);
        } catch (\Exception $e) {
            // Do not block payment
            error_log('Realtor invoice create failed: ' . $e->getMessage());
        }
    }

    public function getPaymentsForRealtor($userId)
    {
        $sql = "SELECT p.*,
                       rc.name AS client_name,
                       rc.id AS client_id,
                       rl.title AS listing_title,
                       rl.id AS listing_id,
                       c.terms_type AS contract_terms_type,
                       c.total_amount AS contract_total_amount,
                       c.monthly_amount AS contract_monthly_amount,
                       c.duration_months AS contract_duration_months,
                       c.start_month AS contract_start_month
                FROM payments p
                LEFT JOIN realtor_clients rc ON rc.id = p.realtor_client_id
                LEFT JOIN realtor_listings rl ON rl.id = p.realtor_listing_id
                LEFT JOIN realtor_contracts c ON c.id = p.realtor_contract_id
                WHERE p.realtor_user_id = ?
                ORDER BY p.payment_date DESC, p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRealtorPaidTotalsByContract(int $userId): array
    {
        $sql = "SELECT realtor_contract_id, COALESCE(SUM(amount),0) AS paid
                FROM payments
                WHERE realtor_user_id = ?
                  AND realtor_contract_id IS NOT NULL
                  AND status IN ('completed','verified')
                GROUP BY realtor_contract_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int)$userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $cid = (int)($r['realtor_contract_id'] ?? 0);
            if ($cid <= 0) continue;
            $out[$cid] = (float)($r['paid'] ?? 0);
        }
        return $out;
    }

    public function realtorMonthAlreadyPaid($userId, $clientId, $listingId, $appliesToMonth, $paymentType)
    {
        if (empty($appliesToMonth)) {
            return false;
        }
        $sql = "SELECT COUNT(*) AS c
                FROM payments
                WHERE realtor_user_id = ?
                  AND realtor_client_id = ?
                  AND realtor_listing_id = ?
                  AND payment_type = ?
                  AND applies_to_month = ?
                  AND status IN ('completed','verified')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int)$userId,
            (int)$clientId,
            (int)$listingId,
            (string)$paymentType,
            (string)$appliesToMonth,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return ((int)($row['c'] ?? 0)) > 0;
    }

    private function ensureAppliesToMonthColumn(): void
    {
        try {
            // Add applies_to_month if missing. Safe no-op if column exists.
            $this->db->exec("ALTER TABLE payments ADD COLUMN applies_to_month DATE NULL AFTER payment_date");
            $this->db->exec("ALTER TABLE payments ADD INDEX idx_applies_to_month (applies_to_month)");
        } catch (\Exception $e) {
            // Ignore: most likely column/index already exists.
        }
    }

    public function getFullyPaidRentMonthsByLease($leaseId): array
    {
        $leaseId = (int)$leaseId;
        if ($leaseId <= 0) {
            return [];
        }

        $leaseStmt = $this->db->prepare("SELECT start_date, rent_amount FROM leases WHERE id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([$leaseId]);
        $lease = $leaseStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        $rentAmount = (float)($lease['rent_amount'] ?? 0);
        if (empty($lease['start_date']) || $rentAmount <= 0.0) {
            return [];
        }

        try {
            $leaseStart = new \DateTime((string)$lease['start_date']);
        } catch (\Exception $e) {
            return [];
        }

        $startMonth = new \DateTime($leaseStart->format('Y-m-01'));
        $today = new \DateTime();
        $endMonth = new \DateTime($today->format('Y-m-01'));

        try {
            $maxStmt = $this->db->prepare("SELECT MAX(applies_to_month) AS m FROM payments WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') AND applies_to_month IS NOT NULL");
            $maxStmt->execute([$leaseId]);
            $maxRaw = (string)($maxStmt->fetch(\PDO::FETCH_ASSOC)['m'] ?? '');
            if ($maxRaw !== '') {
                $maxTagged = new \DateTime(substr($maxRaw, 0, 7) . '-01');
                if ($maxTagged > $endMonth) {
                    $endMonth = $maxTagged;
                }
            }
        } catch (\Exception $e) {
        }

        // Tagged totals by month
        $tagStmt = $this->db->prepare(
            "SELECT DATE_FORMAT(applies_to_month, '%Y-%m-01') AS m, COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
            . "FROM payments\n"
            . "WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') AND applies_to_month IS NOT NULL\n"
            . "GROUP BY DATE_FORMAT(applies_to_month, '%Y-%m-01')"
        );
        $tagStmt->execute([$leaseId]);
        $tagRows = $tagStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $tagged = [];
        foreach ($tagRows as $r) {
            $k = (string)($r['m'] ?? '');
            if ($k !== '') {
                $tagged[$k] = (float)($r['s'] ?? 0);
            }
        }

        // Untagged totals
        $untagStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s FROM payments\n"
            . "WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') AND applies_to_month IS NULL"
        );
        $untagStmt->execute([$leaseId]);
        $untaggedTotal = (float)($untagStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

        // Build month keys from start to end
        $monthKeys = [];
        $cursor = clone $startMonth;
        while ($cursor <= $endMonth) {
            $monthKeys[] = $cursor->format('Y-m-01');
            $cursor->modify('+1 month');
        }

        $outstandingByMonth = [];
        $excess = 0.0;
        foreach ($monthKeys as $k) {
            $out = $rentAmount;
            $paid = (float)($tagged[$k] ?? 0);
            $apply = min($out, max(0.0, $paid));
            $out -= $apply;
            $excess += max(0.0, $paid - $apply);
            $outstandingByMonth[$k] = max(0.0, $out);
        }

        $remaining = max(0.0, $untaggedTotal) + $excess;
        foreach ($monthKeys as $k) {
            if ($remaining <= 0.0) {
                break;
            }
            $apply = min((float)$outstandingByMonth[$k], $remaining);
            $outstandingByMonth[$k] = max(0.0, (float)$outstandingByMonth[$k] - $apply);
            $remaining -= $apply;
        }

        $fullyPaid = [];
        foreach ($monthKeys as $k) {
            if (((float)($outstandingByMonth[$k] ?? $rentAmount)) <= 0.0001) {
                $fullyPaid[] = substr($k, 0, 7);
            }
        }

        return array_values(array_unique($fullyPaid));
    }

    public function isRentMonthFullyPaidByLease($leaseId, $appliesToMonth): bool
    {
        $leaseId = (int)$leaseId;
        if ($leaseId <= 0) {
            return false;
        }

        $m = null;
        if ($appliesToMonth instanceof \DateTimeInterface) {
            $m = $appliesToMonth->format('Y-m');
        } else {
            $raw = trim((string)$appliesToMonth);
            if ($raw !== '') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
                    $m = substr($raw, 0, 7);
                } else if (preg_match('/^\d{4}-\d{2}$/', $raw)) {
                    $m = $raw;
                }
            }
        }

        if ($m === null) {
            return false;
        }

        $paid = $this->getFullyPaidRentMonthsByLease($leaseId);
        return in_array($m, $paid, true);
    }

    public function postExistingPaymentToLedger(int $paymentId): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id, lease_id, amount, payment_date, payment_type, status FROM payments WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$paymentId]);
            $p = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            if (!$p) return;

            $data = [
                'lease_id' => (int)($p['lease_id'] ?? 0),
                'amount' => (float)($p['amount'] ?? 0),
                'payment_date' => (string)($p['payment_date'] ?? date('Y-m-d')),
                'payment_type' => (string)($p['payment_type'] ?? 'rent'),
                'status' => (string)($p['status'] ?? 'completed'),
            ];
            $this->postPaymentToLedgerIfNeeded((int)$paymentId, $data);
        } catch (\Exception $e) {
            error_log('Post existing payment to ledger failed: ' . $e->getMessage());
        }
    }

    private function resolveAccountingUserIdForLease(int $leaseId): ?int
    {
        try {
            $stmt = $this->db->prepare("SELECT p.owner_id, p.manager_id, p.agent_id, p.caretaker_user_id
                                        FROM leases l
                                        JOIN units u ON l.unit_id = u.id
                                        JOIN properties p ON u.property_id = p.id
                                        WHERE l.id = ? LIMIT 1");
            $stmt->execute([(int)$leaseId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            foreach (['owner_id','manager_id','agent_id','caretaker_user_id'] as $k) {
                if (!empty($row[$k])) return (int)$row[$k];
            }
        } catch (\Exception $e) {
        }
        return null;
    }

    private function resolvePropertyIdForLease(int $leaseId): ?int
    {
        try {
            $stmt = $this->db->prepare("SELECT u.property_id
                                        FROM leases l
                                        JOIN units u ON l.unit_id = u.id
                                        WHERE l.id = ? LIMIT 1");
            $stmt->execute([(int)$leaseId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            return !empty($row['property_id']) ? (int)$row['property_id'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getOutstandingBalanceForProperty($propertyId, $userId = null)
    {
        try {
            $propertyId = (int)$propertyId;
            if ($propertyId <= 0) {
                return 0.0;
            }

            $user = new User();
            $isAdmin = false;
            $userData = null;
            if ($userId) {
                $userData = $user->find($userId);
                if (!$userData) {
                    return 0.0;
                }
                $isAdmin = ($userData['role'] === 'admin' || $userData['role'] === 'administrator');
            }

            $sql = "SELECT
                    l.id as lease_id,
                    l.rent_amount,
                    l.start_date,
                    l.end_date,
                    u.id as unit_id
                FROM leases l
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE l.status = 'active'
                  AND pr.id = ?";
            $params = [$propertyId];

            if ($userId && !$isAdmin) {
                $sql .= " AND (";
                $conditions = [];

                if (($userData['role'] ?? '') === 'landlord') { $conditions[] = "pr.owner_id = ?"; $params[] = $userId; }
                if (($userData['role'] ?? '') === 'manager') { $conditions[] = "pr.manager_id = ?"; $params[] = $userId; }
                if (($userData['role'] ?? '') === 'agent') { $conditions[] = "pr.agent_id = ?"; $params[] = $userId; }
                if (($userData['role'] ?? '') === 'caretaker') { $conditions[] = "pr.caretaker_user_id = ?"; $params[] = $userId; }

                if (empty($conditions)) { $conditions[] = "1=0"; }
                $sql .= implode(' OR ', $conditions) . ")";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $leases = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $rentStmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) as total_paid
                FROM payments
                WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified')");

            $utilityStmt = $this->db->prepare("SELECT u.*,
                       CASE
                           WHEN u.is_metered = 1 THEN IFNULL(ur.cost, 0)
                           ELSE IFNULL(u.flat_rate, 0)
                       END as amount
                FROM utilities u
                LEFT JOIN (
                    SELECT ur1.*
                    FROM utility_readings ur1
                    INNER JOIN (
                        SELECT utility_id, MAX(id) as max_id
                        FROM utility_readings
                        GROUP BY utility_id
                    ) ur2 ON ur1.utility_id = ur2.utility_id AND ur1.id = ur2.max_id
                ) ur ON u.id = ur.utility_id
                WHERE u.unit_id = ?");

            $utilityPayStmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) as total_paid
                FROM payments
                WHERE lease_id = ? AND utility_id = ? AND payment_type = 'utility' AND status IN ('completed','verified')");

            $totalOutstanding = 0.0;
            foreach ($leases as $lease) {
                $monthlyRent = (float)($lease['rent_amount'] ?? 0);
                if ($monthlyRent <= 0) {
                    continue;
                }
                try {
                    $leaseStart = new \DateTime($lease['start_date']);
                } catch (\Exception $e) {
                    continue;
                }
                $startMonth = new \DateTime($leaseStart->format('Y-m-01'));
                $currentMonth = new \DateTime(date('Y-m-01'));
                if ($currentMonth < $startMonth) {
                    continue;
                }
                $monthsElapsed = ((int)$currentMonth->format('Y') - (int)$startMonth->format('Y')) * 12
                    + ((int)$currentMonth->format('n') - (int)$startMonth->format('n')) + 1;
                $expectedRent = $monthsElapsed * $monthlyRent;

                $rentStmt->execute([(int)$lease['lease_id']]);
                $rentPaid = (float)($rentStmt->fetch(\PDO::FETCH_ASSOC)['total_paid'] ?? 0);
                $totalOutstanding += max(0.0, $expectedRent - $rentPaid);

                $utilityStmt->execute([(int)$lease['unit_id']]);
                $utilities = $utilityStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($utilities as $util) {
                    $charge = (float)($util['amount'] ?? 0);
                    $utilityPayStmt->execute([(int)$lease['lease_id'], (int)$util['id']]);
                    $paid = (float)($utilityPayStmt->fetch(\PDO::FETCH_ASSOC)['total_paid'] ?? 0);
                    $totalOutstanding += max(0.0, $charge - $paid);
                }
            }

            return $totalOutstanding;
        } catch (\Throwable $e) {
            error_log('Error in getOutstandingBalanceForProperty: ' . $e->getMessage());
            return 0.0;
        }
    }

    private function shouldPostToLedger(?string $status): bool
    {
        return in_array((string)$status, ['completed','verified'], true);
    }

    private function postPaymentToLedgerIfNeeded(int $paymentId, array $data): void
    {
        try {
            $status = $data['status'] ?? 'completed';
            if (!$this->shouldPostToLedger($status)) return;

            $leaseId = (int)($data['lease_id'] ?? 0);
            $realtorUserId = (int)($data['realtor_user_id'] ?? 0);
            $isRealtorPayment = ($leaseId <= 0 && $realtorUserId > 0);
            if ($leaseId <= 0 && !$isRealtorPayment) return;

            $amount = (float)($data['amount'] ?? 0);
            if ($amount <= 0) return;

            $ledger = new LedgerEntry();
            if ($ledger->referenceExists('payment', $paymentId)) return;

            $accModel = new Account();
            $cash = $accModel->findByCode('1000');
            $rev = $accModel->findByCode('4000');
            if (!$cash || !$rev) return;

            $userId = $data['user_id'] ?? null;
            $propertyId = $data['property_id'] ?? null;
            if ($isRealtorPayment) {
                $userId = $realtorUserId;
                $propertyId = null;
            } else {
                if (empty($userId)) {
                    $userId = $this->resolveAccountingUserIdForLease($leaseId);
                }
                if (empty($propertyId)) {
                    $propertyId = $this->resolvePropertyIdForLease($leaseId);
                }
            }

            $date = $data['payment_date'] ?? date('Y-m-d');
            $type = $data['payment_type'] ?? 'rent';
            if ($isRealtorPayment) {
                $clientId = (int)($data['realtor_client_id'] ?? 0);
                $listingId = (int)($data['realtor_listing_id'] ?? 0);
                $desc = 'Contract payment #' . $paymentId;
                if ($clientId > 0) $desc .= ' (Client #' . $clientId . ')';
                if ($listingId > 0) $desc .= ' (Listing #' . $listingId . ')';
            } else {
                $desc = ucfirst((string)$type) . ' payment #' . $paymentId;
            }

            // Debit Cash, Credit Revenue
            $ledger->post([
                'entry_date' => $date,
                'account_id' => (int)$cash['id'],
                'description' => $desc,
                'debit' => $amount,
                'credit' => 0,
                'user_id' => $userId,
                'property_id' => $propertyId,
                'reference_type' => 'payment',
                'reference_id' => $paymentId,
            ]);
            $ledger->post([
                'entry_date' => $date,
                'account_id' => (int)$rev['id'],
                'description' => $desc,
                'debit' => 0,
                'credit' => $amount,
                'user_id' => $userId,
                'property_id' => $propertyId,
                'reference_type' => 'payment',
                'reference_id' => $paymentId,
            ]);
        } catch (\Exception $e) {
            // Do not block payment
            error_log('Payment ledger post failed: ' . $e->getMessage());
        }
    }

    private function ensureInvoicesForAdvanceIfNeeded(int $leaseId, ?int $userId = null): void
    {
        try {
            $stmt = $this->db->prepare("SELECT id, tenant_id, start_date, rent_amount FROM leases WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$leaseId]);
            $lease = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lease) return;
            $tenantId = (int)($lease['tenant_id'] ?? 0);
            $rent = (float)($lease['rent_amount'] ?? 0);
            $startDate = (string)($lease['start_date'] ?? '');
            if ($tenantId <= 0 || $rent <= 0 || $startDate === '') return;

            // Only positive payments count toward rent coverage; negative rows are adjustments/discounts.
            $payStmt = $this->db->prepare("SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s FROM payments WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified')");
            $payStmt->execute([(int)$leaseId]);
            $paidTotal = (float)(($payStmt->fetch(PDO::FETCH_ASSOC)['s'] ?? 0));
            if ($paidTotal <= 0) return;

            $monthsPaid = (int)floor($paidTotal / $rent + 1e-6);
            $today = new \DateTime();
            $end = clone $today;
            if ($monthsPaid > 0) {
                $leaseStart = new \DateTime(date('Y-m-01', strtotime($startDate)));
                $leaseStart->modify('+' . max(0, $monthsPaid - 1) . ' month');
                // Ensure through the last month covered by payments
                if ($leaseStart > $end) { $end = $leaseStart; }
            }

            $inv = new Invoice();
            $inv->ensureInvoicesForLeaseMonths($tenantId, $rent, $startDate, $end->format('Y-m-d'), $userId, 'AUTO');
        } catch (\Exception $e) {
            error_log('Ensure advance invoices failed: ' . $e->getMessage());
        }
    }

    public function getAll($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        $sql = "SELECT p.*,
                t.name as tenant_name,
                u.unit_number,
                pr.name as property_name
                FROM payments p
                JOIN leases l ON p.lease_id = l.id
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id";

        $params = [];
        
        // Add role-based conditions
        if ($userId && !$user->isAdmin()) {
            $sql .= " WHERE (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " ORDER BY p.payment_date DESC, p.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getMonthlyTenantBalances($year, $month, $propertyId = null, $statusFilter = null, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        $isAdmin = $user->isAdmin();

        $ym = sprintf('%04d-%02d', (int)$year, (int)$month);
        $startOfMonth = $ym . '-01';
        $endOfMonth = date('Y-m-t', strtotime($startOfMonth));

        $sql = "SELECT l.id AS lease_id, l.tenant_id, l.unit_id, l.rent_amount, l.start_date, l.end_date,
                       t.name AS tenant_name, u.unit_number, pr.id AS property_id, pr.name AS property_name
                FROM leases l
                INNER JOIN tenants t ON l.tenant_id = t.id
                INNER JOIN units u ON l.unit_id = u.id
                INNER JOIN properties pr ON u.property_id = pr.id
                WHERE l.status = 'active'";
        $params = [];
        if ($propertyId) {
            $sql .= " AND pr.id = ?";
            $params[] = $propertyId;
        }
        if (!$isAdmin) {
            $sql .= " AND (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)";
            $params[] = $userId; $params[] = $userId; $params[] = $userId; $params[] = $userId;
        }
        $sql .= " ORDER BY pr.name, u.unit_number";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $leases = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Rent paid allocation for month-based reports:
        // - Use applies_to_month if present (so late payments still count for the intended month)
        // - Fall back to payment_date when applies_to_month is NULL
        // - Count only positive amounts as rent paid
        $sumPrev = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
            . "FROM payments\n"
            . "WHERE lease_id = ?\n"
            . "  AND payment_type = 'rent'\n"
            . "  AND status IN ('completed','verified')\n"
            . "  AND (\n"
            . "        (applies_to_month IS NOT NULL AND applies_to_month < ?)\n"
            . "     OR (applies_to_month IS NULL AND payment_date < ?)\n"
            . "  )"
        );
        $sumMonth = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
            . "FROM payments\n"
            . "WHERE lease_id = ?\n"
            . "  AND payment_type = 'rent'\n"
            . "  AND status IN ('completed','verified')\n"
            . "  AND (\n"
            . "        (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)\n"
            . "     OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)\n"
            . "  )"
        );

        $unitsUtilitiesStmt = $this->db->prepare("SELECT id, is_metered, flat_rate FROM utilities WHERE unit_id = ?");
        $utilityChargeMeteredStmt = $this->db->prepare("SELECT COALESCE(cost,0) AS c
            FROM utility_readings
            WHERE utility_id = ?
              AND reading_date BETWEEN ? AND ?
            ORDER BY reading_date DESC, id DESC
            LIMIT 1");
        $utilityPaidStmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) AS s
            FROM payments
            WHERE utility_id = ?
              AND payment_type = 'utility'
              AND status IN ('completed','verified')
              AND (
                    (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)
                 OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)
              )");

        $maintChargesStmt = $this->db->prepare("SELECT COALESCE(SUM(ABS(amount)),0) AS s
            FROM payments
            WHERE lease_id = ?
              AND payment_type = 'other'
              AND amount < 0
              AND notes LIKE '%MAINT-%'
              AND status IN ('completed','verified')
              AND payment_date BETWEEN ? AND ?");
        $maintPaidStmt = $this->db->prepare("SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s
            FROM payments
            WHERE lease_id = ?
              AND payment_type = 'other'
              AND status IN ('completed','verified')
              AND (
                    (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)
                 OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)
              )
              AND (notes LIKE 'Maintenance payment:%' OR notes LIKE '%MAINT-%')");

        $startMonthDt = new \DateTime($startOfMonth);
        $rows = [];
        foreach ($leases as $L) {
            $rent = (float)($L['rent_amount'] ?? 0);
            if ($rent <= 0) { continue; }

            $leaseStart = new \DateTime($L['start_date']);
            $leaseEnd = !empty($L['end_date']) ? new \DateTime($L['end_date']) : null;
            $leaseStartMonth = new \DateTime($leaseStart->format('Y-m-01'));
            $selectedMonth = $startMonthDt;
            if ($selectedMonth < $leaseStartMonth) { continue; }
            if ($leaseEnd) {
                $leaseEndMonth = new \DateTime($leaseEnd->format('Y-m-01'));
                if ($selectedMonth > $leaseEndMonth) { continue; }
            }

            $sumPrev->execute([$L['lease_id'], $startOfMonth, $startOfMonth]);
            $paidPrev = (float)($sumPrev->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
            $sumMonth->execute([$L['lease_id'], $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth]);
            $paidInMonth = (float)($sumMonth->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            $monthsPrev = (int)floor($paidPrev / $rent + 1e-6);
            $remainderPrev = $paidPrev - $monthsPrev * $rent;
            // How many months from lease start up to and including the selected month
            $monthsElapsed = ((int)$selectedMonth->format('Y') - (int)$leaseStartMonth->format('Y')) * 12 + ((int)$selectedMonth->format('n') - (int)$leaseStartMonth->format('n')) + 1;
            $coveredByPrev = ($monthsPrev >= $monthsElapsed);
            $applied = $remainderPrev + $paidInMonth;
            if ($coveredByPrev) {
                // Previous payments fully cover this month. Rent balance is settled.
                $rentBalance = 0.0;
            } else {
                $rentBalance = max($rent - $applied, 0.0);
            }

            // Utilities due for this unit within selected month
            $utilitiesDue = 0.0;
            try {
                $unitsUtilitiesStmt->execute([(int)$L['unit_id']]);
                $utils = $unitsUtilitiesStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($utils as $ut) {
                    $charge = 0.0;
                    if ((int)($ut['is_metered'] ?? 0) === 1) {
                        $utilityChargeMeteredStmt->execute([(int)$ut['id'], $startOfMonth, $endOfMonth]);
                        $charge = (float)($utilityChargeMeteredStmt->fetch(\PDO::FETCH_ASSOC)['c'] ?? 0);
                    } else {
                        $charge = (float)($ut['flat_rate'] ?? 0);
                    }
                    $utilityPaidStmt->execute([(int)$ut['id'], $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth]);
                    $paidU = (float)($utilityPaidStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
                    $utilitiesDue += max($charge - $paidU, 0.0);
                }
            } catch (\Throwable $ex) {
                $utilitiesDue = 0.0;
            }

            // Maintenance due for this lease within selected month
            $maintenanceDue = 0.0;
            try {
                $maintChargesStmt->execute([(int)$L['lease_id'], $startOfMonth, $endOfMonth]);
                $mCharged = (float)($maintChargesStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
                $maintPaidStmt->execute([(int)$L['lease_id'], $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth]);
                $mPaid = (float)($maintPaidStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
                $maintenanceDue = max($mCharged - $mPaid, 0.0);
            } catch (\Throwable $ex) {
                $maintenanceDue = 0.0;
            }

            $totalBalance = $rentBalance + $utilitiesDue + $maintenanceDue;

            $totalToEnd = $paidPrev + $paidInMonth;
            $monthsPaidTotal = (int)floor($totalToEnd / $rent + 1e-6);
            $prepaidMonths = max(0, $monthsPaidTotal - $monthsElapsed);

            $status = 'paid';
            if ($totalBalance > 0.009) {
                $status = 'due';
            } else if ($prepaidMonths > 0) {
                $status = 'advance';
            }

            if ($statusFilter) {
                if ($statusFilter === 'due' && $status !== 'due') { continue; }
                if ($statusFilter === 'paid' && $status !== 'paid') { continue; }
                if ($statusFilter === 'advance' && $status !== 'advance') { continue; }
            }

            $rows[] = [
                'lease_id' => (int)$L['lease_id'],
                'tenant_id' => (int)$L['tenant_id'],
                'tenant_name' => $L['tenant_name'],
                'property_id' => (int)$L['property_id'],
                'property_name' => $L['property_name'],
                'unit_number' => $L['unit_number'],
                'rent_amount' => round($rent, 2),
                'paid_in_month' => round($paidInMonth, 2),
                'rent_balance' => round($rentBalance, 2),
                'utilities_due' => round($utilitiesDue, 2),
                'maintenance_due' => round($maintenanceDue, 2),
                'balance' => round($totalBalance, 2),
                'status' => $status,
                'prepaid_months' => $prepaidMonths,
                'year' => (int)$year,
                'month' => (int)$month,
                'month_label' => $startMonthDt->format('F Y'),
            ];
        }
        return $rows;
    }

    public function getById($id, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT p.*,\n                t.name as tenant_name,\n                t.email as tenant_email,\n                t.phone as tenant_phone,\n                u.unit_number,\n                pr.id as property_id,\n                pr.name as property_name,\n                pr.address as property_address,\n                pr.city as property_city,\n                pr.state as property_state,\n                pr.zip_code as property_zip,\n                pr.owner_id as property_owner_id,\n                pr.manager_id as property_manager_id,\n                pr.agent_id as property_agent_id,\n                ut.utility_type,\n                mmp.phone_number, mmp.transaction_code, mmp.verification_status\n                FROM payments p\n                JOIN leases l ON p.lease_id = l.id\n                JOIN tenants t ON l.tenant_id = t.id\n                JOIN units u ON l.unit_id = u.id\n                JOIN properties pr ON u.property_id = pr.id\n                LEFT JOIN utilities ut ON p.utility_id = ut.id\n                LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id\n                WHERE p.id = ?";
        $params = [$id];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $payment = $stmt->fetch(\PDO::FETCH_ASSOC);
        // Fetch manager/owner name
        if ($payment) {
            $ownerName = null;
            $managerName = null;
            $userModel = new User();
            if (!empty($payment['property_owner_id'])) {
                $owner = $userModel->find($payment['property_owner_id']);
                $ownerName = $owner['name'] ?? null;
            }
            if (!empty($payment['property_manager_id'])) {
                $manager = $userModel->find($payment['property_manager_id']);
                $managerName = $manager['name'] ?? null;
            }
            if (!empty($payment['property_agent_id'])) {
                $agent = $userModel->find($payment['property_agent_id']);
                $agentName = $agent['name'] ?? null;
            }
            $payment['property_owner_name'] = $ownerName;
            $payment['property_manager_name'] = $managerName;
            $payment['property_agent_name'] = $agentName;
        }
        return $payment;
    }

    public function getMonthlyRevenue($userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT 
                DATE_FORMAT(p.payment_date, '%Y-%m') as month,
                SUM(p.amount) as total_amount,
                COUNT(*) as payment_count
                FROM payments p
                JOIN leases l ON p.lease_id = l.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";

        $params = [];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
                  ORDER BY month DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPaymentsByProperty($userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT 
                pr.id as property_id,
                pr.name as property_name,
                SUM(p.amount) as total_amount,
                COUNT(*) as payment_count
                FROM payments p
                JOIN leases l ON p.lease_id = l.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE p.payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";

        $params = [];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " GROUP BY pr.id, pr.name
                  ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPaymentsWithTenantInfo($userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT p.*, 
                t.name as tenant_name, 
                t.id as tenant_id,
                u.unit_number,
                pr.name as property_name,
                mmp.phone_number,
                mmp.transaction_code,
                mmp.verification_status as mpesa_status,
                ut.utility_type
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                JOIN tenants t ON l.tenant_id = t.id 
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id
                LEFT JOIN utilities ut ON p.utility_id = ut.id";

        $params = [];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " WHERE (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " ORDER BY p.payment_date DESC, p.id DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActiveLease($tenantId, $userId = null)
    {
        $user = new User();
        $user->find($userId);
        
        $sql = "SELECT l.* 
                FROM leases l
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE l.tenant_id = ? 
                AND l.status = 'active'";

        $params = [$tenantId];
        
        // Add role-based conditions
        if (!$user->isAdmin()) {
            $sql .= " AND (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR pr.agent_id = ?";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR pr.caretaker_user_id = ?";
            $params[] = $userId;
            $sql .= ")";
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getTotalsByTypeForLeaseInRange($leaseId, $startDate, $endDate)
    {
        $stmt = $this->db->prepare("SELECT payment_type, COALESCE(SUM(amount),0) AS total_paid FROM payments WHERE lease_id = ? AND status IN ('completed','verified') AND payment_date BETWEEN ? AND ? GROUP BY payment_type");
        $stmt->execute([$leaseId, $startDate, $endDate]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function getRecent($limit = 5, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        $sql = "SELECT p.*, t.name as tenant_name 
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                JOIN tenants t ON l.tenant_id = t.id ";
        $params = [];
        // Role-based filtering
        if ($userId && !$user->isAdmin()) {
            $sql .= " WHERE (1=0";
            if ($user->isLandlord()) {
                $sql .= " OR l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE owner_id = ?))";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $sql .= " OR l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE manager_id = ?))";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $sql .= " OR l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE agent_id = ?))";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            $sql .= " OR l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE caretaker_user_id = ?))";
            $params[] = $userId;
            // Tenant: only their own payments
            if (isset($userData['role']) && $userData['role'] === 'tenant') {
                // Find tenant_id for this user
                $tenantModel = new \App\Models\Tenant();
                $tenant = $tenantModel->findByEmail($userData['email']);
                if ($tenant && isset($tenant['id'])) {
                    $sql .= " OR l.tenant_id = ?";
                    $params[] = $tenant['id'];
                }
            }
            $sql .= ")";
        }
        $sql .= " ORDER BY p.payment_date DESC, p.id DESC LIMIT ?";
        $params[] = $limit;
        return $this->query($sql, $params);
    }

    public function getTotalRevenue()
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM payments 
                WHERE YEAR(payment_date) = YEAR(CURRENT_DATE()) AND MONTH(payment_date) = MONTH(CURRENT_DATE())";
        $result = $this->query($sql);
        return $result[0]['total'] ?? 0;
    }

    public function getLastMonthRevenue()
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                FROM payments 
                WHERE YEAR(payment_date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH) AND MONTH(payment_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH)";
        $result = $this->query($sql);
        return $result[0]['total'] ?? 0;
    }

    public function getOutstandingBalance($userId = null)
    {
        try {
            $user = new User();
            
            // Only proceed with role check if userId is provided
            $isAdmin = false;
            $userData = null;
            if ($userId) {
                $userData = $user->find($userId);
                if (!$userData) {
                    return 0; // Return 0 if user not found
                }
                $isAdmin = ($userData['role'] === 'admin' || $userData['role'] === 'administrator');
            }
            
            // Calculate outstanding balance for rent and utilities separately
            $sql = "SELECT 
                    l.id as lease_id,
                    l.rent_amount,
                    l.start_date,
                    l.end_date,
                    u.id as unit_id,
                    t.id as tenant_id,
                    t.first_name,
                    t.last_name
                FROM leases l
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                JOIN tenants t ON l.tenant_id = t.id
                WHERE l.status = 'active'";

            $params = [];
            
            // Add role-based conditions only if user is not admin
            if ($userId && !$isAdmin) {
                $sql .= " AND (";
                $conditions = [];
                
                if ($userData['role'] === 'landlord') {
                    $conditions[] = "pr.owner_id = ?";
                    $params[] = $userId;
                }
                if ($userData['role'] === 'manager') {
                    $conditions[] = "pr.manager_id = ?";
                    $params[] = $userId;
                }
                if ($userData['role'] === 'agent') {
                    $conditions[] = "pr.agent_id = ?";
                    $params[] = $userId;
                }
                if ($userData['role'] === 'caretaker') {
                    $conditions[] = "pr.caretaker_user_id = ?";
                    $params[] = $userId;
                }
                
                if (empty($conditions)) {
                    $conditions[] = "1=0"; // No access if role doesn't match
                }
                
                $sql .= implode(" OR ", $conditions) . ")";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $leases = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $totalOutstanding = 0;
            
            foreach ($leases as $lease) {
                $monthlyRent = (float)($lease['rent_amount'] ?? 0);
                if ($monthlyRent <= 0) {
                    continue;
                }

                // Expected rent from lease start up to current month (inclusive)
                try {
                    $leaseStart = new \DateTime($lease['start_date']);
                } catch (\Exception $e) {
                    continue;
                }
                $startMonth = new \DateTime($leaseStart->format('Y-m-01'));
                $currentMonth = new \DateTime(date('Y-m-01'));
                if ($currentMonth < $startMonth) {
                    continue;
                }
                $monthsElapsed = ((int)$currentMonth->format('Y') - (int)$startMonth->format('Y')) * 12
                    + ((int)$currentMonth->format('n') - (int)$startMonth->format('n')) + 1;
                $expectedRent = $monthsElapsed * $monthlyRent;

                // All rent payments made for this lease
                $rentStmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) as total_paid
                    FROM payments
                    WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified')");
                $rentStmt->execute([$lease['lease_id']]);
                $rentPaidAmount = (float)($rentStmt->fetch(\PDO::FETCH_ASSOC)['total_paid'] ?? 0);

                $rentOutstanding = max(0.0, $expectedRent - $rentPaidAmount);
                $totalOutstanding += $rentOutstanding;
                
                // Calculate utility outstanding
                $utilityStmt = $this->db->prepare("
                    SELECT u.*, 
                           CASE 
                               WHEN u.is_metered = 1 THEN IFNULL(ur.cost, 0)
                               ELSE IFNULL(u.flat_rate, 0)
                           END as amount
                    FROM utilities u
                    LEFT JOIN (
                        SELECT ur1.*
                        FROM utility_readings ur1
                        INNER JOIN (
                            SELECT utility_id, MAX(id) as max_id
                            FROM utility_readings
                            GROUP BY utility_id
                        ) ur2 ON ur1.utility_id = ur2.utility_id AND ur1.id = ur2.max_id
                    ) ur ON u.id = ur.utility_id
                    WHERE u.unit_id = ?
                ");
                $utilityStmt->execute([$lease['unit_id']]);
                $utilities = $utilityStmt->fetchAll(\PDO::FETCH_ASSOC);
                
                foreach ($utilities as $utility) {
                    $utilityAmount = (float)($utility['amount'] ?? 0);
                    
                    // Get utility payments for this specific utility
                    $utilityPaymentStmt = $this->db->prepare("
                        SELECT SUM(amount) as total_paid
                        FROM payments 
                        WHERE lease_id = ? AND utility_id = ? AND payment_type = 'utility' AND status IN ('completed','verified')
                    ");
                    $utilityPaymentStmt->execute([$lease['lease_id'], $utility['id']]);
                    $utilityPaid = $utilityPaymentStmt->fetch(\PDO::FETCH_ASSOC);
                    $utilityPaidAmount = (float)($utilityPaid['total_paid'] ?? 0);
                    
                    $utilityOutstanding = max(0.0, $utilityAmount - $utilityPaidAmount);
                    $totalOutstanding += $utilityOutstanding;
                }
            }
            
            return $totalOutstanding;
            
        } catch (\Exception $e) {
            error_log("Error in getOutstandingBalance: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return 0; // Return 0 on error to prevent breaking the dashboard
        }
    }

    public function getOutstandingTenantsCount($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        $sql = "SELECT COUNT(DISTINCT l.tenant_id) as count
                FROM leases l
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                LEFT JOIN payments p ON l.id = p.lease_id 
                    AND YEAR(p.payment_date) = YEAR(CURRENT_DATE()) 
                    AND MONTH(p.payment_date) = MONTH(CURRENT_DATE())
                WHERE l.status = 'active'
                AND (p.id IS NULL OR p.amount < l.rent_amount)";
        $params = [];
        // Role-based filtering
        if ($userId && !$user->isAdmin()) {
            $roleFilter = [];
            if ($user->isLandlord()) {
                $roleFilter[] = "pr.owner_id = ?";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $roleFilter[] = "pr.manager_id = ?";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $roleFilter[] = "pr.agent_id = ?";
                $params[] = $userId;
            }
            if (isset($userData['role']) && $userData['role'] === 'caretaker') {
                $roleFilter[] = "pr.caretaker_user_id = ?";
                $params[] = $userId;
            }
            if (isset($userData['role']) && $userData['role'] === 'tenant') {
                $tenantModel = new \App\Models\Tenant();
                $tenant = $tenantModel->findByEmail($userData['email']);
                if ($tenant && isset($tenant['id'])) {
                    $roleFilter[] = "l.tenant_id = ?";
                    $params[] = $tenant['id'];
                }
            }
            if (!empty($roleFilter)) {
                $sql .= ' AND (' . implode(' OR ', $roleFilter) . ')';
            }
        }
        $result = $this->query($sql, $params);
        return $result[0]['count'] ?? 0;
    }

    public function getRevenueForPeriod($startDate, $endDate, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        $sql = "SELECT COALESCE(SUM(p.amount), 0) as total 
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                JOIN tenants t ON l.tenant_id = t.id ";
        $params = [];
        $where = [];
        $where[] = "p.payment_date BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        // Role-based filtering
        if ($userId && !$user->isAdmin()) {
            $roleFilter = [];
            if ($user->isLandlord()) {
                $roleFilter[] = "l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE owner_id = ?))";
                $params[] = $userId;
            }
            if ($user->isManager()) {
                $roleFilter[] = "l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE manager_id = ?))";
                $params[] = $userId;
            }
            if ($user->isAgent()) {
                $roleFilter[] = "l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE agent_id = ?))";
                $params[] = $userId;
            }
            // Caretaker assigned to property
            if ($user->isCaretaker()) {
                $roleFilter[] = "l.unit_id IN (SELECT id FROM units WHERE property_id IN (SELECT id FROM properties WHERE caretaker_user_id = ?))";
                $params[] = $userId;
            }
            if (isset($userData['role']) && $userData['role'] === 'tenant') {
                $tenantModel = new \App\Models\Tenant();
                $tenant = $tenantModel->findByEmail($userData['email']);
                if ($tenant && isset($tenant['id'])) {
                    $roleFilter[] = "l.tenant_id = ?";
                    $params[] = $tenant['id'];
                }
            }
            if (!empty($roleFilter)) {
                $where[] = '(' . implode(' OR ', $roleFilter) . ')';
            }
        }
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $result = $this->query($sql, $params);
        return $result[0]['total'] ?? 0;
    }

    public function getCollectionRate($startDate, $endDate)
    {
        $sql = "SELECT 
                    (COALESCE(SUM(p.amount), 0) / COALESCE(SUM(l.rent_amount), 0) * 100) as collection_rate
                FROM leases l
                LEFT JOIN payments p ON l.id = p.lease_id 
                    AND p.payment_date BETWEEN ? AND ?
                WHERE l.status = 'active'
                AND l.start_date <= ?";
        
        $result = $this->query($sql, [$startDate, $endDate, $endDate]);
        return round($result[0]['collection_rate'] ?? 0, 2);
    }

    public function getPaymentsForPeriod($startDate, $endDate)
    {
        $sql = "SELECT p.*,
                       t.name as tenant_name,
                       pr.name as property_name,
                       u.unit_number
                FROM payments p
                JOIN leases l ON p.lease_id = l.id
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE p.payment_date BETWEEN ? AND ?
                ORDER BY p.payment_date DESC, p.id DESC";
        
        return $this->query($sql, [$startDate, $endDate]);
    }

    public function getRevenueByProperty($startDate, $endDate)
    {
        $sql = "SELECT 
                    pr.name as property_name,
                    COALESCE(SUM(p.amount), 0) as total_revenue,
                    COUNT(DISTINCT l.id) as total_leases,
                    COUNT(DISTINCT p.id) as total_payments
                FROM properties pr
                LEFT JOIN units u ON pr.id = u.property_id
                LEFT JOIN leases l ON u.id = l.unit_id
                LEFT JOIN payments p ON l.id = p.lease_id 
                    AND p.payment_date BETWEEN ? AND ?
                GROUP BY pr.id, pr.name
                ORDER BY total_revenue DESC";
        
        return $this->query($sql, [$startDate, $endDate]);
    }

    public function getRevenueByType($startDate, $endDate)
    {
        $sql = "SELECT 
                    payment_type,
                    COALESCE(SUM(amount), 0) as total_amount,
                    COUNT(*) as count
                FROM payments
                WHERE payment_date BETWEEN ? AND ?
                GROUP BY payment_type
                ORDER BY total_amount DESC";
        
        return $this->query($sql, [$startDate, $endDate]);
    }

    public function getTotalDelinquent()
    {
        $sql = "SELECT 
                    COALESCE(SUM(l.rent_amount - COALESCE(p.paid_amount, 0)), 0) as total_delinquent
                FROM leases l
                LEFT JOIN (
                    SELECT lease_id, SUM(amount) as paid_amount
                    FROM payments
                    WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())
                    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                    GROUP BY lease_id
                ) p ON l.id = p.lease_id
                WHERE l.status = 'active'
                AND (p.paid_amount IS NULL OR p.paid_amount < l.rent_amount)";
        
        $result = $this->query($sql);
        return $result[0]['total_delinquent'] ?? 0;
    }

    public function getDelinquencyRate()
    {
        $sql = "SELECT 
                    (COUNT(CASE WHEN p.paid_amount IS NULL OR p.paid_amount < l.rent_amount THEN 1 END) * 100.0 / COUNT(*)) as delinquency_rate
                FROM leases l
                LEFT JOIN (
                    SELECT lease_id, SUM(amount) as paid_amount
                    FROM payments
                    WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())
                    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                    GROUP BY lease_id
                ) p ON l.id = p.lease_id
                WHERE l.status = 'active'";
        
        $result = $this->query($sql);
        return round($result[0]['delinquency_rate'] ?? 0, 2);
    }

    public function getDelinquentTenants()
    {
        $sql = "SELECT 
                    t.id,
                    t.name,
                    t.phone,
                    t.email,
                    l.rent_amount,
                    COALESCE(p.paid_amount, 0) as paid_amount,
                    (l.rent_amount - COALESCE(p.paid_amount, 0)) as outstanding_amount,
                    pr.name as property_name,
                    u.unit_number
                FROM leases l
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                LEFT JOIN (
                    SELECT lease_id, SUM(amount) as paid_amount
                    FROM payments
                    WHERE YEAR(payment_date) = YEAR(CURRENT_DATE())
                    AND MONTH(payment_date) = MONTH(CURRENT_DATE())
                    GROUP BY lease_id
                ) p ON l.id = p.lease_id
                WHERE l.status = 'active'
                AND (p.paid_amount IS NULL OR p.paid_amount < l.rent_amount)
                ORDER BY outstanding_amount DESC";
        
        return $this->query($sql);
    }

    public function getAgingReport()
    {
        $sql = "SELECT 
                    CASE 
                        WHEN DATEDIFF(CURRENT_DATE(), payment_date) <= 30 THEN '0-30 days'
                        WHEN DATEDIFF(CURRENT_DATE(), payment_date) <= 60 THEN '31-60 days'
                        WHEN DATEDIFF(CURRENT_DATE(), payment_date) <= 90 THEN '61-90 days'
                        ELSE 'Over 90 days'
                    END as aging_period,
                    COUNT(*) as count,
                    SUM(amount) as total_amount
                FROM payments
                WHERE payment_date <= CURRENT_DATE()
                GROUP BY aging_period
                ORDER BY 
                    CASE aging_period
                        WHEN '0-30 days' THEN 1
                        WHEN '31-60 days' THEN 2
                        WHEN '61-90 days' THEN 3
                        ELSE 4
                    END";
        
        return $this->query($sql);
    }

    public function getPaymentTrends($months = 12)
    {
        $sql = "SELECT 
                    DATE_FORMAT(payment_date, '%Y-%m') as month,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount,
                    COUNT(DISTINCT lease_id) as unique_tenants,
                    AVG(amount) as average_payment
                FROM payments
                WHERE payment_date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month ASC";
        
        return $this->query($sql, [$months]);
    }

    public function getTenantPaymentHistory()
    {
        $sql = "SELECT 
                    t.name as tenant_name,
                    COUNT(p.id) as total_payments,
                    AVG(p.amount) as average_payment,
                    MAX(p.payment_date) as last_payment_date,
                    SUM(CASE WHEN DATEDIFF(p.payment_date, l.payment_day) > 0 THEN 1 ELSE 0 END) as late_payments
                FROM tenants t
                JOIN leases l ON t.id = l.tenant_id
                LEFT JOIN payments p ON l.id = p.lease_id
                GROUP BY t.id, t.name
                ORDER BY total_payments DESC";
        
        return $this->query($sql);
    }

    public function userHasAccessToLease($leaseId, $userId)
    {
        $user = new User();
        $user->find($userId);
        
        // Admin has access to all leases
        if ($user->isAdmin()) {
            return true;
        }
        
        $sql = "SELECT 1
                FROM leases l
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE l.id = ?
                AND (1=0";
        
        $params = [$leaseId];
        
        if ($user->isLandlord()) {
            $sql .= " OR pr.owner_id = ?";
            $params[] = $userId;
        }
        if ($user->isManager()) {
            $sql .= " OR pr.manager_id = ?";
            $params[] = $userId;
        }
        if ($user->isAgent()) {
            $sql .= " OR pr.agent_id = ?";
            $params[] = $userId;
        }
        // Caretaker
        $sql .= " OR pr.caretaker_user_id = ?";
        $params[] = $userId;
        
        $sql .= ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO subscription_payments (user_id, subscription_id, amount, payment_method, status) 
                VALUES (:user_id, :subscription_id, :amount, :payment_method, :status)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'subscription_id' => $data['subscription_id'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'status' => $data['status']
        ]);

        return $this->db->lastInsertId();
    }

    public function updateStatus($paymentId, $status)
    {
        $sql = "UPDATE subscription_payments SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'id' => $paymentId
        ]);
    }

    public function findById($id)
    {
        $sql = "SELECT * FROM subscription_payments WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function logPayment($paymentId, $logType, $logData)
    {
        $sql = "INSERT INTO subscription_payment_logs (payment_id, log_type, log_data) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$paymentId, $logType, $logData]);
    }

    public function getUserPayments($userId)
    {
        $sql = "SELECT p.*, s.plan_type, m.mpesa_receipt_number, m.transaction_date 
                FROM subscription_payments p 
                LEFT JOIN subscriptions s ON p.subscription_id = s.id 
                LEFT JOIN mpesa_transactions m ON m.payment_id = p.id 
                WHERE p.user_id = ? 
                ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAllPayments()
    {
        $sql = "SELECT sp.*, 
                u.name as user_name,
                u.email as user_email,
                s.plan_type,
                s.status as subscription_status,
                mt.mpesa_receipt_number,
                mt.phone_number,
                mt.transaction_date as mpesa_transaction_date,
                mt.result_code as mpesa_result_code,
                mt.result_description as mpesa_result_description,
                mmp.id as manual_mpesa_id,
                mmp.phone_number as manual_phone_number,
                mmp.transaction_code as manual_transaction_code,
                mmp.verification_status as manual_verification_status
                FROM subscription_payments sp
                LEFT JOIN users u ON sp.user_id = u.id
                LEFT JOIN subscriptions s ON sp.subscription_id = s.id
                LEFT JOIN mpesa_transactions mt ON sp.id = mt.payment_id
                LEFT JOIN manual_mpesa_payments mmp ON sp.id = mmp.payment_id
                ORDER BY sp.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPaymentDetails($id)
    {
        $sql = "SELECT sp.*, 
                u.name as user_name,
                u.email as user_email,
                s.plan_type,
                s.status as subscription_status,
                mt.mpesa_receipt_number,
                mt.phone_number,
                mt.transaction_date as mpesa_transaction_date,
                mt.result_code as mpesa_result_code,
                mt.result_description as mpesa_result_description,
                mmp.id as manual_mpesa_id,
                mmp.phone_number as manual_phone_number,
                mmp.transaction_code as manual_transaction_code,
                mmp.verification_status as manual_verification_status,
                spl.log_type,
                spl.log_data,
                spl.created_at as log_created_at
                FROM subscription_payments sp
                LEFT JOIN users u ON sp.user_id = u.id
                LEFT JOIN subscriptions s ON sp.subscription_id = s.id
                LEFT JOIN mpesa_transactions mt ON sp.id = mt.payment_id
                LEFT JOIN manual_mpesa_payments mmp ON sp.id = mmp.payment_id
                LEFT JOIN subscription_payment_logs spl ON sp.id = spl.payment_id
                WHERE sp.id = ?
                ORDER BY spl.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$payment) {
            return null;
        }
        
        // Get all logs for this payment
        $sql = "SELECT * FROM subscription_payment_logs 
                WHERE payment_id = ? 
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $payment['logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $payment;
    }

    public function deleteByUserId($userId)
    {
        try {
            // Delete mpesa transactions first (foreign key constraint)
            $sql = "DELETE FROM mpesa_transactions WHERE payment_id IN (
                SELECT id FROM {$this->table} WHERE user_id = ?
            )";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            // Now delete the payments
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (\Exception $e) {
            error_log("Error deleting payments for user {$userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a rent payment (for property/tenant rent, not subscription)
     */
    public function createRentPayment($data)
    {
        $sql = "INSERT INTO payments (lease_id, amount, payment_date, applies_to_month, payment_type, payment_method, reference_number, notes, status) 
                VALUES (:lease_id, :amount, :payment_date, :applies_to_month, :payment_type, :payment_method, :reference_number, :notes, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'lease_id' => $data['lease_id'],
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'applies_to_month' => $data['applies_to_month'] ?? null,
            'payment_type' => $data['payment_type'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'completed'
        ]);
        $id = (int)$this->db->lastInsertId();
        // Auto-post to ledger + ensure invoices (including future months if paid in advance)
        $this->postPaymentToLedgerIfNeeded($id, $data);
        if (($data['payment_type'] ?? '') === 'rent') {
            $this->ensureInvoicesForAdvanceIfNeeded((int)$data['lease_id'], isset($data['user_id']) ? (int)$data['user_id'] : null);
            try {
                $stmt = $this->db->prepare("SELECT tenant_id FROM leases WHERE id = ? LIMIT 1");
                $stmt->execute([(int)$data['lease_id']]);
                $tenantId = (int)($stmt->fetch(\PDO::FETCH_ASSOC)['tenant_id'] ?? 0);
                if ($tenantId > 0) {
                    $inv = new Invoice();
                    $inv->updateStatusesForMonth($data['payment_date'] ?? date('Y-m-d'));
                    // Also update for this specific tenant/month for deterministic results
                    $inv->updateStatusForTenantMonth($tenantId, $data['payment_date'] ?? date('Y-m-d'));
                }
            } catch (\Exception $e) {
                error_log('Auto-invoice status update failed: ' . $e->getMessage());
            }
        }
        return $id;
    }

    public function createUtilityPayment($data)
    {
        $sql = "INSERT INTO payments (lease_id, utility_id, amount, payment_date, applies_to_month, payment_type, payment_method, reference_number, notes, status) 
                VALUES (:lease_id, :utility_id, :amount, :payment_date, :applies_to_month, :payment_type, :payment_method, :reference_number, :notes, :status)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'lease_id' => $data['lease_id'],
            'utility_id' => $data['utility_id'],
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'applies_to_month' => $data['applies_to_month'] ?? null,
            'payment_type' => $data['payment_type'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $data['status'] ?? 'completed'
        ]);
        $id = (int)$this->db->lastInsertId();
        // Auto-post to ledger
        $this->postPaymentToLedgerIfNeeded($id, $data);
        return $id;
    }

    /**
     * Get the due amount for a lease for the current month
     */
    public function getDueAmountForLease($leaseId) {
        $sql = "SELECT l.rent_amount - COALESCE(SUM(p.amount), 0) as due
                FROM leases l
                LEFT JOIN payments p ON l.id = p.lease_id
                    AND YEAR(p.payment_date) = YEAR(CURRENT_DATE())
                    AND MONTH(p.payment_date) = MONTH(CURRENT_DATE())
                WHERE l.id = ?
                GROUP BY l.rent_amount";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$leaseId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return max(0, $row['due'] ?? 0);
    }

    /**
     * Update a rent payment in the payments table
     */
    public function updatePayment($id, $data)
    {
        $sql = "UPDATE payments SET amount = :amount, payment_date = :payment_date, payment_method = :payment_method, reference_number = :reference_number, status = :status, notes = :notes WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $data['reference_number'],
            'status' => $data['status'],
            'notes' => $data['notes'],
            'id' => $id
        ]);
    }

    public function getTenantRentPayments($tenantId)
    {
        $sql = "SELECT p.*, 
                       mmp.phone_number, 
                       mmp.transaction_code,
                       mmp.verification_status as mpesa_status
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id
                WHERE l.tenant_id = ? AND l.status = 'active' AND p.payment_type = 'rent' 
                ORDER BY p.payment_date DESC, p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTenantAllPayments($tenantId)
    {
        $sql = "SELECT p.*, 
                       mmp.phone_number, 
                       mmp.transaction_code,
                       mmp.verification_status as mpesa_status,
                       u.utility_type
                FROM payments p 
                JOIN leases l ON p.lease_id = l.id 
                LEFT JOIN manual_mpesa_payments mmp ON p.id = mmp.payment_id
                LEFT JOIN utilities u ON p.utility_id = u.id
                WHERE l.tenant_id = ? AND l.status = 'active' 
                ORDER BY p.payment_date DESC, p.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getByTenant($tenantId)
    {
        return $this->getTenantAllPayments($tenantId);
    }

    public function getTenantOverdueRent($tenantId)
    {
        // Compute current-month rent balance for the active lease using monthly allocation logic
        $leaseStmt = $this->db->prepare("SELECT * FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([$tenantId]);
        $lease = $leaseStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$lease) {
            return [];
        }

        $rent = isset($lease['rent_amount']) ? (float)$lease['rent_amount'] : 0.0;
        if ($rent <= 0) {
            $lease['overdue_amount'] = 0.0;
            return [$lease];
        }

        $targetYm = date('Y-m');
        $targetKey = $targetYm . '-01';

        $tagStmt = $this->db->prepare(
            "SELECT DATE_FORMAT(applies_to_month, '%Y-%m-01') AS m, COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
            . "FROM payments\n"
            . "WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') AND applies_to_month IS NOT NULL\n"
            . "GROUP BY DATE_FORMAT(applies_to_month, '%Y-%m-01')"
        );
        $tagStmt->execute([(int)$lease['id']]);
        $tagRows = $tagStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $tagged = [];
        foreach ($tagRows as $r) {
            $k = (string)($r['m'] ?? '');
            if ($k !== '') $tagged[$k] = (float)($r['s'] ?? 0);
        }
        $untagStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s FROM payments\n"
            . "WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') AND applies_to_month IS NULL"
        );
        $untagStmt->execute([(int)$lease['id']]);
        $untaggedTotal = (float)($untagStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

        try {
            $leaseStart = new \DateTime($lease['start_date']);
        } catch (\Exception $e) {
            $lease['overdue_amount'] = max(0.0, $rent);
            return [$lease];
        }
        $startMonth = new \DateTime($leaseStart->format('Y-m-01'));
        $selectedMonth = new \DateTime(date('Y-m-01'));

        $months = [];
        $cursor = clone $startMonth;
        while ($cursor <= $selectedMonth) {
            $k = $cursor->format('Y-m-01');
            $months[$k] = $rent;
            $cursor->modify('+1 month');
        }

        $excess = 0.0;
        foreach ($months as $k => $outstanding) {
            $paid = (float)($tagged[$k] ?? 0);
            $apply = min($outstanding, max(0.0, $paid));
            $months[$k] = max(0.0, $outstanding - $apply);
            $excess += max(0.0, $paid - $apply);
        }

        $remaining = max(0.0, $untaggedTotal) + $excess;
        foreach ($months as $k => $outstanding) {
            if ($remaining <= 0) break;
            $apply = min($outstanding, $remaining);
            $months[$k] = max(0.0, $outstanding - $apply);
            $remaining -= $apply;
        }

        $lease['overdue_amount'] = round((float)($months[$targetKey] ?? $rent), 2);
        return [$lease];
    }

    public function getTenantMissedRentMonths($tenantId)
    {
        $leaseStmt = $this->db->prepare("SELECT * FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([$tenantId]);
        $lease = $leaseStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$lease) {
            return [];
        }

        $rentAmount = isset($lease['rent_amount']) ? (float)$lease['rent_amount'] : 0.0;
        if ($rentAmount <= 0) {
            return [];
        }

        try {
            $leaseStart = new \DateTime($lease['start_date']);
        } catch (\Exception $e) {
            return [];
        }

        $startMonth = new \DateTime($leaseStart->format('Y-m-01'));
        $today = new \DateTime();
        $currentMonth = new \DateTime($today->format('Y-m-01'));
        if (!empty($lease['end_date'])) {
            try {
                $leaseEnd = new \DateTime($lease['end_date']);
                $leaseEndMonth = new \DateTime($leaseEnd->format('Y-m-01'));
                if ($leaseEndMonth < $currentMonth) {
                    $currentMonth = $leaseEndMonth;
                }
            } catch (\Exception $e) {
            }
        }

        $months = [];
        $cursor = clone $startMonth;
        while ($cursor <= $currentMonth) {
            $months[] = [
                'year' => (int)$cursor->format('Y'),
                'month' => (int)$cursor->format('n'),
                'label' => $cursor->format('F Y'),
                'outstanding' => $rentAmount,
            ];
            $cursor->modify('+1 month');
        }

        if (empty($months)) {
            return [];
        }

        $tagStmt = $this->db->prepare(
            "SELECT DATE_FORMAT(applies_to_month, '%Y-%m-01') AS m, COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
            . "FROM payments\n"
            . "WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') AND applies_to_month IS NOT NULL\n"
            . "GROUP BY DATE_FORMAT(applies_to_month, '%Y-%m-01')"
        );
        $tagStmt->execute([(int)$lease['id']]);
        $tagRows = $tagStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $tagged = [];
        foreach ($tagRows as $r) {
            $k = (string)($r['m'] ?? '');
            if ($k !== '') $tagged[$k] = (float)($r['s'] ?? 0);
        }

        $untagStmt = $this->db->prepare(
            "SELECT amount FROM payments\n"
            . "WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') AND applies_to_month IS NULL\n"
            . "  AND amount > 0\n"
            . "ORDER BY payment_date ASC, id ASC"
        );
        $untagStmt->execute([(int)$lease['id']]);
        $untagRows = $untagStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $untaggedTotal = 0.0;
        foreach ($untagRows as $row) {
            $untaggedTotal += isset($row['amount']) ? (float)$row['amount'] : 0.0;
        }

        $excess = 0.0;
        for ($i = 0; $i < count($months); $i++) {
            $k = sprintf('%04d-%02d-01', (int)$months[$i]['year'], (int)$months[$i]['month']);
            $paid = (float)($tagged[$k] ?? 0);
            $apply = min((float)$months[$i]['outstanding'], max(0.0, $paid));
            $months[$i]['outstanding'] = max(0.0, (float)$months[$i]['outstanding'] - $apply);
            $excess += max(0.0, $paid - $apply);
        }

        $remainingPaid = max(0.0, $untaggedTotal) + $excess;
        for ($i = 0; $i < count($months); $i++) {
            if ($remainingPaid <= 0) break;
            $apply = min((float)$months[$i]['outstanding'], $remainingPaid);
            $months[$i]['outstanding'] = max(0.0, (float)$months[$i]['outstanding'] - $apply);
            $remainingPaid -= $apply;
        }

        $missed = [];
        foreach ($months as $m) {
            if ($m['outstanding'] > 0.0001) {
                $missed[] = [
                    'year' => $m['year'],
                    'month' => $m['month'],
                    'label' => $m['label'],
                    'amount' => $m['outstanding'],
                ];
            }
        }

        return $missed;
    }

    public function getTenantRentCoverage($tenantId)
    {
        $leaseStmt = $this->db->prepare("SELECT * FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([$tenantId]);
        $lease = $leaseStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$lease) {
            return [];
        }

        $rentAmount = isset($lease['rent_amount']) ? (float)$lease['rent_amount'] : 0.0;
        if ($rentAmount <= 0.0) {
            return [];
        }

        try {
            $leaseStart = new \DateTime($lease['start_date']);
        } catch (\Exception $e) {
            return [];
        }

        $startMonth = new \DateTime($leaseStart->format('Y-m-01'));
        $today = new \DateTime();
        $currentMonth = new \DateTime($today->format('Y-m-01'));

        $tagStmt = $this->db->prepare(
            "SELECT DATE_FORMAT(applies_to_month, '%Y-%m-01') AS m, COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
            . "FROM payments\n"
            . "WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') AND applies_to_month IS NOT NULL\n"
            . "GROUP BY DATE_FORMAT(applies_to_month, '%Y-%m-01')"
        );
        $tagStmt->execute([(int)$lease['id']]);
        $tagRows = $tagStmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $tagged = [];
        foreach ($tagRows as $r) {
            $k = (string)($r['m'] ?? '');
            if ($k !== '') $tagged[$k] = (float)($r['s'] ?? 0);
        }
        $untagStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s FROM payments\n"
            . "WHERE lease_id = ? AND payment_type = 'rent' AND status IN ('completed','verified') AND applies_to_month IS NULL"
        );
        $untagStmt->execute([(int)$lease['id']]);
        $untaggedTotal = (float)($untagStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

        // Build months from lease start to current month
        $monthKeys = [];
        $cursor = clone $startMonth;
        while ($cursor <= $currentMonth) {
            $monthKeys[] = $cursor->format('Y-m-01');
            $cursor->modify('+1 month');
        }

        $outstandingByMonth = [];
        $excess = 0.0;
        foreach ($monthKeys as $k) {
            $out = $rentAmount;
            $paid = (float)($tagged[$k] ?? 0);
            $apply = min($out, max(0.0, $paid));
            $out -= $apply;
            $excess += max(0.0, $paid - $apply);
            $outstandingByMonth[$k] = max(0.0, $out);
        }

        $remaining = max(0.0, $untaggedTotal) + $excess;
        foreach ($monthKeys as $k) {
            if ($remaining <= 0) break;
            $apply = min((float)$outstandingByMonth[$k], $remaining);
            $outstandingByMonth[$k] = max(0.0, (float)$outstandingByMonth[$k] - $apply);
            $remaining -= $apply;
        }

        $nextDue = clone $startMonth;
        $monthsPaid = 0;
        foreach ($monthKeys as $idx => $k) {
            if (((float)$outstandingByMonth[$k]) > 0.0001) {
                $nextDue = new \DateTime($k);
                $monthsPaid = $idx;
                break;
            }
            // all months covered so far
            $monthsPaid = $idx + 1;
            $nextDue = new \DateTime($k);
            $nextDue->modify('+1 month');
        }

        $dueNow = ($nextDue <= $currentMonth);
        $prepaidMonths = 0;
        if (!$dueNow && $remaining > 0.0) {
            $prepaidMonths = (int)floor($remaining / $rentAmount + 1e-6);
        }

        return [
            'months_paid' => (int)$monthsPaid,
            'prepaid_months' => max(0, (int)$prepaidMonths),
            'next_due_year' => (int)$nextDue->format('Y'),
            'next_due_month' => (int)$nextDue->format('n'),
            'next_due_label' => $nextDue->format('F Y'),
            'due_now' => $dueNow,
        ];
    }

    public function getTenantMaintenanceOutstanding($tenantId)
    {
        $leaseStmt = $this->db->prepare("SELECT * FROM leases WHERE tenant_id = ? AND status = 'active' LIMIT 1");
        $leaseStmt->execute([(int)$tenantId]);
        $lease = $leaseStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$lease) {
            return 0.0;
        }

        $today = date('Y-m-d');
        $monthStart = date('Y-m-01', strtotime($today));
        $monthEnd = date('Y-m-t', strtotime($today));

        try {
            $maintChargesStmt = $this->db->prepare(
                "SELECT COALESCE(SUM(ABS(amount)),0) AS s\n"
                . "FROM payments\n"
                . "WHERE lease_id = ?\n"
                . "  AND payment_type = 'other'\n"
                . "  AND amount < 0\n"
                . "  AND notes LIKE ?\n"
                . "  AND status IN ('completed','verified')\n"
                . "  AND payment_date BETWEEN ? AND ?"
            );
            $maintChargesStmt->execute([(int)$lease['id'], '%MAINT-%', $monthStart, $monthEnd]);
            $charged = (float)($maintChargesStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            $maintPaidStmt = $this->db->prepare(
                "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) AS s\n"
                . "FROM payments\n"
                . "WHERE lease_id = ?\n"
                . "  AND payment_type = 'other'\n"
                . "  AND status IN ('completed','verified')\n"
                . "  AND (\n"
                . "        (applies_to_month IS NOT NULL AND applies_to_month BETWEEN ? AND ?)\n"
                . "     OR (applies_to_month IS NULL AND payment_date BETWEEN ? AND ?)\n"
                . "  )\n"
                . "  AND (notes LIKE 'Maintenance payment:%' OR notes LIKE '%MAINT-%')"
            );
            $maintPaidStmt->execute([(int)$lease['id'], $monthStart, $monthEnd, $monthStart, $monthEnd]);
            $paid = (float)($maintPaidStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);

            return round(max(0.0, $charged - $paid), 2);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Delete a payment by ID
     */
    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            error_log("Error deleting payment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database connection for external use
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Get attachments for this payment
     */
    public function getAttachments($paymentId = null)
    {
        $id = $paymentId ?: $this->id;
        if (!$id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'payment' 
            AND entity_id = ? 
            AND file_type = 'attachment'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$id]);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Add full URL to each file
        foreach ($files as &$file) {
            $file['url'] = BASE_URL . '/' . $file['upload_path'];
        }
        
        return $files;
    }

    /**
     * Get all files for this payment
     */
    public function getAllFiles($paymentId = null)
    {
        $id = $paymentId ?: $this->id;
        if (!$id) return [];
        
        $stmt = $this->db->prepare("
            SELECT * FROM file_uploads 
            WHERE entity_type = 'payment' 
            AND entity_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$id]);
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Add full URL to each file
        foreach ($files as &$file) {
            $file['url'] = BASE_URL . '/' . $file['upload_path'];
        }
        
        return $files;
    }

    /**
     * Get revenue by date range for reports
     */
    public function getRevenueByDateRange($startDate, $endDate, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        DATE(payment_date) as date,
                        SUM(amount) as total_amount,
                        COUNT(*) as payment_count
                    FROM payments
                    WHERE payment_date BETWEEN ? AND ?
                    AND status IN ('completed', 'verified')
                    GROUP BY DATE(payment_date)
                    ORDER BY date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
        } else {
            // For regular users - filter by their properties
            $sql = "SELECT 
                        DATE(p.payment_date) as date,
                        SUM(p.amount) as total_amount,
                        COUNT(*) as payment_count
                    FROM payments p
                    INNER JOIN leases l ON p.lease_id = l.id
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties pr ON u.property_id = pr.id
                    WHERE p.payment_date BETWEEN ? AND ?
                    AND p.status IN ('completed', 'verified')
                    AND (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)
                    GROUP BY DATE(p.payment_date)
                    ORDER BY date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$startDate, $endDate, $userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recent payments for reports
     */
    public function getRecentPayments($userId = null, $limit = 10)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        p.*,
                        t.name as tenant_name,
                        pr.name as property_name,
                        u.unit_number
                    FROM payments p
                    LEFT JOIN leases l ON p.lease_id = l.id
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties pr ON u.property_id = pr.id
                    WHERE p.status IN ('completed', 'verified')
                    ORDER BY p.payment_date DESC, p.id DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
        } else {
            // For regular users
            $sql = "SELECT 
                        p.*,
                        t.name as tenant_name,
                        pr.name as property_name,
                        u.unit_number
                    FROM payments p
                    LEFT JOIN leases l ON p.lease_id = l.id
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties pr ON u.property_id = pr.id
                    WHERE p.status IN ('completed', 'verified')
                    AND (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)
                    ORDER BY p.payment_date DESC, p.id DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId, $limit]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get tenant payment statistics for reports
     */
    public function getTenantPaymentStats($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        t.id,
                        t.name as tenant_name,
                        COUNT(p.id) as total_payments,
                        SUM(p.amount) as total_paid,
                        MAX(p.payment_date) as last_payment_date
                    FROM tenants t
                    LEFT JOIN leases l ON t.id = l.tenant_id
                    LEFT JOIN payments p ON l.id = p.lease_id AND p.status IN ('completed', 'verified')
                    GROUP BY t.id, t.name
                    ORDER BY total_paid DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        t.id,
                        t.name as tenant_name,
                        COUNT(p.id) as total_payments,
                        SUM(p.amount) as total_paid,
                        MAX(p.payment_date) as last_payment_date
                    FROM tenants t
                    LEFT JOIN leases l ON t.id = l.tenant_id
                    LEFT JOIN payments p ON l.id = p.lease_id AND p.status IN ('completed', 'verified')
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties pr ON u.property_id = pr.id
                    WHERE (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)
                    GROUP BY t.id, t.name
                    ORDER BY total_paid DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get outstanding payments for reports
     */
    public function getOutstandingPayments($userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        t.name as tenant_name,
                        t.email as tenant_email,
                        t.phone as tenant_phone,
                        p.name as property_name,
                        u.unit_number,
                        l.rent_amount,
                        COALESCE(SUM(pay.amount), 0) as total_paid,
                        (l.rent_amount - COALESCE(SUM(pay.amount), 0)) as balance_due,
                        l.start_date,
                        l.end_date
                    FROM leases l
                    INNER JOIN tenants t ON l.tenant_id = t.id
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id
                    LEFT JOIN payments pay ON l.id = pay.lease_id 
                        AND pay.status IN ('completed', 'verified')
                        AND MONTH(pay.payment_date) = MONTH(CURRENT_DATE())
                        AND YEAR(pay.payment_date) = YEAR(CURRENT_DATE())
                    WHERE l.status = 'active'
                    GROUP BY l.id, t.name, t.email, t.phone, p.name, u.unit_number, l.rent_amount, l.start_date, l.end_date
                    HAVING balance_due > 0
                    ORDER BY balance_due DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } else {
            // For regular users
            $sql = "SELECT 
                        t.name as tenant_name,
                        t.email as tenant_email,
                        t.phone as tenant_phone,
                        p.name as property_name,
                        u.unit_number,
                        l.rent_amount,
                        COALESCE(SUM(pay.amount), 0) as total_paid,
                        (l.rent_amount - COALESCE(SUM(pay.amount), 0)) as balance_due,
                        l.start_date,
                        l.end_date
                    FROM leases l
                    INNER JOIN tenants t ON l.tenant_id = t.id
                    INNER JOIN units u ON l.unit_id = u.id
                    INNER JOIN properties p ON u.property_id = p.id
                    LEFT JOIN payments pay ON l.id = pay.lease_id 
                        AND pay.status IN ('completed', 'verified')
                        AND MONTH(pay.payment_date) = MONTH(CURRENT_DATE())
                        AND YEAR(pay.payment_date) = YEAR(CURRENT_DATE())
                    WHERE l.status = 'active'
                    AND (p.owner_id = ? OR p.manager_id = ? OR p.agent_id = ? OR p.caretaker_user_id = ?)
                    GROUP BY l.id, t.name, t.email, t.phone, p.name, u.unit_number, l.rent_amount, l.start_date, l.end_date
                    HAVING balance_due > 0
                    ORDER BY balance_due DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get payment history for reports
     */
    public function getPaymentHistory($userId = null, $limit = 100)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        // For admin users
        if ($userData['role'] === 'admin') {
            $sql = "SELECT 
                        p.*,
                        t.name as tenant_name,
                        pr.name as property_name,
                        u.unit_number,
                        l.rent_amount
                    FROM payments p
                    LEFT JOIN leases l ON p.lease_id = l.id
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties pr ON u.property_id = pr.id
                    ORDER BY p.payment_date DESC, p.id DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
        } else {
            // For regular users
            $sql = "SELECT 
                        p.*,
                        t.name as tenant_name,
                        pr.name as property_name,
                        u.unit_number,
                        l.rent_amount
                    FROM payments p
                    LEFT JOIN leases l ON p.lease_id = l.id
                    LEFT JOIN tenants t ON l.tenant_id = t.id
                    LEFT JOIN units u ON l.unit_id = u.id
                    LEFT JOIN properties pr ON u.property_id = pr.id
                    WHERE (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)
                    ORDER BY p.payment_date DESC, p.id DESC
                    LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $userId, $limit]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get property revenue with outstanding balance for financial reports
     */
    public function getPropertyRevenueForReport($startDate, $endDate, $userId = null)
    {
        $user = new User();
        $userData = $user->find($userId);
        
        if (!$userData) {
            return [];
        }

        $isAdmin = ($userData['role'] ?? '') === 'admin' || ($userData['role'] ?? '') === 'administrator';

        $sql = "SELECT
                    pr.id,
                    pr.name,
                    COALESCE(SUM(CASE
                        WHEN p.status IN ('completed', 'verified')
                        AND p.payment_date BETWEEN ? AND ?
                        THEN p.amount
                        ELSE 0
                    END), 0) as revenue
                FROM properties pr
                LEFT JOIN units u ON pr.id = u.property_id
                LEFT JOIN leases l ON u.id = l.unit_id
                LEFT JOIN payments p ON l.id = p.lease_id";
        $params = [$startDate, $endDate];
        if (!$isAdmin) {
            $sql .= " WHERE (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)";
            $params[] = $userId; $params[] = $userId; $params[] = $userId; $params[] = $userId;
        }
        $sql .= " GROUP BY pr.id, pr.name ORDER BY revenue DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Compute outstanding and collection rate consistently
        $rentSumStmt = $this->db->prepare("SELECT COALESCE(SUM(l.rent_amount),0) AS s
            FROM leases l
            JOIN units u ON l.unit_id = u.id
            WHERE l.status='active' AND u.property_id = ?");
        $paidThisMonthStmt = $this->db->prepare("SELECT COALESCE(SUM(p.amount),0) AS s
            FROM payments p
            JOIN leases l ON p.lease_id = l.id
            JOIN units u ON l.unit_id = u.id
            WHERE u.property_id = ?
              AND p.status IN ('completed','verified')
              AND MONTH(p.payment_date) = MONTH(CURRENT_DATE())
              AND YEAR(p.payment_date) = YEAR(CURRENT_DATE())");

        foreach ($rows as &$r) {
            $pid = (int)($r['id'] ?? 0);
            $r['outstanding'] = $pid > 0 ? $this->getOutstandingBalanceForProperty($pid, $userId) : 0.0;
            $rentSumStmt->execute([$pid]);
            $activeRent = (float)($rentSumStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
            $paidThisMonthStmt->execute([$pid]);
            $paidThisMonth = (float)($paidThisMonthStmt->fetch(\PDO::FETCH_ASSOC)['s'] ?? 0);
            $r['collection_rate'] = $activeRent > 0 ? (($paidThisMonth / $activeRent) * 100.0) : 0.0;
        }

        // Keep only rows that have something meaningful
        $rows = array_values(array_filter($rows, function($r){
            return ((float)($r['revenue'] ?? 0) > 0.0001) || ((float)($r['outstanding'] ?? 0) > 0.0001);
        }));

        return $rows;
    }
} 