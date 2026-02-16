<?php

namespace App\Controllers;

use App\Models\RealtorContract;
use App\Models\RealtorClient;
use App\Models\RealtorListing;
use App\Models\Payment;

class RealtorContractsController
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
        $model = new RealtorContract();
        $contracts = $model->getAllWithDetails($this->userId);

        $clientModel = new RealtorClient();
        $listingModel = new RealtorListing();
        $clients = $clientModel->getAll($this->userId);
        $listings = $listingModel->getAll($this->userId);

        echo view('realtor/contracts', [
            'title' => 'Contracts',
            'contracts' => $contracts,
            'clients' => $clients,
            'listings' => $listings,
        ]);
    }

    public function show($id)
    {
        $contractModel = new RealtorContract();
        $contract = $contractModel->getByIdWithAccess((int)$id, $this->userId);
        if (!$contract) {
            $_SESSION['flash_message'] = 'Contract not found';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/contracts');
            exit;
        }

        $clientModel = new RealtorClient();
        $listingModel = new RealtorListing();
        $paymentModel = new Payment();

        $client = $clientModel->getByIdWithAccess((int)($contract['realtor_client_id'] ?? 0), $this->userId);
        $listing = $listingModel->getByIdWithAccess((int)($contract['realtor_listing_id'] ?? 0), $this->userId);

        $payments = $paymentModel->getPaymentsForRealtor($this->userId);
        $filteredPayments = [];
        foreach (($payments ?? []) as $p) {
            if ((int)($p['realtor_client_id'] ?? 0) === (int)($contract['realtor_client_id'] ?? 0)
                && (int)($p['realtor_listing_id'] ?? 0) === (int)($contract['realtor_listing_id'] ?? 0)) {
                $filteredPayments[] = $p;
            }
        }

        echo view('realtor/contract_show', [
            'title' => 'Contract Details',
            'contract' => $contract,
            'client' => $client,
            'listing' => $listing,
            'payments' => $filteredPayments,
        ]);
    }

    public function print($id)
    {
        $contractModel = new RealtorContract();
        $contract = $contractModel->getByIdWithAccess((int)$id, $this->userId);
        if (!$contract) {
            $_SESSION['flash_message'] = 'Contract not found';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/contracts');
            exit;
        }

        $clientModel = new RealtorClient();
        $listingModel = new RealtorListing();
        $client = $clientModel->getByIdWithAccess((int)($contract['realtor_client_id'] ?? 0), $this->userId);
        $listing = $listingModel->getByIdWithAccess((int)($contract['realtor_listing_id'] ?? 0), $this->userId);

        echo view('realtor/contract_print', [
            'title' => 'Contract Print',
            'contract' => $contract,
            'client' => $client,
            'listing' => $listing,
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/realtor/listings');
            exit;
        }

        if (!verify_csrf_token()) {
            $_SESSION['flash_message'] = 'Invalid security token';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/listings');
            exit;
        }

        $clientId = (int)($_POST['realtor_client_id'] ?? 0);
        $listingId = (int)($_POST['realtor_listing_id'] ?? 0);
        $termsType = trim((string)($_POST['terms_type'] ?? 'one_time'));
        $totalAmount = (float)($_POST['total_amount'] ?? 0);
        $durationMonths = (int)($_POST['duration_months'] ?? 0);
        $startMonth = trim((string)($_POST['start_month'] ?? ''));

        if ($clientId <= 0 || $listingId <= 0) {
            $_SESSION['flash_message'] = 'Client and listing are required';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/listings');
            exit;
        }

        if (!in_array($termsType, ['one_time', 'monthly'], true)) {
            $termsType = 'one_time';
        }

        if ($totalAmount <= 0) {
            $_SESSION['flash_message'] = 'Total amount must be greater than 0';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/listings');
            exit;
        }

        if ($termsType === 'monthly') {
            if ($durationMonths <= 0) {
                $_SESSION['flash_message'] = 'Duration (months) is required for monthly contracts';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/listings');
                exit;
            }
            if ($startMonth === '') {
                $_SESSION['flash_message'] = 'Start month is required for monthly contracts';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/listings');
                exit;
            }
        }

        $clientModel = new RealtorClient();
        $listingModel = new RealtorListing();
        $contractModel = new RealtorContract();

        $client = $clientModel->getByIdWithAccess($clientId, $this->userId);
        $listing = $listingModel->getByIdWithAccess($listingId, $this->userId);

        if (!$client || !$listing) {
            $_SESSION['flash_message'] = 'Invalid client or listing';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/listings');
            exit;
        }

        $monthlyAmount = null;
        $startMonthDate = null;

        if ($termsType === 'monthly') {
            $monthlyAmount = round($totalAmount / max(1, $durationMonths), 2);
            $startMonthDate = $startMonth . '-01';
        }

        try {
            $contractModel->beginTransaction();

            $contractId = $contractModel->insert([
                'user_id' => (int)$this->userId,
                'realtor_client_id' => (int)$clientId,
                'realtor_listing_id' => (int)$listingId,
                'terms_type' => (string)$termsType,
                'total_amount' => (float)$totalAmount,
                'monthly_amount' => $monthlyAmount,
                'duration_months' => ($termsType === 'monthly') ? (int)$durationMonths : null,
                'start_month' => $startMonthDate,
                'status' => 'active',
            ]);

            $listingModel->updateById((int)$listingId, [
                'status' => 'sold',
            ]);

            $contractModel->commit();

            $_SESSION['flash_message'] = 'Contract created and listing marked as sold';
            $_SESSION['flash_type'] = 'success';

            header('Location: ' . BASE_URL . '/realtor/contracts/show/' . (int)$contractId);
            exit;
        } catch (\Exception $e) {
            try {
                $contractModel->rollback();
            } catch (\Exception $e2) {
            }
            error_log('RealtorContracts store failed: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Failed to create contract';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/listings');
            exit;
        }
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/realtor/contracts/show/' . (int)$id);
            exit;
        }

        if (!verify_csrf_token()) {
            $_SESSION['flash_message'] = 'Invalid security token';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/contracts/show/' . (int)$id);
            exit;
        }

        $contractModel = new RealtorContract();
        $contract = $contractModel->getByIdWithAccess((int)$id, $this->userId);
        if (!$contract) {
            $_SESSION['flash_message'] = 'Contract not found';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/contracts');
            exit;
        }

        $termsType = trim((string)($_POST['terms_type'] ?? ($contract['terms_type'] ?? 'one_time')));
        $totalAmount = (float)($_POST['total_amount'] ?? ($contract['total_amount'] ?? 0));
        $durationMonths = (int)($_POST['duration_months'] ?? ($contract['duration_months'] ?? 0));
        $startMonth = trim((string)($_POST['start_month'] ?? ''));
        $instructions = trim((string)($_POST['instructions'] ?? ($contract['instructions'] ?? '')));

        if (!in_array($termsType, ['one_time', 'monthly'], true)) {
            $termsType = 'one_time';
        }
        if ($totalAmount <= 0) {
            $_SESSION['flash_message'] = 'Total amount must be greater than 0';
            $_SESSION['flash_type'] = 'danger';
            header('Location: ' . BASE_URL . '/realtor/contracts/show/' . (int)$id);
            exit;
        }

        $monthlyAmount = null;
        $startMonthDate = null;
        $durationToSave = null;

        if ($termsType === 'monthly') {
            if ($durationMonths <= 0) {
                $_SESSION['flash_message'] = 'Duration (months) is required for monthly contracts';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/contracts/show/' . (int)$id);
                exit;
            }
            if ($startMonth === '') {
                $_SESSION['flash_message'] = 'Start month is required for monthly contracts';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/contracts/show/' . (int)$id);
                exit;
            }
            $monthlyAmount = round($totalAmount / max(1, $durationMonths), 2);
            $startMonthDate = $startMonth . '-01';
            $durationToSave = (int)$durationMonths;
        }

        try {
            $ok = $contractModel->updateById((int)$id, [
                'terms_type' => (string)$termsType,
                'total_amount' => (float)$totalAmount,
                'monthly_amount' => $monthlyAmount,
                'duration_months' => $durationToSave,
                'start_month' => $startMonthDate,
                'instructions' => $instructions,
            ]);
            $_SESSION['flash_message'] = $ok ? 'Contract updated' : 'Failed to update contract';
            $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
        } catch (\Exception $e) {
            error_log('RealtorContracts update failed: ' . $e->getMessage());
            $_SESSION['flash_message'] = 'Failed to update contract';
            $_SESSION['flash_type'] = 'danger';
        }

        header('Location: ' . BASE_URL . '/realtor/contracts/show/' . (int)$id);
        exit;
    }
}
