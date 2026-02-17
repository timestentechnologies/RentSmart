<?php

namespace App\Controllers;

use App\Models\Inquiry;
use App\Models\RealtorLead;
use App\Models\RealtorListing;
use App\Models\Unit;

class InquiryController
{
    public function store()
    {
        header('Content-Type: application/json');
        try {
            $unitId = $_POST['unit_id'] ?? null;
            $realtorListingId = $_POST['realtor_listing_id'] ?? null;
            $realtorUserId = $_POST['realtor_user_id'] ?? null;
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $preferredDate = $_POST['preferred_date'] ?? null;
            $message = trim($_POST['message'] ?? '');

            if ((!$unitId && !$realtorListingId) || !$name || !$phone) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Name and phone are required']);
                return;
            }

            $inquiry = new Inquiry();
            $contact = $phone . ($email ? ' | ' . $email : '');

            if ($realtorListingId) {
                $listingModel = new RealtorListing();
                $listing = $listingModel->find((int)$realtorListingId);
                if (!$listing) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Listing not found']);
                    return;
                }

                $expectedRealtorUserId = (int)($listing['user_id'] ?? 0);
                if ((int)$realtorUserId !== $expectedRealtorUserId) {
                    $realtorUserId = $expectedRealtorUserId;
                }

                $inquiryId = $inquiry->create([
                    'realtor_listing_id' => (int)$realtorListingId,
                    'realtor_user_id' => (int)$realtorUserId,
                    'name' => $name,
                    'contact' => $contact,
                    'preferred_date' => $preferredDate ?: null,
                    'message' => $message ?: null,
                    'source' => 'vacant_units',
                    'amount' => (float)($listing['price'] ?? 0),
                ]);

                try {
                    $leadModel = new RealtorLead();
                    $leadModel->insert([
                        'user_id' => (int)$realtorUserId,
                        'realtor_listing_id' => (int)$realtorListingId,
                        'amount' => (float)($listing['price'] ?? 0),
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email ?: null,
                        'source' => 'vacant_units',
                        'status' => 'new',
                        'notes' => trim('Listing: ' . ($listing['title'] ?? '') . "\n" . ($message ?: '')),
                    ]);
                } catch (\Exception $e) {
                    error_log('Failed to create realtor lead from inquiry: ' . $e->getMessage());
                }
            } else {
                $unitModel = new Unit();
                $unit = $unitModel->find($unitId);
                if (!$unit) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Unit not found']);
                    return;
                }

                $inquiryId = $inquiry->create([
                    'unit_id' => $unitId,
                    'property_id' => $unit['property_id'],
                    'name' => $name,
                    'contact' => $contact,
                    'preferred_date' => $preferredDate ?: null,
                    'message' => $message ?: null,
                    'source' => 'vacant_units',
                    'amount' => (float)($unit['rent_amount'] ?? 0),
                ]);
            }

            echo json_encode(['success' => true, 'id' => $inquiryId, 'message' => 'Inquiry submitted']);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }
}
?>

