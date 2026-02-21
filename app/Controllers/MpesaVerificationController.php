<?php

namespace App\Controllers;

use App\Models\Payment;
use App\Database\Connection;

// Include helpers
require_once __DIR__ . '/../helpers.php';

class MpesaVerificationController
{
    private $db;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function verifyAll()
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
            $userId = $_SESSION['user_id'] ?? null;

            if (!in_array($status, ['verified', 'rejected'], true)) {
                throw new \Exception('Invalid verification status');
            }

            $this->db->beginTransaction();

            $sel = $this->db->prepare("SELECT DISTINCT transaction_code FROM manual_mpesa_payments WHERE verification_status = 'pending' AND transaction_code IS NOT NULL AND transaction_code <> ''");
            $sel->execute();
            $codes = $sel->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            $codes = array_values(array_filter(array_map('trim', $codes)));

            if (empty($codes)) {
                $this->db->rollBack();
                throw new \Exception('No pending payments found');
            }

            // Collect all payment IDs linked to these pending codes
            $in = implode(',', array_fill(0, count($codes), '?'));
            $pSel = $this->db->prepare("SELECT DISTINCT payment_id FROM manual_mpesa_payments WHERE verification_status = 'pending' AND transaction_code IN ($in)");
            $pSel->execute($codes);
            $paymentIds = $pSel->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            $paymentIds = array_values(array_unique(array_map('intval', $paymentIds)));

            // Update manual rows
            $upd = $this->db->prepare("UPDATE manual_mpesa_payments SET verification_status = ?, verification_notes = ?, verified_at = NOW(), verified_by = ? WHERE verification_status = 'pending'");
            $upd->execute([$status, $notes, $userId]);

            // Update payments
            $paymentStatus = ($status === 'verified') ? 'completed' : 'failed';
            if (!empty($paymentIds)) {
                $pin = implode(',', array_fill(0, count($paymentIds), '?'));
                $params = array_merge([$paymentStatus], $paymentIds);
                $pupd = $this->db->prepare("UPDATE payments SET status = ? WHERE id IN ($pin)");
                $pupd->execute($params);
            }

            $this->db->commit();

            // Post to ledger for each payment when approved
            if ($paymentStatus === 'completed') {
                foreach ($paymentIds as $pid) {
                    try {
                        $paymentModel = new Payment();
                        $paymentModel->postExistingPaymentToLedger((int)$pid);
                    } catch (\Exception $e) {
                        error_log('Ledger post after bulk M-Pesa verification failed: ' . $e->getMessage());
                    }
                }
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'All pending payments updated successfully']);
                exit;
            }

            $_SESSION['flash_message'] = 'All pending payments updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/mpesa-verification');
        } catch (\Exception $e) {
            error_log("Error in MpesaVerificationController::verifyAll: " . $e->getMessage());
            try {
                if ($this->db && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
            } catch (\Exception $e2) {
            }

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/mpesa-verification');
        }
    }

    public function verifyGroup($code)
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
            $userId = $_SESSION['user_id'] ?? null;

            if (!in_array($status, ['verified', 'rejected'], true)) {
                throw new \Exception('Invalid verification status');
            }

            $code = urldecode((string)$code);
            $code = trim($code);
            if ($code === '') {
                throw new \Exception('Invalid transaction code');
            }

            $this->db->beginTransaction();

            // Get all pending manual mpesa rows for this transaction
            $sel = $this->db->prepare("SELECT id, payment_id FROM manual_mpesa_payments WHERE transaction_code = ? AND verification_status = 'pending'");
            $sel->execute([$code]);
            $rows = $sel->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            if (empty($rows)) {
                $this->db->rollBack();
                throw new \Exception('No pending payments found for this transaction');
            }

            $ids = [];
            $paymentIds = [];
            foreach ($rows as $r) {
                $mid = (int)($r['id'] ?? 0);
                $pid = (int)($r['payment_id'] ?? 0);
                if ($mid > 0) $ids[] = $mid;
                if ($pid > 0) $paymentIds[] = $pid;
            }
            $paymentIds = array_values(array_unique($paymentIds));

            // Update manual rows
            $upd = $this->db->prepare("UPDATE manual_mpesa_payments SET verification_status = ?, verification_notes = ?, verified_at = NOW(), verified_by = ? WHERE transaction_code = ? AND verification_status = 'pending'");
            $upd->execute([$status, $notes, $userId, $code]);

            // Update payments
            $paymentStatus = ($status === 'verified') ? 'completed' : 'failed';
            if (!empty($paymentIds)) {
                $in = implode(',', array_fill(0, count($paymentIds), '?'));
                $params = array_merge([$paymentStatus], $paymentIds);
                $pupd = $this->db->prepare("UPDATE payments SET status = ? WHERE id IN ($in)");
                $pupd->execute($params);
            }

            $this->db->commit();

            // Post to ledger for each payment when approved
            if ($paymentStatus === 'completed') {
                foreach ($paymentIds as $pid) {
                    try {
                        $paymentModel = new Payment();
                        $paymentModel->postExistingPaymentToLedger((int)$pid);
                    } catch (\Exception $e) {
                        error_log('Ledger post after grouped M-Pesa verification failed: ' . $e->getMessage());
                    }
                }
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Payment verification updated successfully']);
                exit;
            }

            $_SESSION['flash_message'] = 'Payment verification updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/mpesa-verification');
        } catch (\Exception $e) {
            error_log("Error in MpesaVerificationController::verifyGroup: " . $e->getMessage());
            try {
                if ($this->db && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
            } catch (\Exception $e2) {
            }

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }

            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/mpesa-verification');
        }
    }

    /**
     * Display pending M-Pesa payments for verification
     */
    public function index()
    {
        try {
            $sql = "
                SELECT 
                    mmp.*,
                    p.amount,
                    p.payment_date,
                    p.payment_type,
                    p.notes as payment_notes,
                    t.name as tenant_name,
                    t.first_name,
                    t.last_name,
                    t.email as tenant_email,
                    t.phone as tenant_phone,
                    pr.name as property_name,
                    u.unit_number,
                    l.id as lease_id,
                    l.rent_amount
                FROM manual_mpesa_payments mmp
                JOIN payments p ON mmp.payment_id = p.id
                JOIN leases l ON p.lease_id = l.id
                JOIN tenants t ON l.tenant_id = t.id
                JOIN units u ON l.unit_id = u.id
                JOIN properties pr ON u.property_id = pr.id
                WHERE mmp.verification_status = 'pending'
                ORDER BY mmp.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $pendingPayments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Group by transaction_code so one M-Pesa code can be approved once
            $grouped = [];
            foreach (($pendingPayments ?: []) as $row) {
                $code = trim((string)($row['transaction_code'] ?? ''));
                if ($code === '') {
                    $code = 'UNKNOWN';
                }
                if (!isset($grouped[$code])) {
                    $grouped[$code] = [
                        'transaction_code' => $code,
                        'phone_number' => $row['phone_number'] ?? '',
                        'created_at' => $row['created_at'] ?? null,
                        'payment_date' => $row['payment_date'] ?? null,
                        'tenant_name' => $row['tenant_name'] ?? '',
                        'tenant_email' => $row['tenant_email'] ?? '',
                        'tenant_phone' => $row['tenant_phone'] ?? '',
                        'property_name' => $row['property_name'] ?? '',
                        'unit_number' => $row['unit_number'] ?? '',
                        'total_amount' => 0.0,
                        'payment_types' => [],
                        'items' => []
                    ];
                }
                $grouped[$code]['total_amount'] += (float)($row['amount'] ?? 0);
                $ptype = strtolower((string)($row['payment_type'] ?? ''));
                if ($ptype !== '' && !in_array($ptype, $grouped[$code]['payment_types'], true)) {
                    $grouped[$code]['payment_types'][] = $ptype;
                }
                $grouped[$code]['items'][] = $row;
                // Use most recent created_at for ordering
                $curTs = !empty($grouped[$code]['created_at']) ? strtotime((string)$grouped[$code]['created_at']) : 0;
                $rowTs = !empty($row['created_at']) ? strtotime((string)$row['created_at']) : 0;
                if ($rowTs > $curTs) {
                    $grouped[$code]['created_at'] = $row['created_at'] ?? $grouped[$code]['created_at'];
                    $grouped[$code]['payment_date'] = $row['payment_date'] ?? $grouped[$code]['payment_date'];
                }
            }

            $groupedPayments = array_values($grouped);
            usort($groupedPayments, function ($a, $b) {
                $at = !empty($a['created_at']) ? strtotime((string)$a['created_at']) : 0;
                $bt = !empty($b['created_at']) ? strtotime((string)$b['created_at']) : 0;
                return $bt <=> $at;
            });
            
            echo view('admin/mpesa_verification', [
                'title' => 'M-Pesa Payment Verification - RentSmart',
                'pendingPayments' => $pendingPayments,
                'groupedPayments' => $groupedPayments
            ]);

        } catch (\Exception $e) {
            error_log("Error in MpesaVerificationController::index: " . $e->getMessage());
            $_SESSION['flash_message'] = 'Error loading M-Pesa payments';
            $_SESSION['flash_type'] = 'danger';
            redirect('/dashboard');
        }
    }

    /**
     * Verify M-Pesa payment
     */
    public function verify($id)
    {
        try {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!in_array($status, ['verified', 'rejected'])) {
                throw new \Exception('Invalid verification status');
            }
            
            $sql = "UPDATE manual_mpesa_payments 
                    SET verification_status = ?, verification_notes = ?, verified_at = NOW(), verified_by = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $notes, $userId, $id]);
            
            // Update payment status based on verification
            $paymentStatus = ($status === 'verified') ? 'completed' : 'failed';
            $this->updatePaymentStatus($id, $paymentStatus);
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Payment verification updated successfully'
                ]);
                exit;
            }
            
            $_SESSION['flash_message'] = 'Payment verification updated successfully';
            $_SESSION['flash_type'] = 'success';
            redirect('/mpesa-verification');
            
        } catch (\Exception $e) {
            error_log("Error in MpesaVerificationController::verify: " . $e->getMessage());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            }
            
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'danger';
            redirect('/mpesa-verification');
        }
    }

    /**
     * Update payment status based on verification
     */
    private function updatePaymentStatus($mpesaPaymentId, $status)
    {
        try {
            // Get the payment_id from manual_mpesa_payments
            $sql = "SELECT payment_id FROM manual_mpesa_payments WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$mpesaPaymentId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result) {
                $updateSql = "UPDATE payments SET status = ? WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$status, $result['payment_id']]);

                // Post to ledger once marked completed/verified so it shows on GL/trial balance/balance sheet
                if (in_array((string)$status, ['completed', 'verified'], true)) {
                    try {
                        $paymentModel = new Payment();
                        $paymentModel->postExistingPaymentToLedger((int)$result['payment_id']);
                    } catch (\Exception $e) {
                        error_log('Ledger post after M-Pesa verification failed: ' . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Error updating payment status: " . $e->getMessage());
        }
    }
}
