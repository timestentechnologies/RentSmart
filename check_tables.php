<?php
$config = require 'config/database.php';

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}", $config['username'], $config['password'], $config['options']);
    
    echo "Checking database tables...\n\n";
    
    $tables = [
        'email_campaigns',
        'campaign_attachments', 
        'survey_questions',
        'survey_responses',
        'email_tracking',
        'follow_up_schedules',
        'follow_up_sent_log'
    ];
    
    // Get all tables first
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        if (in_array($table, $existingTables)) {
            echo "✓ $table table exists\n";
        } else {
            echo "✗ $table table MISSING\n";
        }
    }
    
    echo "\nDatabase check complete.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
