<?php

// Default database configuration
$config = [
    'host' => 'localhost',
    'database' => 'opulentl_rentsmart',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

// Override with environment variables if they exist
if (getenv('DB_HOST')) $config['host'] = getenv('DB_HOST');
if (getenv('DB_NAME')) $config['database'] = getenv('DB_NAME');
if (getenv('DB_USER')) $config['username'] = getenv('DB_USER');
if (getenv('DB_PASS')) $config['password'] = getenv('DB_PASS');

// Log the final configuration
error_log("Database Config - Final configuration: " . print_r($config, true));

// Test database connection
try {
    error_log("Database Config - Testing connection");
    $dsn = sprintf(
        "mysql:host=%s;dbname=%s;charset=%s",
        $config['host'],
        $config['database'],
        $config['charset']
    );
    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $config['options']
    );
    error_log("Database Config - Connection successful");

    // Test database selection
    $stmt = $pdo->query('SELECT DATABASE()');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Database Config - Current database: " . print_r($result, true));

    // Test table listing
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    error_log("Database Config - Available tables: " . print_r($tables, true));

    // Test units table
    if (in_array('units', $tables)) {
        $stmt = $pdo->query('DESCRIBE units');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Database Config - Units table structure: " . print_r($columns, true));

        // Test units data
        $stmt = $pdo->query('SELECT COUNT(*) as count FROM units');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Database Config - Units count: " . print_r($result, true));

        // Test units data for a specific property
        $stmt = $pdo->query('SELECT * FROM units LIMIT 1');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Database Config - Sample unit: " . print_r($result, true));
    } else {
        error_log("Database Config - Units table does not exist");
    }
} catch (PDOException $e) {
    error_log("Database Config Error: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
}

return $config; 