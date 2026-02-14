<?php

namespace App\Controllers;

use App\Models\RealtorListing;
use App\Models\RealtorClient;
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/realtor/listings');
            exit;
        }
        try {
            if (!verify_csrf_token()) {
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
                $_SESSION['flash_message'] = 'Title and location are required';
                $_SESSION['flash_type'] = 'danger';
                header('Location: ' . BASE_URL . '/realtor/listings');
                exit;
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
        } catch (\Exception $e) {
            error_log('RealtorListings store failed: ' . $e->getMessage());
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
                foreach (($imgs ?? []) as $img) {
                    if (!empty($img['url'])) { $urls[] = $img['url']; }
                }
                $row['images'] = $urls;
            } catch (\Exception $e) {
                error_log('Failed to load realtor listing images: ' . $e->getMessage());
                $row['images'] = [];
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
            $ok = $model->deleteById((int)$id);
            echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Deleted' : 'Failed to delete']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
        exit;
    }
}
