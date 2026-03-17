<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'app/Database/Connection.php';

echo "Testing Newsletter Controller...\n";

try {
    // Test database connection
    $db = \App\Database\Connection::getInstance();
    echo "✓ Database connection successful\n";
    
    // Test basic query
    $stmt = $db->query("SELECT COUNT(*) FROM email_campaigns");
    $count = $stmt->fetchColumn();
    echo "✓ Campaign count: $count\n";
    
    // Test NewsletterController class
    require_once 'app/Controllers/NewsletterController.php';
    
    $controller = new NewsletterController();
    
    // Test index method
    echo "Testing index method...\n";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    $_SESSION = ['user_id' => 1, 'user_role' => 'admin']; // Mock admin session
    
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    echo "✓ Index method executed without errors\n";
    echo "Output length: " . strlen($output) . " characters\n";
    
    if (strpos($output, 'Fatal error') !== false) {
        echo "✗ Fatal error detected in output\n";
    } else {
        echo "✓ No fatal errors in output\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
