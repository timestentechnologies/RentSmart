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
            
            echo view('admin/mpesa_verification', [
                'title' => 'M-Pesa Payment Verification - RentSmart',
                'pendingPayments' => $pendingPayments
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
