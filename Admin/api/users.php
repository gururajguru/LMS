<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../include/database.php';
require_once '../../include/users.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is logged in and is admin
session_start();
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Administrator') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$users = new Users();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single user
                $user = $users->getUserById($_GET['id']);
                if ($user) {
                    echo json_encode(['success' => true, 'data' => $user]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            } else {
                // Get all users
                $allUsers = $users->getAllUsers();
                echo json_encode(['success' => true, 'data' => $allUsers]);
            }
            break;

        case 'POST':
            // Create new user
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (empty($input['username']) || empty($input['email']) || empty($input['first_name']) || empty($input['last_name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Username, email, first name, and last name are required']);
                break;
            }
            
            $userId = $users->createUser($input);
            if ($userId) {
                $newUser = $users->getUserById($userId);
                echo json_encode(['success' => true, 'message' => 'User created successfully', 'data' => $newUser]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            }
            break;

        case 'PUT':
            // Update user
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required']);
                break;
            }
            
            $success = $users->updateUser($input['id'], $input);
            if ($success) {
                $updatedUser = $users->getUserById($input['id']);
                echo json_encode(['success' => true, 'message' => 'User updated successfully', 'data' => $updatedUser]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user']);
            }
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'User ID is required']);
                break;
            }
            
            $success = $users->deleteUser($input['id']);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>