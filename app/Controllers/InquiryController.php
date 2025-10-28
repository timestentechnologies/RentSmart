<?php

namespace App\Controllers;

use App\Models\Inquiry;
use App\Models\Unit;

class InquiryController
{
    public function store()
    {
        header('Content-Type: application/json');
        try {
            $unitId = $_POST['unit_id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $contact = trim($_POST['contact'] ?? '');
            $preferredDate = $_POST['preferred_date'] ?? null;
            $message = trim($_POST['message'] ?? '');

            if (!$unitId || !$name || !$contact) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Name, contact and unit are required']);
                return;
            }

            $unitModel = new Unit();
            $unit = $unitModel->find($unitId);
            if (!$unit) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Unit not found']);
                return;
            }

            $inquiry = new Inquiry();
            $inquiryId = $inquiry->create([
                'unit_id' => $unitId,
                'property_id' => $unit['property_id'],
                'name' => $name,
                'contact' => $contact,
                'preferred_date' => $preferredDate ?: null,
                'message' => $message ?: null,
            ]);

            echo json_encode(['success' => true, 'id' => $inquiryId, 'message' => 'Inquiry submitted']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }
}
?>

