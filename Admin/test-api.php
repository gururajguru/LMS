<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database class
require_once("../include/initialize.php");

// Set headers for JSON response
header('Content-Type: text/plain');

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    http_response_code(401);
    echo "Unauthorized access";
    exit();
}

// Test the query directly
try {
    $database = new Database();
    $db = $database->conn;
    
    // Test 1: Check users table
    echo "=== Testing users table ===\n";
    $query = "SHOW TABLES LIKE 'users'";
    $result = $db->query($query);
    if ($result && $result->num_rows > 0) {
        echo "✅ Users table exists\n";
        
        // Count students
        $query = "SELECT COUNT(*) as count FROM users WHERE TYPE='Student'";
        $result = $db->query($query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✅ Found $count students\n";
        } else {
            echo "❌ Error counting students: " . $db->error . "\n";
        }
    } else {
        echo "❌ Users table does not exist\n";
    }
    
    // Test 2: Check courses table
    echo "\n=== Testing courses table ===\n";
    $query = "SHOW TABLES LIKE 'courses'";
    $result = $db->query($query);
    if ($result && $result->num_rows > 0) {
        echo "✅ Courses table exists\n";
        
        // Count active courses
        $query = "SELECT COUNT(*) as count FROM courses WHERE status = 'active'";
        $result = $db->query($query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✅ Found $count active courses\n";
        } else {
            echo "❌ Error counting courses: " . $db->error . "\n";
        }
    } else {
        echo "❌ Courses table does not exist\n";
    }
    
    // Test 3: Check course_enrollments table
    echo "\n=== Testing course_enrollments table ===\n";
    $query = "SHOW TABLES LIKE 'course_enrollments'";
    $result = $db->query($query);
    if ($result && $result->num_rows > 0) {
        echo "✅ Course_enrollments table exists\n";
        
        // Count enrollments
        $query = "SELECT COUNT(DISTINCT course_id) as count FROM course_enrollments";
        $result = $db->query($query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "✅ Found $count courses with enrollments\n";
        } else {
            echo "❌ Error counting enrollments: " . $db->error . "\n";
        }
    } else {
        echo "❌ Course_enrollments table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
