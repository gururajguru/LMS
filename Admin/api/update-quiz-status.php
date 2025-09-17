<?php
require_once("../../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get and validate input
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? (int)$_GET['status'] : 0;

if ($quizId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid quiz ID']);
    exit;
}

// Initialize Quiz class and update status
$quiz = new Quiz();
if ($quiz->updateQuizStatus($quizId, $status)) {
    echo json_encode(['success' => true, 'message' => 'Quiz status updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update quiz status: ' . $quiz->getError()]);
}
?>
