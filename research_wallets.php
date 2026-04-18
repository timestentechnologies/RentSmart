<?php

require_once 'vendor/autoload.php';

if (class_exists('Dotenv\Dotenv')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
        $dotenv->load();
    } catch (\Exception $e) {}
}

use App\Database\Connection;

try {
    $db = Connection::getInstance()->getConnection();
} catch (\Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "--- Researching Airbnb Payments ---\n";

// 1. Total Airbnb payments
$sql = "SELECT COUNT(*) as count, SUM(amount) as total FROM payments WHERE airbnb_booking_id IS NOT NULL OR airbnb_walkin_guest_id IS NOT NULL";
$res = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
echo "Total Airbnb-linked payments: " . $res['count'] . " (Total: KES " . ($res['total'] ?? 0) . ")\n";

// 2. Payments with NULL user_id
$sql = "SELECT COUNT(*) as count, SUM(amount) as total FROM payments WHERE (airbnb_booking_id IS NOT NULL OR airbnb_walkin_guest_id IS NOT NULL) AND user_id IS NULL";
$res = $db->query($sql)->fetch(\PDO::FETCH_ASSOC);
echo "Payments with NULL user_id: " . $res['count'] . " (Total: KES " . ($res['total'] ?? 0) . ")\n";

// 3. Current logged in user (simulation or just check if user_id exists in session? No, we are in CLI)
// Let's see some sample payments to check payment_type and other fields
$sql = "SELECT p.*, b.property_id FROM payments p LEFT JOIN airbnb_bookings b ON p.airbnb_booking_id = b.id WHERE p.airbnb_booking_id IS NOT NULL LIMIT 5";
$samples = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
echo "\nSample Payments Structure:\n";
print_r($samples);

// 4. Check Property Managers
if (!empty($samples)) {
    $propId = $samples[0]['property_id'];
    if ($propId) {
        $sql = "SELECT id, name, owner_id, manager_id, airbnb_manager_id FROM properties WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$propId]);
        $prop = $stmt->fetch(\PDO::FETCH_ASSOC);
        echo "\nAssociated Property Info:\n";
        print_r($prop);
    }
}

echo "\n--- End Research ---\n";
unlink(__FILE__);
