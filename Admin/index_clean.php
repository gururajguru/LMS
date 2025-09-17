<?php
require_once("../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    redirect(web_root . "login.php");
}

// Initialize the Courses class
$courseObj = new Courses();

// Get all active courses for the dashboard
$allCourses = $courseObj->getAllCourses();

// Calculate stats for the dashboard
$stats = [
    'total_courses' => count($allCourses),
    'total_students' => 0, // Will be calculated from enrollments
    'active_courses' => 0,
    'completed_courses' => 0
];

// Get recent courses (first 5 from the list)
$recentCourses = array_slice($allCourses, 0, 5);
$upcomingEvents = []; // You can implement this later

$pageTitle = 'Dashboard';
include('includes/header.php');
?>

<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="mt-4">Dashboard</h1>
                <ol class="breadcrumb mb-4">
                    <li class="breadcrumb-item active">Overview</li>
                </ol>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <!-- Total Students Card -->
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Total Students</div>
                                <div class="h4 mb-0"><?= $stats['total_students'] ?></div>
                            </div>
                            <i class="fas fa-user-graduate fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="students.php">View Details</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
            
            <!-- Total Courses Card -->
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Total Courses</div>
                                <div class="h4 mb-0"><?= $stats['total_courses'] ?></div>
                            </div>
                            <i class="fas fa-book fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="courses.php">View Details</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
            
            <!-- Active Courses Card -->
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Active Courses</div>
                                <div class="h4 mb-0"><?= $stats['active_courses'] ?></div>
                            </div>
                            <i class="fas fa-chalkboard-teacher fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="courses.php?status=active">View Details</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
            
            <!-- Completed Courses Card -->
            <div class="col-xl-3 col-md-6">
                <div class="card bg-danger text-white mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small">Completed Courses</div>
                                <div class="h4 mb-0"><?= $stats['completed_courses'] ?></div>
                            </div>
                            <i class="fas fa-check-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a class="small text-white stretched-link" href="courses.php?status=completed">View Details</a>
                        <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Courses -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <i class="fas fa-book me-1"></i>
                        Recent Courses
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentCourses)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Course Name</th>
                                            <th>Code</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentCourses as $course): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($course->COURSE_NAME) ?></td>
                                                <td><?= htmlspecialchars($course->COURSE_CODE) ?></td>
                                                <td><?= $course->DURATION ?> weeks</td>
                                                <td>
                                                    <span class="badge bg-<?= $course->STATUS == 'Active' ? 'success' : 'secondary' ?>">
                                                        <?= $course->STATUS ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" style="width: 75%;" 
                                                             aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
                                                            75%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No courses found.</p>
                                <a href="course-form.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add New Course
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Events -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Upcoming Events
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($upcomingEvents)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingEvents as $event): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($event['title']) ?></h6>
                                            <small class="text-muted"><?= $event['date'] ?></small>
                                        </div>
                                        <p class="mb-1"><?= htmlspecialchars($event['description']) ?></p>
                                        <small class="text-muted"><?= $event['location'] ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-day fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No upcoming events.</p>
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                                    <i class="fas fa-plus"></i> Add Event
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header bg-white">
                        <i class="fas fa-bolt me-1"></i>
                        Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="course-form.php" class="btn btn-primary mb-2">
                                <i class="fas fa-plus me-1"></i> Add New Course
                            </a>
                            <a href="students.php?action=add" class="btn btn-success mb-2">
                                <i class="fas fa-user-plus me-1"></i> Add New Student
                            </a>
                            <button class="btn btn-info mb-2">
                                <i class="fas fa-envelope me-1"></i> Send Announcement
                            </button>
                            <button class="btn btn-warning mb-2" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="fas fa-upload me-1"></i> Bulk Upload
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-history fa-2x mb-2"></i>
                    <p class="mb-0">Activity timeline will appear here</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>
