<?php

namespace App\Controllers;

use App\Models\Expense;
use App\Models\User;
use App\Helpers\FileUploadHelper;

class ExpensesController
{
    private $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to access expenses';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }

    public function index()
    {
        $expenseModel = new Expense();
        $userModel = new User();
        $userModel->find($this->userId);
        $expenses = $expenseModel->getAll($this->userId);
        $properties = $userModel->getAccessibleProperties();
        require 'views/expenses/index.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!verify_csrf_token()) {
                    $_SESSION['flash_message'] = 'Invalid security token';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: ' . BASE_URL . '/expenses');
                    exit;
                }
                $data = [
                    'user_id' => $this->userId,
                    'property_id' => $_POST['property_id'] ? (int)$_POST['property_id'] : null,
                    'unit_id' => $_POST['unit_id'] ? (int)$_POST['unit_id'] : null,
                    'category' => trim($_POST['category'] ?? 'General'),
                    'amount' => (float)($_POST['amount'] ?? 0),
                    'expense_date' => $_POST['expense_date'] ?? date('Y-m-d'),
                    'payment_method' => $_POST['payment_method'] ?? 'cash',
                    'source_of_funds' => $_POST['source_of_funds'] ?? 'cash',
                    'notes' => $_POST['notes'] ?? null,
                    'reference_type' => $_POST['reference_type'] ?? null,
                    'reference_id' => $_POST['reference_id'] ?? null
                ];
                $expenseModel = new Expense();
                $expenseId = $expenseModel->insertExpense($data);
                if ($expenseId && !empty($_FILES['expense_attachments']['name'][0])) {
                    $uploader = new FileUploadHelper();
                    $uploader->uploadFiles($_FILES['expense_attachments'], 'expense', $expenseId, 'attachment', $this->userId);
                    $uploader->updateEntityFiles('expense', $expenseId);
                }
                $_SESSION['flash_message'] = 'Expense recorded successfully';
                $_SESSION['flash_type'] = 'success';
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = 'Failed to save expense';
                $_SESSION['flash_type'] = 'danger';
            }
            header('Location: ' . BASE_URL . '/expenses');
            exit;
        }
    }

    public function get($id)
    {
        try {
            $expenseModel = new Expense();
            $expense = $expenseModel->getByIdWithAccess($id, $this->userId);
            if (!$expense) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Expense not found']);
                exit;
            }
            echo json_encode(['success' => true, 'data' => $expense]);
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
                $expenseModel = new Expense();
                $expense = $expenseModel->getByIdWithAccess($id, $this->userId);
                if (!$expense) {
                    echo json_encode(['success' => false, 'message' => 'Expense not found']);
                    exit;
                }
                $data = [
                    'property_id' => $_POST['property_id'] ? (int)$_POST['property_id'] : null,
                    'unit_id' => $_POST['unit_id'] ? (int)$_POST['unit_id'] : null,
                    'category' => trim($_POST['category'] ?? $expense['category']),
                    'amount' => (float)($_POST['amount'] ?? $expense['amount']),
                    'expense_date' => $_POST['expense_date'] ?? $expense['expense_date'],
                    'payment_method' => $_POST['payment_method'] ?? $expense['payment_method'],
                    'source_of_funds' => $_POST['source_of_funds'] ?? $expense['source_of_funds'],
                    'notes' => $_POST['notes'] ?? $expense['notes']
                ];
                $success = $expenseModel->updateExpense($id, $data);
                if ($success && !empty($_FILES['expense_attachments']['name'][0])) {
                    $uploader = new FileUploadHelper();
                    $uploader->uploadFiles($_FILES['expense_attachments'], 'expense', $id, 'attachment', $this->userId);
                    $uploader->updateEntityFiles('expense', $id);
                }
                echo json_encode(['success' => $success, 'message' => $success ? 'Updated' : 'Failed to update']);
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating expense']);
            }
            exit;
        }
    }

    public function delete($id)
    {
        try {
            $expenseModel = new Expense();
            $expense = $expenseModel->getByIdWithAccess($id, $this->userId);
            if (!$expense) {
                echo json_encode(['success' => false, 'message' => 'Expense not found']);
                exit;
            }
            $success = $expenseModel->delete($id);
            echo json_encode(['success' => (bool)$success, 'message' => $success ? 'Deleted' : 'Failed to delete']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }
}
