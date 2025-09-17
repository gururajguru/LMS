<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../include/database.php';
require_once '../../include/courses.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$db = new Database();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // List topics with lesson and course names
            $sql = "SELECT t.*, l.title AS lesson_title, c.name AS course_name
                    FROM topics t
                    JOIN lessons l ON t.lesson_id = l.id
                    JOIN courses c ON l.course_id = c.id
                    WHERE t.status = 'active'
                    ORDER BY c.name, l.order_number, t.order_number";
            $db->setQuery($sql);
            echo json_encode(['success' => true, 'data' => $db->loadResultList()]);
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $lessonId = $input['topicLesson'] ?? $input['lesson_id'] ?? null;
            $title = $input['topicTitle'] ?? $input['title'] ?? '';
            $description = $input['topicDescription'] ?? $input['description'] ?? null;
            $content = $input['topicContent'] ?? $input['content'] ?? null;
            $order = intval($input['topicOrder'] ?? $input['order_number'] ?? 0);

            if (empty($lessonId) || empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Lesson and title are required']);
                exit();
            }

            $stmt = $db->prepare("INSERT INTO topics (lesson_id, title, description, content, order_number, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)");
            $db->execute_prepared($stmt, [strval($lessonId), $title, $description, $content, strval($order)]);
            echo json_encode(['success' => true, 'message' => 'Topic created successfully']);
            break;
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) { parse_str(file_get_contents('php://input'), $input); }
            $topicId = $input['topicId'] ?? $input['id'] ?? null;
            if (!$topicId) {
                echo json_encode(['success' => false, 'message' => 'Topic ID is required']);
                exit();
            }
            $title = $input['topicTitle'] ?? $input['title'] ?? '';
            $description = $input['topicDescription'] ?? $input['description'] ?? null;
            $content = $input['topicContent'] ?? $input['content'] ?? null;
            $order = intval($input['topicOrder'] ?? $input['order_number'] ?? 0);

            if (empty($title)) {
                echo json_encode(['success' => false, 'message' => 'Title is required']);
                exit();
            }

            $stmt = $db->prepare("UPDATE topics SET title = ?, description = ?, content = ?, order_number = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $db->execute_prepared($stmt, [$title, $description, $content, strval($order), strval($topicId)]);
            echo json_encode(['success' => true, 'message' => 'Topic updated successfully']);
            break;
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) { parse_str(file_get_contents('php://input'), $input); }
            $topicId = $input['id'] ?? $_GET['id'] ?? null;
            if (!$topicId) {
                echo json_encode(['success' => false, 'message' => 'Topic ID is required']);
                exit();
            }
            // Soft delete
            $stmt = $db->prepare("UPDATE topics SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $db->execute_prepared($stmt, [strval($topicId)]);
            echo json_encode(['success' => true, 'message' => 'Topic deleted successfully']);
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