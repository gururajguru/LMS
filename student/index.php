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
    <style>
        /* Enhanced Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, var(--card-bg) 0%, #F0FDF4 100%);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: 3rem 2rem;
            margin-bottom: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }
        
        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
        }
        
        .welcome-section h2 {
            color: var(--text-color);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.75rem;
            letter-spacing: -0.025em;
        }
        
        .welcome-section p {
            color: var(--text-muted);
            font-size: 1.125rem;
            margin-bottom: 0;
        }
        
        /* Enhanced Course Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        /* Enhanced Quiz Cards */
        .quiz-card {
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border: 1px solid var(--border-color);
            position: relative;
            background: var(--card-bg);
        }
        
        .quiz-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--success-color) 0%, var(--accent-color) 100%);
        }
        
        .quiz-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--box-shadow-xl);
        }
        
        /* Enhanced Quiz Options */
        .option {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .option:hover {
            background: var(--primary-light);
            border-color: var(--primary-color);
            transform: translateX(4px);
        }
        
        .option:has(input:checked) {
            background: var(--primary-light);
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        
        .option input[type="radio"] {
            width: 1.25rem;
            height: 1.25rem;
            accent-color: var(--primary-color);
            margin-right: 1rem;
        }
        
        .option label {
            color: var(--text-color);
            font-weight: 500;
            cursor: pointer;
            margin: 0;
            flex: 1;
        }
    </style>
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
            <div class="welcome-section">
                    <h2 class="mb-2">Welcome back, <?= htmlspecialchars($_SESSION['USERNAME']) ?>!</h2>
                    <p class="text-muted">Here are your enrolled courses and available quizzes.</p>
            </div>

            <div class="row">
                <div class="col-12">

                    <!-- Enrolled Courses -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0"><i class="fas fa-book-open me-2"></i>Your Courses</h3>
                        </div>
                        <div class="card-body">
                            <div class="courses-grid">
                                <?php if (!empty($enrolledCourses)): ?>
                                    <?php foreach ($enrolledCourses as $course): ?>
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
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-book-open"></i>
                                        <h4>No Courses Enrolled</h4>
                                        <p>You are not enrolled in any courses yet.</p>
                                        <p>Contact your administrator to get enrolled.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Available Quizzes -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="mb-0"><i class="fas fa-question-circle me-2"></i>Available Quizzes</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($enrolledCourses)): ?>
                                <?php 
                                $hasQuizzes = false;
                                foreach ($enrolledCourses as $course): 
                                    $courseQuizzes = $courses->getQuizzesByCourse($course->id);
                                    if (!empty($courseQuizzes)):
                                        $hasQuizzes = true;
                                ?>
                                    <div class="quiz-card mb-4">
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
                                <?php 
                                    endif;
                                endforeach; 
                                
                                if (!$hasQuizzes):
                                ?>
                                    <div class="empty-state">
                                        <i class="fas fa-question-circle"></i>
                                        <h4>No Quizzes Available</h4>
                                        <p>No quizzes are available for your enrolled courses at this time.</p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-question-circle"></i>
                                    <h4>No Quizzes Available</h4>
                                    <p>Enroll in courses to access quizzes.</p>
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
            // Enhanced course details view
            console.log('Viewing course details for:', courseId);
        }
        
        function submitQuiz(quizId) {
            const selectedOption = document.querySelector(`input[name="quiz_${quizId}"]:checked`);
            if (!selectedOption) {
                // Enhanced alert styling
                showNotification('Please select an answer before submitting.', 'warning');
                return;
            }
            
            // Enhanced success feedback
            showNotification('Quiz submitted successfully!', 'success');
        }
        
        // Enhanced notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = `
                top: 2rem;
                right: 2rem;
                z-index: 1050;
                min-width: 300px;
                box-shadow: var(--box-shadow-lg);
                border-radius: var(--border-radius);
            `;
            
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${getNotificationIcon(type)} me-2"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        function getNotificationIcon(type) {
            switch(type) {
                case 'success': return 'check-circle';
                case 'warning': return 'exclamation-triangle';
                case 'danger': return 'exclamation-circle';
                default: return 'info-circle';
            }
        }
        
        // Enhanced auto-logout with better UX
        let inactivityTimer;
        let warningTimer;
        
        const resetTimer = () => {
            clearTimeout(inactivityTimer);
            clearTimeout(warningTimer);
            
            // Show warning 5 minutes before logout
            warningTimer = setTimeout(() => {
                showNotification('You will be logged out in 5 minutes due to inactivity.', 'warning');
            }, 25 * 60 * 1000); // 25 minutes
            
            // Auto logout after 30 minutes
            inactivityTimer = setTimeout(() => {
                showNotification('Logging out due to inactivity...', 'info');
                setTimeout(() => {
                    window.location.href = '../logout.php?reason=inactivity';
                }, 2000);
            }, 30 * 60 * 1000); // 30 minutes
        };
        
        // Reset timer on user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetTimer, true);
        });
        
        // Start timer
        resetTimer();
        
        // Enhanced page load animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on load
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
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
