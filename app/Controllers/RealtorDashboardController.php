<?php

namespace App\Controllers;

use App\Models\RealtorListing;
use App\Models\RealtorClient;
use App\Models\RealtorLead;
use App\Models\RealtorContract;
use App\Models\Payment;

class RealtorDashboardController
{
    private $userId;

    private function demoLog(string $message): void
    {
        try {
            $root = dirname(__DIR__, 2);
            $path = $root . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'demo.log';
            $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
            @file_put_contents($path, $line, FILE_APPEND);
        } catch (\Throwable $e) {
        }
    }

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        if (!isset($_SESSION['user_id'])) {
            $this->demoLog('realtor dashboard denied: missing session user_id. session_role=' . (string)($_SESSION['user_role'] ?? ''));
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        if (strtolower((string)($_SESSION['user_role'] ?? '')) !== 'realtor') {
            $this->demoLog('realtor dashboard denied: wrong role. user_id=' . (int)($_SESSION['user_id'] ?? 0) . ' session_role=' . (string)($_SESSION['user_role'] ?? ''));
            $_SESSION['flash_message'] = 'Access denied';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    public function index()
    {
        $listingModel = new RealtorListing();
        $clientModel = new RealtorClient();
        $leadModel = new RealtorLead();
        $contractModel = new RealtorContract();
        $paymentModel = new Payment();

        $stats = [
            'listings_total' => $listingModel->countAll($this->userId),
            'listings_active' => $listingModel->countByStatus($this->userId, 'active'),
            'clients_total' => $clientModel->countAll($this->userId),
            'leads_total' => $leadModel->countAll($this->userId),
            'leads_new' => $leadModel->countByStatus($this->userId, 'new'),
            'leads_won' => $leadModel->countByStatus($this->userId, 'won'),
        ];

        $contracts = $contractModel->getAllWithDetails($this->userId);
        $paidTotals = $paymentModel->getRealtorPaidTotalsByContract((int)$this->userId);

        $expected = 0.0;
        $received = 0.0;
        foreach (($contracts ?? []) as $c) {
            $status = (string)($c['status'] ?? 'active');
            if ($status === 'cancelled') {
                continue;
            }
            $cid = (int)($c['id'] ?? 0);
            $total = (float)($c['total_amount'] ?? 0);
            $expected += max(0.0, $total);
            $received += (float)($paidTotals[$cid] ?? 0.0);
        }
        $remaining = max(0.0, $expected - $received);

        $stats['contracts_expected_amount'] = $expected;
        $stats['contracts_received_amount'] = $received;
        $stats['contracts_remaining_amount'] = $remaining;

        $recentLeads = $leadModel->getRecent($this->userId, 5);
        $recentListings = $listingModel->getRecent($this->userId, 5);

        echo view('realtor/dashboard', [
            'title' => 'Realtor Dashboard',
            'stats' => $stats,
            'recentLeads' => $recentLeads,
            'recentListings' => $recentListings,
        ]);
    }
}
