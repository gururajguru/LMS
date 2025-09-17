<?php
require_once("../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    redirect(web_root . "login.php");
}

// Initialize the Test and Course classes
$testObj = new Test();
$courseObj = new Courses();

// Check if we're editing an existing test
$isEdit = false;
$test = [];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (isset($_GET['id'])) {
    $isEdit = true;
    $test = $testObj->getTestById((int)$_GET['id']);
    if (!$test) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Test not found.'
        ];
        redirect('tests.php' . ($courseId ? '?course_id=' . $courseId : ''));
    }
    $courseId = $test['course_id'];
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
        redirect('tests.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $errors = [];
    
    if (empty(trim($_POST['title']))) {
        $errors[] = 'Test title is required';
    }
    
    if (empty(trim($_POST['description']))) {
        $errors[] = 'Test description is required';
    }
    
    // Check if we have questions
    $questions = [];
    if (!empty($_POST['questions'])) {
        // Handle both array and JSON string input
        $questions = is_string($_POST['questions']) ? json_decode($_POST['questions'], true) : $_POST['questions'];
        
        if (empty($questions)) {
            $errors[] = 'At least one valid question is required';
        } else {
            // Validate each question
            foreach ($questions as $index => $question) {
                if (empty(trim($question['question_text'] ?? ''))) {
                    $errors[] = "Question #" . ($index + 1) . " text is required";
                }
                if (empty(trim($question['option_a'] ?? '')) || empty(trim($question['option_b'] ?? ''))) {
                    $errors[] = "Question #" . ($index + 1) . " must have at least two options";
                }
                if (empty($question['correct_answer'] ?? '')) {
                    $errors[] = "Question #" . ($index + 1) . " must have a correct answer selected";
                }
            }
        }
    } else {
        $errors[] = 'At least one question is required';
    }
    
    if (!empty($errors)) {
        $error = '<ul class="mb-0"><li>' . implode('</li><li>', $errors) . '</li></ul>';
    } else {
        // Prepare questions data
        $questionsData = [];
        if (!empty($_POST['questions'])) {
            if (is_string($_POST['questions'])) {
                $questionsData = json_decode($_POST['questions'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error = 'Invalid questions format';
                    error_log('JSON decode error: ' . json_last_error_msg());
                }
            } else {
                $questionsData = $_POST['questions'];
            }
        }

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
            'fail_message' => trim($_POST['fail_message'] ?? ''),
            'questions' => $questionsData
        ];

        // Add ID if editing
        if ($isEdit) {
            $data['id'] = $test['id'];
        }

        // Save test
        $result = $isEdit ? $testObj->updateTest($data) : $testObj->addTest($data);
        
        if ($result) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Test ' . ($isEdit ? 'updated' : 'created') . ' successfully.'
            ];
            redirect('tests.php?course_id=' . $courseId);
        } else {
            $error = $testObj->getError() ?: 'An error occurred while saving the test.';
        }
    }
}

$pageTitle = ($isEdit ? 'Edit' : 'Create') . ' Test' . ($course ? ' - ' . htmlspecialchars($course['name']) : '');
include('includes/header.php');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus' ?> me-2"></i>
            <?= $isEdit ? 'Edit Test' : 'Create New Test' ?>
        </h1>
        <div>
            <a href="tests.php<?= $courseId ? '?course_id=' . $courseId : ''; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Tests
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
            <form method="post" id="testForm">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Test Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?= htmlspecialchars($test['title'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="3"><?= htmlspecialchars($test['description'] ?? '') ?></textarea>
                                    <div class="form-text">A brief description of what this test is about.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="instructions" class="form-label">Instructions</label>
                                    <textarea class="form-control rich-text-editor" id="instructions" 
                                              name="instructions" rows="5"><?= 
                                        htmlspecialchars($test['instructions'] ?? '') 
                                    ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Test Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="duration_minutes" class="form-label">Duration (minutes) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="duration_minutes" 
                                                   name="duration_minutes" min="1" required
                                                   value="<?= $test['duration_minutes'] ?? 30 ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="passing_score" class="form-label">Passing Score (%) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="passing_score" 
                                                   name="passing_score" min="1" max="100" required
                                                   value="<?= $test['passing_score'] ?? 70 ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="max_attempts" class="form-label">Maximum Attempts</label>
                                            <input type="number" class="form-control" id="max_attempts" 
                                                   name="max_attempts" min="0" 
                                                   value="<?= $test['max_attempts'] ?? 0 ?>">
                                            <div class="form-text">Set to 0 for unlimited attempts</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Options</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="show_correct_answers" 
                                                       name="show_correct_answers" value="1"
                                                       <?= ($test['show_correct_answers'] ?? 1) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="show_correct_answers">
                                                    Show correct answers after submission
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="randomize_questions" 
                                                       name="randomize_questions" value="1"
                                                       <?= ($test['randomize_questions'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="randomize_questions">
                                                    Randomize question order
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="randomize_answers" 
                                                       name="randomize_answers" value="1"
                                                       <?= ($test['randomize_answers'] ?? 0) ? 'checked' : '' ?>>
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
                                               <?= ($test['time_limit_enabled'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="time_limit_enabled">
                                            Enable time limit
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        When enabled, the test will be automatically submitted when the time expires.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Navigation</label>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="allow_navigation" 
                                               name="allow_navigation" value="1"
                                               <?= ($test['allow_navigation'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="allow_navigation">
                                            Allow navigation between questions
                                        </label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="show_progress" 
                                               name="show_progress" value="1"
                                               <?= ($test['show_progress'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="show_progress">
                                            Show progress bar
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Result Messages</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="pass_message" class="form-label">Pass Message</label>
                                    <textarea class="form-control" id="pass_message" name="pass_message" 
                                              rows="2"><?= htmlspecialchars($test['pass_message'] ?? 'Congratulations! You have passed the test.') ?></textarea>
                                    <div class="form-text">Displayed when the user passes the test.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fail_message" class="form-label">Fail Message</label>
                                    <textarea class="form-control" id="fail_message" name="fail_message" 
                                              rows="2"><?= htmlspecialchars($test['fail_message'] ?? 'You did not pass the test. Please try again.') ?></textarea>
                                    <div class="form-text">Displayed when the user fails the test.</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Questions Section -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Test Questions</h6>
                                <button type="button" class="btn btn-sm btn-primary" id="addQuestionBtn">
                                    <i class="fas fa-plus me-1"></i> Add Question
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="questionsContainer">
                                    <!-- Questions will be added here dynamically -->
                                    <?php if ($isEdit && !empty($test['questions'])): ?>
                                        <?php foreach ($test['questions'] as $index => $question): ?>
                                            <div class="question-card card mb-3" data-index="<?= $index ?>">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <h6 class="mb-0">Question #<span class="question-number"><?= $index + 1 ?></span></h6>
                                                        <button type="button" class="btn btn-sm btn-danger remove-question">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Question Text</label>
                                                        <input type="text" name="questions[<?= $index ?>][question_text]" 
                                                               class="form-control" value="<?= htmlspecialchars($question['question_text']) ?>" required>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Option A</label>
                                                                <input type="text" name="questions[<?= $index ?>][option_a]" 
                                                                       class="form-control" value="<?= htmlspecialchars($question['option_a']) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Option B</label>
                                                                <input type="text" name="questions[<?= $index ?>][option_b]" 
                                                                       class="form-control" value="<?= htmlspecialchars($question['option_b']) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Option C</label>
                                                                <input type="text" name="questions[<?= $index ?>][option_c]" 
                                                                       class="form-control" value="<?= htmlspecialchars($question['option_c']) ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Option D</label>
                                                                <input type="text" name="questions[<?= $index ?>][option_d]" 
                                                                       class="form-control" value="<?= htmlspecialchars($question['option_d']) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Correct Answer</label>
                                                                <select name="questions[<?= $index ?>][correct_answer]" class="form-select" required>
                                                                    <option value="a" <?= $question['correct_answer'] === 'a' ? 'selected' : '' ?>>Option A</option>
                                                                    <option value="b" <?= $question['correct_answer'] === 'b' ? 'selected' : '' ?>>Option B</option>
                                                                    <option value="c" <?= $question['correct_answer'] === 'c' ? 'selected' : '' ?>>Option C</option>
                                                                    <option value="d" <?= $question['correct_answer'] === 'd' ? 'selected' : '' ?>>Option D</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Points</label>
                                                                <input type="number" name="questions[<?= $index ?>][points]" 
                                                                       class="form-control" value="<?= $question['points'] ?? 1 ?>" min="1" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Explanation (Optional)</label>
                                                        <textarea name="questions[<?= $index ?>][explanation]" class="form-control" 
                                                                  rows="2"><?= htmlspecialchars($question['explanation'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
                                               <?= ($test['status'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="status">
                                            <?= ($test['status'] ?? 1) ? 'Active' : 'Inactive' ?>
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Inactive tests won't be visible to students.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="save" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> 
                                        <?= $isEdit ? 'Update Test' : 'Create Test' ?>
                                    </button>
                                    
                                    <a href="tests.php<?= $courseId ? '?course_id=' . $courseId : ''; ?>" 
                                       class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                                
                                <?php if ($isEdit): ?>
                                    <hr>
                                    <div class="text-muted small">
                                        <div>Created: <?= date('M j, Y', strtotime($test['created_at'])) ?></div>
                                        <?php if ($test['updated_at']): ?>
                                            <div>Last Updated: <?= date('M j, Y', strtotime($test['updated_at'])) ?></div>
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
                                        <strong><?= (int)($test['question_count'] ?? 0) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Attempts:</span>
                                        <strong><?= (int)($test['attempt_count'] ?? 0) ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Average Score:</span>
                                        <strong><?= number_format($test['average_score'] ?? 0, 1) ?>%</strong>
                                    </div>
                                    
                                    <hr>
                                    
                                    <a href="test-questions.php?test_id=<?= $test['id'] ?>" class="btn btn-outline-primary w-100 mb-2">
                                        <i class="fas fa-question-circle me-1"></i> Manage Questions
                                    </a>
                                    
                                    <a href="test-attempts.php?test_id=<?= $test['id'] ?>" class="btn btn-outline-secondary w-100">
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
                <p>Are you sure you want to delete the test "<strong><?= htmlspecialchars($test['title']) ?></strong>"? This action cannot be undone.</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> All related data (questions, attempts, results) will also be removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="tests.php?action=delete&id=<?= $test['id'] ?>" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Load TinyMCE from CDN without API key -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
// Initialize TinyMCE with basic configuration
tinymce.init({
    selector: '.rich-text-editor',
    plugins: 'lists link image table code help',
    toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
    // Remove the API key requirement
    init_instance_callback: function(editor) {
        // Optional: Add any custom initialization code here
    },
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
    const form = document.getElementById('testForm');
    
    // Delete button handler
    const deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        });
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
    
    // Add delete button if editing
    if (<?= $isEdit ? 'true' : 'false' ?>) {
        const formActions = form.querySelector('.d-grid');
        if (formActions) {
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-outline-danger w-100 mb-2';
            deleteButton.id = 'deleteBtn';
            deleteButton.innerHTML = '<i class="fas fa-trash me-1"></i> Delete Test';
            formActions.insertBefore(deleteButton, formActions.firstChild);
        }
    }
});

// Form validation and submission
document.getElementById('testForm').addEventListener('submit', function(e) {
    // Prevent default form submission
    e.preventDefault();
    
    // Collect all questions
    const questions = [];
    const questionCards = document.querySelectorAll('.question-card');
    
    questionCards.forEach((card, index) => {
        const questionText = card.querySelector('[name$="[question_text]"]').value.trim();
        const optionA = card.querySelector('[name$="[option_a]"]').value.trim();
        const optionB = card.querySelector('[name$="[option_b]"]').value.trim();
        const optionC = card.querySelector('[name$="[option_c]"]')?.value.trim() || '';
        const optionD = card.querySelector('[name$="[option_d]"]')?.value.trim() || '';
        const correctAnswer = card.querySelector('[name$="[correct_answer]"]').value;
        const points = card.querySelector('[name$="[points]"]').value || 1;
        const explanation = card.querySelector('[name$="[explanation]"]')?.value || '';
        
        questions.push({
            question_text: questionText,
            option_a: optionA,
            option_b: optionB,
            option_c: optionC,
            option_d: optionD,
            correct_answer: correctAnswer,
            points: points,
            explanation: explanation,
            question_type: 'multiple_choice'
        });
    });
    
    // Validate at least one question is added
    if (questions.length === 0) {
        alert('Please add at least one question');
        return false;
    }
    
    // Create a form element to submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = this.action;
    
    // Add all form fields
    const formElements = this.elements;
    for (let i = 0; i < formElements.length; i++) {
        const element = formElements[i];
        if (element.name && element.type !== 'button' && element.type !== 'file') {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = element.name;
            input.value = element.value;
            form.appendChild(input);
        }
    }
    
    // Add questions as JSON
    const questionsInput = document.createElement('input');
    questionsInput.type = 'hidden';
    questionsInput.name = 'questions';
    questionsInput.value = JSON.stringify(questions);
    form.appendChild(questionsInput);
    
    // Add form to body and submit
    document.body.appendChild(form);
    form.submit();
});

// Question counter
let questionCounter = <?= $isEdit && !empty($test['questions']) ? count($test['questions']) : 0; ?>;

// Add question
document.getElementById('addQuestionBtn').addEventListener('click', function() {
    const container = document.getElementById('questionsContainer');
    const questionIndex = questionCounter++;
    
    const questionHTML = `
        <div class="question-card card mb-3" data-index="${questionIndex}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Question #<span class="question-number">${questionCounter}</span></h6>
                    <button type="button" class="btn btn-sm btn-danger remove-question">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-3">
                    <label class="form-label">Question Text</label>
                    <input type="text" name="questions[${questionIndex}][question_text]" 
                           class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Option A</label>
                            <input type="text" name="questions[${questionIndex}][option_a]" 
                                   class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Option B</label>
                            <input type="text" name="questions[${questionIndex}][option_b]" 
                                   class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Option C</label>
                            <input type="text" name="questions[${questionIndex}][option_c]" 
                                   class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Option D</label>
                            <input type="text" name="questions[${questionIndex}][option_d]" 
                                   class="form-control">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Correct Answer</label>
                            <select name="questions[${questionIndex}][correct_answer]" class="form-select" required>
                                <option value="a">Option A</option>
                                <option value="b">Option B</option>
                                <option value="c">Option C</option>
                                <option value="d">Option D</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Points</label>
                            <input type="number" name="questions[${questionIndex}][points]" 
                                   class="form-control" value="1" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Explanation (Optional)</label>
                    <textarea name="questions[${questionIndex}][explanation]" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', questionHTML);
    updateQuestionNumbers();
});

// Remove question
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-question')) {
        const questionCard = e.target.closest('.question-card');
        if (confirm('Are you sure you want to remove this question?')) {
            questionCard.remove();
            updateQuestionNumbers();
        }
    }
});

// Update question numbers
function updateQuestionNumbers() {
    const questions = document.querySelectorAll('.question-card');
    questions.forEach((question, index) => {
        const numberSpan = question.querySelector('.question-number');
        if (numberSpan) {
            numberSpan.textContent = index + 1;
        }
        // Update the name attributes to maintain sequential numbering
        const inputs = question.querySelectorAll('[name^="questions["]');
        inputs.forEach(input => {
            input.name = input.name.replace(/questions\[\d+\]/, `questions[${index}]`);
        });
    });
    
    // Enable/disable remove button based on question count
    const removeButtons = document.querySelectorAll('.remove-question');
    if (removeButtons.length <= 1) {
        removeButtons.forEach(btn => btn.disabled = true);
    } else {
        removeButtons.forEach(btn => btn.disabled = false);
    }
}

// Initialize question numbers and remove button state
updateQuestionNumbers();
</script>

<?php include('includes/footer.php'); ?>
