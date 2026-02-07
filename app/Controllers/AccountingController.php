<?php

namespace App\Controllers;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\User;

class AccountingController
{
    private $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    public function index()
    {
        // Simple landing redirect to ledger
        header('Location: ' . BASE_URL . '/accounting/ledger');
        exit;
    }

    public function accounts()
    {
        $accountModel = new Account();
        $accounts = $accountModel->getAll();
        require 'views/accounting/accounts.php';
    }

    public function storeAccount()
    {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') throw new \Exception('Invalid request');
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) throw new \Exception('Invalid CSRF token');
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $type = trim($_POST['type'] ?? '');
            if ($code === '' || $name === '' || !in_array($type, ['asset','liability','equity','revenue','expense'])) {
                throw new \Exception('Please provide code, name, and valid type');
            }
            $acc = new Account();
            $stmt = $acc->getDb()->prepare("INSERT INTO accounts (code,name,type) VALUES (?,?,?)");
            $stmt->execute([$code,$name,$type]);
            $_SESSION['flash_message'] = 'Account created';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: ' . BASE_URL . '/accounting/accounts');
        exit;
    }

    public function ledger()
    {
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $ledger = new LedgerEntry();
        $rows = $ledger->getGeneralLedger($this->userId, $start ?: null, $end ?: null);
        require 'views/accounting/ledger.php';
    }

    public function trialBalance()
    {
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $ledger = new LedgerEntry();
        $rows = $ledger->getTrialBalance($this->userId, $start ?: null, $end ?: null);
        require 'views/accounting/trial_balance.php';
    }

    public function balanceSheet()
    {
        $end = $_GET['end'] ?? date('Y-m-d');
        $ledger = new LedgerEntry();
        $assets = $ledger->getBalancesByType($this->userId, 'asset', null, $end);
        $liabilities = $ledger->getBalancesByType($this->userId, 'liability', null, $end);
        $equity = $ledger->getBalancesByType($this->userId, 'equity', null, $end);
        require 'views/accounting/balance_sheet.php';
    }

    public function profitLoss()
    {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-d');
        $ledger = new LedgerEntry();
        $revenue = $ledger->getBalancesByType($this->userId, 'revenue', $start, $end);
        $expenses = $ledger->getBalancesByType($this->userId, 'expense', $start, $end);
        require 'views/accounting/profit_loss.php';
    }

    public function statements()
    {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-d');

        $user = new User();
        $userData = $this->userId ? $user->find($this->userId) : null;
        $isAdmin = isset($userData['role']) && in_array($userData['role'], ['admin', 'administrator'], true);

        $paymentModel = new Payment();
        $expenseModel = new Expense();

        $transactions = [];

        // Money in: completed/verified payments within date range
        $paySql = "SELECT p.id,
                          p.payment_date AS txn_date,
                          p.amount,
                          p.payment_type,
                          p.payment_method,
                          p.reference_number,
                          p.status,
                          t.name AS tenant_name,
                          u.unit_number,
                          pr.name AS property_name
                   FROM payments p
                   JOIN leases l ON p.lease_id = l.id
                   JOIN tenants t ON l.tenant_id = t.id
                   JOIN units u ON l.unit_id = u.id
                   JOIN properties pr ON u.property_id = pr.id
                   WHERE p.status IN ('completed','verified')
                     AND p.payment_date BETWEEN ? AND ?";
        $payParams = [$start, $end];
        if ($this->userId && !$isAdmin) {
            $paySql .= " AND (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)";
            $payParams[] = $this->userId;
            $payParams[] = $this->userId;
            $payParams[] = $this->userId;
            $payParams[] = $this->userId;
        }
        $paySql .= " ORDER BY p.payment_date ASC, p.id ASC";
        $payments = $paymentModel->query($paySql, $payParams) ?: [];

        foreach ($payments as $p) {
            $transactions[] = [
                'date' => $p['txn_date'] ?? null,
                'direction' => 'in',
                'source' => 'payment',
                'reference' => !empty($p['reference_number']) ? (string)$p['reference_number'] : ('PAY-' . (int)($p['id'] ?? 0)),
                'category' => ucfirst((string)($p['payment_type'] ?? 'payment')),
                'description' => trim((string)($p['tenant_name'] ?? '') . ' | ' . (string)($p['property_name'] ?? '') . ' | Unit ' . (string)($p['unit_number'] ?? '')),
                'amount' => (float)($p['amount'] ?? 0),
                'id' => (int)($p['id'] ?? 0),
            ];
        }

        // Money out: expenses within date range
        $expSql = "SELECT e.id,
                          e.expense_date AS txn_date,
                          e.amount,
                          e.category,
                          e.payment_method,
                          e.source_of_funds,
                          e.notes,
                          p.name AS property_name
                   FROM expenses e
                   LEFT JOIN properties p ON e.property_id = p.id
                   WHERE e.expense_date BETWEEN ? AND ?";
        $expParams = [$start, $end];

        if ($this->userId && !$isAdmin) {
            $propertyIds = $user->getAccessiblePropertyIds();
            $expSql .= " AND (e.user_id = ?";
            $expParams[] = $this->userId;
            if (!empty($propertyIds)) {
                $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
                $expSql .= " OR e.property_id IN ($placeholders)";
                $expParams = array_merge($expParams, $propertyIds);
            }
            $expSql .= ")";
        }

        $expSql .= " ORDER BY e.expense_date ASC, e.id ASC";
        $expenses = $expenseModel->query($expSql, $expParams) ?: [];

        foreach ($expenses as $e) {
            $transactions[] = [
                'date' => $e['txn_date'] ?? null,
                'direction' => 'out',
                'source' => 'expense',
                'reference' => 'EXP-' . (int)($e['id'] ?? 0),
                'category' => (string)($e['category'] ?? 'Expense'),
                'description' => trim((string)($e['property_name'] ?? '') . (!empty($e['notes']) ? (' | ' . (string)$e['notes']) : '')),
                'amount' => (float)($e['amount'] ?? 0),
                'id' => (int)($e['id'] ?? 0),
            ];
        }

        usort($transactions, function ($a, $b) {
            $ad = $a['date'] ?? '';
            $bd = $b['date'] ?? '';
            if ($ad === $bd) {
                return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
            }
            return strcmp($ad, $bd);
        });

        $totalIn = 0.0;
        $totalOut = 0.0;
        foreach ($transactions as $t) {
            if (($t['direction'] ?? '') === 'in') {
                $totalIn += (float)($t['amount'] ?? 0);
            } else {
                $totalOut += (float)($t['amount'] ?? 0);
            }
        }
        $net = $totalIn - $totalOut;

        require 'views/accounting/statements.php';
    }

    public function backfillLedger()
    {
        // Create missing ledger entries for historical payments/expenses (idempotent)
        try {
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['flash_message'] = 'Please login to continue';
                $_SESSION['flash_type'] = 'warning';
                header('Location: ' . BASE_URL . '/');
                exit;
            }
            $userId = (int)($_SESSION['user_id'] ?? 0);
            $acc = new Account();
            $cash = $acc->findByCode('1000');
            $ar = $acc->findByCode('1100');
            $rev = $acc->findByCode('4000');
            $expAcc = $acc->findByCode('5000');
            if (!$cash || !$rev || !$expAcc || !$ar) {
                throw new \Exception('Missing default accounts. Ensure accounts 1000, 1100, 4000, 5000 exist.');
            }

            $ledger = new LedgerEntry();
            $pay = new Payment();
            $exp = new Expense();

            $countPay = 0;
            $countExp = 0;

            // Payments: post only completed/verified
            $payments = $pay->query(
                "SELECT p.id, p.lease_id, p.amount, p.payment_date, p.payment_type,
                        u.property_id
                 FROM payments p
                 JOIN leases l ON p.lease_id = l.id
                 JOIN units u ON l.unit_id = u.id
                 JOIN properties pr ON u.property_id = pr.id
                 WHERE p.status IN ('completed','verified')
                   AND (pr.owner_id = ? OR pr.manager_id = ? OR pr.agent_id = ? OR pr.caretaker_user_id = ?)",
                [$userId, $userId, $userId, $userId]
            );
            foreach ($payments as $p) {
                $pid = (int)($p['id'] ?? 0);
                $amount = (float)($p['amount'] ?? 0);
                if ($pid <= 0 || $amount <= 0) continue;
                if ($ledger->referenceExists('payment', $pid)) continue;
                $date = $p['payment_date'] ?? date('Y-m-d');
                $type = $p['payment_type'] ?? 'rent';
                $desc = ucfirst((string)$type) . ' payment #' . $pid;
                $propertyId = !empty($p['property_id']) ? (int)$p['property_id'] : null;
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
                    'reference_id' => $pid,
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
                    'reference_id' => $pid,
                ]);
                $countPay++;
            }

            // Expenses: post all (they are already cash-based)
            $expenses = $exp->getAll($userId);
            foreach ($expenses as $e) {
                $eid = (int)($e['id'] ?? 0);
                $amount = (float)($e['amount'] ?? 0);
                if ($eid <= 0 || $amount <= 0) continue;
                if ($ledger->referenceExists('expense', $eid)) continue;
                $date = $e['expense_date'] ?? date('Y-m-d');
                $desc = 'Expense #' . $eid . ' - ' . (string)($e['category'] ?? 'expense');
                $propertyId = !empty($e['property_id']) ? (int)$e['property_id'] : null;
                // Debit Expense, Credit Cash
                $ledger->post([
                    'entry_date' => $date,
                    'account_id' => (int)$expAcc['id'],
                    'description' => $desc,
                    'debit' => $amount,
                    'credit' => 0,
                    'user_id' => $userId,
                    'property_id' => $propertyId,
                    'reference_type' => 'expense',
                    'reference_id' => $eid,
                ]);
                $ledger->post([
                    'entry_date' => $date,
                    'account_id' => (int)$cash['id'],
                    'description' => $desc,
                    'debit' => 0,
                    'credit' => $amount,
                    'user_id' => $userId,
                    'property_id' => $propertyId,
                    'reference_type' => 'expense',
                    'reference_id' => $eid,
                ]);
                $countExp++;
            }

            $_SESSION['flash_message'] = 'Ledger backfill complete. Posted ' . $countPay . ' payment(s) and ' . $countExp . ' expense(s).';
            $_SESSION['flash_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = 'Ledger backfill failed: ' . $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
        }
        header('Location: ' . BASE_URL . '/accounting/ledger');
        exit;
    }
}
