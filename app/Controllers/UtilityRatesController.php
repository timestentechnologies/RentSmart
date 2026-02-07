<?php

namespace App\Controllers;

use App\Models\UtilityRate;

class UtilityRatesController
{
    private function ratesSupportUserScope(): bool
    {
        try {
            $db = (new \App\Models\UtilityRate())->getDb();
            $stmt = $db->query("SHOW COLUMNS FROM utility_rates LIKE 'user_id'");
            return (bool)$stmt->fetch();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function ratesSupportPropertyScope(): bool
    {
        try {
            $db = (new \App\Models\UtilityRate())->getDb();
            $stmt = $db->query("SHOW COLUMNS FROM utility_rates LIKE 'property_id'");
            return (bool)$stmt->fetch();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function index()
    {
        $rateModel = new UtilityRate();
        $types = $rateModel->getAllTypes();
        $rates = [];
        foreach ($types as $type) {
            $rates[$type] = $rateModel->getRatesByType($type);
        }
        $content = view('utility_rates/index', [
            'title' => 'Utility Types & Rates',
            'rates' => $rates
        ]);
        echo view('layouts/main', [
            'title' => 'Utility Types & Rates',
            'content' => $content
        ]);
    }

    public function create()
    {
        $content = view('utility_rates/create', [
            'title' => 'Add Utility Type & Rate'
        ]);
        echo view('layouts/main', [
            'title' => 'Add Utility Type & Rate',
            'content' => $content
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/utilities');
        }
        $data = [
            'utility_type' => trim($_POST['utility_type'] ?? ''),
            'rate_per_unit' => floatval($_POST['rate_per_unit'] ?? 0),
            'effective_from' => $_POST['effective_from'] ?? date('Y-m-d'),
            'effective_to' => !empty($_POST['effective_to']) ? $_POST['effective_to'] : null,
            'billing_method' => $_POST['billing_method'] ?? 'flat_rate'
        ];

        if ($this->ratesSupportUserScope() && !empty($_SESSION['user_id'])) {
            $data['user_id'] = (int)$_SESSION['user_id'];
        }

        if ($this->ratesSupportPropertyScope()) {
            $propId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
            $data['property_id'] = $propId > 0 ? $propId : null;
        }

        $rateModel = new UtilityRate();
        if (!empty($_POST['id'])) {
            $rateModel->update($_POST['id'], $data);
        } else {
            $rateModel->create($data);
        }
        $_SESSION['success'] = 'Utility type/rate saved successfully';
        redirect('/utilities');
    }

    public function delete($id)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirect('/utilities');
        }

        if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
            $_SESSION['flash_message'] = 'Invalid security token';
            $_SESSION['flash_type'] = 'danger';
            redirect('/utilities');
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $_SESSION['flash_message'] = 'Unauthorized';
            $_SESSION['flash_type'] = 'danger';
            redirect('/login');
        }

        $userModel = new \App\Models\User();
        $userModel->find($userId);

        $rateModel = new UtilityRate();
        $rate = $rateModel->find((int)$id);
        if (!$rate) {
            $_SESSION['flash_message'] = 'Utility type/rate not found';
            $_SESSION['flash_type'] = 'danger';
            redirect('/utilities');
        }

        // Access control
        if (!$userModel->isAdmin()) {
            // If user scoping exists, enforce ownership
            if ($this->ratesSupportUserScope() && isset($rate['user_id']) && (int)$rate['user_id'] !== (int)$userId) {
                $_SESSION['flash_message'] = 'Unauthorized';
                $_SESSION['flash_type'] = 'danger';
                redirect('/utilities');
            }

            // If property scoping exists, ensure property is accessible
            if ($this->ratesSupportPropertyScope() && !empty($rate['property_id'])) {
                $accessible = $userModel->getAccessiblePropertyIds();
                if (!in_array((int)$rate['property_id'], $accessible)) {
                    $_SESSION['flash_message'] = 'Unauthorized';
                    $_SESSION['flash_type'] = 'danger';
                    redirect('/utilities');
                }
            }
        }

        $rateModel->delete((int)$id);
        $_SESSION['success'] = 'Utility type/rate deleted successfully';
        redirect('/utilities');
    }
}