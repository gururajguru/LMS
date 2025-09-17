<?php
require_once("../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    redirect(web_root . "login.php");
}

// Initialize the Quiz and Course classes
$quizObj = new Quiz();
$courseObj = new Courses();

// Handle quiz actions (delete, toggle status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'delete') {
        if ($quizObj->deleteQuiz($id)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Quiz deleted successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to delete quiz. ' . $quizObj->getError()
            ];
        }
    } 
    elseif ($_GET['action'] === 'toggle_status') {
        $newStatus = $_GET['status'] === 'active' ? 1 : 0;
        if ($quizObj->updateQuizStatus($id, $newStatus)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Quiz status updated successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to update quiz status. ' . $quizObj->getError()
            ];
        }
    }
    
    // Redirect back with the same filters
    $redirectUrl = 'quizzes.php';
    if (isset($_GET['course_id'])) {
        $redirectUrl .= '?course_id=' . (int)$_GET['course_id'];
    }
    redirect($redirectUrl);
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$status = isset($_GET['status']) && in_array($_GET['status'], ['active', 'inactive']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get quizzes with pagination and statistics
$quizzes = $quizObj->getAllQuizzes($search, $courseId, $status, $offset, $perPage);
$totalQuizzes = $quizObj->getTotalQuizzes($search, $courseId, $status);
$totalPages = ceil($totalQuizzes / $perPage);

// Get statistics for the dashboard
$quizStats = $quizObj->getQuizStatistics();

// Get all courses for the filter dropdown and convert objects to arrays
$allCourses = array_map(function($course) {
    return (array)$course;
}, $courseObj->getAllCourses());

// Get recent quiz attempts
$recentAttempts = $quizObj->getRecentAttempts(5); // Get 5 most recent attempts

$pageTitle = 'Quiz Management';
include('includes/header.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <?php if ($courseId && !empty($quizzes)): ?>
            Quizzes for <?php echo htmlspecialchars($quizzes[0]['course_name']); ?>
        <?php else: ?>
            Quiz Management
        <?php endif; ?>
    </h1>
    <div>
        <a href="quiz-form.php<?php echo $courseId ? '?course_id=' . $courseId : ''; ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Create New Quiz
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card border-0 bg-primary bg-opacity-10">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Quizzes</h6>
                        <h3 class="mb-0"><?php echo $quizStats['total_quizzes'] ?? 0; ?></h3>
                    </div>
                    <div class="icon-shape icon-lg bg-primary bg-opacity-25 rounded-3">
                        <i class="fas fa-tasks text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card border-0 bg-success bg-opacity-10">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Active Quizzes</h6>
                        <h3 class="mb-0"><?php echo $quizStats['active_quizzes'] ?? 0; ?></h3>
                    </div>
                    <div class="icon-shape icon-lg bg-success bg-opacity-25 rounded-3">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card border-0 bg-info bg-opacity-10">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Questions Added</h6>
                        <h3 class="mb-0"><?php echo $quizStats['quizzes_with_questions'] ?? 0; ?></h3>
                    </div>
                    <div class="icon-shape icon-lg bg-info bg-opacity-25 rounded-3">
                        <i class="fas fa-question-circle text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-4">
        <div class="card border-0 bg-warning bg-opacity-10">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Attempts</h6>
                        <h3 class="mb-0"><?php echo $quizStats['attempted_quizzes'] ?? 0; ?></h3>
                    </div>
                    <div class="icon-shape icon-lg bg-warning bg-opacity-25 rounded-3">
                        <i class="fas fa-users text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <?php if ($courseId): ?>
                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
            <?php else: ?>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Filter by Course</label>
                    <select name="course_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        <?php foreach ($allCourses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $courseId == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search quizzes..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
            
            <div class="col-md-1">
                <a href="quizzes.php" class="btn btn-outline-secondary w-100" title="Reset">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Quizzes Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($quizzes)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <?php if (!$courseId): ?>
                                <th>Course</th>
                            <?php endif; ?>
                            <th>Questions</th>
                            <th>Attempts</th>
                            <th>Avg. Score</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-light rounded p-2">
                                                <i class="fas fa-question-circle text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">
                                                <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="text-dark">
                                                    <?php echo htmlspecialchars($quiz['title']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo !empty($quiz['description']) ? 
                                                    (strlen($quiz['description']) > 50 ? 
                                                        htmlspecialchars(substr($quiz['description'], 0, 50)) . '...' : 
                                                        htmlspecialchars($quiz['description'])) : 
                                                    'No description'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                
                                <?php if (!$courseId): ?>
                                    <td>
                                        <a href="course.php?id=<?php echo $quiz['course_id']; ?>">
                                            <?php echo htmlspecialchars($quiz['course_name']); ?>
                                        </a>
                                    </td>
                                <?php endif; ?>
                                
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo $quiz['question_count'] ?? 0; ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $quiz['attempt_count'] ?? 0; ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <span class="badge bg-<?php echo ($quiz['average_score'] ?? 0) >= 70 ? 'success' : 'warning'; ?>">
                                        <?php echo number_format($quiz['average_score'] ?? 0, 1); ?>%
                                    </span>
                                </td>
                                
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input status-toggle" type="checkbox" 
                                               data-id="<?php echo $quiz['id']; ?>" 
                                               <?php echo $quiz['status'] ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                
                                <td><?php echo date('M j, Y', strtotime($quiz['created_at'])); ?></td>
                                
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="quiz-questions.php?quiz_id=<?php echo $quiz['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           data-bs-toggle="tooltip"
                                           title="Manage Questions">
                                            <i class="fas fa-question"></i>
                                        </a>
                                        <a href="quiz-attempts.php?quiz_id=<?php echo $quiz['id']; ?>" 
                                           class="btn btn-sm btn-outline-info"
                                           data-bs-toggle="tooltip"
                                           title="View Attempts">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <a href="quiz-form.php?id=<?php echo $quiz['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary"
                                           data-bs-toggle="tooltip"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger delete-btn" 
                                                data-id="<?php echo $quiz['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="d-flex justify-content-center mt-4">
                    <ul class="pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&course_id=<?php echo $courseId; ?>&status=<?php echo $status; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&course_id=<?php echo $courseId; ?>&status=<?php echo $status; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&course_id=<?php echo $courseId; ?>&status=<?php echo $status; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-question-circle fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">No quizzes found</h5>
                <p class="text-muted">
                    <?php 
                    if (!empty($search)) {
                        echo 'No quizzes match your search criteria.';
                    } elseif ($courseId) {
                        echo 'This course doesn\'t have any quizzes yet.';
                    } else {
                        echo 'Get started by creating your first quiz.';
                    }
                    ?>
                </p>
                <a href="quiz-form.php<?php echo $courseId ? '?course_id=' . $courseId : ''; ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Create New Quiz
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this quiz? This will also remove all associated questions and attempt data. This action cannot be undone.</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Warning:</strong> Deleting a quiz will permanently remove all related data.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <a href="#" id="confirmDelete" class="btn btn-danger">
                    <i class="fas fa-trash-alt me-1"></i> Delete Permanently
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const confirmDelete = document.getElementById('confirmDelete');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const quizId = this.getAttribute('data-id');
            const quizTitle = this.closest('tr').querySelector('td:first-child').textContent.trim();
            document.querySelector('#deleteModal .modal-body p').innerHTML = 
                `Are you sure you want to delete the quiz <strong>"${quizTitle}"</strong>? ` +
                `This will also remove all associated questions and attempt data.`;
            confirmDelete.href = `quizzes.php?action=delete&id=${quizId}` + 
                (new URLSearchParams(window.location.search).get('course_id') ? 
                `&course_id=${new URLSearchParams(window.location.search).get('course_id')}` : '');
            deleteModal.show();
        });
    });
    
    // Toggle quiz status
    const statusToggles = document.querySelectorAll('.status-toggle');
    statusToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const quizId = this.getAttribute('data-id');
            const isActive = this.checked;
            
            fetch(`api/update-quiz-status.php?id=${quizId}&status=${isActive ? 1 : 0}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert the toggle if the update failed
                    this.checked = !isActive;
                    showToast('Error', data.message || 'Failed to update quiz status', 'danger');
                } else {
                    showToast('Success', 'Quiz status updated successfully', 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !isActive;
                showToast('Error', 'An error occurred while updating the quiz status', 'danger');
            });
        });
    });
    
    // Show toast notification
    function showToast(title, message, type = 'info') {
        const toastContainer = document.createElement('div');
        toastContainer.className = `toast align-items-center text-white bg-${type} border-0`;
        toastContainer.setAttribute('role', 'alert');
        toastContainer.setAttribute('aria-live', 'assertive');
        toastContainer.setAttribute('aria-atomic', 'true');
        
        toastContainer.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}:</strong> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        document.body.appendChild(toastContainer);
        const toast = new bootstrap.Toast(toastContainer);
        toast.show();
        
        // Remove the toast after it's hidden
        toastContainer.addEventListener('hidden.bs.toast', function () {
            document.body.removeChild(toastContainer);
        });
    }
});
</script>

<?php include('includes/footer.php'); ?>
