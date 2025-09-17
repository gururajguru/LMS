<?php
require_once("../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    redirect(web_root . "login.php");
}

// Initialize the Test and Course classes
$testObj = new Test();
$courseObj = new Courses();

// Handle test actions (delete, toggle status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'delete') {
        if ($testObj->deleteTest($id)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Test deleted successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to delete test. ' . $testObj->getError()
            ];
        }
    } 
    elseif ($_GET['action'] === 'toggle_status') {
        $newStatus = $_GET['status'] === 'active' ? 1 : 0;
        if ($testObj->updateTestStatus($id, $newStatus)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Test status updated successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to update test status. ' . $testObj->getError()
            ];
        }
    }
    
    // Redirect back with the same filters
    $redirectUrl = 'tests.php';
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

// Get tests with pagination
$tests = $testObj->getAllTests($search, $courseId, $status, $offset, $perPage);
$totalTests = $testObj->getTotalTests($search, $courseId, $status);
$totalPages = ceil($totalTests / $perPage);

// Get all courses for the filter dropdown and convert objects to arrays
$allCourses = array_map(function($course) {
    return (array)$course;
}, $courseObj->getAllCourses());

$pageTitle = 'Test Management';
include('includes/header.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <?php if ($courseId && !empty($tests)): ?>
            Tests for <?php echo htmlspecialchars($tests[0]['course_name']); ?>
        <?php else: ?>
            Test Management
        <?php endif; ?>
    </h1>
    <div>
        <a href="test-form.php<?php echo $courseId ? '?course_id=' . $courseId : ''; ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Create New Test
        </a>
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
                    <input type="text" class="form-control" name="search" placeholder="Search tests..." value="<?php echo htmlspecialchars($search); ?>">
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
                <a href="tests.php" class="btn btn-outline-secondary w-100" title="Reset">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tests Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($tests)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <?php if (!$courseId): ?>
                                <th>Course</th>
                            <?php endif; ?>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Passing Score</th>
                            <th>Attempts</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $test): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-light rounded p-2">
                                                <i class="fas fa-file-alt text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">
                                                <a href="test.php?id=<?php echo $test['id']; ?>" class="text-dark">
                                                    <?php echo htmlspecialchars($test['title']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo !empty($test['description']) ? 
                                                    (strlen($test['description']) > 30 ? 
                                                        htmlspecialchars(substr($test['description'], 0, 30)) . '...' : 
                                                        htmlspecialchars($test['description'])) : 
                                                    'No description'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                
                                <?php if (!$courseId): ?>
                                    <td>
                                        <a href="course.php?id=<?php echo $test['course_id']; ?>">
                                            <?php echo htmlspecialchars($test['course_name']); ?>
                                        </a>
                                    </td>
                                <?php endif; ?>
                                
                                <td><?php echo (int)$test['duration_minutes']; ?> mins</td>
                                
                                <td><?php echo (int)$test['passing_score']; ?>%</td>
                                
                                <td>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo (int)$test['max_attempts'] == 0 ? 'Unlimited' : (int)$test['max_attempts']; ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input toggle-status" 
                                               type="checkbox" 
                                               role="switch" 
                                               data-id="<?php echo $test['id']; ?>"
                                               <?php echo ($test['status'] ?? 0) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">
                                            <?php echo ($test['status'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </td>
                                
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="test-questions.php?test_id=<?php echo $test['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="Manage Questions">
                                            <i class="fas fa-question"></i>
                                        </a>
                                        <a href="test-attempts.php?test_id=<?php echo $test['id']; ?>" 
                                           class="btn btn-sm btn-outline-info"
                                           title="View Attempts">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <a href="test.php?id=<?php echo $test['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="test-form.php?id=<?php echo $test['id']; ?>" 
                                           class="btn btn-sm btn-outline-info"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger delete-test"
                                                data-id="<?php echo $test['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($test['title']); ?>"
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
                    <i class="fas fa-file-alt fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">No tests found</h5>
                <p class="text-muted">
                    <?php 
                    if (!empty($search)) {
                        echo 'No tests match your search criteria.';
                    } elseif ($courseId) {
                        echo 'This course doesn\'t have any tests yet.';
                    } else {
                        echo 'Get started by creating your first test.';
                    }
                    ?>
                </p>
                <a href="test-form.php<?php echo $courseId ? '?course_id=' . $courseId : ''; ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Create New Test
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the test "<strong id="testName"></strong>"? This action cannot be undone.</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> All related data (questions, attempts, results) will also be removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Document ready
$(document).ready(function() {
    // Toggle test status
    $('.toggle-status').change(function() {
        const testId = $(this).data('id');
        const isActive = $(this).is(':checked');
        
        if (confirm(`Are you sure you want to ${isActive ? 'activate' : 'deactivate'} this test?`)) {
            window.location.href = `tests.php?action=toggle_status&id=${testId}&status=${isActive ? 'active' : 'inactive'}&course_id=<?php echo $courseId; ?>`;
        } else {
            $(this).prop('checked', !isActive);
        }
    });
    
    // Delete test confirmation
    $('.delete-test').click(function() {
        const testId = $(this).data('id');
        const testName = $(this).data('name');
        
        $('#testName').text(testName);
        $('#confirmDelete').attr('href', `tests.php?action=delete&id=${testId}&course_id=<?php echo $courseId; ?>`);
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include('includes/footer.php'); ?>
