<?php
session_start();
require_once '../include/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Administrator') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    } else {
        header('Location: ../login.php');
    }
    exit();
}

$db = new Database();
$isEdit = false;
$course = null;

// Check if editing existing course
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $isEdit = true;
    $id = (int)$_GET['id'];
    $result = $db->setQuery("SELECT * FROM courses WHERE id = $id");
    $course = $result->fetch_object();
    if (!$course) {
        header('Location: index.php');
        exit();
    }
}

// Handle form submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $data = $_POST;
        
        // Validate required fields
        $required = ['courseName', 'courseCode', 'courseDescription', 'courseDuration', 'courseLevel'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field " . str_replace('course', '', $field) . " is required");
            }
        }
        
        $name = $db->conn->real_escape_string($data['courseName']);
        $code = $db->conn->real_escape_string($data['courseCode']);
        $description = $db->conn->real_escape_string($data['courseDescription']);
        $duration = (int)$data['courseDuration'];
        $level = $db->conn->real_escape_string($data['courseLevel']);
        $categoryId = !empty($data['courseCategory']) ? (int)$data['courseCategory'] : null;
        
        // Handle status conversion
        $statusValue = 0; // Default to inactive
        if (isset($data['courseStatus'])) {
            if ($data['courseStatus'] === 'active') {
                $statusValue = 1;
            } elseif ($data['courseStatus'] === 'draft') {
                $statusValue = 0;
            } else {
                $statusValue = (int)$data['courseStatus'];
            }
        }
        
        if ($isEdit) {
            $id = (int)$_GET['id'];
            $query = "UPDATE courses SET 
                     name = '$name', 
                     code = '$code', 
                     description = '$description', 
                     duration_weeks = $duration, 
                     level = '$level', 
                     status = $statusValue,
                     category_id = " . ($categoryId ? $categoryId : 'NULL') . ",
                     updated_at = CURRENT_TIMESTAMP 
                     WHERE id = $id";
            $db->setQuery($query);
            echo json_encode(['success' => true, 'message' => 'Course updated successfully', 'id' => $id]);
        } else {
            $query = "INSERT INTO courses (name, code, description, duration_weeks, level, status, category_id, created_at, updated_at) 
                     VALUES ('$name', '$code', '$description', $duration, '$level', 
                     $statusValue, " . 
                     ($categoryId ? $categoryId : 'NULL') . ", 
                     CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
            $db->setQuery($query);
            $courseId = $db->conn->insert_id;
            echo json_encode(['success' => true, 'message' => 'Course created successfully', 'id' => $courseId]);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Course' : 'Create Course' ?> - LMS Admin</title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.4 for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Animate.css for smooth animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Custom admin styles -->
    <link href="assets/css/admin-styles.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --light-bg: #f8f9fa;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            min-height: 100vh;
        }

        .card {
            max-width: 900px;
            margin: 2rem auto;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background: white;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }

        .card-header h2 {
            margin: 0;
            font-weight: 600;
        }
        
        .form-section {
            margin-bottom: 1.5rem;
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--accent-color);
        }
        
        .section-title {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--accent-color);
        }
        
        .form-floating > label {
            padding: 0.8rem 0.75rem;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .form-control, .form-select {
            padding: 0.6rem 0.8rem;
            height: calc(2.5rem + 2px);
            border: 1px solid #e1e5ee;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 201, 240, 0.25);
        }
        
        textarea.form-control {
            min-height: 120px !important;
            resize: vertical;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-outline-secondary {
            color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-outline-secondary:hover {
            background: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label,
        .form-floating > .form-select ~ label {
            opacity: 0.8;
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
        }
        .form-control:focus, .form-select:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        .form-text {
            font-size: 0.8rem;
        }
        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
            border-radius: 0.75rem;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            color: white;
            border-bottom: none;
            padding: 1.5rem 2rem;
        }
        .card-title {
            font-weight: 600;
            margin: 0;
        }
        .btn-primary {
            background-color: #4361ee;
            border-color: #4361ee;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #3a0ca3;
            border-color: #3a0ca3;
            transform: translateY(-1px);
        }
        .btn-outline-secondary {
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            transform: translateY(-1px);
        }
        .form-section {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            color: #4361ee;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .character-count {
            font-size: 0.75rem;
            text-align: right;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        .is-invalid {
            border-color: #dc3545 !important;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        .is-valid {
            border-color: #198754 !important;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
        .was-validated .form-control:invalid ~ .invalid-feedback,
        .was-validated .form-control:invalid ~ .invalid-tooltip,
        .form-control.is-invalid ~ .invalid-feedback,
        .form-control.is-invalid ~ .invalid-tooltip {
            display: block;
        }
        .btn-loader {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 0.2em solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 0.5rem;
            vertical-align: middle;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .btn-loading .btn-loader {
            opacity: 1;
        }
        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Header -->
        <header class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-graduation-cap fa-2x me-3" style="color: var(--primary-color);"></i>
                <h1 class="h4 mb-0">Learning Management System</h1>
            </div>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted">
                    <i class="fas fa-user-circle me-1"></i> 
                    <?= htmlspecialchars($_SESSION['USERNAME'] ?? 'Admin') ?>
                </span>
                <a href="../logout.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-sign-out-alt me-1"></i> Sign Out
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main>
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">
                        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?> me-2"></i>
                        <?= $isEdit ? 'Edit Course' : 'Create New Course' ?>
                    </h2>
                </div>
                            <div class="card-body">
                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>
                                <form id="courseForm" class="needs-validation" novalidate>
                                    <!-- Basic Information Section -->
                                    <div class="form-section">
                                        <h5 class="section-title">
                                            <i class="fas fa-info-circle"></i>
                                            Basic Information
                                        </h5>
                                        
                                        <div class="row g-4">
                                            <!-- Course Name -->
                                            <div class="col-md-6">
                                                <div class="form-floating mb-4">
                                                    <input type="text" 
                                                           class="form-control" 
                                                           id="courseName" 
                                                           name="courseName" 
                                                           placeholder="Enter course name" 
                                                           value="<?= htmlspecialchars($course->name ?? '') ?>" 
                                                           required
                                                           minlength="5"
                                                           maxlength="100"
                                                           autofocus>
                                                    <label for="courseName">
                                                        <i class="fas fa-book me-2"></i>Course Name
                                                    </label>
                                                    <div class="invalid-feedback">
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        Please provide a valid course name (5-100 characters).
                                                    </div>
                                                    <div class="character-count">
                                                        <span id="courseNameCount">0</span>/100 characters
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Course Code -->
                                            <div class="col-md-6">
                                                <div class="form-floating mb-4">
                                                    <input type="text" 
                                                           class="form-control text-uppercase" 
                                                           id="courseCode" 
                                                           name="courseCode" 
                                                           placeholder="Enter course code" 
                                                           value="<?= htmlspecialchars($course->code ?? '') ?>" 
                                                           required
                                                           pattern="[A-Z0-9]{3,10}"
                                                           title="3-10 uppercase letters or numbers">
                                                    <label for="courseCode">
                                                        <i class="fas fa-hashtag me-2"></i>Course Code
                                                    </label>
                                                    <div class="invalid-feedback">
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        Please provide a valid course code (3-10 uppercase letters/numbers).
                                                    </div>
                                                    <div class="form-text">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Example: CS101, MATH202, etc.
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Course Description -->
                                            <div class="col-12">
                                                <div class="form-floating mb-3">
                                                    <textarea class="form-control" 
                                                              id="courseDescription" 
                                                              name="courseDescription" 
                                                              placeholder="Enter course description" 
                                                              style="height: 120px; min-height: 120px;" 
                                                              required
                                                              minlength="20"
                                                              maxlength="1000"><?= htmlspecialchars($course->description ?? '') ?></textarea>
                                                    <label for="courseDescription">
                                                        <i class="fas fa-align-left me-2"></i>Course Description
                                                    </label>
                                                    <div class="invalid-feedback">
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        Please provide a detailed course description (20-1000 characters).
                                                    </div>
                                                    <div class="character-count">
                                                        <span id="descriptionCount">0</span>/1000 characters
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Course Details Section -->
                                    <div class="form-section">
                                        <h5 class="section-title">
                                            <i class="fas fa-cog"></i>
                                            Course Details
                                        </h5>
                                        
                                        <div class="row g-4">
                                            <!-- Course Duration -->
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <div class="input-group">
                                                        <input type="number" 
                                                               class="form-control" 
                                                               id="courseDuration" 
                                                               name="courseDuration" 
                                                               placeholder="Enter duration in weeks" 
                                                               min="1" 
                                                               max="52" 
                                                               step="1"
                                                               value="<?= $course->duration ?? '12' ?>" 
                                                               required>
                                                        <span class="input-group-text">weeks</span>
                                                        <label for="courseDuration">
                                                            <i class="fas fa-calendar-week me-2"></i>Duration
                                                        </label>
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        Please enter a duration between 1 and 52 weeks.
                                                    </div>
                                                    <div class="form-text">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Typical course durations are 6-12 weeks.
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Course Level -->
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select" 
                                                            id="courseLevel" 
                                                            name="courseLevel" 
                                                            required>
                                                        <option value="" disabled>Select level</option>
                                                        <option value="Beginner" <?= isset($course) && $course->level === 'Beginner' ? 'selected' : '' ?>>
                                                            <i class="fas fa-baby me-2"></i>Beginner
                                                        </option>
                                                        <option value="Intermediate" <?= isset($course) && $course->level === 'Intermediate' ? 'selected' : '' ?>>
                                                            <i class="fas fa-user-graduate me-2"></i>Intermediate
                                                        </option>
                                                        <option value="Advanced" <?= isset($course) && $course->level === 'Advanced' ? 'selected' : '' ?>>
                                                            <i class="fas fa-user-tie me-2"></i>Advanced
                                                        </option>
                                                        <option value="Expert" <?= isset($course) && $course->level === 'Expert' ? 'selected' : '' ?>>
                                                            <i class="fas fa-award me-2"></i>Expert
                                                        </option>
                                                    </select>
                                                    <label for="courseLevel">
                                                        <i class="fas fa-chart-line me-2"></i>Difficulty Level
                                                    </label>
                                                    <div class="invalid-feedback">
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        Please select a course difficulty level.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Course Status -->
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select" 
                                                            id="courseStatus" 
                                                            name="courseStatus"
                                                            required>
                                                        <option value="draft" <?= isset($course) && $course->status === 'draft' ? 'selected' : '' ?>>Draft</option>
                                                        <option value="active" <?= !isset($course) || $course->status === 'active' ? 'selected' : '' ?>>Active</option>
                                                        <option value="archived" <?= isset($course) && $course->status === 'archived' ? 'selected' : '' ?>>Archived</option>
                                                    </select>
                                                    <label for="courseStatus">
                                                        <i class="fas fa-toggle-on me-2"></i>Status
                                                    </label>
                                                    <div class="form-text">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Set to 'Draft' to save without publishing.
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Course Category -->
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select" 
                                                            id="courseCategory" 
                                                            name="courseCategory">
                                                        <option value="" selected>Select category</option>
                                                        <option value="programming" <?= isset($course) && $course->category === 'programming' ? 'selected' : '' ?>>Programming</option>
                                                        <option value="design" <?= isset($course) && $course->category === 'design' ? 'selected' : '' ?>>Design</option>
                                                        <option value="business" <?= isset($course) && $course->category === 'business' ? 'selected' : '' ?>>Business</option>
                                                        <option value="marketing" <?= isset($course) && $course->category === 'marketing' ? 'selected' : '' ?>>Marketing</option>
                                                        <option value="language" <?= isset($course) && $course->category === 'language' ? 'selected' : '' ?>>Language</option>
                                                        <option value="other" <?= isset($course) && $course->category === 'other' ? 'selected' : '' ?>>Other</option>
                                                    </select>
                                                    <label for="courseCategory">
                                                        <i class="fas fa-tags me-2"></i>Category
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
                                        <div>
                                            <a href="courses.php" class="btn btn-outline-secondary px-4">
                                                <i class="fas fa-arrow-left me-2"></i> Back to Courses
                                            </a>
                                        </div>
                                        <div class="d-flex gap-3">
                                            <button type="reset" class="btn btn-outline-secondary px-4">
                                                <i class="fas fa-undo me-2"></i> Reset
                                            </button>
                                            <button type="submit" class="btn btn-primary px-4" id="submitButton">
                                                <i class="fas fa-<?= $isEdit ? 'save' : 'plus' ?> me-2"></i>
                                                <span class="btn-text"><?= $isEdit ? 'Update Course' : 'Create Course' ?></span>
                                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <!-- JavaScript Libraries -->
                                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                <script src="https://cdn.jsdelivr.net/npm/autosize@5.0.2/dist/autosize.min.js"></script>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        // DOM Elements
                                        const form = document.getElementById('courseForm');
                                        const submitBtn = document.getElementById('submitButton');
                                        const submitText = submitBtn.querySelector('.btn-text');
                                        const spinner = submitBtn.querySelector('.spinner-border');
                                        const descriptionTextarea = document.getElementById('courseDescription');
                                        const courseNameInput = document.getElementById('courseName');
                                        const courseCodeInput = document.getElementById('courseCode');
                                        let isSubmitting = false;
                                        
                                        // Initialize autosize for textareas
                                        if (typeof autosize !== 'undefined') {
                                            autosize(descriptionTextarea);
                                        }
                                        
                                        // Character counters
                                        function updateCharacterCount(element, counterId) {
                                            const count = element.value.length;
                                            const counter = document.getElementById(counterId);
                                            if (counter) {
                                                counter.textContent = count;
                                                
                                                // Update counter color based on remaining characters
                                                const maxLength = parseInt(element.getAttribute('maxlength')) || 0;
                                                if (maxLength > 0) {
                                                    const remaining = maxLength - count;
                                                    if (remaining < 20) {
                                                        counter.style.color = remaining < 10 ? '#dc3545' : '#ffc107';
                                                    } else {
                                                        counter.style.color = '#6c757d';
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Initialize character counters
                                        if (courseNameInput) {
                                            courseNameInput.addEventListener('input', () => {
                                                updateCharacterCount(courseNameInput, 'courseNameCount');
                                            });
                                            // Trigger once on load
                                            updateCharacterCount(courseNameInput, 'courseNameCount');
                                        }
                                        
                                        if (descriptionTextarea) {
                                            descriptionTextarea.addEventListener('input', () => {
                                                updateCharacterCount(descriptionTextarea, 'descriptionCount');
                                            });
                                            // Trigger once on load
                                            updateCharacterCount(descriptionTextarea, 'descriptionCount');
                                        }
                                        
                                        // Auto-format course code to uppercase
                                        if (courseCodeInput) {
                                            courseCodeInput.addEventListener('input', function() {
                                                this.value = this.value.toUpperCase();
                                            });
                                        }

                                        // Add real-time validation
                                        form.querySelectorAll('input, select, textarea').forEach(input => {
                                            input.addEventListener('input', function() {
                                                if (input.checkValidity()) {
                                                    input.classList.remove('is-invalid');
                                                    input.classList.add('is-valid');
                                                } else {
                                                    input.classList.remove('is-valid');
                                                    input.classList.add('is-invalid');
                                                }
                                            });
                                        });

                                        // Initialize form validation
                                        form.addEventListener('submit', async function(event) {
                                            event.preventDefault();
                                            event.stopPropagation();

                                            if (isSubmitting) return;
                                            
                                            // Check form validity
                                            if (!form.checkValidity()) {
                                                form.classList.add('was-validated');
                                                
                                                // Find first invalid field and scroll to it
                                                const firstInvalid = form.querySelector(':invalid');
                                                if (firstInvalid) {
                                                    firstInvalid.scrollIntoView({ 
                                                        behavior: 'smooth',
                                                        block: 'center'
                                                    });
                                                    
                                                    // Add shake animation to highlight the error
                                                    firstInvalid.classList.add('animate__animated', 'animate__headShake');
                                                    setTimeout(() => {
                                                        firstInvalid.classList.remove('animate__animated', 'animate__headShake');
                                                    }, 1000);
                                                    
                                                    firstInvalid.focus();
                                                }
                                                
                                                return;
                                            }

                                            try {
                                                // Update UI for submission
                                                isSubmitting = true;
                                                submitBtn.disabled = true;
                                                submitBtn.classList.add('btn-loading');
                                                spinner.classList.remove('d-none');
                                                submitText.textContent = 'Processing...';

                                                // Prepare form data
                                                const formData = new FormData(form);
                                                const data = {};
                                                
                                                // Convert FormData to object
                                                formData.forEach((value, key) => {
                                                    data[key] = value;
                                                });

                                                // Add additional data if needed
                                                data['status'] = document.getElementById('courseStatus').value;
                                                
                                                // Add category if selected
                                                const category = document.getElementById('courseCategory').value;
                                                if (category) {
                                                    data['category'] = category;
                                                }

                                                // Send data to server
                                                const response = await fetch(`api/courses.php?action=<?= $isEdit ? 'update' : 'create' ?><?= $isEdit ? '&id=' . htmlspecialchars($_GET['id']) : '' ?>`, {
                                                    method: 'POST',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                    },
                                                    body: JSON.stringify(data)
                                                });

                                                const result = await response.json();

                                                if (result.success) {
                                                    // Show success animation
                                                    submitBtn.innerHTML = `
                                                        <i class="fas fa-check me-2"></i>
                                                        <span class="btn-text">${submitText.textContent.replace('Processing...', 'Success!')}</span>
                                                    `;
                                                    submitBtn.classList.add('btn-success');
                                                    
                                                    // Show success message
                                                    await Swal.fire({
                                                        title: 'Success!',
                                                        text: '<?= $isEdit ? 'Course updated successfully!' : 'Course created successfully!' ?>',
                                                        icon: 'success',
                                                        showConfirmButton: false,
                                                        timer: 1500,
                                                        timerProgressBar: true,
                                                        didOpen: (toast) => {
                                                            toast.addEventListener('mouseenter', Swal.stopTimer);
                                                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                                                        }
                                                    });

                                                    // Redirect to courses list
                                                    window.location.href = 'courses.php';
                                                } else {
                                                    throw new Error(result.message || 'An error occurred. Please try again.');
                                                }
                                            } catch (error) {
                                                // Show error message with more details
                                                let errorMessage = error.message || 'An error occurred. Please try again.';
                                                
                                                // Handle specific error cases
                                                if (errorMessage.includes('duplicate') || errorMessage.includes('already exists')) {
                                                    if (errorMessage.includes('code')) {
                                                        errorMessage = 'This course code is already in use. Please choose a different code.';
                                                        if (courseCodeInput) {
                                                            courseCodeInput.classList.add('is-invalid');
                                                            courseCodeInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                                        }
                                                    } else if (errorMessage.includes('name')) {
                                                        errorMessage = 'A course with this name already exists. Please choose a different name.';
                                                        if (courseNameInput) {
                                                            courseNameInput.classList.add('is-invalid');
                                                            courseNameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                                        }
                                                    }
                                                }
                                                
                                                await Swal.fire({
                                                    title: 'Error',
                                                    text: errorMessage,
                                                    icon: 'error',
                                                    confirmButtonColor: '#4361ee',
                                                    confirmButtonText: 'Got it!',
                                                    customClass: {
                                                        confirmButton: 'btn btn-primary'
                                                    }
                                                });
                                            } finally {
                                                // Reset button state
                                                if (submitBtn) {
                                                    isSubmitting = false;
                                                    submitBtn.disabled = false;
                                                    submitBtn.classList.remove('btn-loading', 'btn-success');
                                                    spinner.classList.add('d-none');
                                                    submitText.textContent = '<?= $isEdit ? 'Update Course' : 'Create Course' ?>';
                                                }
                                            }
                                        });

                                        // Real-time validation with debounce
                                        const validateField = (field) => {
                                            const isValid = field.checkValidity();
                                            const feedback = field.nextElementSibling?.classList?.contains('invalid-feedback') ? field.nextElementSibling : null;
                                            
                                            // Skip validation for hidden fields
                                            if (field.offsetParent === null) return true;
                                            
                                            // Clear previous validation
                                            field.classList.remove('is-invalid', 'is-valid');
                                            
                                            // Skip empty optional fields
                                            if (!field.required && !field.value.trim()) {
                                                return true;
                                            }
                                            
                                            // Required field validation
                                            if (field.required && !field.value.trim()) {
                                                field.classList.add('is-invalid');
                                                if (feedback) feedback.style.display = 'block';
                                                return false;
                                            }
                                            
                                            // Custom validation for specific fields
                                            if (field.id === 'courseCode' && field.value) {
                                                const codeRegex = /^[A-Z0-9]{3,10}$/;
                                                if (!codeRegex.test(field.value)) {
                                                    field.setCustomValidity('Course code must be 3-10 uppercase letters or numbers');
                                                    field.classList.add('is-invalid');
                                                    if (feedback) feedback.textContent = '3-10 uppercase letters or numbers only';
                                                    return false;
                                                }
                                            }
                                            
                                            if (field.id === 'courseName' && field.value) {
                                                if (field.value.length < 5 || field.value.length > 100) {
                                                    field.setCustomValidity('Course name must be between 5 and 100 characters');
                                                    field.classList.add('is-invalid');
                                                    if (feedback) feedback.textContent = 'Must be 5-100 characters';
                                                    return false;
                                                }
                                            }
                                            
                                            if (field.id === 'courseDescription' && field.value) {
                                                if (field.value.length < 20) {
                                                    field.setCustomValidity('Description must be at least 20 characters');
                                                    field.classList.add('is-invalid');
                                                    if (feedback) feedback.textContent = 'Please provide a more detailed description (at least 20 characters)';
                                                    return false;
                                                }
                                            }
                                            
                                            if (field.id === 'courseDuration' && field.value) {
                                                const duration = parseInt(field.value);
                                                if (isNaN(duration) || duration < 1 || duration > 52) {
                                                    field.setCustomValidity('Duration must be between 1 and 52 weeks');
                                                    field.classList.add('is-invalid');
                                                    if (feedback) feedback.textContent = 'Please enter a duration between 1 and 52 weeks';
                                                    return false;
                                                }
                                            }
                                            
                                            // If we got here, the field is valid
                                            field.setCustomValidity('');
                                            field.classList.add('is-valid');
                                            if (feedback) feedback.style.display = 'none';
                                            return true;
                                        };
                                        
                                        // Add input event listeners with debounce for real-time validation
                                        let timeout;
                                        form.querySelectorAll('input, select, textarea').forEach(field => {
                                            field.addEventListener('input', (e) => {
                                                clearTimeout(timeout);
                                                timeout = setTimeout(() => {
                                                    validateField(e.target);
                                                }, 300);
                                            });
                                            
                                            field.addEventListener('change', (e) => {
                                                validateField(e.target);
                                            });
                                            
                                            // Remove validation on focus
                                            field.addEventListener('focus', (e) => {
                                                e.target.classList.remove('is-invalid', 'is-valid');
                                            });
                                        });
                                        
                                        // Initialize form validation on page load
                                        form.classList.add('was-validated');
                                        
                                        // Auto-generate course code from course name if empty
                                        if (courseNameInput && courseCodeInput && !courseCodeInput.value) {
                                            courseNameInput.addEventListener('blur', function() {
                                                if (!courseCodeInput.value && this.value) {
                                                    // Generate a code from the name (first 3 uppercase letters + random 2 digits)
                                                    const code = this.value
                                                        .replace(/[^a-zA-Z0-9]/g, '') // Remove special characters
                                                        .toUpperCase()
                                                        .substring(0, 3);
                                                    
                                                    if (code.length >= 2) {
                                                        const randomDigits = Math.floor(10 + Math.random() * 90); // 10-99
                                                        courseCodeInput.value = code + randomDigits;
                                                        validateField(courseCodeInput);
                                                    }
                                                }
                                            });
                                        }
                                    });
                                </script>
                            </body>
                        </html>


