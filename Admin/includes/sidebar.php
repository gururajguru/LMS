<?php
// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Administrator') {
    header('Location: ../login.php');
    exit();
}
?>

<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link text-white" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="courses.php">
                    <i class="fas fa-book me-2"></i>
                    Courses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active text-white" href="manage-topics.php?id=<?= $courseId ?? '' ?>">
                    <i class="fas fa-list-ul me-2"></i>
                    Topics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="quizzes.php">
                    <i class="fas fa-question-circle me-2"></i>
                    Quizzes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="students.php">
                    <i class="fas fa-users me-2"></i>
                    Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="instructors.php">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Instructors
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white" href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Settings
                </a>
            </li>
        </ul>
        
        <div class="position-absolute bottom-0 start-0 p-3 w-100">
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>
                    <strong><?= htmlspecialchars($_SESSION['USERNAME'] ?? 'Admin') ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php">Sign out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
