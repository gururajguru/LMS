<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../include/database.php';
require_once '../../include/students.php';
require_once '../../include/courses.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$students = new Students();
$courses = new Courses();
$database = new Database();

// Get the request body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    parse_str(file_get_contents('php://input'), $input);
}

try {
    switch ($method) {
        case 'GET':
            // Get enrolled students for a course
            if (isset($_GET['course_id'])) {
                $courseId = $_GET['course_id'];
                $enrolledStudents = $courses->getEnrolledStudents($courseId);
                echo json_encode(['success' => true, 'students' => $enrolledStudents]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Course ID is required']);
            }
            break;

        case 'POST':
            // Enroll or unenroll students
            if (!isset($input['action'])) {
                throw new Exception('Action is required');
            }

            $action = $input['action'];
            $courseId = $input['course_id'] ?? null;
            
            if (!$courseId) {
                throw new Exception('Course ID is required');
            }

            // Begin transaction
            $database->conn->begin_transaction();

            try {
                if ($action === 'enroll') {
                    // Enroll students in a course
                    $studentIds = $input['student_ids'] ?? [];
                    
                    if (!is_array($studentIds) || empty($studentIds)) {
                        throw new Exception('No students selected');
                    }

                    $enrolledCount = 0;
                    
                    foreach ($studentIds as $studentId) {
                        // Check if student is already enrolled
                        $checkSql = "SELECT id FROM course_enrollments 
                                    WHERE student_id = ? AND course_id = ? AND status = 'enrolled'";
                        $checkStmt = $database->prepare($checkSql);
                        $database->execute_prepared($checkStmt, [$studentId, $courseId]);
                        
                        if ($database->num_rows() === 0) {
                            // Enroll the student
                            $sql = "INSERT INTO course_enrollments 
                                    (student_id, course_id, enrollment_date, status, progress_percentage) 
                                    VALUES (?, ?, NOW(), 'enrolled', 0)";
                            $stmt = $database->prepare($sql);
                            $database->execute_prepared($stmt, [$studentId, $courseId]);
                            $enrolledCount++;
                        }
                    }

                    $database->conn->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => "Successfully enrolled $enrolledCount student(s)",
                        'enrolled_count' => $enrolledCount
                    ]);

                } elseif ($action === 'unenroll') {
                    // Unenroll a student from a course
                    $studentId = $input['student_id'] ?? null;
                    
                    if (!$studentId) {
                        throw new Exception('Student ID is required');
                    }

                    // Mark enrollment as inactive instead of deleting to preserve history
                    $sql = "UPDATE course_enrollments 
                            SET status = 'inactive', completion_date = NOW() 
                            WHERE student_id = ? AND course_id = ? AND status = 'enrolled'";
                    $stmt = $database->prepare($sql);
                    $database->execute_prepared($stmt, [$studentId, $courseId]);
                    
                    $affectedRows = $database->affected_rows();
                    $database->conn->commit();
                    
                    if ($affectedRows > 0) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Student has been unenrolled from the course',
                            'unenrolled' => true
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Student is not enrolled in this course or already unenrolled'
                        ]);
                    }
                } else {
                    throw new Exception('Invalid action');
                }
            } catch (Exception $e) {
                $database->conn->rollback();
                throw $e;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
