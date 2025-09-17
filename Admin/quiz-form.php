<?php
require_once("../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    redirect(web_root . "login.php");
}

// Initialize the Quiz and Course classes
$quizObj = new Quiz();
$courseObj = new Courses();

// Check if we're editing an existing quiz
$isEdit = false;
$quiz = [];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (isset($_GET['id'])) {
    $isEdit = true;
    $quiz = $quizObj->getQuizById((int)$_GET['id']);
    if (!$quiz) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Quiz not found.'
        ];
        redirect('quizzes.php' . ($courseId ? '?course_id=' . $courseId : ''));
    }
    $courseId = $quiz['course_id'];
}

// Get course details if course_id is provided
$course = null;
if ($courseId) {
    $course = $courseObj->getCourseById($courseId);
    if (!$course) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Invalid course selected.'
        ];
        redirect('quizzes.php');
    }
}

// Get existing questions if editing
$existingQuestions = [];
if ($isEdit && !empty($quiz['id'])) {
    $existingQuestions = $quizObj->getQuestions($quiz['id']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description']),
        'course_id' => $courseId,
        'duration_minutes' => (int)$_POST['duration_minutes'],
        'passing_score' => (float)$_POST['passing_score'],
        'max_attempts' => (int)$_POST['max_attempts'],
        'show_correct_answers' => isset($_POST['show_correct_answers']) ? 1 : 0,
        'randomize_questions' => isset($_POST['randomize_questions']) ? 1 : 0,
        'randomize_answers' => isset($_POST['randomize_answers']) ? 1 : 0,
        'show_progress' => isset($_POST['show_progress']) ? 1 : 0,
        'allow_navigation' => isset($_POST['allow_navigation']) ? 1 : 0,
        'time_limit_enabled' => isset($_POST['time_limit_enabled']) ? 1 : 0,
        'status' => isset($_POST['status']) ? 1 : 0,
        'instructions' => trim($_POST['instructions'] ?? ''),
        'pass_message' => trim($_POST['pass_message'] ?? ''),
        'fail_message' => trim($_POST['fail_message'] ?? '')
    ];

    // Handle questions data
    $questions = [];
    if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
        foreach ($_POST['questions'] as $question) {
            if (!empty($question['question_text']) && !empty($question['option_a']) && 
                !empty($question['option_b']) && !empty($question['correct_answer'])) {
                $questions[] = [
                    'id' => $question['id'] ?? null,
                    'question_text' => trim($question['question_text']),
                    'option_a' => trim($question['option_a']),
                    'option_b' => trim($question['option_b']),
                    'option_c' => trim($question['option_c'] ?? ''),
                    'option_d' => trim($question['option_d'] ?? ''),
                    'correct_answer' => $question['correct_answer'],
                    'points' => (int)($question['points'] ?? 1),
                    'explanation' => trim($question['explanation'] ?? '')
                ];
            }
        }
    }
    
    $data['questions'] = $questions;

    // Add ID if editing
    if ($isEdit) {
        $data['id'] = $quiz['id'];
    }

    // Validate
    $errors = [];
    if (empty($data['title'])) {
        $errors[] = 'Title is required';
    }
    
    if (empty($data['questions'])) {
        $errors[] = 'At least one valid question is required';
    }

    if (!empty($errors)) {
        $error = '<ul class="mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul>';
    } else {
        // Save quiz and questions
        $quizId = $quizObj->saveQuiz($data, $questions);
        
        if ($quizId) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Quiz ' . ($isEdit ? 'updated' : 'created') . ' successfully.'
            ];
            redirect('quizzes.php?course_id=' . $courseId);
        } else {
            $error = 'An error occurred while saving the quiz. Please try again.';
        }
    }
}

$pageTitle = ($isEdit ? 'Edit' : 'Create') . ' Quiz' . ($course ? ' - ' . htmlspecialchars($course['name']) : '');
include('includes/header.php');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus' ?> me-2"></i>
            <?= $isEdit ? 'Edit Quiz' : 'Create New Quiz' ?>
        </h1>
        <div>
            <a href="quizzes.php<?= $courseId ? '?course_id=' . $courseId : ''; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Quizzes
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" id="quizForm">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Quiz Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?= htmlspecialchars($quiz['title'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="3"><?= htmlspecialchars($quiz['description'] ?? '') ?></textarea>
                                    <div class="form-text">A brief description of what this quiz is about.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="instructions" class="form-label">Instructions</label>
                                    <textarea class="form-control rich-text-editor" id="instructions" 
                                              name="instructions" rows="5"><?= 
                                        htmlspecialchars($quiz['instructions'] ?? '') 
                                    ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Quiz Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="duration_minutes" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="duration_minutes" 
                                                   name="duration_minutes" min="1" required
                                                   value="<?= $quiz['duration_minutes'] ?? 30 ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="passing_score" class="form-label">Passing Score (%) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="passing_score" 
                                                   name="passing_score" min="1" max="100" required
                                                   value="<?= $quiz['passing_score'] ?? 70 ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_attempts" class="form-label">Maximum Attempts</label>
                                            <input type="number" class="form-control" id="max_attempts" 
                                                   name="max_attempts" min="0" 
                                                   value="<?= $quiz['max_attempts'] ?? 0 ?>">
                                            <div class="form-text">Set to 0 for unlimited attempts</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Options</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="show_correct_answers" 
                                                       name="show_correct_answers" value="1"
                                                       <?= ($quiz['show_correct_answers'] ?? 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="show_correct_answers">
                                                    Show correct answers after submission
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="randomize_questions" 
                                                       name="randomize_questions" value="1"
                                                       <?= ($quiz['randomize_questions'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="randomize_questions">
                                                    Randomize question order
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="randomize_answers" 
                                                       name="randomize_answers" value="1"
                                                       <?= ($quiz['randomize_answers'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="randomize_answers">
                                                    Randomize answer order
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Time Limit</label>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="time_limit_enabled" 
                                               name="time_limit_enabled" value="1"
                                               <?= ($quiz['time_limit_enabled'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="time_limit_enabled">
                                            Enable time limit
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        When enabled, the quiz will be automatically submitted when the time expires.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Navigation</label>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="allow_navigation" 
                                               name="allow_navigation" value="1"
                                               <?= ($quiz['allow_navigation'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="allow_navigation">
                                            Allow navigation between questions
                                        </label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="show_progress" 
                                               name="show_progress" value="1"
                                               <?= ($quiz['show_progress'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_progress">
                                            Show progress bar
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Questions Section -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Questions</h6>
                                <button type="button" class="btn btn-sm btn-primary" id="addQuestionBtn">
                                    <i class="fas fa-plus me-1"></i> Add Question
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="questionsContainer">
                                    <!-- Questions will be added here dynamically -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Result Messages</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="pass_message" class="form-label">Pass Message</label>
                                    <textarea class="form-control" id="pass_message" name="pass_message" 
                                              rows="2"><?= htmlspecialchars($quiz['pass_message'] ?? 'Congratulations! You have passed the quiz.') ?></textarea>
                                    <div class="form-text">Displayed when the user passes the quiz.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fail_message" class="form-label">Fail Message</label>
                                    <textarea class="form-control" id="fail_message" name="fail_message" 
                                              rows="2"><?= htmlspecialchars($quiz['fail_message'] ?? 'You did not pass the quiz. Please try again.') ?></textarea>
                                    <div class="form-text">Displayed when the user fails the quiz.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Publish</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="status" 
                                               name="status" value="1" 
                                               <?= ($quiz['status'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="status">
                                            <?= ($quiz['status'] ?? 1) ? 'Active' : 'Inactive' ?>
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Inactive quizzes won't be visible to students.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="save" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> 
                                        <?= $isEdit ? 'Update Quiz' : 'Create Quiz' ?>
                                    </button>
                                    
                                    <a href="quizzes.php<?= $courseId ? '?course_id=' . $courseId : ''; ?>" 
                                       class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                                
                                <?php if ($isEdit): ?>
                                    <hr>
                                    <div class="text-muted small">
                                        <div>Created: <?= date('M j, Y', strtotime($quiz['created_at'])) ?></div>
                                        <?php if ($quiz['updated_at']): ?>
                                            <div>Last Updated: <?= date('M j, Y', strtotime($quiz['updated_at'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Course</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($course): ?>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($course['image'])): ?>
                                            <img src="../<?= htmlspecialchars($course['image']) ?>" 
                                                 alt="<?= htmlspecialchars($course['name']) ?>" 
                                                 class="rounded me-2" 
                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($course['name']) ?></h6>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($course['code'] ?? '') ?>
                                            </small>
                                        </div>
                                    </div>
                                    <input type="hidden" name="course_id" value="<?= $courseId ?>">
                                <?php else: ?>
                                    <select class="form-select" name="course_id" required>
                                        <option value="">Select Course</option>
                                        <?php 
                                        $courses = $courseObj->getAllCourses();
                                        foreach ($courses as $c): 
                                            $courseData = (array)$c; // Convert object to array if needed
                                        ?>
                                            <option value="<?= $courseData['id'] ?>" 
                                                <?= ($courseId == $courseData['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($courseData['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($isEdit): ?>
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Questions:</span>
                                        <strong><?= (int)($quiz['question_count'] ?? 0) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Attempts:</span>
                                        <strong><?= (int)($quiz['attempt_count'] ?? 0) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Average Score:</span>
                                        <strong><?= number_format($quiz['average_score'] ?? 0, 1) ?>%</strong>
                                    </div>
                                    
                                    <hr>
                                    
                                    <a href="quiz-questions.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="fas fa-question-circle me-1"></i> Manage Questions
                                    </a>
                                    
                                    <a href="quiz-attempts.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-history me-1"></i> View Attempts
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<?php if ($isEdit): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the quiz "<strong><?= htmlspecialchars($quiz['title']) ?></strong>"? This action cannot be undone.</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> All related data (questions, attempts, results) will also be removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="quizzes.php?action=delete&id=<?= $quiz['id'] ?>" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Include TinyMCE for rich text editor -->
<script src="https://cdn.tiny.cloud/1/your-tinymce-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>

<script>
// Initialize TinyMCE
tinymce.init({
    selector: '.rich-text-editor',
    plugins: 'lists link image table code help',
    toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
    menubar: false,
    height: 200,
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
    // Add upload handler for images
    images_upload_handler: function (blobInfo, success, failure) {
        var xhr, formData;
        
        xhr = new XMLHttpRequest();
        xhr.withCredentials = false;
        xhr.open('POST', 'upload.php');
        
        xhr.onload = function() {
            var json;
            
            if (xhr.status != 200) {
                failure('HTTP Error: ' + xhr.status);
                return;
            }
            
            json = JSON.parse(xhr.responseText);
            
            if (!json || typeof json.location != 'string') {
                failure('Invalid JSON: ' + xhr.responseText);
                return;
            }
            
            success(json.location);
        };
        
        formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        formData.append('type', 'image');
        
        xhr.send(formData);
    }
});

// Form validation and submission
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('quizForm');
    
    // Delete button handler
    const deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        });
    }
    
    // Question management
    let questionCounter = 0;
    const questionsContainer = document.getElementById('questionsContainer');
    const addQuestionBtn = document.getElementById('addQuestionBtn');
    
    // Add question
    function addQuestion(questionData = {}) {
        const questionId = questionData.id || `question-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
        const questionIndex = questionCounter++;
        
        const questionCard = document.createElement('div');
        questionCard.className = 'card mb-3 question-card';
        questionCard.dataset.questionId = questionId;
        
        questionCard.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Question #${questionIndex + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-question">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="card-body">
                <input type="hidden" name="questions[${questionIndex}][id]" value="${questionData.id || ''}">
                <div class="mb-3">
                    <label class="form-label">Question Text <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="questions[${questionIndex}][question_text]" 
                           value="${questionData.question_text || ''}" required>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Option A <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="questions[${questionIndex}][option_a]" 
                                   value="${questionData.option_a || ''}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Option B <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="questions[${questionIndex}][option_b]" 
                                   value="${questionData.option_b || ''}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Option C</label>
                            <input type="text" class="form-control" name="questions[${questionIndex}][option_c]" 
                                   value="${questionData.option_c || ''}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Option D</label>
                            <input type="text" class="form-control" name="questions[${questionIndex}][option_d]" 
                                   value="${questionData.option_d || ''}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Correct Answer <span class="text-danger">*</span></label>
                            <select class="form-select" name="questions[${questionIndex}][correct_answer]" required>
                                <option value="" ${!questionData.correct_answer ? 'selected' : ''}>Select correct answer</option>
                                <option value="a" ${questionData.correct_answer === 'a' ? 'selected' : ''}>Option A</option>
                                <option value="b" ${questionData.correct_answer === 'b' ? 'selected' : ''}>Option B</option>
                                <option value="c" ${questionData.correct_answer === 'c' ? 'selected' : ''}>Option C</option>
                                <option value="d" ${questionData.correct_answer === 'd' ? 'selected' : ''}>Option D</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" class="form-control" name="questions[${questionIndex}][points]" 
                                   min="1" value="${questionData.points || 1}">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Explanation (optional)</label>
                    <textarea class="form-control rich-text-editor" name="questions[${questionIndex}][explanation]" 
                              rows="2">${questionData.explanation || ''}</textarea>
                </div>
            </div>
        `;
        
        questionsContainer.appendChild(questionCard);
        
        // Initialize TinyMCE for the new explanation field
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: `[name="questions[${questionIndex}][explanation]"]`,
                menubar: false,
                plugins: 'lists link',
                toolbar: 'undo redo | bold italic | bullist numlist | link',
                height: 100
            });
        }
        
        // Add event listener to the remove button
        const removeBtn = questionCard.querySelector('.remove-question');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to remove this question?')) {
                    questionCard.remove();
                    updateQuestionNumbers();
                }
            });
        }
        
        return questionCard;
    }
    
    // Update question numbers
    function updateQuestionNumbers() {
        const questions = document.querySelectorAll('.question-card');
        questions.forEach((question, index) => {
            const header = question.querySelector('.card-header h6');
            if (header) {
                header.textContent = `Question #${index + 1}`;
            }
        });
    }
    
    // Add question button
    addQuestionBtn.addEventListener('click', function() {
        addQuestion();
    });
    
    // Form validation
    const form = document.getElementById('quizForm');
    form.addEventListener('submit', function(e) {
        // Check if at least one question exists
        const questionCards = document.querySelectorAll('.question-card');
        if (questionCards.length === 0) {
            e.preventDefault();
            alert('Please add at least one question');
            return false;
        }
        
        // Validate each question
        let isValid = true;
        questionCards.forEach((card, index) => {
            const questionText = card.querySelector('[name$="[question_text]"]').value.trim();
            const optionA = card.querySelector('[name$="[option_a]"]').value.trim();
            const optionB = card.querySelector('[name$="[option_b]"]').value.trim();
            const correctAnswer = card.querySelector('[name$="[correct_answer]"]').value;
            
            if (!questionText || !optionA || !optionB || !correctAnswer) {
                isValid = false;
                card.classList.add('border-danger');
            } else {
                card.classList.remove('border-danger');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields for each question');
            return false;
        }
        
        return true;
    });
    
    // Add sample question if no questions exist
    if (document.querySelectorAll('.question-card').length === 0) {
        addQuestion();
    }
    
    // Toggle time limit settings
    const timeLimitEnabled = document.getElementById('time_limit_enabled');
    const durationField = document.getElementById('duration_minutes');
    
    if (timeLimitEnabled) {
        timeLimitEnabled.addEventListener('change', function() {
            durationField.disabled = !this.checked;
        });
        
        // Initialize on page load
        durationField.disabled = !timeLimitEnabled.checked;
    }
    
    // Form submission
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
});

<style>
    .question-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,.125);
    }
    .question-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .question-card.border-danger {
        border-color: #dc3545 !important;
    }
    .remove-question {
        padding: 0.25rem 0.5rem;
        line-height: 1;
    }
    .rich-text-editor {
        min-height: 100px;
    }
</style>

<?php include('includes/footer.php'); ?>
