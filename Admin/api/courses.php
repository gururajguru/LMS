<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Log request for debugging
error_log('API Request: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);

try {
    // Debug: Log session and file existence
    error_log('Session USERID: ' . ($_SESSION['USERID'] ?? 'not set'));
    error_log('Session TYPE: ' . ($_SESSION['TYPE'] ?? 'not set'));
    error_log('Database file exists: ' . (file_exists('../../include/database.php') ? 'yes' : 'no'));

    // Check if database file exists
    if (!file_exists('../../include/database.php')) {
        throw new Exception('Database configuration file not found');
    }
    require_once '../../include/database.php';
    // Check if database connection is successful
    if (!class_exists('Database')) {
        throw new Exception('Database class not found');
    }

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    // Check if user is logged in and is admin
    if (!isset($_SESSION['USERID'])) {
    error_log('API Error: User not logged in');
    throw new Exception('User not logged in', 401);
    }
    
    if ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin') {
    error_log('API Error: Access denied. TYPE=' . ($_SESSION['TYPE'] ?? 'not set'));
    throw new Exception('Access denied. Administrator privileges required.', 403);
    }

    // Initialize database connection
    $db = new Database();
    if (!isset($db->conn) || !($db->conn instanceof mysqli)) {
        error_log('API Error: Database connection failed');
        throw new Exception('Database connection failed');
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single course
                $id = (int)$_GET['id'];
                $includeStudents = isset($_GET['students']) && $_GET['students'] == '1';
                
                // Get course details
                $result = $db->setQuery("SELECT * FROM courses WHERE id = $id");
                $course = $result->fetch_object();
                
                if ($course) {
                    $response = ['success' => true, 'data' => $course];
                    
                    // Include students if requested
                    if ($includeStudents) {
                        require_once '../../include/courses.php';
                        $courses = new Courses();
                        $students = $courses->getEnrolledStudents($id);
                        $stats = $courses->getCourseStats($id);
                        
                        $response['students'] = $students;
                        $response['stats'] = $stats;
                    }
                    
                    echo json_encode($response);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Course not found']);
                }
            } else {
                // Get all courses with student counts and progress
                $sql = "SELECT 
                            c.*, 
                            (SELECT COUNT(*) FROM course_enrollments ce WHERE ce.course_id = c.id AND ce.status = 'enrolled') as enrollment_count,
                            (SELECT COUNT(*) FROM user_progress up 
                              WHERE up.course_id = c.id AND up.completed = 1) as completed_lessons,
                            (SELECT COUNT(*) FROM lessons l WHERE l.course_id = c.id) as total_lessons
                        FROM courses c 
                        WHERE c.status = 'active'
                        ORDER BY c.name";
                        
                $result = $db->setQuery($sql);
                $courses = [];
                while ($row = $result->fetch_object()) {
                    $courses[] = $row;
                }
                echo json_encode(['success' => true, 'data' => $courses]);
            }
            break;

        case 'POST':
            // Create or update course
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            // Validate required fields
            $required = ['courseName', 'courseCode', 'courseDescription', 'courseDuration', 'courseLevel'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field " . str_replace('course', '', $field) . " is required");
                }
            }
            
            $name = $db->conn->real_escape_string($input['courseName']);
            $code = $db->conn->real_escape_string($input['courseCode']);
            $description = $db->conn->real_escape_string($input['courseDescription']);
            $duration = (int)$input['courseDuration'];
            $level = $db->conn->real_escape_string($input['courseLevel']);
            $status = 'active';
            
            if (isset($_GET['id'])) {
                // Update existing course
                $id = (int)$_GET['id'];
                
                // Check if course code already exists for another course
                $result = $db->setQuery("SELECT id FROM courses WHERE code = '$code' AND id != $id");
                if ($result->num_rows > 0) {
                    throw new Exception('A course with this code already exists');
                }
                
                $query = "UPDATE courses SET 
                         name = '$name', 
                         code = '$code', 
                         description = '$description', 
                         duration_weeks = $duration, 
                         level = '$level',
                         status = '$status',
                         updated_at = CURRENT_TIMESTAMP 
                         WHERE id = $id";
                $db->setQuery($query);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Course updated successfully'
                ]);
            } else {
                // Create new course
                
                // Check if course code already exists
                $result = $db->setQuery("SELECT id FROM courses WHERE code = '$code'");
                if ($result->num_rows > 0) {
                    throw new Exception('A course with this code already exists');
                }
                
                $query = "INSERT INTO courses (name, code, description, duration_weeks, level, status) 
                         VALUES ('$name', '$code', '$description', $duration, '$level', '$status')";
                $db->setQuery($query);
                $courseId = $db->conn->insert_id;
                
                http_response_code(201);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Course created successfully',
                    'id' => $courseId
                ]);
            }
            break;

        case 'PUT':
            // Update existing course (handled via POST with ID parameter)
            if (!isset($_GET['id'])) {
                throw new Exception('Course ID is required for update');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $courseId = $input['courseId'] ?? null;
            if (!$courseId) {
                echo json_encode(['success' => false, 'message' => 'Course ID is required']);
                exit();
            }
            
            $courseData = [
                'name' => $input['courseName'] ?? '',
                'code' => $input['courseCode'] ?? '',
                'description' => $input['courseDescription'] ?? '',
                'duration_weeks' => intval($input['courseDuration'] ?? 8),
                'level' => $input['courseLevel'] ?? 'beginner'
            ];
            
            // Validate required fields
            if (empty($courseData['name']) || empty($courseData['code'])) {
                echo json_encode(['success' => false, 'message' => 'Course name and code are required']);
                exit();
            }
            
            // Check if course code already exists for other courses
            $existingCourse = $courses->getCourseByCode($courseData['code']);
            if ($existingCourse && $existingCourse->id != $courseId) {
                echo json_encode(['success' => false, 'message' => 'Course code already exists']);
                exit();
            }
            
            // This is handled in the POST method with ID parameter
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Use POST with ID parameter to update a course']);
            break;

        case 'DELETE':
            // Delete course
            if (!isset($_GET['id'])) {
                throw new Exception('Course ID is required');
            }
            
            $id = (int)$_GET['id'];
            
            // First, delete related records in other tables (if any)
            // For example: lessons, enrollments, etc.
            
            // Then delete the course
            $db->setQuery("DELETE FROM courses WHERE id = $id");
            
            if ($db->conn->affected_rows > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Course deleted successfully'
                ]);
            } else {
                throw new Exception('Course not found or already deleted');
            }
            break;

        default:
            http_response_code(405);
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
    http_response_code($statusCode);
    
    $errorResponse = [
        'success' => false,
        'message' => $e->getMessage(),
        'error' => [
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ];
    
    // Log the full error
    error_log('API Error: ' . print_r($errorResponse, true));
    
    // Don't expose sensitive information in production
    if (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') === false) {
        unset($errorResponse['error']['file'], $errorResponse['error']['line'], $errorResponse['error']['trace']);
    }
    
    echo json_encode($errorResponse);
}
