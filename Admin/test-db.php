<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database class
require_once("../include/database.php");

echo "<h1>Database Connection Test</h1>";

try {
    // Create a new database connection
    $database = new Database();
    $db = $database->conn;
    
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test query
    $result = $db->query("SELECT DATABASE() as db");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Connected to database: <strong>" . htmlspecialchars($row['db']) . "</strong></p>";
    }
    
    // Check if tables exist
    echo "<h3>Checking required tables:</h3>";
    $tables = ['tblstudent', 'courses', 'course_enrollments'];
    
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<p>✅ Table <strong>$table</strong> exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table <strong>$table</strong> is missing!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Show database credentials (for debugging only - remove in production)
    echo "<h3>Database Configuration:</h3>";
    echo "<pre>" . htmlspecialchars(print_r([
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '(empty)',
        'db' => 'lms_db'
    ], true)) . "</pre>";
}

echo "<p><a href='javascript:history.back()'>Go back</a></p>";
?>
