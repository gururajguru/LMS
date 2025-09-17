<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Administrator') {
    header("Location: login.php");
    exit();
}

require_once '../include/database.php';
require_once '../include/TopicResource.php';

$db = new Database();
$resourceModel = new TopicResource($db);

// Get parameters from URL
$topicId = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
$resourceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';
$resourceType = $_GET['type'] ?? '';

// Get topic details
$topic = null;
if ($topicId) {
    $db->setQuery("SELECT t.*, l.title as lesson_title, c.id as course_id, c.name as course_name 
                  FROM topics t 
                  JOIN lessons l ON t.lesson_id = l.id 
                  JOIN courses c ON l.course_id = c.id 
                  WHERE t.id = " . $db->escape_string($topicId));
    $topic = $db->loadSingleResult();
}

if (!$topic) {
    $_SESSION['error'] = "Topic not found";
    header("Location: manage-topics.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $action;
    
    if ($action === 'add_resource' || $action === 'edit_resource') {
        $data = [
            'topic_id' => $topicId,
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'resource_type' => $_POST['resource_type'],
            'content' => $_POST['content'],
            'display_order' => (int)($_POST['display_order'] ?? 0)
        ];
        
        if ($action === 'add_resource') {
            $result = $resourceModel->create($data);
            $message = $result ? 'Resource added successfully' : 'Failed to add resource';
        } else {
            $result = $resourceModel->update($resourceId, $data);
            $message = $result ? 'Resource updated successfully' : 'Failed to update resource';
        }
        
        if ($result) {
            $_SESSION['success'] = $message;
            $redirectUrl = "manage-topic-resources.php?topic_id=$topicId";
            if ($resourceType) $redirectUrl .= "&type=$resourceType";
            header("Location: $redirectUrl");
            exit();
        } else {
            $error = $message;
        }
    }
}

// Handle delete action
if ($action === 'delete' && $resourceId) {
    if ($resourceModel->delete($resourceId)) {
        $_SESSION['success'] = 'Resource deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete resource';
    }
    $redirectUrl = "manage-topic-resources.php?topic_id=$topicId";
    if ($resourceType) $redirectUrl .= "&type=$resourceType";
    header("Location: $redirectUrl");
    exit();
}

// Get resources for the topic with optional filtering by type
$resources = [];
if ($topicId) {
    $query = "SELECT * FROM topic_resources WHERE topic_id = " . $db->escape_string($topicId);
    if ($resourceType) {
        $query .= " AND resource_type = " . $db->escape_string($resourceType);
    }
    $query .= " ORDER BY display_order, created_at";
    
    $db->setQuery($query);
    $resources = $db->loadResultList();
}

// Get selected resource for editing
$selectedResource = $resourceId ? $resourceModel->getById($resourceId) : null;

// Set page title based on resource type
$resourceTypeNames = [
    'pdf' => 'PDFs',
    'video' => 'Videos',
    'link' => 'Links'
];

$pageTitle = $selectedResource ? 'Edit Resource' : 
            ($resourceType ? 'Add New ' . ($resourceTypeNames[$resourceType] ?? 'Resource') : 'Add New Resource');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= htmlspecialchars($topic->title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin-styles.css" rel="stylesheet">
    <link href="../assets/css/admin-forms.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Main content -->
            <main class="col-md-12">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-paperclip me-2"></i>
                        <?= $pageTitle ?>
                        <?php if ($resourceType && !$selectedResource): ?>
                            <small class="text-muted"><?= $resourceTypeNames[$resourceType] ?? ucfirst($resourceType) ?></small>
                        <?php endif; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="manage-topics.php?id=<?= $topic->course_id ?>" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Back to Topics
                        </a>
                        <?php if ($selectedResource): ?>
                            <a href="manage-topic-resources.php?topic_id=<?= $topicId ?><?= $resourceType ? '&type=' . $resourceType : '' ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-plus me-1"></i> Add New
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <?= $pageTitle ?> for "<?= htmlspecialchars($topic->title) ?>"
                            <small class="text-muted d-block">
                                Course: <?= htmlspecialchars($topic->course_name) ?> > 
                                Lesson: <?= htmlspecialchars($topic->lesson_title) ?>
                            </small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$selectedResource && $resourceType && !empty($resources)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                You're viewing all <?= strtolower($resourceTypeNames[$resourceType] ?? $resourceType) ?> for this topic. 
                                <a href="manage-topic-resources.php?topic_id=<?= $topicId ?>" class="alert-link">View all resource types</a>.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="<?= $selectedResource ? 'edit_resource' : 'add_resource' ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="resource_type" class="form-label">Resource Type</label>
                                    <select class="form-select" id="resource_type" name="resource_type" required 
                                        <?= $resourceType ? 'disabled' : '' ?>>
                                        <?php if (!$resourceType): ?>
                                            <option value="" disabled <?= !$selectedResource ? 'selected' : '' ?>>Select a resource type</option>
                                        <?php endif; ?>
                                        <option value="pdf" <?= (($selectedResource && $selectedResource->resource_type === 'pdf') || $resourceType === 'pdf') ? 'selected' : '' ?>>PDF Document</option>
                                        <option value="video" <?= (($selectedResource && $selectedResource->resource_type === 'video') || $resourceType === 'video') ? 'selected' : '' ?>>Video</option>
                                        <option value="link" <?= (($selectedResource && $selectedResource->resource_type === 'link') || $resourceType === 'link') ? 'selected' : '' ?>>External Link</option>
                                    </select>
                                    <?php if ($resourceType): ?>
                                        <input type="hidden" name="resource_type" value="<?= $resourceType ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label for="display_order" class="form-label">Display Order</label>
                                    <input type="number" class="form-control" id="display_order" name="display_order" 
                                           value="<?= $selectedResource ? $selectedResource->display_order : '0' ?>" min="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?= $selectedResource ? htmlspecialchars($selectedResource->title) : '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description (Optional)</label>
                                <textarea class="form-control" id="description" name="description" rows="2"><?= $selectedResource ? htmlspecialchars($selectedResource->description) : '' ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label" id="content_label">
                                    <?php 
                                    $type = $selectedResource ? $selectedResource->resource_type : $resourceType;
                                    echo $type === 'pdf' ? 'PDF URL' : 
                                         ($type === 'video' ? 'Video URL or Embed Code' : 'URL'); 
                                    ?>
                                </label>
                                <?php if ($selectedResource && $selectedResource->resource_type === 'video'): ?>
                                    <div class="mb-2" id="video_preview">
                                        <?php if (strpos($selectedResource->content, 'youtube.com') !== false || strpos($selectedResource->content, 'youtu.be') !== false): ?>
                                            <div class="ratio ratio-16x9 mb-3">
                                                <iframe src="<?= htmlspecialchars($selectedResource->content) ?>" 
                                                        title="<?= htmlspecialchars($selectedResource->title) ?>" 
                                                        allowfullscreen></iframe>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Video preview not available. Please ensure you're using a supported platform (e.g., YouTube).
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <textarea class="form-control" id="content" name="content" rows="3" required><?= $selectedResource ? htmlspecialchars($selectedResource->content) : '' ?></textarea>
                                <div class="form-text">
                                    <span id="content_help">
                                        <?php 
                                        $type = $selectedResource ? $selectedResource->resource_type : $resourceType;
                                        if ($type === 'pdf'): ?>
                                            Enter the full URL to the PDF file (e.g., https://example.com/document.pdf)
                                        <?php elseif ($type === 'video'): ?>
                                            Enter the video URL (YouTube, Vimeo, etc.) or embed code
                                        <?php else: ?>
                                            Enter the full URL (e.g., https://example.com)
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="manage-topic-resources.php?topic_id=<?= $topicId ?><?= $resourceType ? '&type=' . $resourceType : '' ?>" 
                                   class="btn btn-outline-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> 
                                    <?= $selectedResource ? 'Update Resource' : 'Add Resource' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resources List -->
                <?php if (!$selectedResource): ?>
                    <div class="card mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?= $resourceType ? $resourceTypeNames[$resourceType] : 'All Resources' ?>
                                <?php if ($resourceType): ?>
                                    <a href="manage-topic-resources.php?topic_id=<?= $topicId ?>" class="btn btn-sm btn-outline-secondary ms-2">
                                        <i class="fas fa-times me-1"></i> Clear Filter
                                    </a>
                                <?php endif; ?>
                            </h5>
                            <div>
                                <?php if (!$resourceType): ?>
                                    <div class="btn-group btn-group-sm me-2">
                                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-filter me-1"></i> Filter by Type
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="manage-topic-resources.php?topic_id=<?= $topicId ?>&type=pdf">PDFs</a></li>
                                            <li><a class="dropdown-item" href="manage-topic-resources.php?topic_id=<?= $topicId ?>&type=video">Videos</a></li>
                                            <li><a class="dropdown-item" href="manage-topic-resources.php?topic_id=<?= $topicId ?>&type=link">Links</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="manage-topic-resources.php?topic_id=<?= $topicId ?>">All Resources</a></li>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                <a href="manage-topic-resources.php?topic_id=<?= $topicId ?><?= $resourceType ? '&type=' . $resourceType : '' ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($resources)): ?>
                                <div class="text-center p-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="mb-0">
                                        No <?= $resourceType ? strtolower($resourceTypeNames[$resourceType] ?? $resourceType) : '' ?> resources found.
                                        <?php if (!$resourceType): ?>
                                            Add your first resource using the form above.
                                        <?php else: ?>
                                            <a href="manage-topic-resources.php?topic_id=<?= $topicId ?><?= $resourceType ? '&type=' . $resourceType : '' ?>&action=add" 
                                               class="btn btn-sm btn-link p-0 align-baseline">
                                                Add a new <?= strtolower($resourceTypeNames[$resourceType] ?? $resourceType) ?>.
                                            </a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Title</th>
                                                <th>Type</th>
                                                <th>Content</th>
                                                <th>Order</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($resources as $resource): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php 
                                                            $icon = 'fa-link';
                                                            $iconClass = '';
                                                            if ($resource->resource_type === 'pdf') {
                                                                $icon = 'fa-file-pdf';
                                                                $iconClass = 'text-danger';
                                                            } elseif ($resource->resource_type === 'video') {
                                                                $icon = 'fa-video';
                                                                $iconClass = 'text-primary';
                                                            }
                                                            ?>
                                                            <i class="fas <?= $icon ?> <?= $iconClass ?> me-2"></i>
                                                            <?= htmlspecialchars($resource->title) ?>
                                                        </div>
                                                        <?php if (!empty($resource->description)): ?>
                                                            <small class="text-muted d-block text-truncate" style="max-width: 300px;" 
                                                                   title="<?= htmlspecialchars($resource->description) ?>">
                                                                <?= htmlspecialchars($resource->description) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="manage-topic-resources.php?topic_id=<?= $topicId ?>&type=<?= $resource->resource_type ?>" 
                                                           class="badge bg-secondary text-uppercase text-decoration-none" 
                                                           title="Filter by <?= $resourceTypeNames[$resource->resource_type] ?? $resource->resource_type ?>">
                                                            <?= $resource->resource_type ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-truncate" style="max-width: 200px;" 
                                                        title="<?= htmlspecialchars($resource->content) ?>">
                                                        <?= htmlspecialchars($resource->content) ?>
                                                    </td>
                                                    <td><?= $resource->display_order ?></td>
                                                    <td class="text-end">
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="manage-topic-resources.php?topic_id=<?= $topicId ?>&id=<?= $resource->id ?><?= $resourceType ? '&type=' . $resourceType : '' ?>" 
                                                               class="btn btn-outline-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="manage-topic-resources.php?topic_id=<?= $topicId ?>&id=<?= $resource->id ?>&action=delete<?= $resourceType ? '&type=' . $resourceType : '' ?>" 
                                                               class="btn btn-outline-danger" 
                                                               onclick="return confirm('Are you sure you want to delete this resource?')"
                                                               title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update content field label and help text based on resource type
        document.addEventListener('DOMContentLoaded', function() {
            const resourceTypeSelect = document.getElementById('resource_type');
            if (resourceTypeSelect) {
                resourceTypeSelect.addEventListener('change', updateContentField);
                // Initialize on page load
                updateContentField();
            }

            function updateContentField() {
                const typeSelect = document.getElementById('resource_type');
                if (!typeSelect) return;
                
                const type = typeSelect.value;
                const contentLabel = document.getElementById('content_label');
                const contentHelp = document.getElementById('content_help');
                
                if (type === 'pdf') {
                    contentLabel.textContent = 'PDF URL';
                    contentHelp.textContent = 'Enter the full URL to the PDF file (e.g., https://example.com/document.pdf)';
                } else if (type === 'video') {
                    contentLabel.textContent = 'Video URL or Embed Code';
                    contentHelp.textContent = 'Enter the video URL (YouTube, Vimeo, etc.) or embed code';
                } else {
                    contentLabel.textContent = 'URL';
                    contentHelp.textContent = 'Enter the full URL (e.g., https://example.com)';
<!-- View Resource Modal -->
<div class="modal fade" id="viewResourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewResourceModalLabel">Resource</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="ratio ratio-16x9">
                    <iframe id="resourceIframe" src="" allowfullscreen></iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Handle edit resource modal
const editResourceModal = document.getElementById('editResourceModal');
if (editResourceModal) {
    editResourceModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const form = document.getElementById('editResourceForm');
        const resourceId = button.getAttribute('data-id');
        
        form.action = `?course_id=<?= $courseId ?>&topic_id=<?= $topicId ?>&action=edit_resource&resource_id=${resourceId}`;
        document.getElementById('edit_title').value = button.getAttribute('data-title');
        document.getElementById('edit_resource_type').value = button.getAttribute('data-resource-type');
        document.getElementById('edit_content').value = button.getAttribute('data-content');
        document.getElementById('edit_display_order').value = button.getAttribute('data-display-order');
    });
}

// Handle view resource modal
const viewResourceModal = document.getElementById('viewResourceModal');
if (viewResourceModal) {
    viewResourceModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const modalTitle = viewResourceModal.querySelector('.modal-title');
        const iframe = document.getElementById('resourceIframe');
        
        modalTitle.textContent = button.getAttribute('data-title');
        
        // Handle different video platforms
        let videoUrl = button.getAttribute('data-content');
        if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
            // YouTube
            let videoId = '';
            if (videoUrl.includes('youtube.com/watch?v=')) {
                videoId = videoUrl.split('v=')[1];
                const ampersandPosition = videoId.indexOf('&');
                if (ampersandPosition !== -1) {
                    videoId = videoId.substring(0, ampersandPosition);
                }
            } else if (videoUrl.includes('youtu.be/')) {
                videoId = videoUrl.split('youtu.be/')[1];
            }
            
            if (videoId) {
                videoUrl = `https://www.youtube.com/embed/${videoId}`;
            }
        } else if (videoUrl.includes('vimeo.com')) {
            // Vimeo
            const videoId = videoUrl.split('vimeo.com/')[1];
            if (videoId) {
                videoUrl = `https://player.vimeo.com/video/${videoId}`;
            }
        }
        
        iframe.src = videoUrl;
    });
    
    // Clear iframe src when modal is hidden to stop video playback
    viewResourceModal.addEventListener('hidden.bs.modal', function () {
        document.getElementById('resourceIframe').src = '';
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
