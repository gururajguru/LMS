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
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border-right: 1px solid var(--border-color);
            height: calc(100vh - 120px);
            overflow-y: auto;
            box-shadow: inset -1px 0 3px rgba(0, 0, 0, 0.05);
        }
        
        .topic-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            color: var(--text-color);
        }
        
        .topic-item:hover {
            background: var(--primary-light);
            transform: translateX(4px);
            color: var(--primary-color);
        }
        
        .topic-item.active {
            background: var(--primary-light);
            border-left: 4px solid var(--primary-color);
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .resource-card {
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        .resource-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--box-shadow-lg);
        }
        
        .resource-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            transition: var(--transition);
        }
        
        .resource-card:hover .resource-icon {
            transform: scale(1.1);
        }
        
        .resource-card .card-body {
            padding: 2rem;
            text-align: center;
        }
        
        .resource-card h5 {
            color: var(--text-color);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .resource-card .text-muted {
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        /* Enhanced Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(135deg, var(--card-bg) 0%, var(--gray-50) 100%);
            border-radius: var(--border-radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .empty-state::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }
        
        .empty-state h4 {
            color: var(--text-color);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .empty-state p {
            color: var(--text-muted);
            font-size: 1.125rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <!-- Header -->
        <header class="bg-white shadow-sm py-4">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <a href="index.php" class="text-decoration-none d-flex align-items-center">
                            <i class="fas fa-arrow-left me-2"></i> Back to Courses
                        </a>
                        <h4 class="mb-0 ms-4 d-inline text-primary"><?= htmlspecialchars($course->name) ?></h4>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="me-3 text-muted">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?= htmlspecialchars($_SESSION['USERNAME']) ?>
                        </span>
                        <a href="../logout.php" class="btn btn-sm btn-outline-danger">
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
                    <div class="p-4 border-bottom">
                        <h5 class="mb-0 text-primary font-weight-bold">
                            <i class="fas fa-list-ul me-2"></i>Course Topics
                        </h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (count($topics) > 0): ?>
                            <?php foreach ($topics as $topic): ?>
                                <a href="?id=<?= $courseId ?>&topic_id=<?= $topic->id ?>" 
                                   class="list-group-item list-group-item-action topic-item <?= ($selectedTopic && $selectedTopic->id == $topic->id) ? 'active' : '' ?>">
                                    <i class="fas fa-bookmark me-2"></i>
                                    <?= htmlspecialchars($topic->title) ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-4 text-center">
                                <i class="fas fa-folder-open fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No topics available for this course.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9 p-5">
                    <?php if ($selectedTopic): ?>
                        <div class="d-flex justify-content-between align-items-start mb-5">
                            <div>
                                <h3 class="text-primary mb-3">
                                    <i class="fas fa-bookmark me-2"></i>
                                    <?= htmlspecialchars($selectedTopic->title) ?>
                                </h3>
                                <?php if (!empty($selectedTopic->description)): ?>
                                    <p class="text-muted lead"><?= nl2br(htmlspecialchars($selectedTopic->description)) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (count($resources) > 0): ?>
                                <?php foreach ($resources as $resource): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card resource-card h-100">
                                            <div class="card-body text-center">
                                                <?php if ($resource->resource_type === 'video'): ?>
                                                    <i class="fas fa-play-circle resource-icon text-danger"></i>
                                                    <h5><?= htmlspecialchars($resource->title) ?></h5>
                                                    <p class="text-muted small mb-3">Video Resource</p>
                                                    <button class="btn btn-primary btn-sm" 
                                                            onclick="openResource('<?= htmlspecialchars($resource->content, ENT_QUOTES) ?>', 'video')">
                                                        <i class="fas fa-play me-1"></i> Watch Video
                                                    </button>
                                                <?php elseif ($resource->resource_type === 'pdf'): ?>
                                                    <i class="fas fa-file-pdf resource-icon text-danger"></i>
                                                    <h5><?= htmlspecialchars($resource->title) ?></h5>
                                                    <p class="text-muted small mb-3">PDF Document</p>
                                                    <a href="<?= htmlspecialchars($resource->content) ?>" 
                                                       class="btn btn-primary btn-sm" target="_blank">
                                                        <i class="fas fa-download me-1"></i> Download PDF
                                                    </a>
                                                <?php else: // link ?>
                                                    <i class="fas fa-external-link-alt resource-icon text-info"></i>
                                                    <h5><?= htmlspecialchars($resource->title) ?></h5>
                                                    <p class="text-muted small mb-3">External Resource</p>
                                                    <a href="<?= htmlspecialchars($resource->content) ?>" 
                                                       class="btn btn-primary btn-sm" target="_blank">
                                                        <i class="fas fa-external-link-alt me-1"></i> Open Link
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <h4 class="text-muted">No Resources Available</h4>
                                        <p class="text-muted">This topic doesn't have any resources yet.</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-book-open"></i>
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
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white" id="videoModalLabel">
                        <i class="fas fa-play-circle me-2"></i>Video Player
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="ratio ratio-16x9">
                        <iframe id="videoFrame" src="" allowfullscreen></iframe>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
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
                const modalTitle = document.getElementById('videoModalLabel');
                
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
        
        // Enhanced page animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate topic items
            const topicItems = document.querySelectorAll('.topic-item');
            topicItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease-out';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 50);
            });
            
            // Animate resource cards
            const resourceCards = document.querySelectorAll('.resource-card');
            resourceCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.4s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100 + 200);
            });
        });
    </script>
</body>
</html>
