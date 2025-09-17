// Admin Dashboard JavaScript
"use strict";

/**
 * Main application functionality for the LMS Admin Dashboard
 */

// Global utility functions
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

// Page navigation
function loadPage(pageId) {
    debug(`Loading page: ${pageId}`);
    
    // Update navigation state
    document.querySelectorAll('.nav-link').forEach(link => {
        link.parentElement.classList.remove('active');
    });
    
    const activeLink = document.querySelector(`.nav-link[data-page="${pageId}"]`);
    if (activeLink) {
        activeLink.parentElement.classList.add('active');
    }
    
    // Load the appropriate content
    switch (pageId) {
        case 'courses':
            loadCourses();
            break;
        case 'students':
            loadStudents();
            break;
        case 'lessons':
            // loadLessons();
            break;
        default:
            loadDashboard();
            break;
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

function viewCourse(id, name) {
    console.log(`Viewing course: ${name} (ID: ${id})`);
    // Implement view functionality
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
                <div class="col-md-4 mb-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Courses</h5>
                            <h2 id="total-courses" class="mb-0">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Students</h5>
                            <h2 id="total-students" class="mb-0">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">Active Users</h5>
                            <h2 id="active-users" class="mb-0">0</h2>
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
    Promise.all([
        fetch('api/courses.php').then(res => res.json()),
        fetch('api/students.php').then(res => res.json())
    ]).then(([coursesData, studentsData]) => {
        // Update courses count
        const coursesElem = document.getElementById('total-courses');
        if (coursesElem && coursesData?.success && Array.isArray(coursesData.data)) {
            coursesElem.textContent = coursesData.data.length;
        }
        
        // Update students count
        const studentsElem = document.getElementById('total-students');
        if (studentsElem && studentsData?.success && Array.isArray(studentsData.data)) {
            studentsElem.textContent = studentsData.data.length;
        }
        
        // Update active users count
        const activeUsersElem = document.getElementById('active-users');
        if (activeUsersElem && studentsData?.data) {
            const activeCount = studentsData.data.filter(s => s.status === 'active').length;
            activeUsersElem.textContent = activeCount || 0;
        }
    }).catch(error => {
        console.error('Error loading dashboard data:', error);
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
