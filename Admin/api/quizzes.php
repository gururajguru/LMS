<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../include/database.php';
require_once '../../include/courses.php';

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Administrator') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$courses = new Courses();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get specific quiz
                $quiz = $courses->getQuizById($_GET['id']);
                if ($quiz) {
                    echo json_encode(['success' => true, 'data' => $quiz]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Quiz not found']);
                }
            } else {
                // Get all quizzes
                $quizzes = $courses->getAllQuizzes();
                echo json_encode(['success' => true, 'data' => $quizzes]);
            }
            break;
            
        case 'POST':
            // Create new quiz
            $input = json_decode(file_get_contents('php://input'), true);
            $result = $courses->createQuiz($input);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Quiz created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create quiz']);
            }
            break;
            
        case 'PUT':
            // Update quiz
            if (isset($_GET['id'])) {
                $input = json_decode(file_get_contents('php://input'), true);
                $result = $courses->updateQuiz($_GET['id'], $input);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Quiz updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update quiz']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Quiz ID required']);
            }
            break;
            
        case 'DELETE':
            // Delete quiz
            if (isset($_GET['id'])) {
                $result = $courses->deleteQuiz($_GET['id']);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Quiz deleted successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete quiz']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Quiz ID required']);
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




