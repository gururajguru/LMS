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
                // Get single lesson
                $lesson = $courses->getLessonById($_GET['id']);
                if ($lesson) {
                    echo json_encode(['success' => true, 'data' => $lesson]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Lesson not found']);
                }
            } elseif (isset($_GET['course_id'])) {
                // Get lessons for a specific course
                $lessons = $courses->getCourseLessons($_GET['course_id']);
                echo json_encode(['success' => true, 'data' => $lessons]);
            } else {
                // Get all lessons
                $allLessons = $courses->getAllLessons();
                echo json_encode(['success' => true, 'data' => $allLessons]);
            }
            break;

        case 'POST':
            // Create new lesson
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $input = $_POST;
            }
            
            $lessonData = [
                'course_id' => $input['lessonCourse'] ?? '',
                'title' => $input['lessonTitle'] ?? '',
                'description' => $input['lessonDescription'] ?? '',
                'content' => $input['lessonContent'] ?? '',
                'video_url' => $input['lessonVideoUrl'] ?? null,
                'order_number' => intval($input['lessonOrder'] ?? 1),
                'duration_minutes' => intval($input['lessonDuration'] ?? 45)
            ];
            
            // Validate required fields
            if (empty($lessonData['course_id']) || empty($lessonData['title']) || empty($lessonData['content'])) {
                echo json_encode(['success' => false, 'message' => 'Course, title, and content are required']);
                exit();
            }
            
            $result = $courses->createLesson($lessonData);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Lesson created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create lesson']);
            }
            break;

        case 'PUT':
            // Update existing lesson
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                parse_str(file_get_contents('php://input'), $input);
            }
            
            $lessonId = $input['lessonId'] ?? null;
            if (!$lessonId) {
                echo json_encode(['success' => false, 'message' => 'Lesson ID is required']);
                exit();
            }
            
            $lessonData = [
                'title' => $input['lessonTitle'] ?? '',
                'description' => $input['lessonDescription'] ?? '',
                'content' => $input['lessonContent'] ?? '',
                'video_url' => $input['lessonVideoUrl'] ?? null,
                'order_number' => intval($input['lessonOrder'] ?? 1),
                'duration_minutes' => intval($input['lessonDuration'] ?? 45)
            ];
            
            // Validate required fields
            if (empty($lessonData['title']) || empty($lessonData['content'])) {
                echo json_encode(['success' => false, 'message' => 'Title and content are required']);
                exit();
            }
            
            $result = $courses->updateLesson($lessonId, $lessonData);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Lesson updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update lesson']);
            }
            break;

        case 'DELETE':
            // Delete lesson
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                parse_str(file_get_contents('php://input'), $input);
            }
            
            $lessonId = $input['id'] ?? $_GET['id'] ?? null;
            if (!$lessonId) {
                echo json_encode(['success' => false, 'message' => 'Lesson ID is required']);
                exit();
            }
            
            $result = $courses->deleteLesson($lessonId);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Lesson deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete lesson']);
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
