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

// Debug: Check what's in allCourses
error_log('All Courses: ' . print_r($allCourses, true));

// Calculate stats for the dashboard
$stats = [
    'total_courses' => count($allCourses),
    'total_students' => 0, // Will be calculated from enrollments
    'active_courses' => 0,
    'completed_courses' => 0
];

// Calculate total students from all courses
foreach ($allCourses as $course) {
    $stats['total_students'] += $course->enrollment_count ?? 0;
    if (($course->progress ?? 0) > 0) {
        $stats['active_courses']++;
    }
    if (($course->progress ?? 0) >= 100) {
        $stats['completed_courses']++;
    }
}

// Get recent courses (first 5 from the list)
$recentCourses = array_slice($allCourses, 0, 5);
$upcomingEvents = []; // You can implement this later

$pageTitle = 'Dashboard';
include('includes/header.php');
?>

    <!-- Admin Layout -->
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar content here -->
        </div>
        
        <!-- Main Content -->
        <main class="main-content">
                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <!-- Total Students -->
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Total Students</h6>
                                        <h3 class="mb-0"><?php echo number_format($stats['total_students']); ?></h3>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                        <i class="fas fa-users text-primary"></i>
                                    </div>
                                </div>
                            <div>
                                <h6 class="text-muted mb-2">Total Students</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['total_students']); ?></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-users text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Courses -->
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Courses</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['total_courses']); ?></h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-book text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Courses -->
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Active Courses</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['active_courses']); ?></h3>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-chart-line text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Courses -->
            <div class="col-md-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Completed Courses</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['completed_courses']); ?></h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle">
                                <i class="fas fa-check-circle text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row g-4">
            <!-- Recent Courses -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Courses</h5>
                            <a href="courses.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentCourses)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Students</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentCourses as $course): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0 me-3">
                                                            <div class="bg-light p-2 rounded">
                                                                <i class="fas fa-book text-primary"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($course->name); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($course->code); ?></small>
                                                </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $course->enrollment_count ?? 0; ?></td>
                                                <td>
                                                    <span class="badge bg-success">Active</span>
                                                </td>
                                                <td>
                                                    <a href="#" class="btn btn-sm btn-outline-primary">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="text-muted mb-3">
                                    <i class="fas fa-book-open fa-3x"></i>
                                </div>
                                <h5>No courses found</h5>
                                <p class="text-muted">Get started by creating your first course</p>
                                <a href="course-form.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i> Create Course
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0">
                        <h5 class="mb-0">Upcoming Events</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($upcomingEvents)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($upcomingEvents as $event): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="bg-light p-2 rounded">
                                                    <i class="fas fa-calendar-day text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($event['title']); ?></h6>
                                                <p class="mb-0 text-muted small">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?php echo date('M j, Y', strtotime($event['date'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="text-muted mb-3">
                                    <i class="fas fa-calendar-alt fa-3x"></i>
                                </div>
                                <h5>No upcoming events</h5>
                                <p class="text-muted">Check back later for updates</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            </div>
        </main>
    </div>

<?php include('includes/footer.php'); ?>

<!-- Dashboard Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar')?.classList.toggle('show');
        });
    }
    
    // Auto-update stats
    function updateStats() {
        fetch('/LMS/Admin/api/dashboard-stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update stats with animation
                    updateStatWithAnimation('total-students', data.stats.total_students);
                    updateStatWithAnimation('total-courses', data.stats.total_courses);
                    updateStatWithAnimation('active-courses', data.stats.active_courses);
                    updateStatWithAnimation('completed-courses', data.stats.completed_courses);
                }
            })
            .catch(console.error);
    }

    // Animate number updates
    function updateStatWithAnimation(elementId, newValue) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const start = parseInt(element.innerText.replace(/,/g, '')) || 0;
        const end = parseInt(newValue);
        const duration = 1000;
        const frameRate = 60;
        const frames = duration / (1000 / frameRate);
        const step = (end - start) / frames;
        
        let current = start;
        let frame = 0;

        const animate = () => {
            current += step;
            frame++;
            
            element.innerText = Math.round(current).toLocaleString();
            
            if (frame < frames) {
                requestAnimationFrame(animate);
            } else {
                element.innerText = end.toLocaleString();
            }
        };

        requestAnimationFrame(animate);
    }

    // Initialize
    updateStats();
    setInterval(updateStats, 30000); // Update every 30 seconds
});
</script>

<!-- Prevent FOUC -->
<script>
    document.documentElement.classList.remove('no-js');
    document.documentElement.classList.add('js-loaded');
</script>
