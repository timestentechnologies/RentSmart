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

        $rateModel = new UtilityRate();
        if (!empty($_POST['id'])) {
            $rateModel->update($_POST['id'], $data);
        } else {
            $rateModel->create($data);
        }
        $_SESSION['success'] = 'Utility type/rate saved successfully';
        redirect('/utilities');
    }
} 