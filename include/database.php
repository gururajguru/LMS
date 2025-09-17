<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Database {
    private $host = "localhost";
    private $user = "root";
    private $pass = "";
    private $db = "lms_db";
    public $conn;
    public $cur;

    public function __construct() {
        // Log connection attempt
        error_log("Attempting to connect to database: {$this->db} on {$this->host} as {$this->user}");
        
        // Create connection with error reporting
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
            
            // Set character set
            if (!$this->conn->set_charset("utf8mb4")) {
                throw new Exception("Error loading character set utf8mb4: " . $this->conn->error);
            }
            
            // Verify connection
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            error_log("Successfully connected to database");
            
        } catch (Exception $e) {
            // Log the detailed error
            $error_msg = "Database connection error: " . $e->getMessage();
            error_log($error_msg);
            
            // For API responses, output JSON
            if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
                header('Content-Type: application/json');
                die(json_encode([
                    'success' => false,
                    'message' => 'Database connection failed',
                    'error' => $error_msg
                ]));
            } else {
                // For regular pages, show a more user-friendly error
                die("<div style='font-family: Arial; padding: 20px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;'>
                        <h3>Database Connection Error</h3>
                        <p>We're having trouble connecting to the database. Please try again later.</p>
                        <p><small>Technical details: " . htmlspecialchars($e->getMessage()) . "</small></p>
                    </div>");
            }
        }
    }

    public function setQuery($sql) {
        if (!$this->conn) {
            throw new Exception("Database connection not established");
        }
        
        // Log the query for debugging
        error_log("Executing query: " . $sql);
        
        $this->cur = $this->conn->query($sql);
        if (!$this->cur) {
            throw new Exception("Query failed: " . $this->conn->error);
        }
        return $this->cur;
    }

    public function loadResultList() {
        $rows = [];
        if ($this->cur && $this->cur->num_rows > 0) {
            while ($row = $this->cur->fetch_object()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function loadSingleResult() {
        if ($this->cur && $this->cur->num_rows > 0) {
            return $this->cur->fetch_object();
        }
        return null;
    }

    public function loadResult() {
        if ($this->cur && $this->cur->num_rows > 0) {
            $row = $this->cur->fetch_object();
            return $row;
        }
        return null;
    }

    public function num_rows() {
        return $this->cur ? $this->cur->num_rows : 0;
    }

    public function affected_rows() {
        return $this->conn->affected_rows;
    }

    public function insert_id() {
        return $this->conn->insert_id;
    }

    public function escape_string($string) {
        return $this->conn->real_escape_string($string);
    }

    public function close() {
        $this->conn->close();
    }

    // Prepared statement methods
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    public function execute_prepared($stmt, $params = []) {
        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // Assume all strings for now
            if (!$stmt) {
                die("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param($types, ...$params);
        }
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        return $result;
    }

    public function get_result($stmt) {
        return $stmt->get_result();
    }
    
    public function beginTransaction() {
        return $this->conn->begin_transaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollback();
    }
}
?>

