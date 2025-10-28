<?php

namespace App\Controllers;

use App\Helpers\FileUploadHelper;
use Exception;

class FileController
{
    public function __construct()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_message'] = 'Please login to continue';
            $_SESSION['flash_type'] = 'danger';
            redirect('/home');
        }
    }

    /**
     * Delete a file
     */
    public function delete($fileId)
    {
        try {
            $fileUploadHelper = new FileUploadHelper();
            
            if ($fileUploadHelper->deleteFile($fileId, $_SESSION['user_id'])) {
                $response = [
                    'success' => true,
                    'message' => 'File deleted successfully'
                ];
            } else {
                throw new Exception('Failed to delete file');
            }
        } catch (Exception $e) {
            error_log("Error in FileController::delete: " . $e->getMessage());
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
