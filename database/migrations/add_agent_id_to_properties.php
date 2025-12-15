<?php

/**
 * Migration to add agent_id column to properties table
 * This allows agents to directly create and manage properties
 */

// Database connection
$host = 'localhost';
$dbname = 'opulentl_rentsmart';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Adding agent_id column to properties table...\n";
    
    // Add agent_id column to properties table
    $sql = "ALTER TABLE properties ADD COLUMN agent_id INT(11) DEFAULT NULL AFTER manager_id";
    $pdo->exec($sql);
    
    // Add foreign key constraint
    $sql = "ALTER TABLE properties ADD CONSTRAINT fk_properties_agent_id 
            FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL";
    $pdo->exec($sql);
    
    // Add index for better performance
    $sql = "CREATE INDEX idx_properties_agent_id ON properties(agent_id)";
    $pdo->exec($sql);
    
    echo "agent_id column added successfully!\n";
    
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "agent_id column already exists. Skipping migration.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "Migration completed successfully!\n";
