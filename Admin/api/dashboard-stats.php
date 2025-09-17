<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any unexpected output
ob_start();

try {
    require_once("../../include/initialize.php");
    
    // Set headers for JSON response
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    // Log the request
    error_log('Dashboard stats request received');

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

try {
    // Initialize database connection using the Database class
    $database = new Database();
    $db = $database->conn; // Get the database connection
    
    // Get total students (using users table with user_type = 'Student')
    $query = "SELECT COUNT(*) as total_students FROM users WHERE user_type = 'Student' AND status = 'active'";
    $result = $db->query($query);
    if (!$result) {
        throw new Exception("Error fetching student count: " . $db->error);
    }
    $total_students = $result->fetch_assoc()['total_students'];
    
    // Get total courses
    $query = "SELECT COUNT(*) as total_courses FROM courses WHERE status = 'active'";
    $result = $db->query($query);
    if (!$result) {
        throw new Exception("Error fetching courses count: " . $db->error);
    }
    $total_courses = $result->fetch_assoc()['total_courses'];
    
    // Get active courses (courses with at least one enrollment)
    $query = "SELECT COUNT(DISTINCT course_id) as active_courses FROM course_enrollments";
    $result = $db->query($query);
    if (!$result) {
        throw new Exception("Error fetching active courses: " . $db->error);
    }
    $active_courses = $result->fetch_assoc()['active_courses'];
    
    // Get completed courses (this is a simplified example - you might need to adjust based on your business logic)
    $query = "SELECT COUNT(DISTINCT course_id) as completed_courses FROM course_enrollments WHERE status = 'completed'";
    $result = $db->query($query);
    if (!$result) {
        throw new Exception("Error fetching completed courses: " . $db->error);
    }
    $completed_courses = $result->fetch_assoc()['completed_courses'];
    
    // Return the stats
    echo json_encode([
        'success' => true,
        'data' => [
            'total_students' => (int)$total_students,
            'total_courses' => (int)$total_courses,
            'active_courses' => (int)$active_courses,
            'completed_courses' => (int)$completed_courses
        ]
    ]);
    
} catch (Exception $e) {
    // Get any output that might have been generated before the error
    $unexpectedOutput = ob_get_clean();
    
    // Log the error
    error_log('Dashboard stats error: ' . $e->getMessage());
    error_log('Unexpected output: ' . $unexpectedOutput);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching dashboard stats: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'unexpected_output' => $unexpectedOutput
    ]);
}

// No need to close connection as it's handled by the Database class destructor
?>
