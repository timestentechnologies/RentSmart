<?php

namespace App\Controllers;

use App\Models\RealtorListing;
use App\Models\RealtorClient;
use App\Models\RealtorLead;

class RealtorDashboardController
{
    private $userId;

    public function __construct()
    {
        $this->userId = $_SESSION['user_id'] ?? null;
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'warning';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
        if (strtolower((string)($_SESSION['user_role'] ?? '')) !== 'realtor') {
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

        $stats = [
            'listings_total' => $listingModel->countAll($this->userId),
            'listings_active' => $listingModel->countByStatus($this->userId, 'active'),
            'clients_total' => $clientModel->countAll($this->userId),
            'leads_total' => $leadModel->countAll($this->userId),
            'leads_new' => $leadModel->countByStatus($this->userId, 'new'),
            'leads_won' => $leadModel->countByStatus($this->userId, 'won'),
        ];

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
