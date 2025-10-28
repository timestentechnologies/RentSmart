<?php

namespace App\Controllers;

use App\Models\Inquiry;

class InquiriesController
{
    public function index()
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            $role = $_SESSION['user_role'] ?? 'guest';
            $model = new Inquiry();
            $inquiries = $model->allVisibleForUser($userId, $role);
            echo view('admin/inquiries', [
                'title' => 'Inquiries',
                'inquiries' => $inquiries
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo view('errors/500', ['title' => '500 Internal Server Error']);
        }
    }
}
?>

