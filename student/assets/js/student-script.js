// Student Portal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeStudentPortal();
});

function initializeStudentPortal() {
    // Initialize navigation
    initializeNavigation();
    
    // Initialize charts
    initializeCharts();
    
    // Initialize FAQ functionality
    initializeFAQ();
    
    // Initialize quick actions
    initializeQuickActions();
    
    // Load initial data
    loadPageData('dashboard');
}

// Navigation Management
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.content-section');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and sections
            navLinks.forEach(l => l.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Show corresponding section
            const targetSection = this.getAttribute('data-section');
            const targetSectionElement = document.getElementById(targetSection);
            
            if (targetSectionElement) {
                targetSectionElement.classList.add('active');
                
                // Load section-specific data
                loadPageData(targetSection);
            }
        });
    });
    
    // Sidebar toggle for mobile
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
}

// Charts Initialization
function initializeCharts() {
    // Course Progress Chart
    const courseProgressCtx = document.getElementById('courseProgressChart');
    if (courseProgressCtx) {
        new Chart(courseProgressCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: ['#4CAF50', '#FF9800', '#9E9E9E'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
}

// FAQ Functionality
function initializeFAQ() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', function() {
            const isActive = item.classList.contains('active');
            
            // Close all FAQ items
            faqItems.forEach(faqItem => {
                faqItem.classList.remove('active');
            });
            
            // Open clicked item if it wasn't active
            if (!isActive) {
                item.classList.add('active');
            }
        });
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
        case 'continue-learning':
            // Navigate to courses section
            navigateToSection('courses');
            break;
        case 'take-quiz':
            // Navigate to assessments section
            navigateToSection('assessments');
            break;
        case 'view-progress':
            // Navigate to progress section
            navigateToSection('progress');
            break;
        case 'download-certificate':
            // Navigate to certificates section
            navigateToSection('certificates');
            break;
        default:
            console.log('Unknown action:', action);
    }
}

function navigateToSection(sectionName) {
    // Remove active class from all links and sections
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.content-section');
    
    navLinks.forEach(l => l.classList.remove('active'));
    sections.forEach(s => s.classList.remove('active'));
    
    // Add active class to target section
    const targetLink = document.querySelector(`[data-section="${sectionName}"]`);
    const targetSection = document.getElementById(sectionName);
    
    if (targetLink && targetSection) {
        targetLink.classList.add('active');
        targetSection.classList.add('active');
        
        // Load section data
        loadPageData(sectionName);
    }
}

// Page Data Loading
function loadPageData(page) {
    switch(page) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'courses':
            loadCoursesData();
            break;
        case 'lessons':
            loadLessonsData();
            break;
        case 'assessments':
            loadAssessmentsData();
            break;
        case 'exercises':
            loadExercisesData();
            break;
        case 'progress':
            loadProgressData();
            break;
        case 'certificates':
            loadCertificatesData();
            break;
        case 'profile':
            loadProfileData();
            break;
        case 'announcements':
            loadAnnouncementsData();
            break;
        case 'support':
            loadSupportData();
            break;
    }
}

function loadDashboardData() {
    // Dashboard data is already loaded from PHP
    console.log('Dashboard data loaded');
    
    // Update stats if needed
    updateDashboardStats();
}

function loadCoursesData() {
    // Courses data is loaded from PHP, but we can enhance it here
    console.log('Courses data loaded');
    
    // Add course card interactions
    initializeCourseCards();
}

function loadLessonsData() {
    console.log('Loading lessons data...');
    
    // Simulate loading lessons data
    const lessonsList = document.getElementById('lessonsList');
    if (lessonsList) {
        lessonsList.innerHTML = `
            <div class="lesson-item">
                <div class="lesson-header">
                    <h3>Introduction to HTML</h3>
                    <span class="lesson-status completed">Completed</span>
                </div>
                <div class="lesson-content">
                    <p>Learn the basics of HTML markup and structure</p>
                    <div class="lesson-meta">
                        <span><i class="fas fa-clock"></i> 45 minutes</span>
                        <span><i class="fas fa-book"></i> 3 topics</span>
                    </div>
                    <button class="btn btn-secondary">Review Lesson</button>
                </div>
            </div>
            <div class="lesson-item">
                <div class="lesson-header">
                    <h3>CSS Fundamentals</h3>
                    <span class="lesson-status in-progress">In Progress</span>
                </div>
            <div class="lesson-content">
                    <p>Master CSS styling and layout techniques</p>
                    <div class="lesson-meta">
                        <span><i class="fas fa-clock"></i> 60 minutes</span>
                        <span><i class="fas fa-book"></i> 4 topics</span>
                    </div>
                    <button class="btn btn-primary">Continue Learning</button>
                </div>
            </div>
        `;
    }
}

function loadAssessmentsData() {
    console.log('Assessments data loaded');
    
    // Add assessment interactions
    initializeAssessmentCards();
}

function loadExercisesData() {
    console.log('Exercises data loaded');
    
    // Add exercise interactions
    initializeExerciseCards();
}

function loadProgressData() {
    console.log('Progress data loaded');
    
    // Initialize progress charts
    initializeProgressCharts();
}

function loadCertificatesData() {
    console.log('Certificates data loaded');
    
    // Add certificate interactions
    initializeCertificateCards();
}

function loadProfileData() {
    console.log('Profile data loaded');
    
    // Initialize profile form
    initializeProfileForm();
}

function loadAnnouncementsData() {
    console.log('Announcements data loaded');
    
    // Add announcement interactions
    initializeAnnouncementItems();
}

function loadSupportData() {
    console.log('Support data loaded');
    
    // Initialize support features
    initializeSupportFeatures();
}

// Course Management
function initializeCourseCards() {
    const courseCards = document.querySelectorAll('.course-card');
    
    courseCards.forEach(card => {
        const continueBtn = card.querySelector('.btn-primary');
        const detailsBtn = card.querySelector('.btn-secondary');
        
        if (continueBtn) {
            continueBtn.addEventListener('click', function() {
                const courseId = this.closest('.course-card').dataset.courseId;
                startCourse(courseId);
            });
        }
        
        if (detailsBtn) {
            detailsBtn.addEventListener('click', function() {
                const courseId = this.closest('.course-card').dataset.courseId;
                viewCourseDetails(courseId);
            });
        }
    });
}

function startCourse(courseId) {
    console.log('Starting course:', courseId);
    
    // Navigate to lessons section
    navigateToSection('lessons');
    
    // Show success message
    showNotification('Course started successfully!', 'success');
}

function viewCourseDetails(courseId) {
    console.log('Viewing course details:', courseId);
    
    // Open course detail modal
    openCourseModal(courseId);
}

// Assessment Management
function initializeAssessmentCards() {
    const assessmentCards = document.querySelectorAll('.assessment-card');
    
    assessmentCards.forEach(card => {
        const startBtn = card.querySelector('.btn-primary');
        const reviewBtn = card.querySelector('.btn-secondary');
        
        if (startBtn) {
            startBtn.addEventListener('click', function() {
                const assessmentName = this.closest('.assessment-card').querySelector('h3').textContent;
                startAssessment(assessmentName);
            });
        }
        
        if (reviewBtn) {
            reviewBtn.addEventListener('click', function() {
                const assessmentName = this.closest('.assessment-card').querySelector('h3').textContent;
                reviewAssessment(assessmentName);
            });
        }
    });
}

function startAssessment(assessmentName) {
    console.log('Starting assessment:', assessmentName);
    
    // Show confirmation dialog
    if (confirm(`Are you ready to start "${assessmentName}"?`)) {
        // Navigate to assessment page or open assessment modal
        showNotification('Assessment started!', 'success');
    }
}

function reviewAssessment(assessmentName) {
    console.log('Reviewing assessment:', assessmentName);
    
    // Show assessment results
    showNotification('Opening assessment results...', 'info');
}

// Exercise Management
function initializeExerciseCards() {
    const exerciseCards = document.querySelectorAll('.exercise-card');
    
    exerciseCards.forEach(card => {
        const startBtn = card.querySelector('.btn-primary');
        
        if (startBtn) {
            startBtn.addEventListener('click', function() {
                const exerciseName = this.closest('.exercise-card').querySelector('h3').textContent;
                startExercise(exerciseName);
            });
        }
    });
}

function startExercise(exerciseName) {
    console.log('Starting exercise:', exerciseName);
    
    // Show exercise instructions
    showNotification(`Starting "${exerciseName}" exercise...`, 'info');
}

// Progress Management
function initializeProgressCharts() {
    // Progress charts are already initialized in initializeCharts()
    console.log('Progress charts initialized');
    
    // Update progress data if needed
    updateProgressData();
}

function updateProgressData() {
    // Simulate updating progress data
    console.log('Updating progress data...');
}

// Certificate Management
function initializeCertificateCards() {
    const certificateCards = document.querySelectorAll('.certificate-card');
    
    certificateCards.forEach(card => {
        const downloadBtn = card.querySelector('.btn-primary');
        const shareBtn = card.querySelector('.btn-secondary');
        
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                const certificateName = this.closest('.certificate-card').querySelector('h3').textContent;
                downloadCertificate(certificateName);
            });
        }
        
        if (shareBtn) {
            shareBtn.addEventListener('click', function() {
                const certificateName = this.closest('.certificate-card').querySelector('h3').textContent;
                shareCertificate(certificateName);
            });
        }
    });
}

function downloadCertificate(certificateName) {
    console.log('Downloading certificate:', certificateName);
    
    // Simulate download
    showNotification('Certificate download started!', 'success');
}

function shareCertificate(certificateName) {
    console.log('Sharing certificate:', certificateName);
    
    // Show share options
    showNotification('Share options opened!', 'info');
}

// Profile Management
function initializeProfileForm() {
    const profileForm = document.querySelector('.profile-form');
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveProfileChanges();
        });
    }
    
    // Avatar edit functionality
    const avatarEdit = document.querySelector('.avatar-edit');
    if (avatarEdit) {
        avatarEdit.addEventListener('click', function() {
            // Trigger file input for avatar upload
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.click();
            
            fileInput.addEventListener('change', function() {
                if (this.files[0]) {
                    uploadAvatar(this.files[0]);
                }
            });
        });
    }
}

function saveProfileChanges() {
    console.log('Saving profile changes...');
    
    // Simulate saving
    showNotification('Profile updated successfully!', 'success');
}

function uploadAvatar(file) {
    console.log('Uploading avatar:', file.name);
    
    // Simulate upload
    showNotification('Avatar uploaded successfully!', 'success');
}

// Announcement Management
function initializeAnnouncementItems() {
    const announcementItems = document.querySelectorAll('.announcement-item');
    
    announcementItems.forEach(item => {
        item.addEventListener('click', function() {
            const announcementTitle = this.querySelector('h3').textContent;
            viewAnnouncement(announcementTitle);
        });
    });
}

function viewAnnouncement(announcementTitle) {
    console.log('Viewing announcement:', announcementTitle);
    
    // Show announcement details
    showNotification(`Opening "${announcementTitle}"...`, 'info');
}

// Support Management
function initializeSupportFeatures() {
    const supportCards = document.querySelectorAll('.support-card');
    
    supportCards.forEach(card => {
        const actionBtn = card.querySelector('.btn-primary');
        
        if (actionBtn) {
            actionBtn.addEventListener('click', function() {
                const supportType = this.closest('.support-card').querySelector('h3').textContent;
                handleSupportAction(supportType);
            });
        }
    });
}

function handleSupportAction(supportType) {
    switch(supportType) {
        case 'Knowledge Base':
            openKnowledgeBase();
            break;
        case 'Live Chat':
            startLiveChat();
            break;
        case 'Email Support':
            openEmailSupport();
            break;
        case 'Phone Support':
            openPhoneSupport();
            break;
        default:
            console.log('Unknown support type:', supportType);
    }
}

function openKnowledgeBase() {
    console.log('Opening knowledge base...');
    showNotification('Knowledge base opened!', 'info');
}

function startLiveChat() {
    console.log('Starting live chat...');
    showNotification('Live chat initiated!', 'info');
}

function openEmailSupport() {
    console.log('Opening email support...');
    showNotification('Email support opened!', 'info');
}

function openPhoneSupport() {
    console.log('Opening phone support...');
    showNotification('Phone support opened!', 'info');
}

// Dashboard Stats Update
function updateDashboardStats() {
    // Simulate updating dashboard statistics
    console.log('Updating dashboard stats...');
    
    // Update stat cards with real-time data if available
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        // Add animation or update values
        card.style.opacity = '1';
    });
}

// Course Modal Management
function openCourseModal(courseId) {
    const modal = document.getElementById('courseDetailModal');
    if (modal) {
        modal.classList.add('active');
        
        // Load course-specific data
        loadCourseModalData(courseId);
    }
}

function closeCourseModal() {
    const modal = document.getElementById('courseDetailModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function loadCourseModalData(courseId) {
    console.log('Loading course modal data for:', courseId);
    
    // Initialize tab functionality
    initializeCourseTabs();
}

function initializeCourseTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and panes
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding pane
            this.classList.add('active');
            const targetPane = document.getElementById(targetTab);
            if (targetPane) {
                targetPane.classList.add('active');
            }
        });
    });
}

// Notification System
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            </div>
        <button class="notification-close">&times;</button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-left: 4px solid ${getNotificationColor(type)};
        padding: 16px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        transform: translateX(400px);
        transition: transform 0.3s ease;
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        hideNotification(notification);
    }, 5000);
    
    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        hideNotification(notification);
    });
}

function getNotificationIcon(type) {
    switch(type) {
        case 'success': return 'check-circle';
        case 'error': return 'exclamation-circle';
        case 'warning': return 'exclamation-triangle';
        default: return 'info-circle';
    }
}

function getNotificationColor(type) {
    switch(type) {
        case 'success': return '#4CAF50';
        case 'error': return '#f44336';
        case 'warning': return '#FF9800';
        default: return '#2196F3';
    }
}

function hideNotification(notification) {
    notification.style.transform = 'translateX(400px)';
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Utility Functions
function formatDate(date) {
    return new Date(date).toLocaleDateString();
}

function formatTime(date) {
    return new Date(date).toLocaleTimeString();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export functions for global access
window.openCourseModal = openCourseModal;
window.closeCourseModal = closeCourseModal;
window.showNotification = showNotification;
window.startCourse = startCourse;
window.viewCourseDetails = viewCourseDetails;
