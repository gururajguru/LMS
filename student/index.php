<?php
session_start();

// Check if user is logged in and is student
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Student') {
    header('Location: ../login.php');
    exit();
}

require_once '../include/database.php';
require_once '../include/courses.php';

$courses = new Courses();
$studentId = $_SESSION['USERID'];

// Get student's enrolled courses
$enrolledCourses = $courses->getStudentCourses($studentId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - LMS</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.4 for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Animate.css for smooth animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom styles -->
    <link href="assets/css/student-styles.css" rel="stylesheet">
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-graduation-cap fa-2x me-3" style="color: var(--primary-color);"></i>
                <h1 class="h4 mb-0">Student Portal</h1>
            </div>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted">
                    <i class="fas fa-user-circle me-1"></i> 
                    <?= htmlspecialchars($_SESSION['USERNAME']) ?>
                </span>
                <a href="../logout.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sign-out-alt me-1"></i> Sign Out
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <div class="card mb-4">
                <div class="card-body text-center py-5">
                    <h2 class="mb-2">Welcome back, <?= htmlspecialchars($_SESSION['USERNAME']) ?>!</h2>
                    <p class="text-muted">Here are your enrolled courses and available quizzes.</p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">

                    <!-- Enrolled Courses -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0"><i class="fas fa-book-open me-2"></i>Your Courses</h3>
                        </div>
                        <div class="card-body">
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                <?php if (!empty($enrolledCourses)): ?>
                                    <?php foreach ($enrolledCourses as $course): ?>
                                        <div class="col">
                                            <div class="card h-100 border-0 shadow-sm">
                                                <div class="card-header bg-primary text-white">
                                                    <h5 class="mb-0"><?= htmlspecialchars($course->name) ?></h5>
                                                    <small class="text-white-50"><?= htmlspecialchars($course->code) ?></small>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text text-muted"><?= htmlspecialchars($course->description) ?></p>
                                                    <div class="d-flex justify-content-between text-muted small mb-3">
                                                        <span><i class="fas fa-clock me-1"></i> <?= $course->duration_weeks ?> weeks</span>
                                                        <span><i class="fas fa-signal me-1"></i> <?= ucfirst($course->level) ?></span>
                                                    </div>
                                                </div>
                                                <div class="card-footer bg-transparent border-top-0">
                                                    <a href="course-topics.php?id=<?= $course->id ?>" class="btn btn-primary w-100">
                                                        <i class="fas fa-book-open me-1"></i> View Topics
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="text-center p-5 bg-light rounded-3">
                                            <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                                            <h4 class="text-muted">No Courses Enrolled</h4>
                                            <p class="text-muted mb-0">You are not enrolled in any courses yet.</p>
                                            <p class="text-muted">Contact your administrator to get enrolled.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <!-- Available Quizzes -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="mb-0"><i class="fas fa-question-circle me-2"></i>Available Quizzes</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <?php if (!empty($enrolledCourses)): ?>
                                    <?php foreach ($enrolledCourses as $course): ?>
                                        <?php 
                                        $courseQuizzes = $courses->getQuizzesByCourse($course->id);
                                        if (!empty($courseQuizzes)):
                                        ?>
                                            <div class="quiz-card">
                                                <div class="quiz-header">
                                                    <h4><?= htmlspecialchars($course->name) ?> - Quiz</h4>
                                                </div>
                                                <div class="quiz-body">
                                                    <?php foreach ($courseQuizzes as $quiz): ?>
                                                        <div class="quiz-item">
                                                            <h5><?= htmlspecialchars($quiz->title) ?></h5>
                                                            <p><?= htmlspecialchars($quiz->description ?? '') ?></p>
                                                            <div class="quiz-question">
                                                                <strong>Question:</strong> <?= htmlspecialchars($quiz->question) ?>
                                                            </div>
                                                            <div class="quiz-options">
                                                                <div class="option">
                                                                    <input type="radio" name="quiz_<?= $quiz->id ?>" value="1" id="opt1_<?= $quiz->id ?>">
                                                                    <label for="opt1_<?= $quiz->id ?>"><?= htmlspecialchars($quiz->option1) ?></label>
                                                                </div>
                                                                <div class="option">
                                                                    <input type="radio" name="quiz_<?= $quiz->id ?>" value="2" id="opt2_<?= $quiz->id ?>">
                                                                    <label for="opt2_<?= $quiz->id ?>"><?= htmlspecialchars($quiz->option2) ?></label>
                                                                </div>
                                                                <?php if ($quiz->option3): ?>
                                                                <div class="option">
                                                                    <input type="radio" name="quiz_<?= $quiz->id ?>" value="3" id="opt3_<?= $quiz->id ?>">
                                                                    <label for="opt3_<?= $quiz->id ?>"><?= htmlspecialchars($quiz->option3) ?></label>
                                                                </div>
                                                                <?php endif; ?>
                                                                <?php if ($quiz->option4): ?>
                                                                <div class="option">
                                                                    <input type="radio" name="quiz_<?= $quiz->id ?>" value="4" id="opt4_<?= $quiz->id ?>">
                                                                    <label for="opt4_<?= $quiz->id ?>"><?= htmlspecialchars($quiz->option4) ?></label>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <button class="btn btn-success btn-sm" onclick="submitQuiz(<?= $quiz->id ?>)">
                                                                <i class="fas fa-check"></i> Submit Answer
                                                            </button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-quizzes">
                                        <i class="fas fa-question-circle fa-3x text-muted"></i>
                                        <p>No quizzes available for your enrolled courses.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewCourseDetails(courseId) {
            // This would open a modal or navigate to course details page
            alert('Course details for course ID: ' + courseId);
        }
        
        function submitQuiz(quizId) {
            const selectedOption = document.querySelector(`input[name="quiz_${quizId}"]:checked`);
            if (!selectedOption) {
                alert('Please select an answer before submitting.');
                return;
            }
            
            // Here you would submit the quiz answer to the server
            alert('Quiz submitted! Your answer: ' + selectedOption.value);
        }
        
        // Auto-logout after 30 minutes of inactivity
        let inactivityTimer;
        const resetTimer = () => {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                window.location.href = '../logout.php?reason=inactivity';
            }, 30 * 60 * 1000); // 30 minutes
        };
        
        // Reset timer on user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetTimer, true);
        });
        
        // Start timer
        resetTimer();
    </script>
</body>
</html>
