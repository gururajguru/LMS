<?php
session_start();

// Check if user is logged in and is student
if (!isset($_SESSION['USERID']) || $_SESSION['TYPE'] != 'Student') {
    header('Location: ../login.php');
    exit();
}

require_once '../include/database.php';
require_once '../include/Topic.php';
require_once '../include/TopicResource.php';

$courseId = $_GET['id'] ?? 0;
$topicId = $_GET['topic_id'] ?? 0;

// Initialize models
$topicModel = new Topic();
$resourceModel = new TopicResource();

// Get course details
$db = new Database();
$course = $db->get_single("SELECT * FROM courses WHERE id = ?", [$courseId]);

if (!$course) {
    header('Location: index.php?error=Course not found');
    exit();
}

// Get topics for the course
$topics = $topicModel->getByCourseId($courseId);

// Get selected topic and its resources
$selectedTopic = null;
$resources = [];

if ($topicId) {
    $selectedTopic = $topicModel->getById($topicId);
    if ($selectedTopic) {
        $resources = $resourceModel->getByTopicId($topicId);
    }
} elseif (count($topics) > 0) {
    // Default to first topic if none selected
    $selectedTopic = $topics[0];
    $resources = $resourceModel->getByTopicId($selectedTopic->id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course->name) ?> - Topics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/student-styles.css" rel="stylesheet">
    <style>
        .topic-sidebar {
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            height: calc(100vh - 120px);
            overflow-y: auto;
        }
        .topic-item {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.2s;
        }
        .topic-item:hover, .topic-item.active {
            background-color: #e9ecef;
        }
        .topic-item.active {
            border-left: 3px solid var(--primary-color);
            font-weight: 500;
        }
        .resource-card {
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        .resource-card:hover {
            transform: translateY(-2px);
        }
        .resource-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <!-- Header -->
        <header class="bg-white shadow-sm py-3">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i> Back to Courses
                        </a>
                        <h4 class="mb-0 ms-4 d-inline"><?= htmlspecialchars($course->name) ?></h4>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="me-3">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?= htmlspecialchars($_SESSION['USERNAME']) ?>
                        </span>
                        <a href="../logout.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-sign-out-alt me-1"></i> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="container-fluid">
            <div class="row">
                <!-- Topics Sidebar -->
                <div class="col-md-3 p-0 topic-sidebar">
                    <div class="p-3 border-bottom">
                        <h5>Course Topics</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (count($topics) > 0): ?>
                            <?php foreach ($topics as $topic): ?>
                                <a href="?id=<?= $courseId ?>&topic_id=<?= $topic->id ?>" 
                                   class="list-group-item list-group-item-action topic-item <?= ($selectedTopic && $selectedTopic->id == $topic->id) ? 'active' : '' ?>">
                                    <?= htmlspecialchars($topic->title) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-3 text-muted">No topics available for this course.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9 p-4">
                    <?php if ($selectedTopic): ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h3><?= htmlspecialchars($selectedTopic->title) ?></h3>
                                <?php if (!empty($selectedTopic->description)): ?>
                                    <p class="text-muted"><?= nl2br(htmlspecialchars($selectedTopic->description)) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (count($resources) > 0): ?>
                                <?php foreach ($resources as $resource): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card resource-card h-100">
                                            <div class="card-body text-center">
                                                <?php if ($resource->resource_type === 'video'): ?>
                                                    <i class="fas fa-play-circle resource-icon"></i>
                                                    <h5><?= htmlspecialchars($resource->title) ?></h5>
                                                    <p class="text-muted small">Video Resource</p>
                                                    <button class="btn btn-outline-primary btn-sm" 
                                                            onclick="openResource('<?= htmlspecialchars($resource->content, ENT_QUOTES) ?>', 'video')">
                                                        <i class="fas fa-play me-1"></i> Watch Video
                                                    </button>
                                                <?php elseif ($resource->resource_type === 'pdf'): ?>
                                                    <i class="fas fa-file-pdf resource-icon"></i>
                                                    <h5><?= htmlspecialchars($resource->title) ?></h5>
                                                    <p class="text-muted small">PDF Document</p>
                                                    <a href="<?= htmlspecialchars($resource->content) ?>" 
                                                       class="btn btn-outline-primary btn-sm" target="_blank">
                                                        <i class="fas fa-download me-1"></i> Download PDF
                                                    </a>
                                                <?php else: // link ?>
                                                    <i class="fas fa-link resource-icon"></i>
                                                    <h5><?= htmlspecialchars($resource->title) ?></h5>
                                                    <p class="text-muted small">External Resource</p>
                                                    <a href="<?= htmlspecialchars($resource->content) ?>" 
                                                       class="btn btn-outline-primary btn-sm" target="_blank">
                                                        <i class="fas fa-external-link-alt me-1"></i> Open Link
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="text-center p-5 bg-light rounded-3">
                                        <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                        <h4 class="text-muted">No Resources Available</h4>
                                        <p class="text-muted">This topic doesn't have any resources yet.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-5 bg-light rounded-3">
                            <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Topic Selected</h4>
                            <p class="text-muted">Select a topic from the sidebar to view its content.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="videoModalLabel">Video Player</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="ratio ratio-16x9">
                        <iframe id="videoFrame" src="" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to open video in modal
        function openResource(url, type) {
            if (type === 'video') {
                const videoModal = new bootstrap.Modal(document.getElementById('videoModal'));
                const videoFrame = document.getElementById('videoFrame');
                
                // Check if URL is YouTube or Vimeo embed URL
                if (url.includes('youtube.com/embed/') || url.includes('player.vimeo.com/video/')) {
                    videoFrame.src = url;
                } else if (url.includes('youtube.com/watch')) {
                    // Convert YouTube watch URL to embed URL
                    const videoId = new URL(url).searchParams.get('v');
                    videoFrame.src = `https://www.youtube.com/embed/${videoId}`;
                } else if (url.includes('vimeo.com/')) {
                    // Convert Vimeo URL to embed URL
                    const videoId = url.split('vimeo.com/')[1].split('?')[0];
                    videoFrame.src = `https://player.vimeo.com/video/${videoId}`;
                } else {
                    // Assume it's a direct video URL
                    videoFrame.src = url;
                }
                
                videoModal.show();
            }
        }

        // Clean up video when modal is closed
        document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () {
            const videoFrame = document.getElementById('videoFrame');
            videoFrame.src = '';
        });
    </script>
</body>
</html>
