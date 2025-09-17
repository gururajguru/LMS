<?php
require_once '../include/database.php';
require_once '../include/auth.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$pdo = $db->getConnection();

// Function to fetch and display data from a table
function displayTable($pdo, $tableName, $title, $columns = '*', $orderBy = 'id') {
    try {
        $stmt = $pdo->query("SELECT $columns FROM `$tableName` ORDER BY $orderBy");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($rows)) {
            return "<div class='alert alert-info'>No data found in $tableName</div>";
        }
        
        $output = "<h3 class='mt-4 mb-3'>$title</h3>";
        $output .= "<div class='table-responsive mb-5'>";
        $output .= "<table class='table table-striped table-bordered table-hover'>";
        
        // Table header
        $output .= "<thead class='table-dark'><tr>";
        foreach (array_keys($rows[0]) as $column) {
            $output .= "<th>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $column))) . "</th>";
        }
        $output .= "</tr></thead><tbody>";
        
        // Table rows
        foreach ($rows as $row) {
            $output .= "<tr>";
            foreach ($row as $key => $value) {
                if (is_null($value)) {
                    $value = '<span class="text-muted">NULL</span>';
                } elseif (is_bool($value)) {
                    $value = $value ? '<span class="badge bg-success">True</span>' : '<span class="badge bg-secondary">False</span>';
                } elseif ($key === 'status') {
                    $badgeClass = $value === 'active' ? 'success' : ($value === 'inactive' ? 'warning' : 'danger');
                    $value = "<span class='badge bg-$badgeClass'>" . ucfirst($value) . "</span>";
                } elseif (in_array($key, ['created_at', 'updated_at', 'enrollment_date', 'started_at', 'completed_at', 'end_time']) && !empty($value)) {
                    $date = new DateTime($value);
                    $value = $date->format('M d, Y H:i');
                } elseif ($key === 'content' || $key === 'description') {
                    $value = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                    $value = htmlspecialchars($value);
                } else {
                    $value = htmlspecialchars($value);
                }
                $output .= "<td>$value</td>";
            }
            $output .= "</tr>";
        }
        
        $output .= "</tbody></table></div>";
        return $output;
        
    } catch (PDOException $e) {
        return "<div class='alert alert-danger'>Error fetching data from $tableName: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Viewer - LMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { padding-top: 20px; }
        .table th { white-space: nowrap; }
        .table td { vertical-align: middle; }
        .badge { font-size: 0.8em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-database me-2"></i>Database Viewer</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-info-circle me-2"></i>Database Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-users me-2"></i>Users</h5>
                                <?php 
                                $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                                echo "<p class='display-6'>$count</p>";
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-book me-2"></i>Courses</h5>
                                <?php 
                                $count = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
                                echo "<p class='display-6'>$count</p>";
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-graduation-cap me-2"></i>Enrollments</h5>
                                <?php 
                                $count = $pdo->query("SELECT COUNT(*) FROM course_enrollments")->fetchColumn();
                                echo "<p class='display-6'>$count</p>";
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <?php
                // Users
                echo displayTable($pdo, 'users', 'Users', 'id, username, email, first_name, last_name, user_type, status, created_at');
                
                // Students
                echo displayTable($pdo, 'students', 'Students', 'id, user_id, student_id, phone, date_of_birth, gender, created_at');
                
                // Courses
                echo displayTable($pdo, 'courses', 'Courses', 'id, name, code, level, status, created_at');
                ?>
            </div>
            <div class="col-md-6">
                <?php
                // Course Enrollments
                echo displayTable($pdo, 'course_enrollments', 'Course Enrollments', 'id, student_id, course_id, enrollment_date, progress_percentage, status');
                
                // Lessons
                echo displayTable($pdo, 'lessons', 'Lessons', 'id, course_id, title, order_number, duration_minutes, status, created_at');
                
                // Topics
                echo displayTable($pdo, 'topics', 'Topics', 'id, lesson_id, title, order_number, status, created_at');
                
                // Quizzes
                echo displayTable($pdo, 'quizzes', 'Quizzes', 'id, course_id, title, status, created_at');
                
                // Tests
                echo displayTable($pdo, 'tests', 'Tests', 'id, course_id, title, duration_minutes, passing_score, status, created_at');
                
                // Test Questions
                echo displayTable($pdo, 'test_questions', 'Test Questions', 'id, test_id, question_type, points, order_number');
                
                // Test Attempts
                echo displayTable($pdo, 'test_attempts', 'Test Attempts', 'id, student_id, test_id, attempt_number, score, passed, start_time, end_time');
                
                // Lesson Progress
                echo displayTable($pdo, 'lesson_progress', 'Lesson Progress', 'id, student_id, lesson_id, status, started_at, completed_at, time_spent_minutes');
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Make tables sortable
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('th').forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => {
                    const table = header.closest('table');
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const index = Array.from(header.parentNode.children).indexOf(header);
                    
                    // Toggle sort direction
                    const isAsc = header.classList.toggle('asc');
                    header.classList.toggle('desc', !isAsc);
                    
                    // Remove sort classes from other headers
                    header.parentNode.querySelectorAll('th').forEach(th => {
                        if (th !== header) {
                            th.classList.remove('asc', 'desc');
                        }
                    });
                    
                    // Sort rows
                    rows.sort((a, b) => {
                        const aText = a.children[index].textContent.trim().toLowerCase();
                        const bText = b.children[index].textContent.trim().toLowerCase();
                        
                        // Try to convert to numbers for numeric comparison
                        const aNum = parseFloat(aText.replace(/[^\d.-]/g, '')) || 0;
                        const bNum = parseFloat(bText.replace(/[^\d.-]/g, '')) || 0;
                        
                        if (!isNaN(aNum) && !isNaN(bNum) && aText !== bText) {
                            return isAsc ? aNum - bNum : bNum - aNum;
                        }
                        
                        return isAsc 
                            ? aText.localeCompare(bText)
                            : bText.localeCompare(aText);
                    });
                    
                    // Re-append rows in new order
                    rows.forEach(row => tbody.appendChild(row));
                });
            });
        });
    </script>
</body>
</html>
