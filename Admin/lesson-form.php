<?php
session_start();
require_once '../include/database.php';
require_once '../include/courses.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Administrator') {
    header('Location: ../login.php');
    exit();
}

$courses = new Courses();
$isEdit = false;
$lesson = null;
$allCourses = [];

// Get all courses for selection
try {
    $allCourses = $courses->getAllCourses();
} catch (Exception $e) {
    $courseError = 'Failed to load courses: ' . $e->getMessage();
}

// Check if editing existing lesson
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $isEdit = true;
    $lesson = $courses->getLessonById($_GET['id']);
    if (!$lesson) {
        header('Location: index.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lessonData = [
        'course_id' => $_POST['lessonCourse'] ?? '',
        'title' => $_POST['lessonTitle'] ?? '',
        'description' => $_POST['lessonDescription'] ?? '',
        'content' => $_POST['lessonContent'] ?? '',
        'video_url' => $_POST['lessonVideoUrl'] ?? null,
        'order_number' => intval($_POST['lessonOrder'] ?? 1),
        'duration_minutes' => intval($_POST['lessonDuration'] ?? 45)
    ];
    
    try {
        if ($isEdit) {
            $result = $courses->updateLesson($_GET['id'], $lessonData);
            $message = $result ? 'Lesson updated successfully!' : 'Failed to update lesson';
        } else {
            $result = $courses->createLesson($lessonData);
            $message = $result ? 'Lesson created successfully!' : 'Failed to create lesson';
        }
        
        if ($result) {
            header('Location: index.php?success=' . urlencode($message));
            exit();
        } else {
            $error = 'Failed to save lesson. Please try again.';
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
    <title><?= $isEdit ? 'Edit Lesson' : 'Create Lesson' ?> - LMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin-styles.css" rel="stylesheet">
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
                                    <?= $isEdit ? 'Edit Lesson' : 'Create New Lesson' ?>
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

                                <form id="lessonForm" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="lessonCourse" class="form-label">
                                            Course <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" id="lessonCourse" name="lessonCourse" required <?= $isEdit ? 'disabled' : '' ?>>
                                            <option value="">Select Course</option>
                                            <?php if (!empty($allCourses)): ?>
                                                <?php foreach ($allCourses as $course): ?>
                                                    <option value="<?= $course->id ?>" 
                                                            <?= ($lesson->course_id ?? '') == $course->id ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($course->name) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <?php if ($isEdit): ?>
                                            <input type="hidden" name="lessonCourse" value="<?= $lesson->course_id ?>">
                                        <?php endif; ?>
                                        <div class="invalid-feedback">
                                            Please select a course.
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="lessonTitle" class="form-label">
                                            Lesson Title <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="lessonTitle" 
                                               name="lessonTitle" 
                                               value="<?= htmlspecialchars($lesson->title ?? '') ?>" 
                                               required>
                                        <div class="invalid-feedback">
                                            Please provide a lesson title.
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="lessonDescription" class="form-label">Description</label>
                                        <textarea class="form-control" 
                                                  id="lessonDescription" 
                                                  name="lessonDescription" 
                                                  rows="3"><?= htmlspecialchars($lesson->description ?? '') ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="lessonContent" class="form-label">
                                            Content <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" 
                                                  id="lessonContent" 
                                                  name="lessonContent" 
                                                  rows="10" 
                                                  required><?= htmlspecialchars($lesson->content ?? '') ?></textarea>
                                        <div class="invalid-feedback">
                                            Please provide lesson content.
                                        </div>
                                        <small class="form-text text-muted">You can use HTML tags for formatting.</small>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="lessonVideoUrl" class="form-label">Video URL</label>
                                                <input type="url" 
                                                       class="form-control" 
                                                       id="lessonVideoUrl" 
                                                       name="lessonVideoUrl" 
                                                       value="<?= htmlspecialchars($lesson->video_url ?? '') ?>" 
                                                       placeholder="https://...">
                                                <small class="form-text text-muted">YouTube, Vimeo, or direct video link</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="lessonOrder" class="form-label">Order Number</label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="lessonOrder" 
                                                       name="lessonOrder" 
                                                       min="1" 
                                                       value="<?= $lesson->order_number ?? 1 ?>">
                                                <small class="form-text text-muted">Determines the sequence of lessons</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="lessonDuration" class="form-label">Duration (minutes)</label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="lessonDuration" 
                                               name="lessonDuration" 
                                               min="15" 
                                               value="<?= $lesson->duration_minutes ?? 45 ?>">
                                        <small class="form-text text-muted">Estimated time to complete this lesson</small>
                                    </div>

                                    <div class="form-actions">
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-<?= $isEdit ? 'save' : 'plus' ?>"></i>
                                            <?= $isEdit ? 'Update Lesson' : 'Create Lesson' ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form.needs-validation');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            let isSubmitting = false;

            // Initialize form validation
            form.addEventListener('submit', async function(event) {
                event.preventDefault();
                event.stopPropagation();

                if (isSubmitting) return;
                
                // Check form validity
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return;
                }

                // Disable submit button and show loading state
                isSubmitting = true;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

                try {
                    const formData = new FormData(form);
                    const data = {};
                    formData.forEach((value, key) => data[key] = value);

                    // Convert numeric fields to integers
                    data.lessonOrder = parseInt(data.lessonOrder) || 1;
                    data.lessonDuration = parseInt(data.lessonDuration) || 45;

                    <?php if ($isEdit): ?>
                        const response = await fetch('api/lessons.php?id=<?= $_GET['id'] ?>', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(data)
                        });
                    <?php else: ?>
                        const response = await fetch('api/lessons.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(data)
                        });
                    <?php endif; ?>

                    const result = await response.json();

                    if (result.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: result.message || 'Operation completed successfully',
                            confirmButtonText: 'OK',
                            timer: 2000,
                            timerProgressBar: true
                        });
                        window.location.href = 'index.php';
                    } else {
                        throw new Error(result.message || 'An error occurred');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'An error occurred while processing your request',
                        confirmButtonText: 'OK'
                    });
                } finally {
                    // Re-enable submit button
                    isSubmitting = false;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });

            // Real-time validation
            const validateField = (field) => {
                if (field.required && !field.value.trim()) {
                    field.classList.add('is-invalid');
                    return false;
                }

                // Custom validation for specific fields
                if (field.id === 'lessonVideoUrl' && field.value) {
                    try {
                        new URL(field.value); // Will throw if invalid URL
                    } catch (_) {
                        field.classList.add('is-invalid');
                        field.nextElementSibling.textContent = 'Please enter a valid URL';
                        return false;
                    }
                }

                // Validate numeric fields
                if ((field.id === 'lessonOrder' || field.id === 'lessonDuration') && field.value) {
                    if (isNaN(field.value) || field.value < 1) {
                        field.classList.add('is-invalid');
                        field.nextElementSibling.textContent = 'Please enter a valid number greater than 0';
                        return false;
                    }
                }

                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
                return true;
            };

            // Add input event listeners for real-time validation
            form.querySelectorAll('input, select, textarea').forEach(field => {
                field.addEventListener('input', () => validateField(field));
                field.addEventListener('blur', () => validateField(field));
            });

            // Initialize TinyMCE for lesson content if available
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: '#lessonContent',
                    height: 300,
                    menubar: false,
                    plugins: [
                        'advlist autolink lists link image charmap print preview anchor',
                        'searchreplace visualblocks code fullscreen',
                        'insertdatetime media table paste code help wordcount'
                    ],
                    toolbar: 'undo redo | formatselect | ' +
                             'bold italic backcolor | alignleft aligncenter ' +
                             'alignright alignjustify | bullist numlist outdent indent | ' +
                             'removeformat | help',
                    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
                    setup: function(editor) {
                        editor.on('change', function() {
                            editor.save();
                            validateField(document.getElementById('lessonContent'));
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>




