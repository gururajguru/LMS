<?php
require_once("../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    redirect(web_root . "login.php");
}

// Initialize the Courses class
$courseObj = new Courses();

// Handle course actions (delete, toggle status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'delete') {
        if ($courseObj->deleteCourse($id)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Course deleted successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to delete course. ' . $courseObj->getError()
            ];
        }
    } 
    elseif ($_GET['action'] === 'toggle_status') {
        $newStatus = $_GET['status'] === 'active' ? 1 : 0;
        if ($courseObj->updateCourseStatus($id, $newStatus)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Course status updated successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to update course status. ' . $courseObj->getError()
            ];
        }
    }
    
    redirect('courses.php');
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = isset($_GET['status']) && in_array($_GET['status'], ['active', 'inactive']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get courses with pagination
$courses = $courseObj->getAllCourses($search, $status, $offset, $perPage);
$totalCourses = $courseObj->getTotalCourses($search, $status);
$totalPages = ceil($totalCourses / $perPage);

$pageTitle = 'Course Management';
include('includes/header.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Course Management</h1>
    <a href="course-form.php" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Add New Course
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
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
            <div class="col-md-2">
                <a href="courses.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-sync-alt me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Courses Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($courses)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Duration</th>
                            <th>Level</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course->code); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="fas fa-book text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($course->name); ?></h6>
                                            <small class="text-muted"><?php echo htmlspecialchars($course->category ?? 'Uncategorized'); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo (int)$course->duration_weeks; ?> weeks</td>
                                <td><?php echo ucfirst($course->level ?? 'Beginner'); ?></td>
                                <td>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo (int)$course->enrollment_count; ?> enrolled
                                    </span>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input toggle-status" 
                                               type="checkbox" 
                                               role="switch" 
                                               data-id="<?php echo $course->id; ?>"
                                               <?php echo ($course->status ?? 0) ? 'checked' : ''; ?>>
                                        <span class="form-check-label">
                                            <?php echo ($course->status ?? 0) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="manage-topics.php?id=<?php echo $course->id; ?>" class="btn btn-sm btn-outline-info" title="Manage Topics">
                                            <i class="fas fa-list-ul"></i>
                                        </a>
                                        <a href="course-form.php?id=<?php echo $course->id; ?>" class="btn btn-sm btn-outline-primary" title="Edit Course">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-course" data-id="<?php echo $course->id; ?>" data-name="<?php echo htmlspecialchars($course->name); ?>" title="Delete">
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-book fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">No courses found</h5>
                <p class="text-muted">
                    <?php echo !empty($search) ? 'No courses match your search criteria.' : 'Get started by adding your first course.'; ?>
                </p>
                <a href="course-form.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add New Course
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
                <p>Are you sure you want to delete <strong id="courseName"></strong>? This action cannot be undone.</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> All related data (lessons, quizzes, enrollments) will also be removed.</p>
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
    // Toggle course status
    $('.toggle-status').change(function() {
        const courseId = $(this).data('id');
        const isActive = $(this).is(':checked');
        const statusText = isActive ? 'activate' : 'deactivate';
        
        if (confirm(`Are you sure you want to ${statusText} this course?`)) {
            window.location.href = `courses.php?action=toggle_status&id=${courseId}&status=${isActive ? 'active' : 'inactive'}`;
        } else {
            $(this).prop('checked', !isActive);
        }
    });
    
    // Delete course confirmation
    $('.delete-course').click(function() {
        const courseId = $(this).data('id');
        const courseName = $(this).data('name');
        
        $('#courseName').text(courseName);
        $('#confirmDelete').attr('href', `courses.php?action=delete&id=${courseId}`);
        
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
