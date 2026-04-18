<?php

require_once 'vendor/autoload.php';

// Load .env
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
    $dotenv->load();
}

if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost/rentsmart');

use App\Database\Connection;
use App\Models\Wallet;

try {
    $db = Connection::getInstance()->getConnection();
} catch (\Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$walletModel = new Wallet();

echo "Starting wallet sync...\n";

$sql = "SELECT user_id, SUM(amount) as total 
        FROM payments 
        WHERE (payment_type = 'airbnb_booking' OR payment_type LIKE 'airbnb_%') 
        AND status = 'completed' 
        AND user_id IS NOT NULL 
        AND user_id > 0
        GROUP BY user_id";

try {
    $airbnbPayments = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    foreach ($airbnbPayments as $row) {
        $userId = $row['user_id'];
        $total = (float)$row['total'];
        
        echo "User $userId has KES $total in completed Airbnb payments.\n";
        
        $checkSql = "SELECT SUM(amount) as existing_total 
                     FROM wallet_transactions 
                     WHERE user_id = ? AND (reference_type = 'airbnb_payment' OR reference_type = 'airbnb_payment_sync')";
        $stmt = $db->prepare($checkSql);
        $stmt->execute([$userId]);
        $existing = (float)($stmt->fetch(\PDO::FETCH_ASSOC)['existing_total'] ?? 0);
        
        $toAdd = $total - $existing;
        
        if ($toAdd > 0.01) {
            echo "Adding KES $toAdd to User $userId's wallet...\n";
            try {
                $walletModel->add(
                    $userId,
                    $toAdd,
                    'Retroactive sync: Airbnb payments (' . date('Y-m-d') . ')',
                    'airbnb_payment_sync'
                );
                echo "Success.\n";
            } catch (\Exception $e) {
                echo "Failed for User $userId: " . $e->getMessage() . "\n";
            }
        } else {
            echo "User $userId wallet already up to date (Current: $existing, Goal: $total).\n";
        }
    }
} catch (\Exception $e) {
    echo "Error processing payments: " . $e->getMessage() . "\n";
}

echo "Sync complete.\n";
unlink(__FILE__); 
