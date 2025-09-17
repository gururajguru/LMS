<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../include/database.php';
require_once '../../include/students.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$students = new Students();

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single student
                $student = $students->getStudentById($_GET['id']);
                if ($student) {
                    echo json_encode(['success' => true, 'data' => $student]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Student not found']);
                }
            } else {
                // Get all students
                $allStudents = $students->getAllStudents();
                echo json_encode(['success' => true, 'data' => $allStudents]);
            }
            break;

        case 'POST':
            // Create new student
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                parse_str(file_get_contents('php://input'), $input);
            }
            
            $studentData = [
                'first_name' => $input['studentFirstName'] ?? '',
                'last_name' => $input['studentLastName'] ?? '',
                'email' => $input['studentEmail'] ?? '',
                'username' => $input['studentUsername'] ?? '',
                'password' => $input['studentPassword'] ?? '',
                'phone' => $input['studentPhone'] ?? '',
                'address' => $input['studentAddress'] ?? '',
                'date_of_birth' => $input['studentDateOfBirth'] ?? null,
                'gender' => $input['studentGender'] ?? null,
                'courses' => $input['studentCourses'] ?? []
            ];
            
            // Validate required fields
            if (empty($studentData['first_name']) || empty($studentData['last_name']) || 
                empty($studentData['email']) || empty($studentData['username']) || 
                empty($studentData['password'])) {
                echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                exit();
            }
            
            $result = $students->createStudent($studentData);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Student created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create student']);
            }
            break;

        case 'PUT':
            // Update existing student
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                parse_str(file_get_contents('php://input'), $input);
            }
            
            $studentId = $input['studentId'] ?? null;
            if (!$studentId) {
                echo json_encode(['success' => false, 'message' => 'Student ID is required']);
                exit();
            }
            
            $studentData = [
                'first_name' => $input['studentFirstName'] ?? '',
                'last_name' => $input['studentLastName'] ?? '',
                'email' => $input['studentEmail'] ?? '',
                'phone' => $input['studentPhone'] ?? '',
                'address' => $input['studentAddress'] ?? '',
                'date_of_birth' => $input['studentDateOfBirth'] ?? null,
                'gender' => $input['studentGender'] ?? null,
                'courses' => $input['studentCourses'] ?? []
            ];
            
            // Validate required fields
            if (empty($studentData['first_name']) || empty($studentData['last_name']) || 
                empty($studentData['email'])) {
                echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
                exit();
            }
            
            $result = $students->updateStudent($studentId, $studentData);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update student']);
            }
            break;

        case 'DELETE':
            // Delete student
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                parse_str(file_get_contents('php://input'), $input);
            }
            
            $studentId = $input['id'] ?? $_GET['id'] ?? null;
            if (!$studentId) {
                echo json_encode(['success' => false, 'message' => 'Student ID is required']);
                exit();
            }
            
            $result = $students->deleteStudent($studentId);
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Student deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete student']);
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
