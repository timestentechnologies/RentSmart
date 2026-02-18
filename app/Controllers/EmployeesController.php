<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\Expense;
use App\Models\Account;
use App\Models\LedgerEntry;
use App\Models\User;

class EmployeesController
{
    private $userId;
    private $role;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        $this->role = strtolower((string)($_SESSION['user_role'] ?? ''));
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to access employees';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }

    public function index()
    {
        $employeeModel = new Employee();
        if ($this->role === 'realtor') {
            $employees = $employeeModel->getAll($this->userId);
            $properties = [];
        } else {
            $userModel = new User();
            $userModel->find($this->userId);
            $employees = $employeeModel->getAll($this->userId);
            $properties = $userModel->getAccessibleProperties();
        }
        require 'views/employees/index.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!verify_csrf_token()) {
                    $_SESSION['flash_message'] = 'Invalid security token';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/employees');
                    exit;
                }

                $propertyId = null;
                if ($this->role !== 'realtor') {
                    $propertyId = !empty($_POST['property_id']) ? (int)$_POST['property_id'] : null;
                }
                $data = [
                    'user_id' => $this->userId,
                    'name' => trim($_POST['name'] ?? ''),
                    'email' => $_POST['email'] ?? null,
                    'phone' => $_POST['phone'] ?? null,
                    'salary' => (float)($_POST['salary'] ?? 0),
                    'property_id' => $propertyId,
                    'status' => $_POST['status'] ?? 'active',
                    'role' => $_POST['role'] ?? 'general'
                ];
                $employeeModel = new Employee();
                $employeeId = $employeeModel->insert($data);

                // If caretaker, create a user account and optionally assign to property
                if ($this->role !== 'realtor' && $employeeId && strtolower($data['role']) === 'caretaker') {
                    $userModel = new \App\Models\User();
                    $db = $userModel->getDb();
                    try {
                        // Ensure users.role enum contains caretaker
                        $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
                        $col = $stmt->fetch(\PDO::FETCH_ASSOC);
                        if ($col && isset($col['Type']) && strpos($col['Type'], "'caretaker'") === false) {
                            $db->exec("ALTER TABLE users MODIFY role ENUM('admin','landlord','agent','manager','caretaker') NOT NULL DEFAULT 'agent'");
                        }
                    } catch (\Exception $e) {}

                    // Generate password using name and phone
                    $name = $data['name'] ?: 'Caretaker';
                    $phone = preg_replace('/\D+/', '', (string)($data['phone'] ?? ''));
                    $base = strtolower(preg_replace('/[^a-z]/i', '', explode(' ', trim($name))[0] ?? 'caretaker'));
                    $suffix = substr($phone, -4);
                    $plainPassword = $base . ($suffix ?: '1234') . '!';

                    // Check if user exists by email or phone
                    $existing = null;
                    if (!empty($data['email'])) {
                        $existing = $userModel->findByEmail($data['email']);
                    }
                    if (!$existing && !empty($data['phone'])) {
                        $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
                        $stmt->execute([$data['phone']]);
                        $existing = $stmt->fetch(\PDO::FETCH_ASSOC);
                    }

                    if (!$existing) {
                        $caretakerUserId = $userModel->createUser([
                            'name' => $data['name'],
                            'email' => $data['email'] ?: ($data['phone'] . '@caretaker.local'),
                            'phone' => $data['phone'],
                            'address' => null,
                            'password' => $plainPassword,
                            'role' => 'caretaker',
                            'is_subscribed' => 0
                        ]);
                    } else {
                        $caretakerUserId = $existing['id'];
                    }

                    // Assign to property if provided
                    if (!empty($data['property_id'])) {
                        $propDb = $userModel->getDb();
                        $stmt = $propDb->prepare("UPDATE properties SET caretaker_user_id = ? , caretaker_name = ?, caretaker_contact = ? WHERE id = ?");
                        $stmt->execute([$caretakerUserId, $data['name'], ($data['phone'] ?: $data['email']), $data['property_id']]);
                    }

                    // Notify current user (manager/agent/landlord)
                    $to = $_SESSION['user_email'] ?? null;
                    if ($to && function_exists('send_email') && function_exists('get_setting')) {
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $loginUrl = $scheme . '://' . $host . BASE_URL . '/';
                        $subject = 'Caretaker account created';
                        $body = "Caretaker account created for {$data['name']} (phone: {$data['phone']}).\nLogin URL: {$loginUrl}\nUsername (email/phone): " . ($data['email'] ?: $data['phone']) . "\nTemporary Password: {$plainPassword}";
                        send_email($to, $subject, $body);
                    }
                }
                $_SESSION['flash_message'] = 'Employee added successfully';
                $_SESSION['flash_type'] = 'success';
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = 'Failed to save employee';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: ' . BASE_URL . '/employees');
            exit;
        }
    }

    public function get($id)
    {
        try {
            $employeeModel = new Employee();
            $employee = $employeeModel->getByIdWithAccess($id, $this->userId);
            if (!$employee) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Employee not found']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $employee]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $employeeModel = new Employee();
                $employee = $employeeModel->getByIdWithAccess($id, $this->userId);
                if (!$employee) {
                    echo json_encode(['success' => false, 'message' => 'Employee not found']);
                    exit;
                }
                $data = [
                    'name' => trim($_POST['name'] ?? $employee['name']),
                    'email' => $_POST['email'] ?? $employee['email'],
                    'phone' => $_POST['phone'] ?? $employee['phone'],
                    'salary' => (float)($_POST['salary'] ?? $employee['salary']),
                    'property_id' => $_POST['property_id'] ? (int)$_POST['property_id'] : null,
                    'status' => $_POST['status'] ?? $employee['status']
                ];
                $success = $employeeModel->updateById($id, $data);
                echo json_encode(['success' => $success, 'message' => $success ? 'Updated' : 'Failed to update']);
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating employee']);
            }
            exit;
        }
    }

    public function delete($id)
    {
        try {
            $employeeModel = new Employee();
            $employee = $employeeModel->getByIdWithAccess($id, $this->userId);
            if (!$employee) {
                echo json_encode(['success' => false, 'message' => 'Employee not found']);
                exit;
            }
            $success = $employeeModel->deleteById($id);
            echo json_encode(['success' => (bool)$success, 'message' => $success ? 'Deleted' : 'Failed to delete']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }

    // Create a payroll payment and record it in expenses
    public function pay($employeeId)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
            try {
                if (!verify_csrf_token()) {
                    if ($isAjax) {
                        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                        exit;
                    }
                    $_SESSION['flash_message'] = 'Invalid security token';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/employees');
                    exit;
                }
                $employeeModel = new Employee();
                $employee = $employeeModel->getByIdWithAccess($employeeId, $this->userId);
                if (!$employee) {
                    if ($isAjax) {
                        echo json_encode(['success' => false, 'message' => 'Employee not found']);
                        exit;
                    }
                    $_SESSION['flash_message'] = 'Employee not found';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/employees');
                    exit;
                }
                $amount = (float)($_POST['amount'] ?? $employee['salary'] ?? 0);
                $payDate = $_POST['pay_date'] ?? date('Y-m-d');
                $method = $_POST['payment_method'] ?? 'cash';
                $source = $_POST['source_of_funds'] ?? 'cash';
                $notes = $_POST['notes'] ?? null;
                // Insert employee payment
                $paymentModel = new EmployeePayment();
                $paymentId = $paymentModel->insertPayment([
                    'employee_id' => $employeeId,
                    'amount' => $amount,
                    'pay_date' => $payDate,
                    'payment_method' => $method,
                    'source_of_funds' => $source,
                    'notes' => $notes
                ]);
                // Insert expense linked to this payroll
                $expenseModel = new Expense();
                $expenseId = $expenseModel->insert([
                    'user_id' => $this->userId,
                    'property_id' => $employee['property_id'] ?? null,
                    'unit_id' => null,
                    'category' => 'Payroll',
                    'amount' => $amount,
                    'expense_date' => $payDate,
                    'payment_method' => $method,
                    'source_of_funds' => $source,
                    'notes' => trim('Salary payment to ' . ($employee['name'] ?? 'Employee') . ($notes ? (': ' . $notes) : '')),
                    'reference_type' => 'employee_payment',
                    'reference_id' => $paymentId
                ]);

                // Link expense_id to employee payment record if column exists
                try {
                    if (!empty($paymentId) && !empty($expenseId)) {
                        $db = $paymentModel->getDb();
                        $upd = $db->prepare("UPDATE employee_payments SET expense_id = ? WHERE id = ?");
                        $upd->execute([(int)$expenseId, (int)$paymentId]);
                    }
                } catch (\Exception $e) {
                }

                // Auto-post payroll expense to ledger (idempotent)
                try {
                    if (!empty($expenseId)) {
                        $ledger = new LedgerEntry();
                        if (!$ledger->referenceExists('expense', (int)$expenseId)) {
                            $accModel = new Account();

                            // Ensure core accounts exist or fallback to first by type
                            $cash = $accModel->findByCode('1000');
                            if (!$cash) {
                                $cash = $accModel->findFirstByType('asset');
                            }
                            // Prefer a dedicated payroll expense account code
                            $payrollAcc = $accModel->findByCode('5100');
                            if (!$payrollAcc) {
                                $payrollAcc = $accModel->ensureByCode('5100', 'Payroll Expense', 'expense');
                            }
                            if (!$cash) {
                                $cash = $accModel->ensureByCode('1000', 'Cash', 'asset');
                            }

                            if ($cash && $payrollAcc) {
                                $desc = 'Expense #' . (int)$expenseId . ' - ' . 'Payroll';
                                $propertyId = $employee['property_id'] ?? null;
                                $ledger->post([
                                    'entry_date' => $payDate,
                                    'account_id' => (int)$payrollAcc['id'],
                                    'description' => $desc,
                                    'debit' => (float)$amount,
                                    'credit' => 0,
                                    'user_id' => $this->userId,
                                    'property_id' => $propertyId,
                                    'reference_type' => 'expense',
                                    'reference_id' => (int)$expenseId,
                                ]);
                                $ledger->post([
                                    'entry_date' => $payDate,
                                    'account_id' => (int)$cash['id'],
                                    'description' => $desc,
                                    'debit' => 0,
                                    'credit' => (float)$amount,
                                    'user_id' => $this->userId,
                                    'property_id' => $propertyId,
                                    'reference_type' => 'expense',
                                    'reference_id' => (int)$expenseId,
                                ]);
                            }
                        }
                    }
                } catch (\Exception $le) {
                    error_log('Payroll expense ledger post failed: ' . $le->getMessage());
                }
                if ($isAjax) {
                    echo json_encode(['success' => true, 'message' => 'Payment recorded', 'expense_id' => $expenseId]);
                    exit;
                }
                $_SESSION['flash_message'] = 'Employee payment recorded and expense created';
                $_SESSION['flash_type'] = 'success';
                header('Location: ' . BASE_URL . '/employees');
                exit;
            } catch (\Exception $e) {
                if ($isAjax) {
                    echo json_encode(['success' => false, 'message' => 'Failed to process payment']);
                    exit;
                }
                $_SESSION['flash_message'] = 'Failed to process payment';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/employees');
                exit;
            }
        }
    }
}
