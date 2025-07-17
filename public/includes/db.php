<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'config.php';

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : 5432) . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("PostgreSQL connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

class Database {
    private $pdo;

    public function __construct() {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . (defined('DB_PORT') ? DB_PORT : 5432) . ";dbname=" . DB_NAME;
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PostgreSQL connection failed: " . $e->getMessage());
            die("Database connection error. Please try again later.");
        }
    }

    /**
     * Execute a query with optional parameters
     *
     * @param string $query SQL query
     * @param array $params Optional parameters
     * @return mixed
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            if (stripos($query, 'SELECT') === 0) {
                return $stmt->fetchAll();
            }

            return [
                'affected_rows' => $stmt->rowCount(),
                'insert_id' => $this->pdo->lastInsertId()
            ];
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage());
            return false;
        }
    }

    public function __destruct() {
        $this->pdo = null;
    }
}
?>
