<?php

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            error_log("Database Connection - Initializing connection");
            
            // Get config
            $config = require __DIR__ . '/../../config/database.php';
            error_log("Database Connection - Loaded config: " . print_r($config, true));
            
            if (!is_array($config)) {
                throw new PDOException("Invalid database configuration");
            }

            // Ensure required config keys exist
            $required = ['host', 'database', 'username', 'password', 'charset'];
            foreach ($required as $key) {
                if (!isset($config[$key])) {
                    throw new PDOException("Missing required database configuration: {$key}");
                }
            }
            
            // Build DSN
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $config['host'],
                $config['database'],
                $config['charset']
            );
            error_log("Database Connection - DSN: " . $dsn);

            // Set default options if not provided
            $options = isset($config['options']) && is_array($config['options']) 
                ? $config['options'] 
                : [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
            error_log("Database Connection - Options: " . print_r($options, true));

            // Create connection
            error_log("Database Connection - Attempting to connect");
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $options
            );
            error_log("Database Connection - Connected successfully");

            // Test connection
            $this->testConnection();

        } catch (PDOException $e) {
            // Log the error
            error_log("Database Connection Error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            
            // Show error in development
            if (getenv('APP_ENV') === 'development') {
                throw new PDOException("Connection failed: " . $e->getMessage(), (int)$e->getCode());
            } else {
                throw new PDOException("Database connection error. Please try again later.", 500);
            }
        }
    }

    private function testConnection()
    {
        try {
            error_log("Database Connection - Testing connection");
            
            // Test basic query
            $stmt = $this->connection->query('SELECT 1');
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            error_log("Database Connection - Test query result: " . print_r($result, true));

            // Test database selection
            $stmt = $this->connection->query('SELECT DATABASE()');
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            error_log("Database Connection - Current database: " . print_r($result, true));

            // Test table listing
            $stmt = $this->connection->query('SHOW TABLES');
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            error_log("Database Connection - Available tables: " . print_r($tables, true));

            // Test table structure
            if (in_array('units', $tables)) {
                $stmt = $this->connection->query('DESCRIBE units');
                $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                error_log("Database Connection - Units table structure: " . print_r($columns, true));

                // Test units data
                $stmt = $this->connection->query('SELECT COUNT(*) as count FROM units');
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                error_log("Database Connection - Units count: " . print_r($result, true));
            } else {
                error_log("Database Connection - Units table does not exist");
            }

            error_log("Database Connection - Connection test successful");
        } catch (PDOException $e) {
            error_log("Database Connection Test Error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            error_log("Database Connection - Creating new instance");
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        if (!$this->connection) {
            error_log("Database Connection - Connection is null");
            throw new PDOException("Database connection is not initialized");
        }
        return $this->connection;
    }

    private function __clone() {}
    public function __wakeup() {}
} 