<?php
require_once("../../../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Administrator') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$exerciseObj = new Exercise();

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_exercises':
        $exercises = $exerciseObj->getAllExercises();
        echo json_encode($exercises);
        break;

    case 'get_exercise':
        $id = $_GET['id'] ?? $_POST['id'] ?? 0;
        $exercise = $exerciseObj->getExercise($id);
        echo json_encode($exercise);
        break;

    case 'get_exercises_by_lesson':
        $lessonId = $_GET['lesson_id'] ?? $_POST['lesson_id'] ?? 0;
        $exercises = $exerciseObj->getExercisesByLesson($lessonId);
        $result = [];
        while ($exercise = $exercises->fetch_object()) {
            $result[] = $exercise;
        }
        echo json_encode($result);
        break;

    case 'create_exercise':
        $data = [
            'Question' => $_POST['Question'] ?? '',
            'ChoiceA' => $_POST['ChoiceA'] ?? '',
            'ChoiceB' => $_POST['ChoiceB'] ?? '',
            'ChoiceC' => $_POST['ChoiceC'] ?? '',
            'ChoiceD' => $_POST['ChoiceD'] ?? '',
            'Answer' => $_POST['Answer'] ?? '',
            'LessonID' => $_POST['LessonID'] ?? 0
        ];
        
        if ($exerciseObj->createExercise($data)) {
            echo json_encode(['success' => true, 'message' => 'Exercise created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create exercise']);
        }
        break;

    case 'update_exercise':
        $id = $_POST['ExerciseID'] ?? 0;
        $data = [
            'Question' => $_POST['Question'] ?? '',
            'ChoiceA' => $_POST['ChoiceA'] ?? '',
            'ChoiceB' => $_POST['ChoiceB'] ?? '',
            'ChoiceC' => $_POST['ChoiceC'] ?? '',
            'ChoiceD' => $_POST['ChoiceD'] ?? '',
            'Answer' => $_POST['Answer'] ?? '',
            'LessonID' => $_POST['LessonID'] ?? 0
        ];
        
        if ($exerciseObj->updateExercise($id, $data)) {
            echo json_encode(['success' => true, 'message' => 'Exercise updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update exercise']);
        }
        break;

    case 'delete_exercise':
        $id = $_POST['ExerciseID'] ?? 0;
        if ($exerciseObj->deleteExercise($id)) {
            echo json_encode(['success' => true, 'message' => 'Exercise deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete exercise']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
