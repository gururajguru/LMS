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
            // loadStudents();
            break;
        case 'lessons':
            // loadLessons();
            break;
        default:
            // loadDashboard();
            break;
    }
}

// Course management functions
function loadCourses() {
    debug('Loading courses page');
    
    const container = document.getElementById('main-content');
    if (!container) {
        console.error('Main content container not found');
        return;
    }
    
    // Set up courses page structure
    container.innerHTML = `
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Course Management</h1>
                <button type="button" class="btn btn-primary" onclick="showCourseForm()">
                    <i class="fas fa-plus me-2"></i>Add Course
                </button>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
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
    fetchCourses();
}

function fetchCourses() {
    debug('Fetching courses data');
    
    const tbody = document.getElementById('coursesTableBody');
    if (!tbody) {
        console.error('Courses table body not found');
        return;
    }
    
    fetch('api/courses.php')
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(courses => {
            if (!Array.isArray(courses)) throw new Error('Invalid data format received');

            if (courses.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">No courses found</td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = courses.map(course => `
                <tr>
                    <td>${escapeHtml(course.name)}</td>
                    <td>${escapeHtml(course.code)}</td>
                    <td>${course.duration_weeks} ${course.duration_weeks === 1 ? 'week' : 'weeks'}</td>
                    <td>${formatLevel(course.level)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="viewCourseStudents(${course.id}, '${escapeHtml(course.name)}', '${escapeHtml(course.code)}')">
                            <i class="fas fa-users me-1"></i>${course.enrollment_count || 0} enrolled
                        </button>
                    </td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" title="View"
                                    onclick="viewCourse(${course.id}, '${escapeHtml(course.name)}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                    onclick="editCourse(${course.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                    onclick="deleteCourse(${course.id}, '${escapeHtml(course.name)}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Error loading courses:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger">
                        Error loading courses: ${escapeHtml(error.message)}
                    </td>
                </tr>`;
        });
}

// Course action functions
function showCourseForm(courseId = null) {
    debug(`Opening course form. Course ID: ${courseId}`);
    // Implementation will be added
}

function viewCourse(id, name) {
    debug(`Opening course view. ID: ${id}, Name: ${name}`);
    // Implementation will be added
}

function editCourse(id) {
    debug(`Opening course editor. ID: ${id}`);
    // Implementation will be added
}

function deleteCourse(id, name) {
    debug(`Opening delete confirmation. ID: ${id}, Name: ${name}`);
    // Implementation will be added
}

function viewCourseStudents(courseId, courseName, courseCode) {
    debug(`Opening student list. Course: ${courseName} (${courseId})`);
    // Implementation will be added
}

// Page Loading System
class PageManager {
    static currentPage = 'dashboard';
    
    static init() {
        debug('Initializing page manager');
        this.setupNavigation();
        this.loadInitialPage();
    }
    
    static setupNavigation() {
        debug('Setting up navigation');
        document.querySelectorAll('.nav-link[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const pageId = link.getAttribute('data-page');
                if (pageId) this.loadPage(pageId);
            });
        });
    }
    
    static loadInitialPage() {
        const urlParams = new URLSearchParams(window.location.search);
        const pageId = urlParams.get('page') || 'dashboard';
        this.loadPage(pageId);
    }
    
    static loadPage(pageId) {
        debug(`Loading page: ${pageId}`);
        
        // Update navigation state
        document.querySelectorAll('.nav-link').forEach(link => {
            link.parentElement.classList.remove('active');
        });
        
        const activeLink = document.querySelector(`.nav-link[data-page="${pageId}"]`);
        if (activeLink) {
            activeLink.parentElement.classList.add('active');
        }
        
        // Update current page
        this.currentPage = pageId;
        
        // Load the appropriate content
        switch (pageId) {
            case 'courses':
                CourseManager.loadCourses();
                break;
            case 'students':
                // loadStudents();
                break;
            case 'lessons':
                // loadLessons();
                break;
            default:
                // loadDashboard();
                break;
        }
    }
}

// Course Management System
class CourseManager {
    static loadCourses() {
        debug('Loading courses');
        
        const container = document.getElementById('main-content');
        if (!container) {
            console.error('Main content container not found');
            return;
        }
        
        // Set up the courses page structure
        container.innerHTML = `
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Course Management</h1>
                    <button type="button" class="btn btn-primary" onclick="CourseManager.showCourseForm()">
                        <i class="fas fa-plus me-2"></i>Add Course
                    </button>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
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
        this.fetchCourses();
    }
    
    static async fetchCourses() {
        const tbody = document.getElementById('coursesTableBody');
        if (!tbody) {
            console.error('Courses table body not found');
            return;
        }

        try {
            const response = await fetch('api/courses.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const courses = await response.json();
            if (!Array.isArray(courses)) {
                throw new Error('Invalid data format received');
            }

            if (courses.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center">No courses found</td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = courses.map(course => `
                <tr>
                    <td>${escapeHtml(course.name)}</td>
                    <td>${escapeHtml(course.code)}</td>
                    <td>${course.duration_weeks} ${course.duration_weeks === 1 ? 'week' : 'weeks'}</td>
                    <td>${formatLevel(course.level)}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="CourseManager.viewStudents(${course.id}, '${escapeHtml(course.name)}', '${escapeHtml(course.code)}')">
                            <i class="fas fa-users me-1"></i>${course.enrollment_count || 0} enrolled
                        </button>
                    </td>
                    <td class="text-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" title="View"
                                    onclick="CourseManager.viewCourse(${course.id}, '${escapeHtml(course.name)}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit"
                                    onclick="CourseManager.editCourse(${course.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete"
                                    onclick="CourseManager.deleteCourse(${course.id}, '${escapeHtml(course.name)}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            
        } catch (error) {
            console.error('Error loading courses:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger">
                        Error loading courses: ${escapeHtml(error.message)}
                    </td>
                </tr>`;
        }
    }
    
    static showCourseForm(courseId = null) {
        debug(`Opening course form. Course ID: ${courseId}`);
        // Implementation will be added
    }
    
    static viewCourse(id, name) {
        debug(`Opening course view. ID: ${id}, Name: ${name}`);
        // Implementation will be added
    }
    
    static editCourse(id) {
        debug(`Opening course editor. ID: ${id}`);
        // Implementation will be added
    }
    
    static deleteCourse(id, name) {
        debug(`Opening delete confirmation. ID: ${id}, Name: ${name}`);
        // Implementation will be added
    }
    
    static viewStudents(courseId, courseName, courseCode) {
        debug(`Opening student list. Course: ${courseName} (${courseId})`);
        // Implementation will be added
    }
}

// Initialize application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    debug('DOM loaded - initializing application');
    
    // Initialize UI components
    if (typeof initUIComponents === 'function') {
        initUIComponents();
    } else if (typeof initializeUI === 'function') {
        initializeUI();
    }
    
    // Set up navigation
    if (typeof setupNavigation === 'function') {
        setupNavigation();
    }
    
    // Load initial page
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page') || 'dashboard';
    if (typeof loadPage === 'function') {
        loadPage(page);
    }
});

// Initialize clipboard functionality if ClipboardJS is available
if (typeof ClipboardJS !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
        try {
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
            
            clipboard.on('error', function(e) {
                console.error('Clipboard error:', e);
            });
        } catch (error) {
            console.error('Error initializing clipboard:', error);
        }
    });
}

// Helper Functions
function escapeHtml(unsafe) {
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function formatLevel(level) {
    return level.charAt(0).toUpperCase() + level.slice(1).toLowerCase();
}

function initializeUI() {
    // Initialize Bootstrap components if available
    if (typeof bootstrap !== 'undefined') {
        // Initialize tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => new bootstrap.Tooltip(el));
    }
    
    // Setup navigation
    setupNavigation();
}

function setupNavigation() {
    document.querySelectorAll('.nav-link[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            if (page) loadPage(page);
        });
    });
}

function loadPage(pageId) {
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
        case 'dashboard':
            loadDashboard();
            break;
        case 'courses':
            loadCourses();
            break;
        case 'students':
            loadStudents();
            break;
        default:
            loadDashboard();
    }
}

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

/**
 * Load dashboard
 */
function loadDashboard() {
    const container = document.getElementById('main-content');
    if (container) {
        container.innerHTML = `
        <div class="container-fluid">
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
                <div class="col-12">
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
            </div>
        </div>`;
    }
    
    // Load dashboard stats
    Promise.all([
        fetch(API_BASE_URL + 'courses.php').then(res => res.json()),
        fetch(API_BASE_URL + 'students.php').then(res => res.json())
    ]).then(([coursesData, studentsData]) => {
        // Update courses count
        var coursesElem = document.getElementById('total-courses');
        if (coursesElem && coursesData?.success && Array.isArray(coursesData.data)) {
            coursesElem.textContent = coursesData.data.length;
        }

        // Update students count
        var studentsElem = document.getElementById('total-students');
        if (studentsElem && studentsData?.success && Array.isArray(studentsData.data)) {
            studentsElem.textContent = studentsData.data.length;
        }

        // Update active users (example - adjust based on your API)
        var activeUsersElem = document.getElementById('active-users');
        if (activeUsersElem && studentsData?.data) {
            activeUsersElem.textContent = studentsData.data.filter(s => s.status === 'active').length || 0;
        }
    }).catch(error => {
        console.error('Error loading dashboard data:', error);
    });
}

/**
 * Load students
 */
function loadStudents() {
    const container = document.getElementById('main-content');
    if (!container) {
        console.error('Main content container not found.');
        return;
    }
    container.innerHTML = `
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Students</h1>
                <button class="btn btn-primary" onclick="showStudentForm()">
                    <i class="fas fa-plus me-2"></i> Add Student
                </button>
            </div>
            
            <div class="card">
                <div class="card-body p-0">
                    <div id="students-table-container"></div>
                </div>
            </div>
        </div>`;
    
    const columns = [
        { label: 'ID', field: 'id', width: '10%' },
        { 
            label: 'Name', 
            field: 'first_name', 
            render: student => `${student.first_name} ${student.last_name}`,
            width: '25%'
        },
        { label: 'Email', field: 'email', width: '25%' },
        { label: 'Username', field: 'username', width: '15%' },
        { 
            label: 'Status', 
            field: 'status',
            render: student => {
                const statusClass = student.status === 'active' ? 'success' : 'secondary';
                return `<span class="badge bg-${statusClass}">${student.status}</span>`;
            },
            width: '10%'
        }
    ];
    
    const actions = [
        { 
            type: 'outline-primary', 
            icon: 'fas fa-eye', 
            title: 'View',
            onClick: student => viewStudent(student.id)
        },
        { 
            type: 'outline-secondary', 
            icon: 'fas fa-edit', 
            title: 'Edit',
            onClick: student => editStudent(student.id)
        },
        { 
            type: 'outline-danger', 
            icon: 'fas fa-trash', 
            title: 'Delete',
            onClick: student => deleteStudent(student.id, `${student.first_name} ${student.last_name}`)
        }
    ];
    
    loadData('students.php', 'students-table-container', columns, actions);
}

/**
 * Load users
 */
function loadUsers() {
    const container = document.getElementById('main-content');
    if (container) {
        container.innerHTML = `
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Users</h1>
                <button class="btn btn-primary" onclick="showUserForm()">
                    <i class="fas fa-plus me-2"></i> Add User
                </button>
            </div>
            
            <div class="card">
                <div class="card-body p-0">
                    <div id="users-table-container"></div>
                </div>
            </div>
    }
        </div>`;
    
    const columns = [
        { label: 'ID', field: 'id', width: '10%' },
        { label: 'Username', field: 'username', width: '20%' },
        { label: 'Email', field: 'email', width: '25%' },
        { 
            label: 'Role', 
            field: 'role',
            render: user => {
                const roles = {
                    'admin': 'danger',
                    'instructor': 'primary',
                    'student': 'success',
                    'user': 'secondary'
                };
                const roleClass = roles[user.role] || 'secondary';
                return `<span class="badge bg-${roleClass}">${user.role}</span>`;
            },
            width: '15%'
        },
        { 
            label: 'Status', 
            field: 'status',
            render: user => {
                const statusClass = user.status === 'active' ? 'success' : 'secondary';
                return `<span class="badge bg-${statusClass}">${user.status}</span>`;
            },
            width: '10%'
        },
        { 
            label: 'Last Login', 
            field: 'last_login',
            render: user => user.last_login ? formatDate(user.last_login) : 'Never',
            width: '20%'
        }
    ];
    
    const actions = [
        { 
            type: 'outline-primary', 
            icon: 'fas fa-eye', 
            title: 'View',
            onClick: user => viewUser(user.id)
        },
        { 
            type: 'outline-secondary', 
            icon: 'fas fa-edit', 
            title: 'Edit',
            onClick: user => editUser(user.id)
        },
        { 
            type: 'outline-danger', 
            icon: 'fas fa-trash', 
            title: 'Delete',
            condition: user => user.role !== 'admin',
            onClick: user => deleteUser(user.id, user.username)
        }
    ];
    
    loadData('users.php', 'users-table-container', columns, actions);
}

// Form Handling

document.querySelectorAll('.ajax-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Operation completed successfully',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    // Redirect if needed
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        // Reload the page or update the UI
                        window.location.reload();
                    }
                });
            } else {
                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'An error occurred. Please try again.'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred. Please try again.'
            });
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
});

// Initialize select2 if available
if (typeof $ !== 'undefined' && $.fn.select2) {
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
}

// Initialize date picker if available
if (typeof $ !== 'undefined' && $.fn.datepicker) {
    $(document).ready(function(){
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });
    });
}

/**
 * Delete a course
 * @param {number} id - Course ID
 * @param {string} name - Course name (for confirmation)
 */
function deleteCourse(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        return;
    }
    
    fetch(`${API_BASE_URL}courses.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Course deleted successfully!');
            
            // Refresh courses list
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

/**
 * View course details
 * @param {number} id - Course ID
 * @param {string} name - Course name
 */
function viewCourse(id, name) {
    // Redirect to course details page or show in a modal
    console.log(`Viewing course: ${name} (ID: ${id})`);
    // Implement view functionality
}

// Similar functions for students and users
function showStudentForm(studentId) {
    // Implement student form
    console.log('Show student form for ID:', studentId);
}

function saveStudent(e) {
    // Implement student save
    e.preventDefault();
    console.log('Save student');
}

function deleteStudent(id, name) {
    if (!confirm(`Are you sure you want to delete student "${name}"?`)) {
        return;
    }
    
    fetch(`${API_BASE_URL}students.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Student deleted successfully!');
            loadStudents();
        } else {
            throw new Error(data.message || 'Failed to delete student');
        }
    })
    .catch(error => {
        console.error('Error deleting student:', error);
        alert(`Error: ${error.message}`);
    });
}

function showUserForm(userId) {
    // Implement user form
    console.log('Show user form for ID:', userId);
}

function saveUser(e) {
    // Implement user save
    e.preventDefault();
    console.log('Save user');
}

function deleteUser(id, username) {
    if (!confirm(`Are you sure you want to delete user "${username}"?`)) {
        return;
    }
    
    fetch(`${API_BASE_URL}users.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('User deleted successfully!');
            loadUsers();
        } else {
            throw new Error(data.message || 'Failed to delete user');
        }
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        alert(`Error: ${error.message}`);
    });
}

// File input handling
function readURL(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// ... (rest of the code remains the same)
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
    const icon = document.querySelector(`[onclick="togglePassword('${inputId}')"] i`);
    
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
        tabElm.addEventListener('shown.bs.tab', function (e) {
            window.location.hash = e.target.getAttribute('href');
        });
    });
}

// Initialize charts if Chart.js is available
function initCharts() {
    // Example chart - replace with your actual chart data
    const ctx = document.getElementById('dashboardChart');
    if (ctx) {
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
