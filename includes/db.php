<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


// create a db connection
require_once 'config.php';

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    
} catch (Exception $e) {
    error_log($e->getMessage());
    die("Database connection error. Please try again later.");
}

class Database {
    private $conn;
    
    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            error_log('Database connection failed: ' . $this->conn->connect_error);
            die('Database connection failed. Please try again later.');
        }
        
        $this->conn->set_charset('utf8mb4');
    }
    
    /**
     * Execute a query with optional parameters
     * 
     * @param string $query SQL query
     * @param array $params Optional parameters for prepared statement
     * @return mixed Result array for SELECT, affected rows for others
     */
    public function query($query, $params = []) {
        $stmt = $this->conn->prepare($query);
        
        if (!$stmt) {
            error_log('Query preparation failed: ' . $this->conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // Default to string type
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log('Query execution failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        
        // Handle SELECT queries
        if (stripos($query, 'SELECT') === 0) {
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $data;
        }
        
        // Handle INSERT, UPDATE, DELETE
        $result = [
            'affected_rows' => $stmt->affected_rows,
            'insert_id' => $stmt->insert_id
        ];
        
        $stmt->close();
        return $result;
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Usage example:
// $db = new Database();
// $users = $db->query("SELECT * FROM users WHERE id = ?", [1]);
// $result = $db->query("INSERT INTO users (name, email) VALUES (?, ?)", ['John', 'john@example.com']);