<?php

namespace App\Controllers;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Expense;

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
        // Simple page with quick links to BS & P&L forms
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
