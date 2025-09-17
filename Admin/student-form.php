<?php
session_start();
require_once '../include/database.php';
require_once '../include/students.php';
require_once '../include/courses.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Administrator') {
    header('Location: ../login.php');
    exit();
}

$students = new Students();
$courses = new Courses();
$isEdit = false;
$student = null;
$allCourses = [];

// Get all courses for enrollment
try {
    $allCourses = $courses->getAllCourses();
} catch (Exception $e) {
    $courseError = 'Failed to load courses: ' . $e->getMessage();
}

// Check if editing existing student
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $isEdit = true;
    $student = $students->getStudentById($_GET['id']);
    if (!$student) {
        header('Location: index.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentData = [
        'first_name' => $_POST['studentFirstName'] ?? '',
        'last_name' => $_POST['studentLastName'] ?? '',
        'email' => $_POST['studentEmail'] ?? '',
        'username' => $_POST['studentUsername'] ?? '',
        'password' => $_POST['studentPassword'] ?? '',
        'phone' => $_POST['studentPhone'] ?? '',
        'address' => $_POST['studentAddress'] ?? '',
        'date_of_birth' => $_POST['studentDateOfBirth'] ?? null,
        'gender' => $_POST['studentGender'] ?? null,
        'courses' => $_POST['studentCourses'] ?? []
    ];
    
    try {
        if ($isEdit) {
            $result = $students->updateStudent($_GET['id'], $studentData);
            $message = $result ? 'Student updated successfully!' : 'Failed to update student';
        } else {
            $result = $students->createStudent($studentData);
            $message = $result ? 'Student created successfully!' : 'Failed to create student';
        }
        
        if ($result) {
            header('Location: index.php?success=' . urlencode($message));
            exit();
        } else {
            $error = 'Failed to save student. Please try again.';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit Student' : 'Create Student' ?> - LMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-forms.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-content">
                <h1><i class="fas fa-graduation-cap"></i> LMS Admin</h1>
                <div class="header-actions">
                    <a href="index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="../logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">
                                    <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?>"></i>
                                    <?= $isEdit ? 'Edit Student' : 'Create New Student' ?>
                                </h2>
                            </div>
                            <div class="card-body">
                                <?php if (isset($error)): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($courseError)): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($courseError) ?>
                                    </div>
                                <?php endif; ?>

                                <form id="studentForm" class="needs-validation" novalidate>
                                    <div class="row g-4">
                                        <!-- First Name -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="studentFirstName" 
                                                       name="studentFirstName" 
                                                       placeholder="Enter first name"
                                                       value="<?= htmlspecialchars($student->first_name ?? '') ?>" 
                                                       required>
                                                <label for="studentFirstName" class="form-label">
                                                    <i class="fas fa-user me-2"></i>First Name
                                                </label>
                                                <div class="invalid-feedback">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Please provide a first name.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Last Name -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="studentLastName" 
                                                       name="studentLastName" 
                                                       placeholder="Enter last name"
                                                       value="<?= htmlspecialchars($student->last_name ?? '') ?>" 
                                                       required>
                                                <label for="studentLastName" class="form-label">
                                                    <i class="fas fa-user me-2"></i>Last Name
                                                </label>
                                                <div class="invalid-feedback">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Please provide a last name.
                                                </div>
                                            </div>
                                        </div>

                                    <div class="row g-4">
                                        <!-- Email -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="email" 
                                                       class="form-control" 
                                                       id="studentEmail" 
                                                       name="studentEmail" 
                                                       placeholder="Enter email address"
                                                       value="<?= htmlspecialchars($student->email ?? '') ?>" 
                                                       required>
                                                <label for="studentEmail" class="form-label">
                                                    <i class="fas fa-envelope me-2"></i>Email Address
                                                </label>
                                                <div class="invalid-feedback">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Please provide a valid email address.
                                                </div>
                                                <div class="form-text text-muted small mt-1">
                                                    <i class="fas fa-info-circle me-1"></i> We'll never share your email with anyone else.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Username -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="studentUsername" 
                                                       name="studentUsername" 
                                                       placeholder="Choose a username"
                                                       value="<?= htmlspecialchars($student->username ?? '') ?>" 
                                                       pattern="^[a-zA-Z0-9_]{4,30}$"
                                                       title="Username must be 4-30 characters and can only contain letters, numbers, and underscores"
                                                       required>
                                                <label for="studentUsername" class="form-label">
                                                    <i class="fas fa-at me-2"></i>Username
                                                </label>
                                                <div class="invalid-feedback">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Username must be 4-30 characters (letters, numbers, _)
                                                </div>
                                                <div class="form-text text-muted small mt-1">
                                                    <i class="fas fa-info-circle me-1"></i> 4-30 characters, letters, numbers, and underscores only
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Password Section (Only for new students) -->
                                    <?php if (!$isEdit): ?>
                                    <div class="row g-4 mt-2">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="studentPassword" 
                                                       name="studentPassword" 
                                                       placeholder="Create a password"
                                                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                                       title="Password must be at least 8 characters with uppercase, lowercase, number and special character"
                                                       required>
                                                <label for="studentPassword" class="form-label">
                                                    <i class="fas fa-lock me-2"></i>Password
                                                </label>
                                                <div class="invalid-feedback">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Password must be at least 8 characters with uppercase, lowercase, number, and special character.
                                                </div>
                                                <div id="passwordStrength" class="small mt-2" style="display: none;">
                                                    <div class="progress mb-2" style="height: 4px;">
                                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                    </div>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-muted">Password strength</span>
                                                        <span class="fw-medium">Very Weak</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="password" 
                                                       class="form-control" 
                                                       id="confirmPassword" 
                                                       name="confirmPassword" 
                                                       placeholder="Confirm password"
                                                       required>
                                                <label for="confirmPassword" class="form-label">
                                                    <i class="fas fa-check-circle me-2"></i>Confirm Password
                                                </label>
                                                <div class="invalid-feedback">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Passwords do not match.
                                                </div>
                                                <div class="form-text text-muted small mt-1">
                                                    <i class="fas fa-info-circle me-1"></i> Use a strong, unique password
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Contact Information -->
                                    <h5 class="mt-4 mb-3 text-muted">
                                        <i class="fas fa-address-card me-2"></i>Contact Information
                                    </h5>

                                    <div class="row g-4">
                                        <!-- Phone -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="tel" 
                                                       class="form-control" 
                                                       id="studentPhone" 
                                                       name="studentPhone" 
                                                       placeholder="Enter phone number"
                                                       pattern="^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\./0-9]*$"
                                                       value="<?= htmlspecialchars($student->phone ?? '') ?>">
                                                <label for="studentPhone" class="form-label">
                                                    <i class="fas fa-phone me-2"></i>Phone Number
                                                </label>
                                                <div class="invalid-feedback">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Please enter a valid phone number
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Gender -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" 
                                                        id="studentGender" 
                                                        name="studentGender"
                                                        aria-label="Select gender">
                                                    <option value="">Select Gender</option>
                                                    <option value="male" <?= ($student->gender ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                                    <option value="female" <?= ($student->gender ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                                    <option value="other" <?= ($student->gender ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                                    <option value="prefer_not_to_say" <?= ($student->gender ?? '') === 'prefer_not_to_say' ? 'selected' : '' ?>>Prefer not to say</option>
                                                </select>
                                                <label for="studentGender" class="form-label">
                                                    <i class="fas fa-venus-mars me-2"></i>Gender
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Address -->
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <textarea class="form-control" 
                                                          id="studentAddress" 
                                                          name="studentAddress" 
                                                          placeholder="Enter address"
                                                          style="height: 100px"><?= htmlspecialchars($student->address ?? '') ?></textarea>
                                                <label for="studentAddress" class="form-label">
                                                    <i class="fas fa-map-marker-alt me-2"></i>Address
                                                </label>
                                                <div class="form-text text-muted small mt-1">
                                                    <i class="fas fa-info-circle me-1"></i> Street address, city, state, and ZIP code
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Information -->
                                    <h5 class="mt-4 mb-3 text-muted">
                                        <i class="fas fa-calendar-alt me-2"></i>Additional Information
                                    </h5>

                                    <div class="row g-4">
                                        <!-- Date of Birth -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="date" 
                                                       class="form-control" 
                                                       id="studentDateOfBirth" 
                                                       name="studentDateOfBirth"
                                                       max="<?= date('Y-m-d', strtotime('-13 years')) ?>"
                                                       value="<?= $student->date_of_birth ?? '' ?>">
                                                <label for="studentDateOfBirth" class="form-label">
                                                    <i class="fas fa-birthday-cake me-2"></i>Date of Birth
                                                </label>
                                                <div class="form-text text-muted small mt-1">
                                                    <i class="fas fa-info-circle me-1"></i> Must be at least 13 years old
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Course Enrollment -->
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" 
                                                        id="studentCourses" 
                                                        name="studentCourses[]" 
                                                        multiple
                                                        aria-label="Select courses to enroll in"
                                                        style="height: auto; min-height: 58px;">
                                                    <?php if (!empty($allCourses)): ?>
                                                        <?php foreach ($allCourses as $course): ?>
                                                            <option value="<?= $course->id ?>" 
                                                                    <?= $isEdit && in_array($course->id, array_column($student->enrolled_courses ?? [], 'id')) ? 'selected' : '' ?>
                                                                    data-subtext="<?= htmlspecialchars($course->code) ?>">
                                                                <?= htmlspecialchars($course->name) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <option disabled>No courses available</option>
                                                    <?php endif; ?>
                                                </select>
                                                <label for="studentCourses" class="form-label">
                                                    <i class="fas fa-book me-2"></i>Enroll in Courses
                                                </label>
                                                <div class="form-text text-muted small mt-1">
                                                    <i class="fas fa-info-circle me-1"></i> Hold Ctrl/Cmd to select multiple courses
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
                                        <div>
                                            <a href="students.php" class="btn btn-outline-secondary px-4">
                                                <i class="fas fa-arrow-left me-2"></i> Back to Students
                                            </a>
                                        </div>
                                        <div class="d-flex gap-3">
                                            <a href="students.php" class="btn btn-outline-secondary px-4">
                                                <i class="fas fa-times me-2"></i> Cancel
                                            </a>
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="fas fa-<?= $isEdit ? 'save' : 'plus' ?> me-2"></i>
                                                <span class="btn-text"><?= $isEdit ? 'Update Student' : 'Create Student' ?></span>
                                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status" aria-hidden="true"></span>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 for beautiful alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 for enhanced select boxes -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const form = document.getElementById('studentForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            const submitText = submitBtn.querySelector('.btn-text');
            const spinner = submitBtn.querySelector('.spinner-border');
            const passwordInput = document.getElementById('studentPassword');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const passwordStrength = document.getElementById('passwordStrength');
            const passwordToggle = document.getElementById('passwordToggle');
            const dateOfBirthInput = document.getElementById('studentDateOfBirth');
            let isSubmitting = false;

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Password visibility toggle
            if (passwordToggle && passwordInput) {
                passwordToggle.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                    
                    // If confirm password exists, sync its visibility
                    if (confirmPasswordInput) {
                        confirmPasswordInput.setAttribute('type', type);
                    }
                });
            }

            // Password strength checker
            function checkPasswordStrength(password) {
                let strength = 0;
                const feedback = [];
                
                // Length check
                if (password.length >= 8) strength++;
                else feedback.push('At least 8 characters');
                
                // Lowercase check
                if (/[a-z]/.test(password)) strength++;
                else feedback.push('Lowercase letters');
                
                // Uppercase check
                if (/[A-Z]/.test(password)) strength++;
                else feedback.push('Uppercase letters');
                
                // Number check
                if (/[0-9]/.test(password)) strength++;
                else feedback.push('Numbers');
                
                // Special character check
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                else feedback.push('Special characters');
                
                return { strength, feedback };
            }

            // Update password strength UI
            function updatePasswordStrength() {
                if (!passwordInput || !passwordStrength) return;
                
                if (!passwordInput.value) {
                    passwordStrength.style.display = 'none';
                    return;
                }
                
                const { strength, feedback } = checkPasswordStrength(passwordInput.value);
                const strengthText = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'][strength];
                const strengthClass = ['bg-danger', 'bg-danger', 'bg-warning', 'bg-info', 'bg-primary', 'bg-success'][strength];
                
                passwordStrength.innerHTML = `
                    <div class="progress mb-2" style="height: 4px;">
                        <div class="progress-bar ${strengthClass}" role="progressbar" 
                             style="width: ${(strength / 5) * 100}%" 
                             aria-valuenow="${strength}" 
                             aria-valuemin="0" 
                             aria-valuemax="5">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Password strength</span>
                        <span class="small fw-medium">${strengthText}</span>
                    </div>
                    ${feedback.length > 0 ? `
                        <div class="mt-1">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Add: ${feedback.join(', ')}
                            </small>
                        </div>` : ''
                    }
                `;
                passwordStrength.style.display = 'block';
            }

            // Form submission handler
            form.addEventListener('submit', async function(event) {
                event.preventDefault();
                event.stopPropagation();

                if (isSubmitting) return;
                
                // Check form validity
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    
                    // Scroll to first invalid field
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ 
                            behavior: 'smooth',
                            block: 'center'
                        });
                        
                        // Add shake animation
                        firstInvalid.classList.add('animate__animated', 'animate__headShake');
                        setTimeout(() => {
                            firstInvalid.classList.remove('animate__animated', 'animate__headShake');
                        }, 1000);
                    }
                    return;
                }

                try {
                    // Update UI for submission
                    isSubmitting = true;
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitText.textContent = 'Processing...';

                    // Prepare form data
                    const formData = new FormData(form);
                    const data = {};
                    
                    // Convert form data to object
                    formData.forEach((value, key) => {
                        if (key.endsWith('[]')) {
                            const cleanKey = key.replace('[]', '');
                            if (!data[cleanKey]) data[cleanKey] = [];
                            data[cleanKey].push(value);
                        } else {
                            data[key] = value;
                        }
                    });

                    // Handle course selection from select2 if available
                    if (typeof $('#studentCourses').select2 !== 'undefined') {
                        data['studentCourses'] = $('#studentCourses').val() || [];
                    }

                    // Send request to server
                    const response = await fetch(`api/students.php?action=<?= $isEdit ? 'update' : 'create' ?><?= $isEdit ? '&id=' . htmlspecialchars($_GET['id']) : '' ?>`, {
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
                            text: '<?= $isEdit ? 'Student updated successfully!' : 'Student created successfully!' ?>',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer);
                                toast.addEventListener('mouseleave', Swal.resumeTimer);
                            }
                        });

                        // Redirect to students list
                        window.location.href = 'students.php';
                    } else {
                        throw new Error(result.message || 'An error occurred. Please try again.');
                    }
                } catch (error) {
                    // Show error message with more details
                    let errorMessage = error.message || 'An error occurred. Please try again.';
                    
                    // Handle specific error cases
                    if (errorMessage.includes('duplicate') || errorMessage.includes('already exists')) {
                        if (errorMessage.includes('email')) {
                            errorMessage = 'This email is already registered. Please use a different email.';
                            const emailField = document.getElementById('studentEmail');
                            if (emailField) {
                                emailField.classList.add('is-invalid');
                                emailField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        } else if (errorMessage.includes('username')) {
                            errorMessage = 'This username is already taken. Please choose another one.';
                            const usernameField = document.getElementById('studentUsername');
                            if (usernameField) {
                                usernameField.classList.add('is-invalid');
                                usernameField.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
                        spinner.classList.add('d-none');
                        submitText.textContent = '<?= $isEdit ? 'Update Student' : 'Create Student' ?>';
                        submitBtn.classList.remove('btn-success');
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
                
                // Email validation
                if (field.type === 'email' && field.value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(field.value)) {
                        field.setCustomValidity('Please enter a valid email address');
                        field.classList.add('is-invalid');
                        if (feedback) feedback.textContent = 'Please enter a valid email address';
                        return false;
                    }
                }
                
                // Username validation
                if (field.id === 'studentUsername' && field.value) {
                    const usernameRegex = /^[a-zA-Z0-9_]{4,30}$/;
                    if (!usernameRegex.test(field.value)) {
                        field.setCustomValidity('Username must be 4-30 characters (letters, numbers, _)');
                        field.classList.add('is-invalid');
                        if (feedback) feedback.textContent = '4-30 characters, letters, numbers, and underscores only';
                        return false;
                    }
                }
                
                // Phone validation
                if (field.id === 'studentPhone' && field.value) {
                    const phoneRegex = /^[+]?[(]?[0-9]{1,4}[)]?[-\s./0-9]*$/;
                    if (!phoneRegex.test(field.value)) {
                        field.setCustomValidity('Please enter a valid phone number');
                        field.classList.add('is-invalid');
                        if (feedback) feedback.textContent = 'Please enter a valid phone number';
                        return false;
                    }
                }
                
                // Password validation
                if (field.id === 'studentPassword' && field.value) {
                    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                    if (!passwordRegex.test(field.value)) {
                        field.setCustomValidity('Password must be at least 8 characters with uppercase, lowercase, number and special character');
                        field.classList.add('is-invalid');
                        return false;
                    }
                    
                    // Update password strength
                    updatePasswordStrength();
                    
                    // Check password match if confirm password exists
                    if (confirmPasswordInput && confirmPasswordInput.value) {
                        validateField(confirmPasswordInput);
                    }
                }
                
                // Confirm password validation
                if (field.id === 'confirmPassword' && field.value && passwordInput && passwordInput.value) {
                    if (field.value !== passwordInput.value) {
                        field.setCustomValidity('Passwords do not match');
                        field.classList.add('is-invalid');
                        if (feedback) feedback.textContent = 'Passwords do not match';
                        return false;
                    }
                }
                
                // If we got here, the field is valid
                field.setCustomValidity('');
                field.classList.add('is-valid');
                if (feedback) feedback.style.display = 'none';
                return true;
            };
            
            // Date field validation
            const validateDateField = (field) => {
                if (!field.value) return true;
                
                const selectedDate = new Date(field.value);
                const minDate = new Date('1900-01-01');
                const maxDate = new Date();
                maxDate.setFullYear(maxDate.getFullYear() - 13); // 13 years ago
                
                if (selectedDate < minDate || selectedDate > maxDate) {
                    field.setCustomValidity(`Date must be between ${minDate.toLocaleDateString()} and ${maxDate.toLocaleDateString()}`);
                    field.classList.add('is-invalid');
                    return false;
                }
                
                field.setCustomValidity('');
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
                return true;
            };

            // Add input event listeners with debounce
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
            
            // Initialize Select2 for course selection
            if (typeof $.fn.select2 !== 'undefined') {
                $('#studentCourses').select2({
                    placeholder: 'Select courses to enroll',
                    allowClear: true,
                    width: '100%',
                    templateResult: formatCourse,
                    templateSelection: formatCourseSelection,
                    closeOnSelect: false
                });
                
                // Fix for Bootstrap 5 styling
                $('.select2-container--default .select2-selection--multiple').addClass('form-control');
                
                // Trigger validation when selection changes
                $('#studentCourses').on('change', function() {
                    validateField(this);
                });
            }
            
            // Format course display in select2
            function formatCourse(course) {
                if (!course.id) return course.text;
                const $course = $(
                    '<div class="d-flex justify-content-between align-items-center">' +
                    '   <span class="text-truncate">' + course.text + '</span>' +
                    '   <small class="text-muted ms-2 text-nowrap">' + $(course.element).data('subtext') + '</small>' +
                    '</div>'
                );
                return $course;
            }
            
            function formatCourseSelection(course) {
                if (!course.id) return course.text;
                return $('<span>' + course.text + '</span>');
            }
            
            // Initialize date picker for date of birth
            if (dateOfBirthInput) {
                // Set max date to 13 years ago
                const today = new Date();
                const maxDate = new Date(today.getFullYear() - 13, today.getMonth(), today.getDate());
                dateOfBirthInput.max = maxDate.toISOString().split('T')[0];
                
                // Format date for display
                if (dateOfBirthInput.value) {
                    const date = new Date(dateOfBirthInput.value);
                    dateOfBirthInput.value = date.toISOString().split('T')[0];
                }
            }
            
            // Initialize form validation on page load
            form.classList.add('was-validated');
        });
    </script>
</body>
</html>

