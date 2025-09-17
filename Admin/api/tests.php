<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../include/database.php';
require_once '../../include/courses.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$courses = new Courses();

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single test
                $test = $courses->getTestById($_GET['id']);
                if ($test) {
                    echo json_encode(['success' => true, 'data' => $test]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Test not found']);
                }
            } elseif (isset($_GET['course_id'])) {
                // Get tests for a specific course
                $tests = $courses->getCourseTests($_GET['course_id']);
                echo json_encode(['success' => true, 'data' => $tests]);
            } else {
                // Get all tests
                $allTests = $courses->getAllTests();
                echo json_encode(['success' => true, 'data' => $allTests]);
            }
            break;

        case 'POST':
            // Create new test
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $testData = [
                'course_id' => $input['testCourse'] ?? '',
                'title' => $input['testName'] ?? '',
                'description' => $input['testDescription'] ?? '',
                'duration_minutes' => intval($input['testDuration'] ?? 30),
                'passing_score' => floatval($input['testPassingScore'] ?? 70.00),
                'max_attempts' => intval($input['testAttempts'] ?? 3),
                'instructions' => $input['testInstructions'] ?? null
            ];
            
            // Validate required fields
            if (empty($testData['course_id']) || empty($testData['title'])) {
                echo json_encode(['success' => false, 'message' => 'Course and title are required']);
                exit();
            }
            
            $result = $courses->createTest($testData);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Test created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create test']);
            }
            break;

        case 'PUT':
            // Update existing test
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                parse_str(file_get_contents('php://input'), $input);
            }
            
            $testId = $input['testId'] ?? null;
            if (!$testId) {
                echo json_encode(['success' => false, 'message' => 'Test ID is required']);
                exit();
            }
            
            $testData = [
                'title' => $input['testName'] ?? '',
                'description' => $input['testDescription'] ?? '',
                'duration_minutes' => intval($input['testDuration'] ?? 30),
                'passing_score' => floatval($input['testPassingScore'] ?? 70.00),
                'max_attempts' => intval($input['testAttempts'] ?? 3),
                'instructions' => $input['testInstructions'] ?? null
            ];
            
            // Validate required fields
            if (empty($testData['title'])) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit();
            }
            
            $result = $courses->updateTest($testId, $testData);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Test updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update test']);
            }
            break;

        case 'DELETE':
            // Delete test
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                parse_str(file_get_contents('php://input'), $input);
            }
            
            $testId = $input['id'] ?? $_GET['id'] ?? null;
            if (!$testId) {
                echo json_encode(['success' => false, 'message' => 'Test ID is required']);
                exit();
            }
            
            $result = $courses->deleteTest($testId);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Test deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete test']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
