<?php

namespace App\Controllers;

use App\Models\Account;
use App\Models\LedgerEntry;

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
}
