<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Administrator') {
    header('Location: ../login.php');
    exit();
}

require_once '../include/database.php';
require_once '../include/Topic.php';

$courseId = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';
$topicId = $_GET['topic_id'] ?? 0;

// Initialize models
$topicModel = new Topic();
$db = new Database();

// Get course details
$db->setQuery("SELECT * FROM courses WHERE id = " . $db->escape_string($courseId));
$course = $db->loadSingleResult();

if (!$course) {
    header('Location: courses.php?error=Course not found');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_topic') {
        $data = [
            'lesson_id' => $_POST['lesson_id'],
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'display_order' => $_POST['display_order'] ?? 0
        ];
        
        try {
            $topicId = $topicModel->create($data);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Topic added successfully']);
            exit();
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit();
        }
    } elseif ($action === 'edit_topic' && $topicId) {
        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'lesson_id' => $_POST['lesson_id'],
            'display_order' => $_POST['display_order'] ?? 0
        ];
        
        try {
            $topicModel->update($topicId, $data);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Topic updated successfully']);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit();
    }
}

// Handle delete topic action
if ($action === 'delete_topic' && $topicId) {
    try {
        $topicModel->delete($topicId);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Topic deleted successfully']);
        exit();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

// Get lessons for the course
$db->setQuery("SELECT * FROM lessons WHERE course_id = " . $db->escape_string($courseId) . " ORDER BY created_at, id");
$lessons = $db->loadResultList();

// Get topics for the course
$topics = [];
if (!empty($lessons)) {
    $lessonIds = array_map(function($lesson) {
        return $lesson->id;
    }, $lessons);
    
    $db->setQuery("SELECT t.*, l.title as lesson_title FROM topics t 
                   JOIN lessons l ON t.lesson_id = l.id 
                   WHERE t.lesson_id IN (" . implode(',', $lessonIds) . ") 
                   ORDER BY l.created_at, l.id, t.display_order, t.id");
    $topics = $db->loadResultList();
}

// Get the selected topic if any
$selectedTopic = $topicId ? $topicModel->getById($topicId) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Topics - <?= htmlspecialchars($course->name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin-styles.css" rel="stylesheet">
    <link href="../assets/css/admin-forms.css" rel="stylesheet">
    <style>
        .topic-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .topic-sidebar {
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            height: calc(100vh - 120px);
            overflow-y: auto;
        }
        .topic-item {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .topic-item:hover {
            background-color: #f1f3f5;
        }
        .topic-item.active {
            background-color: #e9ecef;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-list-ul me-2"></i>
                        Manage Topics - <?= htmlspecialchars($course->name) ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTopicModal">
                            <i class="fas fa-plus me-1"></i> Add Topic
                        </button>
                    </div>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_GET['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_GET['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($lessons)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No lessons found for this course.
                        <a href="manage-lessons.php?id=<?= $courseId ?>" class="alert-link">Add lessons first</a>.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Lesson</th>
                                            <th>Description</th>
                                            <th>Order</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topics as $topic): ?>
                                            <tr>
                                                <td>
                                                    <a href="manage-topic-resources.php?course_id=<?= $courseId ?>&topic_id=<?= $topic->id ?>" class="text-decoration-none">
                                                        <i class="far fa-file-alt me-2"></i>
                                                        <?= htmlspecialchars($topic->title) ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($topic->lesson_title ?? 'N/A') ?></td>
                                                <td class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($topic->description) ?>">
                                                    <?= strlen($topic->description) > 50 ? htmlspecialchars(substr($topic->description, 0, 50)) . '...' : htmlspecialchars($topic->description) ?>
                                                </td>
                                                <td><?= $topic->display_order ?></td>
                                                <td class="text-end">
                                                    <div class="btn-group btn-group-sm topic-actions">
                                                        <div class="dropdown">
                                                            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="resourcesDropdown<?= $topic->id ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-paperclip"></i> Resources
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="resourcesDropdown<?= $topic->id ?>">
                                                                <li><a class="dropdown-item" href="manage-topic-resources.php?topic_id=<?= $topic->id ?>&type=pdf"><i class="fas fa-file-pdf me-2"></i>PDFs</a></li>
                                                                <li><a class="dropdown-item" href="manage-topic-resources.php?topic_id=<?= $topic->id ?>&type=video"><i class="fas fa-video me-2"></i>Videos</a></li>
                                                                <li><a class="dropdown-item" href="manage-topic-resources.php?topic_id=<?= $topic->id ?>&type=link"><i class="fas fa-link me-2"></i>Links</a></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item" href="manage-topic-resources.php?topic_id=<?= $topic->id ?>"><i class="fas fa-list me-2"></i>All Resources</a></li>
                                                            </ul>
                                                        </div>
                                                        <button type="button" class="btn btn-outline-secondary edit-topic" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editTopicModal"
                                                                data-id="<?= $topic->id ?>"
                                                                data-title="<?= htmlspecialchars($topic->title) ?>"
                                                                data-description="<?= htmlspecialchars($topic->description) ?>"
                                                                data-lesson-id="<?= $topic->lesson_id ?>"
                                                                data-display-order="<?= $topic->display_order ?>"
                                                                title="Edit Topic">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?id=<?= $courseId ?>&action=delete_topic&topic_id=<?= $topic->id ?>" 
                                                           class="btn btn-outline-danger btn-delete-topic" 
                                                           title="Delete Topic">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($topics)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="fas fa-inbox fa-3x d-block mb-3"></i>
                                                    No topics found. Add your first topic to get started.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Add Topic Modal -->
    <div class="modal fade" id="addTopicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="?id=<?= $courseId ?>" method="POST">
                    <input type="hidden" name="action" value="add_topic">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Topic</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="lesson_id" class="form-label">Lesson</label>
                            <select class="form-select" id="lesson_id" name="lesson_id" required>
                                <option value="">Select a lesson</option>
                                <?php foreach ($lessons as $lesson): ?>
                                    <option value="<?= $lesson->id ?>"><?= htmlspecialchars($lesson->title) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" autocomplete="off" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3" autocomplete="off"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="display_order" name="display_order" value="0" autocomplete="off">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Topic</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Topic Modal -->
    <div class="modal fade" id="editTopicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editTopicForm" action="" method="POST">
                    <input type="hidden" name="action" value="edit_topic">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Topic</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_lesson_id" class="form-label">Lesson</label>
                            <select class="form-select" id="edit_lesson_id" name="lesson_id" required>
                                <option value="">Select a lesson</option>
                                <?php foreach ($lessons as $lesson): ?>
                                    <option value="<?= $lesson->id ?>"><?= htmlspecialchars($lesson->title) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" autocomplete="off" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" autocomplete="off"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_display_order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="edit_display_order" name="display_order" autocomplete="off">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="id" id="edit_topic_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit Topic Modal Handler
            const editTopicModal = document.getElementById('editTopicModal');
            if (editTopicModal) {
                editTopicModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const form = document.getElementById('editTopicForm');
                    const topicId = button.getAttribute('data-id');
                    
                    form.action = `?id=<?= $courseId ?>&action=edit_topic&topic_id=${topicId}`;
                    document.getElementById('edit_title').value = button.getAttribute('data-title');
                    document.getElementById('edit_description').value = button.getAttribute('data-description');
                    document.getElementById('edit_lesson_id').value = button.getAttribute('data-lesson-id');
                    document.getElementById('edit_display_order').value = button.getAttribute('data-display-order');
                });
            }

            // Handle form submissions with AJAX
            const forms = ['addTopicForm', 'editTopicForm'];
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formData = new FormData(this);
                        const submitButton = this.querySelector('button[type="submit"]');
                        const originalButtonText = submitButton.innerHTML;
                        
                        // Show loading state
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
                        
                        fetch(this.action, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: data.message,
                                    showConfirmButton: false,
                                    timer: 1500
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.message || 'An error occurred');
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: error.message || 'An error occurred. Please try again.',
                                confirmButtonText: 'OK'
                            });
                        })
                        .finally(() => {
                            // Reset button state
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalButtonText;
                        });
                    });
                }
            });

            // Handle delete actions with confirmation
            document.querySelectorAll('.btn-delete-topic').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const deleteUrl = this.getAttribute('href');
                    
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'This will delete the topic and all its resources. This action cannot be undone!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch(deleteUrl, {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: data.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    throw new Error(data.message || 'Failed to delete topic');
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: error.message || 'Failed to delete topic',
                                    confirmButtonText: 'OK'
                                });
                            });
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>
