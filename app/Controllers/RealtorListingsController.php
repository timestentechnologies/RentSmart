<?php

namespace App\Controllers;

use App\Models\RealtorListing;
use App\Models\RealtorClient;
use App\Database\Connection;
use App\Models\Subscription;
use App\Helpers\FileUploadHelper;

class RealtorListingsController
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
        $model = new RealtorListing();
        $listings = $model->getAll($this->userId);

        $clientModel = new RealtorClient();
        $clients = $clientModel->getAll($this->userId);
        echo view('realtor/listings', [
            'title' => 'Listings',
            'listings' => $listings,
            'clients' => $clients,
        ]);
    }

    public function store()
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (!empty($_SERVER['HTTP_ACCEPT']) && stripos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/realtor/listings');
            exit;
        }
        try {
            if (!verify_csrf_token()) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
                    exit;
                }
                $_SESSION['flash_message'] = 'Invalid security token';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/listings');
                exit;
            }

            $data = [
                'user_id' => $this->userId,
                'title' => trim((string)($_POST['title'] ?? '')),
                'listing_type' => trim((string)($_POST['listing_type'] ?? 'plot')),
                'location' => trim((string)($_POST['location'] ?? '')),
                'price' => (float)($_POST['price'] ?? 0),
                'status' => trim((string)($_POST['status'] ?? 'active')),
                'description' => trim((string)($_POST['description'] ?? '')),
            ];

            if ($data['title'] === '' || $data['location'] === '') {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Title and location are required']);
                    exit;
                }
                $_SESSION['flash_message'] = 'Title and location are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/listings');
                exit;
            }

            // Plan limits: enforce listing limit per subscription plan (dynamic from DB). Blank/0/NULL => unlimited.
            try {
                $subModel = new Subscription();
                $sub = $subModel->getUserSubscription((int)$this->userId);
                $listingLimit = null;
                if (isset($sub['listing_limit']) && $sub['listing_limit'] !== null && $sub['listing_limit'] !== '') {
                    $listingLimit = (int)$sub['listing_limit'];
                    if ($listingLimit <= 0) {
                        $listingLimit = null;
                    }
                }

                $modelCount = new RealtorListing();
                $currentCount = (int)$modelCount->countAll((int)$this->userId);
                if ($listingLimit !== null && $currentCount >= $listingLimit) {
                    $msg = 'You have reached your plan limit of ' . $listingLimit . ' listings. Please upgrade to add more.';
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => false,
                            'over_limit' => true,
                            'type' => 'listing',
                            'limit' => $listingLimit,
                            'current' => $currentCount,
                            'plan' => $sub['name'] ?? ($sub['plan_type'] ?? ''),
                            'upgrade_url' => BASE_URL . '/subscription/renew',
                            'message' => $msg,
                        ]);
                        exit;
                    }
                    $_SESSION['flash_message'] = $msg;
                    $_SESSION['flash_type'] = 'warning';
                    header('Location: ' . BASE_URL . '/subscription/renew');
                    exit;
                }
            } catch (\Exception $e) {
                // ignore; do not block listing creation if subscription tables are not available
            }

            $model = new RealtorListing();
            $listingId = $model->insert($data);

            // Handle image uploads
            try {
                if (!empty($_FILES['listing_images']['name'][0])) {
                    $fileUploadHelper = new FileUploadHelper();
                    $imageResult = $fileUploadHelper->uploadFiles(
                        $_FILES['listing_images'],
                        'realtor_listing',
                        (int)$listingId,
                        'image',
                        $_SESSION['user_id']
                    );
                    $fileUploadHelper->updateEntityFiles('realtor_listing', (int)$listingId);
                    if (!empty($imageResult['errors'])) {
                        $_SESSION['flash_message'] = 'Listing saved, but some images failed to upload: ' . implode('; ', $imageResult['errors']);
                        $_SESSION['flash_type'] = 'warning';
                    }
                }
            } catch (\Exception $e) {
                error_log('Realtor listing image upload failed: ' . $e->getMessage());
            }

            $_SESSION['flash_message'] = 'Listing added successfully';
            $_SESSION['flash_type'] = 'success';

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Listing added successfully',
                    'listing_id' => (int)$listingId,
                    'title' => (string)($data['title'] ?? ''),
                    'location' => (string)($data['location'] ?? ''),
                ]);
                exit;
            }
        } catch (\Exception $e) {
            error_log('RealtorListings store failed: ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to add listing']);
                exit;
            }
            $_SESSION['flash_message'] = 'Failed to add listing';
            $_SESSION['flash_type'] = 'danger';
        }

        header('Location: ' . BASE_URL . '/realtor/listings');
        exit;
    }

    public function get($id)
    {
        try {
            $model = new RealtorListing();
            $row = $model->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Listing not found']);
                exit;
            }
            try {
                $fileUploadHelper = new FileUploadHelper();
                $imgs = $fileUploadHelper->getEntityFiles('realtor_listing', (int)$id, 'image');
                $urls = [];
                $meta = [];

                $projectRoot = realpath(__DIR__ . '/../../');
                if (!$projectRoot) {
                    $projectRoot = __DIR__ . '/../../';
                }
                foreach (($imgs ?? []) as $img) {
                    if (!empty($img['url'])) { $urls[] = $img['url']; }

                    $uploadPath = (string)($img['upload_path'] ?? '');
                    $uploadPathNorm = ltrim($uploadPath, '/');
                    $pathInPublic = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadPathNorm);
                    $pathInRoot = rtrim($projectRoot, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadPathNorm);
                    $meta[] = [
                        'id' => (int)($img['id'] ?? 0),
                        'upload_path' => $uploadPath,
                        'url' => (string)($img['url'] ?? ''),
                        'exists_public' => (bool)($uploadPathNorm !== '' && file_exists($pathInPublic)),
                        'exists_root' => (bool)($uploadPathNorm !== '' && file_exists($pathInRoot)),
                    ];
                }
                $row['images'] = $urls;
                $row['images_meta'] = $meta;
            } catch (\Exception $e) {
                error_log('Failed to load realtor listing images: ' . $e->getMessage());
                $row['images'] = [];
                $row['images_meta'] = [];
            }
            echo json_encode(['success' => true, 'data' => $row]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        try {
            $model = new RealtorListing();
            $row = $model->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Listing not found']);
                exit;
            }

            $data = [
                'title' => trim((string)($_POST['title'] ?? ($row['title'] ?? ''))),
                'listing_type' => trim((string)($_POST['listing_type'] ?? ($row['listing_type'] ?? 'plot'))),
                'location' => trim((string)($_POST['location'] ?? ($row['location'] ?? ''))),
                'price' => (float)($_POST['price'] ?? ($row['price'] ?? 0)),
                'status' => trim((string)($_POST['status'] ?? ($row['status'] ?? 'active'))),
                'description' => trim((string)($_POST['description'] ?? ($row['description'] ?? ''))),
            ];

            $ok = $model->updateById((int)$id, $data);

            // Handle image uploads
            try {
                if (!empty($_FILES['listing_images']['name'][0])) {
                    $fileUploadHelper = new FileUploadHelper();
                    $imageResult = $fileUploadHelper->uploadFiles(
                        $_FILES['listing_images'],
                        'realtor_listing',
                        (int)$id,
                        'image',
                        $_SESSION['user_id']
                    );
                    $fileUploadHelper->updateEntityFiles('realtor_listing', (int)$id);
                    if (!empty($imageResult['errors'])) {
                        echo json_encode(['success' => false, 'message' => 'Updated, but some images failed to upload: ' . implode('; ', $imageResult['errors'])]);
                        exit;
                    }
                }
            } catch (\Exception $e) {
                error_log('Realtor listing image upload failed: ' . $e->getMessage());
            }
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Updated' : 'Failed to update']);
        } catch (\Exception $e) {
            error_log('RealtorListings update failed: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating listing']);
        }
        exit;
    }

    public function delete($id)
    {
        try {
            $model = new RealtorListing();
            $row = $model->getByIdWithAccess((int)$id, $this->userId);
            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Listing not found']);
                exit;
            }

            $db = Connection::getInstance()->getConnection();
            $db->beginTransaction();

            try {
                $listingId = (int)$id;

                // Collect related clients
                $clientIds = [];
                try {
                    $stmt = $db->prepare("SELECT id FROM realtor_clients WHERE user_id = ? AND realtor_listing_id = ?");
                    $stmt->execute([(int)$this->userId, (int)$listingId]);
                    $clientIds = array_values(array_filter(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [])));
                } catch (\Throwable $e) {
                    $clientIds = [];
                }

                // Delete payments by listing
                try {
                    $stmtPay = $db->prepare("DELETE FROM payments WHERE realtor_user_id = ? AND realtor_listing_id = ?");
                    $stmtPay->execute([(int)$this->userId, (int)$listingId]);
                } catch (\Throwable $e) {
                }

                // Delete payments tied to contracts of clients in this listing
                if (!empty($clientIds)) {
                    try {
                        $ph = implode(',', array_fill(0, count($clientIds), '?'));
                        $stmtPay2 = $db->prepare(
                            "DELETE p FROM payments p\n"
                            . "JOIN realtor_contracts c ON c.id = p.realtor_contract_id\n"
                            . "WHERE c.user_id = ? AND c.realtor_client_id IN ($ph)"
                        );
                        $stmtPay2->execute(array_merge([(int)$this->userId], $clientIds));
                    } catch (\Throwable $e) {
                    }
                }

                // Delete contracts by listing
                try {
                    $stmtC = $db->prepare("DELETE FROM realtor_contracts WHERE user_id = ? AND realtor_listing_id = ?");
                    $stmtC->execute([(int)$this->userId, (int)$listingId]);
                } catch (\Throwable $e) {
                }

                // Delete contracts for clients in this listing (covers any NULL listing_id contracts)
                if (!empty($clientIds)) {
                    try {
                        $ph = implode(',', array_fill(0, count($clientIds), '?'));
                        $stmtC2 = $db->prepare("DELETE FROM realtor_contracts WHERE user_id = ? AND realtor_client_id IN ($ph)");
                        $stmtC2->execute(array_merge([(int)$this->userId], $clientIds));
                    } catch (\Throwable $e) {
                    }
                }

                // Mark related leads as lost + unconvert
                try {
                    $stmtLost = $db->prepare(
                        "UPDATE realtor_leads\n"
                        . "SET status = 'lost', converted_client_id = NULL\n"
                        . "WHERE user_id = ? AND (realtor_listing_id = ? OR converted_client_id IN (SELECT id FROM realtor_clients WHERE user_id = ? AND realtor_listing_id = ?))"
                    );
                    $stmtLost->execute([(int)$this->userId, (int)$listingId, (int)$this->userId, (int)$listingId]);
                } catch (\Throwable $e) {
                }

                // Detach listing_id from leads (since listing is being deleted)
                try {
                    $stmtDetach = $db->prepare("UPDATE realtor_leads SET realtor_listing_id = NULL WHERE user_id = ? AND realtor_listing_id = ?");
                    $stmtDetach->execute([(int)$this->userId, (int)$listingId]);
                } catch (\Throwable $e) {
                }

                // Delete clients linked to this listing
                if (!empty($clientIds)) {
                    try {
                        $ph = implode(',', array_fill(0, count($clientIds), '?'));
                        $stmtDelClients = $db->prepare("DELETE FROM realtor_clients WHERE user_id = ? AND id IN ($ph)");
                        $stmtDelClients->execute(array_merge([(int)$this->userId], $clientIds));
                    } catch (\Throwable $e) {
                    }
                }

                // Finally delete listing
                $stmtDel = $db->prepare("DELETE FROM realtor_listings WHERE user_id = ? AND id = ?");
                $stmtDel->execute([(int)$this->userId, (int)$listingId]);

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Deleted']);
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                echo json_encode(['success' => false, 'message' => 'Failed to delete']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }
}
