<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\Expense;
use App\Models\User;

class EmployeesController
{
    private $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
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
        $userModel = new User();
        $userModel->find($this->userId);
        $employees = $employeeModel->getAll($this->userId);
        $properties = $userModel->getAccessibleProperties();
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
                $data = [
                    'user_id' => $this->userId,
                    'name' => trim($_POST['name'] ?? ''),
                    'email' => $_POST['email'] ?? null,
                    'phone' => $_POST['phone'] ?? null,
                    'title' => $_POST['title'] ?? null,
                    'salary' => (float)($_POST['salary'] ?? 0),
                    'property_id' => $_POST['property_id'] ? (int)$_POST['property_id'] : null,
                    'status' => $_POST['status'] ?? 'active'
                ];
                $employeeModel = new Employee();
                $employeeId = $employeeModel->insert($data);
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
                    'title' => $_POST['title'] ?? $employee['title'],
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
