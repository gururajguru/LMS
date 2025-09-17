<?php
require_once("../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    redirect(web_root . "login.php");
}

// Initialize the Students class
$studentObj = new Students();

// Handle student actions (delete, toggle status)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($_GET['action'] === 'delete') {
        if ($studentObj->deleteStudent($id)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Student deleted successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to delete student. ' . $studentObj->getError()
            ];
        }
    } 
    elseif ($_GET['action'] === 'toggle_status') {
        $newStatus = $_GET['status'] === 'active' ? 1 : 0;
        if ($studentObj->updateStudentStatus($id, $newStatus)) {
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Student status updated successfully.'
            ];
        } else {
            $_SESSION['message'] = [
                'type' => 'danger',
                'text' => 'Failed to update student status. ' . $studentObj->getError()
            ];
        }
    }
    
    redirect('students.php');
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status = isset($_GET['status']) && in_array($_GET['status'], ['active', 'inactive']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get students with pagination
$students = $studentObj->getAllStudents($search, $status, $offset, $perPage);
$totalStudents = $studentObj->getTotalStudents($search, $status);
$totalPages = ceil($totalStudents / $perPage);

$pageTitle = 'Student Management';
include('includes/header.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Student Management</h1>
    <div>
        <a href="student-form.php" class="btn btn-primary me-2">
            <i class="fas fa-user-plus me-1"></i> Add Student
        </a>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-file-import me-1"></i> Import
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search students by name or ID..." value="<?php echo htmlspecialchars($search); ?>">
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
            <div class="col-md-3">
                <a href="students.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-sync-alt me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (!empty($students)): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Enrolled Courses</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <?php if (!empty($student['photo'])): ?>
                                                <img src="../<?php echo htmlspecialchars($student['photo']); ?>" 
                                                     alt="<?php echo htmlspecialchars($student['fullname']); ?>" 
                                                     class="rounded-circle" 
                                                     style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 40px; height: 40px;">
                                                    <i class="fas fa-user text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo !empty($student['created_at']) ? date('M j, Y', strtotime($student['created_at'])) : 'N/A'; ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo !empty($student['phone']) ? htmlspecialchars($student['phone']) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo (int)$student['enrolled_courses']; ?> courses
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo ($student['user_status'] === 'active') ? 'success' : 'secondary'; ?>">
                                        <?php echo ($student['user_status'] === 'active') ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="student.php?id=<?php echo $student['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary"
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="student-form.php?id=<?php echo $student['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger delete-student"
                                                data-id="<?php echo $student['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>"
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
                    <i class="fas fa-user-graduate fa-3x text-muted"></i>
                </div>
                <h5 class="text-muted">No students found</h5>
                <p class="text-muted">
                    <?php echo !empty($search) ? 'No students match your search criteria.' : 'No students have been added yet.'; ?>
                </p>
                <a href="student-form.php" class="btn btn-primary">
                    <i class="fas fa-user-plus me-1"></i> Add New Student
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="import-students.php" method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="csvFile" class="form-label">Select CSV File</label>
                        <input class="form-control" type="file" id="csvFile" name="csv_file" accept=".csv" required>
                        <div class="form-text">
                            <a href="sample-students.csv" class="small">
                                <i class="fas fa-download me-1"></i> Download sample CSV file
                            </a>
                        </div>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Ensure your CSV file includes the following columns: Student ID, Full Name, Email, Phone (optional)
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Import
                    </button>
                </div>
            </form>
        </div>
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
                <p>Are you sure you want to delete <strong id="studentName"></strong>? This action cannot be undone.</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> All related data (enrollments, submissions) will also be removed.</p>
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
    // Toggle student status
    $('.toggle-status').change(function() {
        const studentId = $(this).data('id');
        const isActive = $(this).is(':checked');
        
        if (confirm(`Are you sure you want to ${isActive ? 'activate' : 'deactivate'} this student?`)) {
            window.location.href = `students.php?action=toggle_status&id=${studentId}&status=${isActive ? 'active' : 'inactive'}`;
        } else {
            $(this).prop('checked', !isActive);
        }
    });
    
    // Delete student confirmation
    $('.delete-student').click(function() {
        const studentId = $(this).data('id');
        const studentName = $(this).data('name');
        
        $('#studentName').text(studentName);
        $('#confirmDelete').attr('href', `students.php?action=delete&id=${studentId}`);
        
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong><span id="studentName"></span></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDelete" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete student confirmation
    const deleteButtons = document.querySelectorAll('.delete-student');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const studentNameSpan = document.getElementById('studentName');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.getAttribute('data-id');
            const studentName = this.getAttribute('data-name');
            
            studentNameSpan.textContent = studentName;
            confirmDeleteBtn.href = `students.php?action=delete&id=${studentId}`;
            deleteModal.show();
        });
    });
});
</script>
