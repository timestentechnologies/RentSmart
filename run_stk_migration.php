<?php
/**
 * Run M-Pesa STK Transactions Table Migration
 * 
 * This script creates the mpesa_stk_transactions table
 * Run this once to set up the database table for STK push functionality
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

try {
    // Load environment variables
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/');
    $dotenv->load();
    
    // Get database connection
    $db = getDbConnection();
    
    // Read migration file
    $migrationFile = __DIR__ . '/database/migrations/create_mpesa_stk_transactions_table.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Execute migration
    echo "Running migration: create_mpesa_stk_transactions_table.sql\n";
    $db->exec($sql);
    echo "✓ Migration completed successfully!\n";
    echo "✓ Table 'mpesa_stk_transactions' has been created.\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
