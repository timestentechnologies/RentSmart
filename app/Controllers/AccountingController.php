<?php

namespace App\Controllers;

use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;

class AccountingController
{
    private $userId;

    private function buildStatementData(string $start, string $end): array
    {
        if (!$this->userId) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $user = new User();
        $userData = $user->find($this->userId);
        $isAdmin = isset($userData['role']) && in_array($userData['role'], ['admin', 'administrator'], true);

        $paymentModel = new Payment();
        $expenseModel = new Expense();

        $transactions = [];

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

        return [
            'start' => $start,
            'end' => $end,
            'transactions' => $transactions,
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'net' => $totalIn - $totalOut,
        ];
    }

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
        $data = $this->buildStatementData($start, $end);
        $transactions = $data['transactions'];
        $totalIn = $data['totalIn'];
        $totalOut = $data['totalOut'];
        $net = $data['net'];
        $start = $data['start'];
        $end = $data['end'];

        require 'views/accounting/statements.php';
    }

    public function exportStatementsCsv()
    {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-d');
        $data = $this->buildStatementData($start, $end);

        $filename = 'statements_' . $data['start'] . '_to_' . $data['end'] . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Reference', 'Type', 'Category', 'Description', 'Money In', 'Money Out']);
        foreach (($data['transactions'] ?? []) as $t) {
            $in = ($t['direction'] ?? '') === 'in' ? (float)($t['amount'] ?? 0) : 0.0;
            $outAmt = ($t['direction'] ?? '') === 'out' ? (float)($t['amount'] ?? 0) : 0.0;
            fputcsv($out, [
                $t['date'] ?? '',
                $t['reference'] ?? '',
                ($t['direction'] ?? '') === 'in' ? 'Money In' : 'Money Out',
                $t['category'] ?? '',
                $t['description'] ?? '',
                $in > 0 ? number_format($in, 2, '.', '') : '',
                $outAmt > 0 ? number_format($outAmt, 2, '.', '') : '',
            ]);
        }
        fputcsv($out, []);
        fputcsv($out, ['Totals', '', '', '', '', number_format((float)($data['totalIn'] ?? 0), 2, '.', ''), number_format((float)($data['totalOut'] ?? 0), 2, '.', '')]);
        fputcsv($out, ['Net Cash Movement', '', '', '', '', number_format((float)($data['net'] ?? 0), 2, '.', ''), '']);
        fclose($out);
        exit;
    }

    public function exportStatementsPdf()
    {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-d');
        $data = $this->buildStatementData($start, $end);

        $rowsHtml = '';
        foreach (($data['transactions'] ?? []) as $t) {
            $in = ($t['direction'] ?? '') === 'in' ? (float)($t['amount'] ?? 0) : 0.0;
            $outAmt = ($t['direction'] ?? '') === 'out' ? (float)($t['amount'] ?? 0) : 0.0;
            $rowsHtml .= '<tr>'
                . '<td>' . htmlspecialchars((string)($t['date'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)($t['reference'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((($t['direction'] ?? '') === 'in') ? 'Money In' : 'Money Out') . ' - ' . htmlspecialchars((string)($t['category'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)($t['description'] ?? '')) . '</td>'
                . '<td style="text-align:right">' . ($in > 0 ? number_format($in, 2) : '-') . '</td>'
                . '<td style="text-align:right">' . ($outAmt > 0 ? number_format($outAmt, 2) : '-') . '</td>'
                . '</tr>';
        }

        $html = '<html><head><meta charset="utf-8">'
            . '<style>'
            . 'body{font-family: DejaVu Sans, sans-serif; font-size:12px; color:#111;}'
            . 'h2{margin:0 0 6px 0;}'
            . '.meta{color:#555; margin-bottom:10px;}'
            . 'table{width:100%; border-collapse:collapse;}'
            . 'th,td{border:1px solid #ddd; padding:6px; vertical-align:top;}'
            . 'th{background:#f3f5f7; text-align:left;}'
            . '.totals td{font-weight:bold; background:#fafafa;}'
            . '</style>'
            . '</head><body>'
            . '<h2>Statement of Money In / Money Out</h2>'
            . '<div class="meta">Period: ' . htmlspecialchars($data['start']) . ' to ' . htmlspecialchars($data['end']) . '</div>'
            . '<div class="meta">Money In: ' . number_format((float)($data['totalIn'] ?? 0), 2) . ' | Money Out: ' . number_format((float)($data['totalOut'] ?? 0), 2) . ' | Net: ' . number_format((float)($data['net'] ?? 0), 2) . '</div>'
            . '<table>'
            . '<thead><tr>'
            . '<th style="width:10%">Date</th>'
            . '<th style="width:15%">Reference</th>'
            . '<th style="width:20%">Type</th>'
            . '<th>Description</th>'
            . '<th style="width:12%; text-align:right">Money In</th>'
            . '<th style="width:12%; text-align:right">Money Out</th>'
            . '</tr></thead>'
            . '<tbody>'
            . ($rowsHtml !== '' ? $rowsHtml : '<tr><td colspan="6" style="text-align:center; color:#777; padding:14px;">No transactions found for this period</td></tr>')
            . '<tr class="totals">'
            . '<td colspan="4">Totals</td>'
            . '<td style="text-align:right">' . number_format((float)($data['totalIn'] ?? 0), 2) . '</td>'
            . '<td style="text-align:right">' . number_format((float)($data['totalOut'] ?? 0), 2) . '</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>'
            . '</body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $filename = 'statements_' . $data['start'] . '_to_' . $data['end'] . '.pdf';
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
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
