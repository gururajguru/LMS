// Admin Portal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminPortal();
});

function initializeAdminPortal() {
    // Initialize navigation
    initializeNavigation();
    
    // Initialize modals
    initializeModals();
    
    // Initialize quick actions
    initializeQuickActions();
    
    // Initialize forms
    initializeForms();
    
    // Initialize charts
    initializeCharts();
    
    // Load initial data
    loadDashboardData();
}

// Navigation Management
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const pages = document.querySelectorAll('.page');
    const pageTitle = document.querySelector('.page-title');
    const pageSubtitle = document.querySelector('.page-subtitle');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and pages
            navLinks.forEach(l => l.parentElement.classList.remove('active'));
            pages.forEach(p => p.classList.remove('active'));
            
            // Add active class to clicked link
            this.parentElement.classList.add('active');
            
            // Show corresponding page
            const targetPage = this.getAttribute('data-page');
            const targetPageElement = document.getElementById(targetPage + '-page');
            
            if (targetPageElement) {
                targetPageElement.classList.add('active');
                
                // Update page title and subtitle
                const linkText = this.querySelector('span').textContent;
                pageTitle.textContent = linkText;
                pageSubtitle.textContent = `Manage your ${linkText.toLowerCase()}`;
                
                // Load page-specific data
                loadPageData(targetPage);
            }
        });
    });
    
    // Sidebar toggle for mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
}
    
// Modal Management
function initializeModals() {
    // Close modal when clicking overlay
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            closeAllModals();
        }
    });
    
    // Close modal with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

// Quick Actions
function initializeQuickActions() {
    const quickActionBtns = document.querySelectorAll('.quick-action-btn');
    
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            handleQuickAction(action);
        });
    });
}

function handleQuickAction(action) {
    switch(action) {
        case 'add-course':
            openModal('courseModal', 'Add New Course');
            break;
        case 'add-student':
            openModal('studentModal', 'Add New Student');
            break;
        case 'add-lesson':
            openModal('lessonModal', 'Add New Lesson');
            break;
        case 'add-topic':
            openModal('topicModal', 'Add New Topic');
            break;
        case 'add-quiz':
            openModal('quizModal', 'Add New Quiz');
            break;
        case 'add-test':
            openModal('testModal', 'Add New Test');
            break;
        default:
            console.log('Unknown action:', action);
    }
}

// Form Management
function initializeForms() {
    // Course form
    const courseForm = document.getElementById('courseForm');
    if (courseForm) {
        courseForm.addEventListener('submit', handleCourseSubmit);
    }
    
    // Student form
    const studentForm = document.getElementById('studentForm');
    if (studentForm) {
        studentForm.addEventListener('submit', handleStudentSubmit);
    }
    
    // Lesson form
    const lessonForm = document.getElementById('lessonForm');
    if (lessonForm) {
        lessonForm.addEventListener('submit', handleLessonSubmit);
    }
    
    // Topic form
    const topicForm = document.getElementById('topicForm');
    if (topicForm) {
        topicForm.addEventListener('submit', handleTopicSubmit);
    }
    
    // Quiz form
    const quizForm = document.getElementById('quizForm');
    if (quizForm) {
        quizForm.addEventListener('submit', handleQuizSubmit);
    }
    
    // Test form
    const testForm = document.getElementById('testForm');
    if (testForm) {
        testForm.addEventListener('submit', handleTestSubmit);
    }
}

// Course Form Handlers
async function handleCourseSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const courseData = {
        courseName: formData.get('courseName'),
        courseCode: formData.get('courseCode'),
        courseDescription: formData.get('courseDescription'),
        courseDuration: formData.get('courseDuration'),
        courseLevel: formData.get('courseLevel')
    };
    
    const courseId = document.getElementById('courseId').value;
    const isEdit = courseId && courseId !== '';
    
    try {
        const response = await fetch('api/courses.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(courseData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal('courseModal');
            e.target.reset();
            document.getElementById('courseId').value = '';
            loadPageData('courses');
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred while saving the course', 'error');
        console.error('Error:', error);
    }
}

// Student Form Handlers
async function handleStudentSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const studentData = {
        studentFirstName: formData.get('studentFirstName'),
        studentLastName: formData.get('studentLastName'),
        studentEmail: formData.get('studentEmail'),
        studentUsername: formData.get('studentUsername'),
        studentPassword: formData.get('studentPassword'),
        studentPhone: formData.get('studentPhone'),
        studentAddress: formData.get('studentAddress'),
        studentDateOfBirth: formData.get('studentDateOfBirth'),
        studentGender: formData.get('studentGender'),
        studentCourses: Array.from(formData.getAll('studentCourses'))
    };
    
    const studentId = document.getElementById('studentId').value;
    const isEdit = studentId && studentId !== '';
    
    try {
        const response = await fetch('api/students.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(studentData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal('studentModal');
            e.target.reset();
            document.getElementById('studentId').value = '';
            loadPageData('students');
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred while saving the student', 'error');
        console.error('Error:', error);
    }
}

// Lesson Form Handlers
async function handleLessonSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const lessonData = {
        lessonCourse: formData.get('lessonCourse'),
        lessonTitle: formData.get('lessonTitle'),
        lessonDescription: formData.get('lessonDescription'),
        lessonContent: formData.get('lessonContent'),
        lessonVideoUrl: formData.get('lessonVideoUrl'),
        lessonOrder: formData.get('lessonOrder'),
        lessonDuration: formData.get('lessonDuration')
    };
    
    const lessonId = document.getElementById('lessonId').value;
    const isEdit = lessonId && lessonId !== '';
    
    try {
        const response = await fetch('api/lessons.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(lessonData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal('lessonModal');
            e.target.reset();
            document.getElementById('lessonId').value = '';
            loadPageData('lessons');
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred while saving the lesson', 'error');
        console.error('Error:', error);
    }
}

// Topic Form Handlers
async function handleTopicSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const topicData = {
        topicLesson: formData.get('topicLesson'),
        topicTitle: formData.get('topicTitle'),
        topicDescription: formData.get('topicDescription'),
        topicContent: formData.get('topicContent'),
        topicOrder: formData.get('topicOrder')
    };
    
    const topicId = document.getElementById('topicId').value;
    const isEdit = topicId && topicId !== '';
    
    try {
        const response = await fetch('api/topics.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(topicData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal('topicModal');
            e.target.reset();
            document.getElementById('topicId').value = '';
            loadPageData('topics');
        } else {
            showNotification(result.message, 'error');
        }
    } catch (error) {
        showNotification('An error occurred while saving the topic', 'error');
        console.error('Error:', error);
    }
}

// Quiz Form Handlers (aligned with API and DB)
async function handleQuizSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    // Map to server-side expected keys for simple single-question quiz
    const payload = {
        course_id: formData.get('quizCourse') || formData.get('course_id'),
        title: formData.get('quizName') || formData.get('title'),
        description: formData.get('quizDescription') || formData.get('description') || null,
        question: formData.get('quizQuestion') || formData.get('question'),
        option1: formData.get('quizOption1') || formData.get('option1'),
        option2: formData.get('quizOption2') || formData.get('option2'),
        option3: formData.get('quizOption3') || formData.get('option3') || null,
        option4: formData.get('quizOption4') || formData.get('option4') || null,
        correct_answer: formData.get('quizCorrectAnswer') || formData.get('correct_answer')
    };
    
    const quizId = document.getElementById('quizId') ? document.getElementById('quizId').value : '';
    const isEdit = quizId && quizId !== '';
    
    try {
        const response = await fetch('api/quizzes.php' + (isEdit ? `?id=${encodeURIComponent(quizId)}` : ''), {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal('quizModal');
            e.target.reset();
            if (document.getElementById('quizId')) document.getElementById('quizId').value = '';
            loadPageData('quizzes');
            // Refresh stats
            if (typeof loadDashboardData === 'function') loadDashboardData();
        } else {
            showNotification(result.message || 'Failed to save quiz', 'error');
        }
    } catch (error) {
        showNotification('An error occurred while saving the quiz', 'error');
        console.error('Error:', error);
    }
}

// Test Form Handlers (align with tests API)
async function handleTestSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const payload = {
        testCourse: formData.get('testCourse') || formData.get('course_id'),
        testName: formData.get('testName') || formData.get('title'),
        testDescription: formData.get('testDescription') || formData.get('description') || null,
        testDuration: formData.get('testDuration') || formData.get('duration_minutes'),
        testPassingScore: formData.get('testPassingScore') || formData.get('passing_score'),
        testAttempts: formData.get('testAttempts') || formData.get('max_attempts'),
        testInstructions: formData.get('testInstructions') || formData.get('instructions') || null,
        testId: (document.getElementById('testId') && document.getElementById('testId').value) ? document.getElementById('testId').value : undefined
    };
    
    const testId = document.getElementById('testId') ? document.getElementById('testId').value : '';
    const isEdit = testId && testId !== '';
    
    try {
        const response = await fetch('api/tests.php', {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            closeModal('testModal');
            e.target.reset();
            if (document.getElementById('testId')) document.getElementById('testId').value = '';
            loadPageData('tests');
            if (typeof loadDashboardData === 'function') loadDashboardData();
        } else {
            showNotification(result.message || 'Failed to save test', 'error');
        }
    } catch (error) {
        showNotification('An error occurred while saving the test', 'error');
        console.error('Error:', error);
    }
}

// Edit Functions
function editCourse(courseId) {
    fetch(`api/courses.php?id=${courseId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const course = result.data;
                document.getElementById('courseId').value = course.id;
                document.getElementById('courseName').value = course.name;
                document.getElementById('courseCode').value = course.code;
                document.getElementById('courseDescription').value = course.description;
                document.getElementById('courseDuration').value = course.duration_weeks;
                document.getElementById('courseLevel').value = course.level;
                openModal('courseModal', 'Edit Course');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading course data', 'error');
        });
}

function editStudent(studentId) {
    fetch(`api/students.php?id=${studentId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const student = result.data;
                document.getElementById('studentId').value = student.id;
                document.getElementById('studentFirstName').value = student.first_name;
                document.getElementById('studentLastName').value = student.last_name;
                document.getElementById('studentEmail').value = student.email;
                document.getElementById('studentPhone').value = student.phone || '';
                document.getElementById('studentAddress').value = student.address || '';
                document.getElementById('studentDateOfBirth').value = student.date_of_birth || '';
                document.getElementById('studentGender').value = student.gender || '';
                openModal('studentModal', 'Edit Student');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading student data', 'error');
        });
}

function editLesson(lessonId) {
    fetch(`api/lessons.php?id=${lessonId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const lesson = result.data;
                document.getElementById('lessonId').value = lesson.id;
                document.getElementById('lessonCourse').value = lesson.course_id;
                document.getElementById('lessonTitle').value = lesson.title;
                document.getElementById('lessonDescription').value = lesson.description || '';
                document.getElementById('lessonContent').value = lesson.content;
                document.getElementById('lessonVideoUrl').value = lesson.video_url || '';
                document.getElementById('lessonOrder').value = lesson.order_number;
                document.getElementById('lessonDuration').value = lesson.duration_minutes;
                openModal('lessonModal', 'Edit Lesson');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading lesson data', 'error');
        });
}

// Delete Functions
function deleteCourse(courseId) {
    if (confirm('Are you sure you want to delete this course?')) {
        fetch('api/courses.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: courseId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification(result.message, 'success');
                loadPageData('courses');
            } else {
                showNotification(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting course', 'error');
        });
    }
}

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student?')) {
        fetch('api/students.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: studentId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification(result.message, 'success');
                loadPageData('students');
            } else {
                showNotification(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting student', 'error');
        });
    }
}

function deleteLesson(lessonId) {
    if (confirm('Are you sure you want to delete this lesson?')) {
        fetch('api/lessons.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: lessonId })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showNotification(result.message, 'success');
                loadPageData('lessons');
            } else {
                showNotification(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error deleting lesson', 'error');
        });
    }
}

// Modal Functions
function openModal(modalId, title = '') {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Prefer Bootstrap modal if available
    if (typeof bootstrap !== 'undefined' && modal.classList.contains('modal')) {
        if (title) {
            const headerTitle = modal.querySelector('.modal-title, .modal-header h3');
            if (headerTitle) headerTitle.textContent = title;
        }
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    } else {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        if (title) {
            const modalTitle = modal.querySelector('.modal-header h3');
            if (modalTitle) modalTitle.textContent = title;
        }
    }
    // Load dynamic data for forms
    loadFormData(modalId);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    if (typeof bootstrap !== 'undefined' && modal.classList.contains('modal')) {
        const instance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
        instance.hide();
    } else {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Reset form
    const form = modal.querySelector('form');
    if (form) form.reset();
}

function closeAllModals() {
    // Close Bootstrap modals
    if (typeof bootstrap !== 'undefined') {
        document.querySelectorAll('.modal.show').forEach(m => {
            const inst = bootstrap.Modal.getInstance(m) || new bootstrap.Modal(m);
            inst.hide();
        });
    }
    // Close custom overlays
    document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    document.body.style.overflow = '';
}

// Form Data Loading
function loadFormData(modalId) {
    switch(modalId) {
        case 'courseModal':
            // Load course-specific data if needed
            break;
        case 'studentModal':
            loadCoursesForStudent();
            break;
        case 'lessonModal':
            loadCoursesForLesson();
            break;
        case 'topicModal':
            loadLessonsForTopic();
            break;
        case 'quizModal':
            loadCoursesForQuiz();
            break;
        case 'testModal':
            loadLessonsForTest();
            break;
    }
}

// Load Courses for Student Form
function loadCoursesForStudent() {
    fetch('api/courses.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const select = document.getElementById('studentCourses');
                select.innerHTML = '<option value="">Select Courses</option>';
                
                result.data.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = course.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
        });
}

// Load Courses for Lesson Form
function loadCoursesForLesson() {
    fetch('api/courses.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const select = document.getElementById('lessonCourse');
                select.innerHTML = '<option value="">Select Course</option>';
                
                result.data.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = course.name;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
        });
}

// Load Lessons for Topic Form
function loadLessonsForTopic() {
    fetch('api/lessons.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const select = document.getElementById('topicLesson');
                select.innerHTML = '<option value="">Select Lesson</option>';
                
                result.data.forEach(lesson => {
                    const option = document.createElement('option');
                    option.value = lesson.id;
                    option.textContent = `${lesson.course_name} - ${lesson.title}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading lessons:', error);
        });
}

// Load Topics for Quiz Form
function loadTopicsForQuiz() {
    // This would need to be implemented based on your topic structure
    console.log('Loading topics for quiz...');
}

// Load Lessons for Test Form
function loadLessonsForTest() {
    fetch('api/lessons.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const select = document.getElementById('testLesson');
                select.innerHTML = '<option value="">Select Lesson</option>';
                
                result.data.forEach(lesson => {
                    const option = document.createElement('option');
                    option.value = lesson.id;
                    option.textContent = `${lesson.course_name} - ${lesson.title}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading lessons:', error);
        });
}

// Helper loaders added to prevent runtime errors
function loadCoursesForQuiz() {
    try {
        fetch('api/courses.php')
            .then(response => response.json())
            .then(result => {
                if (!result.success) return;
                const select = document.getElementById('quizCourse') || document.getElementById('quizTopic');
                if (!select) return;
                select.innerHTML = '<option value="">Select Course</option>';
                result.data.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = course.name;
                    select.appendChild(option);
                });
            })
            .catch(err => console.warn('Error loading courses for quiz:', err));
    } catch (e) {
        console.warn('loadCoursesForQuiz failed:', e);
    }
}

// Wrapper used by openAssignmentModal; resolves immediately if not needed
function loadCoursesForAssignmentForm() {
    return loadCoursesForAssignment();
}

async function loadLessonsForCourse(courseId, selectedLessonId = null) {
    if (!courseId) return;
    try {
        const response = await fetch(`api/lessons.php?course_id=${courseId}`);
        const result = await response.json();
        if (!result.success) return;
        const lessonSelect = document.getElementById('assignmentLesson');
        if (!lessonSelect) return;
        lessonSelect.innerHTML = '<option value="">Select Lesson (Optional)</option>';
        result.data.forEach(lesson => {
            const option = document.createElement('option');
            option.value = lesson.id;
            option.textContent = lesson.title;
            lessonSelect.appendChild(option);
        });
        if (selectedLessonId) lessonSelect.value = String(selectedLessonId);
    } catch (err) {
        console.warn('Error loading lessons for course:', err);
    }
}

// Page Data Loading
function loadPageData(page) {
    switch(page) {
        case 'courses':
            loadCoursesData();
            break;
        case 'students':
            loadStudentsData();
            break;
        case 'lessons':
            loadLessonsData();
            break;
        case 'topics':
            // Safely handle topics page if module is unavailable
            if (typeof loadTopicsData === 'function') {
                loadTopicsData();
            } else {
                console.warn('Topics module not available.');
            }
            break;
        case 'quizzes':
            // Safely handle quizzes page if module is unavailable
            if (typeof loadQuizzesData === 'function') {
                loadQuizzesData();
            } else {
                console.warn('Quizzes module not available.');
            }
            break;
        case 'tests':
            // Use the existing loader and avoid undefined functions
            loadTestsData();
            break;
        case 'reports':
            // Navigation already shows the page; just load data
            loadReports();
            break;
        case 'calendar':
            // Navigation already shows the page; initialize calendar
            initializeCalendar();
            break;
    }
}

// Load Courses Data
function loadCoursesData() {
    fetch('api/courses.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayCoursesData(result.data);
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
        });
}

// Load Students Data
function loadStudentsData() {
    fetch('api/students.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayStudentsData(result.data);
            }
        })
        .catch(error => {
            console.error('Error loading students:', error);
        });
}

// Load Lessons Data
function loadLessonsData() {
    fetch('api/lessons.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayLessonsData(result.data);
            }
        })
        .catch(error => {
            console.error('Error loading lessons:', error);
        });
}

// Load Topics Data
function loadTopicsData() {
    fetch('api/topics.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayTopicsData(result.data);
            }
        })
        .catch(error => {
            console.error('Error loading topics:', error);
        });
}

// Load Tests Data
function loadTestsData() {
    fetch('api/tests.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayTestsData(result.data);
            }
        })
        .catch(error => {
            console.error('Error loading tests:', error);
        });
}

// Display Functions
function displayCoursesData(courses) {
    const tbody = document.querySelector('#coursesTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    courses.forEach(course => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${course.name}</td>
            <td>${course.description || ''}</td>
            <td>${course.duration_weeks} weeks</td>
            <td>${course.enrolled_students || 0} students</td>
            <td><span class="badge badge-success">${course.status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editCourse(${course.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteCourse(${course.id})">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function displayStudentsData(students) {
    const tbody = document.querySelector('#studentsTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    students.forEach(student => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${student.first_name} ${student.last_name}</td>
            <td>${student.email}</td>
            <td>${student.enrolled_courses || 0} courses</td>
            <td>${student.avg_progress || 0}%</td>
            <td><span class="badge badge-success">${student.user_status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editStudent(${student.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteStudent(${student.id})">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function displayLessonsData(lessons) {
    const tbody = document.querySelector('#lessonsTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    lessons.forEach(lesson => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${lesson.title}</td>
            <td>${lesson.course_name}</td>
            <td>${lesson.duration_minutes} min</td>
            <td>${lesson.topics_count ?? 0} topics</td>
            <td><span class="badge badge-success">${lesson.status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editLesson(${lesson.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteLesson(${lesson.id})">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function displayTestsData(tests) {
    const tbody = document.querySelector('#testsTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    tests.forEach(test => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${test.title}</td>
            <td>${test.course_name}</td>
            <td>${test.duration_minutes} min</td>
            <td>${test.passing_score}%</td>
            <td><span class="badge badge-success">${test.status}</span></td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editTest(${test.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteTest(${test.id})">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Dashboard Data Loading
async function loadDashboardData() {
    try {
        const response = await fetch('api/reports.php?type=dashboard_stats');
        const result = await response.json();
        
        if (result.success) {
            updateDashboardStats(result.data);
        } else {
            console.error('Failed to load dashboard stats:', result.message);
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

function updateDashboardStats(stats) {
    // Update dashboard stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        const type = card.dataset.stat;
        const valueElement = card.querySelector('.stat-value');
        
        switch(type) {
            case 'courses':
                if (valueElement) valueElement.textContent = stats.total_courses || 0;
                break;
            case 'students':
                if (valueElement) valueElement.textContent = stats.total_students || 0;
                break;
            case 'lessons':
                if (valueElement) valueElement.textContent = stats.total_lessons || 0;
                break;
            case 'tests':
                if (valueElement) valueElement.textContent = stats.total_tests || 0;
                break;
            case 'quizzes':
                if (valueElement) valueElement.textContent = stats.total_quizzes || 0;
                break;
            case 'enrollments':
                if (valueElement) valueElement.textContent = stats.active_enrollments || 0;
                break;
        }
    });
}

// Reports Management
async function loadReports() {
    try {
        // Load different report types
        await Promise.all([
            loadStudentProgressChart(),
            loadCourseCompletionChart(),
            loadTestResultsChart(),
            loadMonthlyActivityChart()
        ]);
    } catch (error) {
        console.error('Error loading reports:', error);
    }
}

async function loadStudentProgressChart() {
    try {
        const response = await fetch('api/reports.php?type=student_progress');
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            createStudentProgressChart(result.data);
        }
    } catch (error) {
        console.error('Error loading student progress chart:', error);
    }
}

async function loadCourseCompletionChart() {
    try {
        const response = await fetch('api/reports.php?type=course_completion');
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            createCourseCompletionChart(result.data);
        }
    } catch (error) {
        console.error('Error loading course completion chart:', error);
    }
}

async function loadTestResultsChart() {
    try {
        const response = await fetch('api/reports.php?type=test_results');
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            createTestResultsChart(result.data);
        }
    } catch (error) {
        console.error('Error loading test results chart:', error);
    }
}

async function loadMonthlyActivityChart() {
    try {
        const response = await fetch('api/reports.php?type=monthly_activity');
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            createMonthlyActivityChart(result.data);
        }
    } catch (error) {
        console.error('Error loading monthly activity chart:', error);
    }
}

function createStudentProgressChart(data) {
    const ctx = document.getElementById('studentProgressChart');
    if (!ctx) return;

    const labels = data.map(item => item.course_name);
    const enrolledData = data.map(item => parseInt(item.enrolled_students) || 0);
    const completedData = data.map(item => parseInt(item.completed_students) || 0);
    const progressData = data.map(item => parseFloat(item.avg_progress) || 0);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Enrolled Students',
                data: enrolledData,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }, {
                label: 'Completed Students',
                data: completedData,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Student Progress by Course'
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function createCourseCompletionChart(data) {
    const ctx = document.getElementById('courseCompletionChart');
    if (!ctx) return;

    const labels = data.map(item => item.course_name);
    const completionRates = data.map(item => parseFloat(item.completion_rate) || 0);
    const colors = completionRates.map(rate => {
        if (rate >= 80) return 'rgba(75, 192, 192, 0.8)';
        if (rate >= 60) return 'rgba(255, 206, 86, 0.8)';
        return 'rgba(255, 99, 132, 0.8)';
    });

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: completionRates,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Course Completion Rates (%)'
                },
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });
}

function createTestResultsChart(data) {
    const ctx = document.getElementById('testResultsChart');
    if (!ctx) return;

    const labels = data.map(item => item.test_name);
    const passRates = data.map(item => parseFloat(item.pass_rate) || 0);
    const avgScores = data.map(item => parseFloat(item.avg_score) || 0);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pass Rate (%)',
                data: passRates,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Average Score',
                data: avgScores,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Test Results Analysis'
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Pass Rate (%)'
                    },
                    max: 100
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Average Score'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

function createMonthlyActivityChart(data) {
    const ctx = document.getElementById('monthlyActivityChart');
    if (!ctx) return;

    // Process data by month and activity type
    const months = [...new Set(data.map(item => item.month))].sort();
    const activityTypes = [...new Set(data.map(item => item.activity_type))];
    
    const datasets = activityTypes.map((type, index) => {
        const colors = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(255, 206, 86, 0.8)'
        ];
        
        const monthlyData = months.map(month => {
            const item = data.find(d => d.month === month && d.activity_type === type);
            return item ? parseInt(item.count) : 0;
        });

        return {
            label: type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()),
            data: monthlyData,
            backgroundColor: colors[index % colors.length],
            borderColor: colors[index % colors.length].replace('0.8', '1'),
            borderWidth: 1
        };
    });

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: months,
            datasets: datasets
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Monthly Activity Trends'
                },
                legend: {
                    position: 'top'
                }
            },
            scales: {
                x: {
                    stacked: false
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Calendar Management
let calendar;

function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            fetch('api/assignments.php?calendar=true')
                .then(response => response.json())
                .then(result => {
                    if (result.success && Array.isArray(result.data)) {
                        // API already returns FullCalendar-compatible event objects
                        successCallback(result.data);
                    } else {
                        successCallback([]);
                    }
                })
                .catch(error => {
                    console.error('Error loading calendar events:', error);
                    successCallback([]);
                });
        },
        eventClick: function(info) {
            showAssignmentDetails(info.event);
        },
        dateClick: function(info) {
            openAssignmentModal();
            document.getElementById('assignmentDueDate').value = info.dateStr + 'T23:59';
        },
        eventDidMount: function(info) {
            info.el.setAttribute('title', info.event.extendedProps.description || info.event.title);
        }
    });

    calendar.render();
}

// Helper function to get assignment colors based on status
function getAssignmentColor(status) {
    switch(status) {
        case 'active':
            return '#007bff'; // Blue
        case 'completed':
            return '#28a745'; // Green
        case 'overdue':
            return '#dc3545'; // Red
        case 'draft':
            return '#6c757d'; // Gray
        default:
            return '#17a2b8'; // Teal
    }
}

function showAssignmentDetails(event) {
    const props = event.extendedProps || {};
    const details = `
        <div class="assignment-details">
            <h5>${event.title}</h5>
            <p><strong>Course:</strong> ${props.course || 'Unknown Course'}</p>
            <p><strong>Type:</strong> ${props.type || 'assignment'}</p>
            <p><strong>Due Date:</strong> ${event.start ? event.start.toLocaleString() : ''}</p>
            <p><strong>Description:</strong> ${props.description || 'No description'}</p>
        </div>
    `;

    const container = document.createElement('div');
    container.className = 'modal fade';
    container.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assignment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">${details}</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>`;
    document.body.appendChild(container);
    const modal = new bootstrap.Modal(container);
    modal.show();
}

// Assignment Management
async function openAssignmentModal(assignmentId = null) {
    const form = document.getElementById('assignmentForm');
    form.reset();
    form.classList.remove('was-validated');
    
    // Reset form and set modal title
    document.getElementById('assignmentId').value = '';
    const modalTitle = document.getElementById('assignmentModalLabel');
    
    // Load courses for dropdown
    await loadCoursesForAssignmentForm();
    
    if (assignmentId) {
        // Edit mode - load assignment data
        modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Assignment';
        document.getElementById('assignmentId').value = assignmentId;
        
        try {
            const response = await fetch(`api/assignments.php?id=${assignmentId}`);
            const data = await response.json();
            
            if (data.success) {
                const assignment = data.data;
                document.getElementById('assignmentTitle').value = assignment.title || '';
                document.getElementById('assignmentDescription').value = assignment.description || '';
                document.getElementById('assignmentType').value = assignment.type || 'assignment';
                document.getElementById('assignmentCourse').value = assignment.course_id || '';
                
                // Load lessons for the selected course
                if (assignment.course_id) {
                    await loadLessonsForCourse(assignment.course_id, assignment.lesson_id);
                }
                
                // Format date for datetime-local input
                if (assignment.due_date) {
                    const dueDate = new Date(assignment.due_date);
                    const formattedDate = dueDate.toISOString().slice(0, 16);
                    document.getElementById('assignmentDueDate').value = formattedDate;
                }
                
                document.getElementById('assignmentMaxPoints').value = assignment.max_points || 100;
                document.getElementById('assignmentInstructions').value = assignment.instructions || '';
            }
        } catch (error) {
            console.error('Error loading assignment:', error);
            showNotification('Failed to load assignment details', 'error');
        }
    } else {
        // Create mode
        modalTitle.innerHTML = '<i class="fas fa-plus"></i> Create New Assignment';
        
        // Set default due date to tomorrow
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('assignmentDueDate').value = tomorrow.toISOString().slice(0, 16);
    }
    
    // Load courses for assignment
    loadCoursesForAssignment();
    
    const modal = new bootstrap.Modal(document.getElementById('assignmentModal'));
    modal.show();
}

function loadCoursesForAssignment() {
    fetch('api/courses.php')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const courseSelect = document.getElementById('assignmentCourse');
                courseSelect.innerHTML = '<option value="">Select Course</option>';
                
                result.data.forEach(course => {
                    const option = document.createElement('option');
                    option.value = course.id;
                    option.textContent = course.name;
                    courseSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading courses:', error));
}

// Handle course change to load lessons
document.addEventListener('change', function(e) {
    if (e.target.id === 'assignmentCourse') {
        const courseId = e.target.value;
        const lessonSelect = document.getElementById('assignmentLesson');
        
        lessonSelect.innerHTML = '<option value="">Select Lesson (Optional)</option>';
        
        if (courseId) {
            fetch(`api/lessons.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        result.data.forEach(lesson => {
                            const option = document.createElement('option');
                            option.value = lesson.id;
                            option.textContent = lesson.title;
                            lessonSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading lessons:', error));
        }
    }
});

async function saveAssignment() {
    const form = document.getElementById('assignmentForm');
    
    // Validate form
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    // Prepare assignment data matching API expectations
    const assignmentData = {
        title: document.getElementById('assignmentTitle').value,
        description: document.getElementById('assignmentDescription').value,
        course_id: parseInt(document.getElementById('assignmentCourse').value),
        lesson_id: document.getElementById('assignmentLesson').value ? parseInt(document.getElementById('assignmentLesson').value) : null,
        type: document.getElementById('assignmentType').value,
        due_date: document.getElementById('assignmentDueDate').value,
        max_points: parseFloat(document.getElementById('assignmentMaxPoints').value) || 100,
        instructions: document.getElementById('assignmentInstructions').value,
        id: document.getElementById('assignmentId').value || null
    };
    
    const isEdit = assignmentData.id !== null;
    const url = 'api/assignments.php' + (isEdit ? `?id=${assignmentData.id}` : '');
    
    try {
        const response = await fetch(url, {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(assignmentData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message || 'Assignment saved successfully!', 'success');
            
            // Hide modal and refresh calendar
            const modal = bootstrap.Modal.getInstance(document.getElementById('assignmentModal'));
            modal.hide();
            
            // Refresh calendar and assignments list
            if (typeof calendar !== 'undefined' && calendar) {
                calendar.refetchEvents();
            }
            
            // If on assignments page, refresh the list
            if (document.getElementById('assignmentsTable')) {
                loadAssignmentsData();
            }
        } else {
            showNotification(data.message || 'Failed to save assignment', 'error');
        }
    } catch (error) {
        console.error('Error saving assignment:', error);
        showNotification('Failed to save assignment', 'error');
    }
}

// Charts Initialization
function initializeCharts() {
    // Initialize any charts if needed
    console.log('Initializing charts...');
}

// Notification System
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Hide and remove after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Add CSS for notifications
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification-success {
        background-color: #28a745;
    }
    
    .notification-error {
        background-color: #dc3545;
    }
    
    .notification-info {
        background-color: #17a2b8;
    }
    
    .notification-warning {
        background-color: #ffc107;
        color: #212529;
    }
`;
document.head.appendChild(notificationStyles);

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    initializeCharts();
    
    // Load initial data
    loadDashboardData();
    
    // Load reports when reports page is accessed
    if (document.getElementById('reports-page')) {
        loadReports();
    }
    console.log('Admin script loaded successfully');
});