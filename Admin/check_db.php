<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database configuration
require_once("../include/database.php");

// Create a new database connection
try {
    $db = new Database();
    $conn = $db->conn;
    
    // Get database name using a query since the property is private
    $dbName = $conn->query("SELECT DATABASE() as dbname")->fetch_assoc()['dbname'];
    
    echo "<h1>Database Connection Test</h1>";
    echo "<p>Connected to database: " . htmlspecialchars($dbName) . " on localhost</p>";
    
    // List all tables in the database
    $result = $conn->query("SHOW TABLES");
    
    echo "<h2>Database Tables:</h2>";
    if ($result->num_rows > 0) {
        echo "<ul>";
        while ($row = $result->fetch_row()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
        
        // Check if courses table exists and has data
        if ($conn->query("SHOW TABLES LIKE 'courses'")->num_rows > 0) {
            $courses = $conn->query("SELECT COUNT(*) as count FROM courses");
            $row = $courses->fetch_assoc();
            echo "<p>Total courses in database: " . $row['count'] . "</p>";
            
            // Show first 5 courses
            $recentCourses = $conn->query("SELECT * FROM courses LIMIT 5");
            if ($recentCourses->num_rows > 0) {
                echo "<h3>Sample Courses:</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Status</th></tr>";
                while ($course = $recentCourses->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($course['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($course['name']) . "</td>";
                    echo "<td>" . htmlspecialchars($course['code']) . "</td>";
                    echo "<td>" . htmlspecialchars($course['status']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p>No 'courses' table found in the database.</p>";
        }
    } else {
        echo "<p>No tables found in the database.</p>";
    }
    
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>Error message: " . $e->getMessage() . "</p>";
    echo "<p>Error code: " . $e->getCode() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>
