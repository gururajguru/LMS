// Admin Dashboard JavaScript
"use strict";

document.addEventListener('DOMContentLoaded', function() {
    // Initialize loading screen
    const loadingScreen = document.querySelector('.loading');
    window.addEventListener('load', function() {
        if (loadingScreen) {
            loadingScreen.style.display = 'none';
        }
        document.documentElement.classList.add('js-loaded');
    });

    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const adminContainer = document.querySelector('.admin-container');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            adminContainer.classList.toggle('sidebar-visible');
            this.setAttribute('aria-expanded', 
                this.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'
            );
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 768) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    adminContainer.classList.remove('sidebar-visible');
                    sidebarToggle.setAttribute('aria-expanded', 'false');
                }
            }
        });
    }
});

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatLevel(level) {
    return level.charAt(0).toUpperCase() + level.slice(1).toLowerCase();
}

function debug(msg) {
    console.log(`[Debug] ${msg}`);
}

// Form handling 
document.addEventListener('DOMContentLoaded', function() {
    // Form validation and styling
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Enable Bootstrap tooltips
    const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltips.map(function (tooltipTrigger) {
        return new bootstrap.Tooltip(tooltipTrigger);
    });

    // Initialize DataTables if present
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.datatable').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search...",
                lengthMenu: "_MENU_ per page"
            }
        });
    }
});
    }
}

// Course management functions
function loadCourses() {
    const container = document.getElementById('main-content');
    if (!container) {
        console.error('Main content container not found');
        return;
    }
    
    container.innerHTML = `
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Courses Management</h1>
                <button type="button" class="btn btn-primary" onclick="showCourseForm()">
                    <i class="fas fa-plus me-2"></i>Add Course
                </button>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="coursesTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Duration</th>
                                    <th>Level</th>
                                    <th>Students</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="coursesTableBody">
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>`;
    
    // Load courses data
    fetch('api/courses.php')
        .then(response => response.json())
        .then(courses => {
            const tbody = document.getElementById('coursesTableBody');
            if (!tbody) {
                console.error('Courses table body not found');
                return;
            }
            
            if (!Array.isArray(courses)) {
                console.error('Invalid courses data received');
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading courses</td></tr>';
                return;
            }
            
            if (courses.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No courses found</td></tr>';
                return;
            }
            
            tbody.innerHTML = courses.map(course => {
                const safeName = escapeHtml(course.name).replace(/'/g, "\\'");
                const safeCode = escapeHtml(course.code).replace(/'/g, "\\'");
                return `
                <tr>
                    <td>${escapeHtml(course.name)}</td>
                    <td>${escapeHtml(course.code)}</td>
                    <td>${course.duration_weeks} ${course.duration_weeks === 1 ? 'week' : 'weeks'}</td>
                    <td>${escapeHtml(course.level)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="viewCourseStudents('${course.id}', '${safeName}', '${safeCode}')">
                            <i class="fas fa-users me-1"></i>${course.enrollment_count || 0} enrolled
                        </button>
                    </td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" title="View"
                                    onclick="viewCourse('${course.id}', '${safeName}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                    onclick="editCourse('${course.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                    onclick="deleteCourse('${course.id}', '${safeName}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        })
        .catch(error => {
            console.error('Error loading courses:', error);
            const tbody = document.getElementById('coursesTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            Error loading courses: ${escapeHtml(error.message)}
                        </td>
                    </tr>`;
            }
        });
}

// Course action functions
function showCourseForm(courseId = null) {
    console.log('Show course form for ID:', courseId);
    // Implement course form
}

function viewCourse(id) {
    if (!id) {
        console.error('No course ID provided');
        return;
    }

    const container = document.getElementById('main-content');
    if (!container) {
        console.error('Main content container not found');
        return;
    }

    try {
        const button = document.querySelector(`[data-id="${id}"]`);
        const name = button ? decodeURIComponent(button.getAttribute('data-name') || 'Course') : 'Course';
        console.log(`Viewing course: ${name} (ID: ${id})`);
        
        // Show loading state
        container.innerHTML = `
            <div class="d-flex flex-column justify-content-center align-items-center" style="min-height: 300px;">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading course details...</span>
                </div>
                <p class="text-muted">Loading course details...</p>
            </div>`;
        
        // Fetch course details with error handling
        fetch(`api/courses.php?id=${encodeURIComponent(id)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success || !data.course) {
                    throw new Error(data.message || 'Failed to load course data');
                }
                const course = data.course;
                const safeName = escapeHtml(course.name || 'Course').replace(/'/g, "\\'");
                const safeCode = escapeHtml(course.code || '').replace(/'/g, "\\'");
                
                // Render course details
                container.innerHTML = `
                    <div class="container-fluid py-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>${escapeHtml(course.name || 'Course Details')}</h2>
                            <div class="btn-group">
                                <button class="btn btn-outline-primary" onclick="editCourse('${course.id}')">
                                    <i class="fas fa-edit me-2"></i>Edit
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteCourse('${course.id}', '${safeName}')">
                                    <i class="fas fa-trash me-2"></i>Delete
                                </button>
                                <button class="btn btn-outline-secondary" onclick="loadCourses()">
                                    <i class="fas fa-arrow-left me-2"></i>Back to List
                                </button>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <h5 class="card-title">Course Information</h5>
                                        <table class="table table-borderless">
                                            <tr>
                                                <th style="width: 150px;">Code:</th>
                                                <td>${escapeHtml(course.code || 'N/A')}</td>
                                            </tr>
                                            <tr>
                                                <th>Duration:</th>
                                                <td>${course.duration_weeks || 0} week${course.duration_weeks != 1 ? 's' : ''}</td>
                                            </tr>
                                            <tr>
                                                <th>Level:</th>
                                                <td>${formatLevel(course.level || '')}</td>
                                            </tr>
                                            <tr>
                                                <th>Status:</th>
                                                <td>${course.status ? 'Active' : 'Inactive'}</td>
                                            </tr>
                                            ${course.description ? `
                                            <tr>
                                                <th>Description:</th>
                                                <td>${escapeHtml(course.description)}</td>
                                            </tr>` : ''}
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0">Enrolled Students</h5>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewCourseStudents('${course.id}', '${safeName}', '${safeCode}')">
                                                <i class="fas fa-users me-1"></i>
                                                View All Students (${course.enrollment_count || 0})
                                            </button>
                                        </div>
                                        <div id="enrolledStudentsList">
                                            <div class="text-center py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading students...</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Quick Actions</h5>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        <a href="#" class="list-group-item list-group-item-action" 
                                           onclick="viewCourseStudents('${course.id}', '${safeName}', '${safeCode}')">
                                            <i class="fas fa-users me-2"></i> View All Students
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action" 
                                           onclick="addStudentToCourse('${course.id}')">
                                            <i class="fas fa-user-plus me-2"></i> Add Students
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-book me-2"></i> View Lessons
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action">
                                            <i class="fas fa-chart-bar me-2"></i> View Progress
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Course Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Enrollment</span>
                                                <span>${course.enrollment_count || 0} students</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: ${Math.min(100, (course.enrollment_count || 0) * 10)}%" 
                                                     aria-valuenow="${course.enrollment_count || 0}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="10">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Completion Rate</span>
                                                <span>${course.completion_rate || 0}%</span>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-success" role="progressbar" 
                                                     style="width: ${course.completion_rate || 0}%" 
                                                     aria-valuenow="${course.completion_rate || 0}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
                
                // Load enrolled students
                loadEnrolledStudents(course.id);
            })
            .catch(error => {
                console.error('Error loading course details:', error);
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">Error Loading Course</h5>
                        <p>${escapeHtml(error.message || 'An error occurred while loading the course details.')}</p>
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadCourses()">
                            <i class="fas fa-arrow-left me-1"></i>Back to Courses
                        </button>
                    </div>`;
            });
    } catch (error) {
        console.error('Error in viewCourse:', error);
        if (container) {
            container.innerHTML = `
                <div class="alert alert-danger">
                    <h5 class="alert-heading">Error</h5>
                    <p>${escapeHtml(error.message || 'An unexpected error occurred.')}</p>
                    <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadCourses()">
                        <i class="fas fa-arrow-left me-1"></i>Back to Courses
                    </button>
                </div>`;
        }
    }
}

function loadEnrolledStudents(courseId) {
    const container = document.getElementById('enrolledStudentsList');
    if (!container) return;
    
    fetch(`api/courses.php?id=${courseId}&students=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.students && data.students.length > 0) {
                const students = data.students.slice(0, 5); // Show only first 5 students
                container.innerHTML = `
                    <div class="list-group">
                        ${students.map(student => `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">${escapeHtml(student.name)}</h6>
                                        <small class="text-muted">${escapeHtml(student.email)}</small>
                                    </div>
                                    <span class="badge bg-${student.status === 'active' ? 'success' : 'secondary'}">
                                        ${student.status}
                                    </span>
                                </div>
                            </div>
                        `).join('')}
                        ${data.students.length > 5 ? `
                            <div class="text-center mt-2">
                                <small class="text-muted">+${data.students.length - 5} more students</small>
                            </div>
                        ` : ''}
                    </div>`;
            } else {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-users-slash fa-2x text-muted mb-2"></i>
                        <p class="mb-0">No students enrolled in this course yet.</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="addStudentToCourse('${courseId}')">
                            <i class="fas fa-user-plus me-1"></i> Add Students
                        </button>
                    </div>`;
            }
        })
        .catch(error => {
            console.error('Error loading enrolled students:', error);
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Failed to load student list: ${error.message}
                </div>`;
        });
}

function editCourse(id) {
    console.log('Edit course ID:', id);
    // Implement edit functionality
}

function deleteCourse(id, name) {
    if (!confirm(`Are you sure you want to delete course "${name}"?`)) {
        return;
    }
    
    fetch(`api/courses.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Course deleted successfully!');
            loadCourses();
        } else {
            throw new Error(data.message || 'Failed to delete course');
        }
    })
    .catch(error => {
        console.error('Error deleting course:', error);
        alert(`Error: ${error.message}`);
    });
}

function viewCourseStudents(courseId, courseName, courseCode) {
    console.log(`Viewing students for course: ${courseName} (${courseCode})`);
    // Implement student list functionality
}

// Student management functions
function loadStudents() {
    const container = document.getElementById('main-content');
    if (!container) {
        console.error('Main content container not found');
        return;
    }
    
    container.innerHTML = `
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Students</h1>
                <button type="button" class="btn btn-primary" onclick="showStudentForm()">
                    <i class="fas fa-plus me-2"></i>Add Student
                </button>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody">
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>`;
    
    // Load students data
    fetch('api/students.php')
        .then(response => response.json())
        .then(students => {
            const tbody = document.getElementById('studentsTableBody');
            if (!tbody) {
                console.error('Students table body not found');
                return;
            }
            
            if (!Array.isArray(students)) {
                console.error('Invalid students data received');
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading students</td></tr>';
                return;
            }
            
            if (students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No students found</td></tr>';
                return;
            }
            
            tbody.innerHTML = students.map(student => {
                const safeName = escapeHtml(`${student.first_name} ${student.last_name}`).replace(/'/g, "\\'");
                const safeEmail = escapeHtml(student.email).replace(/'/g, "\\'");
                
                return `
                <tr>
                    <td>${escapeHtml(`${student.first_name} ${student.last_name}`)}</td>
                    <td>${escapeHtml(student.email)}</td>
                    <td>${escapeHtml(student.phone || 'N/A')}</td>
                    <td><span class="badge bg-${student.status === 'active' ? 'success' : 'secondary'}">${student.status}</span></td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" title="View"
                                    onclick="viewStudent('${student.id}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                    onclick="editStudent('${student.id}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                    onclick="deleteStudent('${student.id}', '${safeName}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        })
        .catch(error => {
            console.error('Error loading students:', error);
            const tbody = document.getElementById('studentsTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            Error loading students: ${escapeHtml(error.message)}
                        </td>
                    </tr>`;
            }
        });
}

// Dashboard functions
function loadDashboard() {
    const container = document.getElementById('main-content');
    if (!container) {
        console.error('Main content container not found');
        return;
    }
    
    container.innerHTML = `
        <div class="container-fluid py-4">
            <h1 class="h3 mb-4">Dashboard</h1>
            
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Total Courses</h5>
                                <i class="fas fa-book fa-2x opacity-50"></i>
                            </div>
                            <h2 id="total-courses" class="display-6 mb-0 mt-auto">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Total Students</h5>
                                <i class="fas fa-users fa-2x opacity-50"></i>
                            </div>
                            <h2 id="total-students" class="display-6 mb-0 mt-auto">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Active Courses</h5>
                                <i class="fas fa-chart-line fa-2x opacity-50"></i>
                            </div>
                            <h2 id="active-users" class="display-6 mb-0 mt-auto">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Completed</h5>
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
                            </div>
                            <h2 id="completed-courses" class="display-6 mb-0 mt-auto">0</h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div id="recent-activity">
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action" onclick="loadPage('courses')">
                                <i class="fas fa-book me-2"></i> Manage Courses
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="loadPage('students')">
                                <i class="fas fa-users me-2"></i> Manage Students
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="loadPage('users')">
                                <i class="fas fa-user-shield me-2"></i> Manage Users
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" onclick="showCourseForm()">
                                <i class="fas fa-plus-circle me-2"></i> Add New Course
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>`;
    
    // Load dashboard stats
    fetch('api/dashboard-stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to load dashboard stats');
            }
            
            // Update courses count
            const coursesElem = document.getElementById('total-courses');
            if (coursesElem && data.data?.total_courses !== undefined) {
                coursesElem.textContent = data.data.total_courses.toLocaleString();
            }
            
            // Update students count
            const studentsElem = document.getElementById('total-students');
            if (studentsElem && data.data?.total_students !== undefined) {
                studentsElem.textContent = data.data.total_students.toLocaleString();
            }
            
            // Update active courses count
            const activeUsersElem = document.getElementById('active-users');
            if (activeUsersElem && data.data?.active_courses !== undefined) {
                activeUsersElem.textContent = data.data.active_courses.toLocaleString();
            }
            
            // Update completed courses count if the element exists
            const completedCoursesElem = document.getElementById('completed-courses');
            if (completedCoursesElem && data.data?.completed_courses !== undefined) {
                completedCoursesElem.textContent = data.data.completed_courses.toLocaleString();
            }
        })
        .catch(error => {
            console.error('Error loading dashboard data:', error);
            // Optionally show an error message to the user
            const errorContainer = document.createElement('div');
            errorContainer.className = 'alert alert-danger mt-3';
            errorContainer.textContent = 'Failed to load dashboard statistics. Please try again later.';
            document.querySelector('.container-fluid').appendChild(errorContainer);
        });
}

// File input handling
document.querySelectorAll('.file-input').forEach(input => {
    input.addEventListener('change', function() {
        const previewId = this.getAttribute('data-preview');
        if (previewId) {
            readURL(this, previewId);
        }
    });
});

// Initialize password toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const icon = document.querySelector(`[onclick="togglePassword('${inputId}')"] i`);
    if (!icon) return;
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Handle tab state in URL
function initTabs() {
    // Activate tab from URL hash
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`a[href="${hash}"][data-bs-toggle="tab"]`);
        if (tab) {
            new bootstrap.Tab(tab).show();
        }
    }
    
    // Update URL when tab is shown
    const tabElms = document.querySelectorAll('a[data-bs-toggle="tab"]');
    tabElms.forEach(tabElm => {
        tabElm.addEventListener('shown.bs.tab', function(e) {
            window.location.hash = e.target.getAttribute('href');
        });
    });
}

// Initialize charts if Chart.js is available
function initCharts() {
    const ctx = document.getElementById('dashboardChart');
    if (ctx && typeof Chart !== 'undefined') {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Users',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'User Growth'
                    }
                }
            }
        });
    }
}

// Initialize the application when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    try {
        // Initialize UI components
        if (typeof initUIComponents === 'function') initUIComponents();
        if (typeof setupNavigation === 'function') setupNavigation();
        if (typeof initTabs === 'function') initTabs();
        
        // Initialize DataTables if jQuery is available
        if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
            jQuery('.datatable').DataTable({
                responsive: true,
                pageLength: 25,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search..."
                }
            });
        }
        
        // Initialize charts if Chart.js is available
        if (typeof Chart !== 'undefined' && typeof initCharts === 'function') {
            initCharts();
        }
        
        // Initialize clipboard.js if available
        if (typeof ClipboardJS !== 'undefined') {
            const clipboard = new ClipboardJS('.btn-copy');
            
            // Show tooltip when copied
            document.querySelectorAll('.btn-copy').forEach(button => {
                button.addEventListener('click', function() {
                    const tooltip = bootstrap?.Tooltip?.getInstance(button);
                    if (tooltip) {
                        const originalTitle = button.getAttribute('data-bs-original-title');
                        button.setAttribute('data-bs-original-title', 'Copied!');
                        tooltip.show();
                        
                        setTimeout(() => {
                            button.setAttribute('data-bs-original-title', originalTitle);
                            tooltip.hide();
                        }, 2000);
                    }
                });
            });

            // Handle clipboard errors
            clipboard.on('error', function(e) {
                console.error('Clipboard error:', e);
            });
        }
    } catch (error) {
        console.error('Error initializing application:', error);
    }
});
