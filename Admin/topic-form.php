<?php
require_once("../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    redirect(web_root . "login.php");
}

// Initialize the Topic and Course classes
$topicObj = new Topic();
$courseObj = new Courses();

// Check if we're editing an existing topic
$isEdit = false;
$topic = [];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (isset($_GET['id'])) {
    $isEdit = true;
    $topic = $topicObj->getTopicById((int)$_GET['id']);
    if (!$topic) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Topic not found.'
        ];
        redirect('topics.php' . ($courseId ? '?course_id=' . $courseId : ''));
    }
    $courseId = $topic['course_id'];
}

// Get course details if course_id is provided
$course = null;
if ($courseId) {
    $course = $courseObj->getCourseById($courseId);
    if (!$course) {
        $_SESSION['message'] = [
            'type' => 'danger',
            'text' => 'Invalid course selected.'
        ];
        redirect('topics.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description']),
        'course_id' => $courseId,
        'sort_order' => (int)$_POST['sort_order'],
        'status' => isset($_POST['status']) ? 1 : 0,
        'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? '')
    ];

    // Handle file upload for thumbnail
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/topics/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExt = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('topic_') . '.' . $fileExt;
        $targetPath = $uploadDir . $fileName;
        
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($fileExt, $allowedTypes)) {
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetPath)) {
                // Delete old thumbnail if it exists
                if ($isEdit && !empty($topic['thumbnail'])) {
                    $oldThumbnail = $uploadDir . basename($topic['thumbnail']);
                    if (file_exists($oldThumbnail)) {
                        @unlink($oldThumbnail);
                    }
                }
                $data['thumbnail'] = 'uploads/topics/' . $fileName;
            }
        }
    }

    // Add ID if editing
    if ($isEdit) {
        $data['id'] = $topic['id'];
    }

    // Save topic
    $result = $isEdit ? $topicObj->updateTopic($data) : $topicObj->addTopic($data);
    
    if ($result) {
        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Topic ' . ($isEdit ? 'updated' : 'created') . ' successfully.'
        ];
        redirect('topics.php?course_id=' . $courseId);
    } else {
        $error = $topicObj->getError() ?: 'An error occurred while saving the topic.';
    }
}

$pageTitle = ($isEdit ? 'Edit' : 'Create') . ' Topic' . ($course ? ' - ' . htmlspecialchars($course['name']) : '');
include('includes/header.php');
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas <?= $isEdit ? 'fa-edit' : 'fa-plus' ?> me-2"></i>
            <?= $isEdit ? 'Edit Topic' : 'Create New Topic' ?>
        </h1>
        <div>
            <a href="topics.php<?= $courseId ? '?course_id=' . $courseId : ''; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Topics
            </a>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" id="topicForm">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required 
                                   value="<?= htmlspecialchars($topic['title'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="4"><?= htmlspecialchars($topic['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control rich-text-editor" id="content" name="content" rows="10">
                                <?= htmlspecialchars($topic['content'] ?? '') ?>
                            </textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                           min="0" value="<?= $topic['sort_order'] ?? 0 ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               id="status" name="status" value="1" 
                                               <?= ($topic['status'] ?? 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="status">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Thumbnail</h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <?php if (!empty($topic['thumbnail'])): ?>
                                        <img src="../<?= htmlspecialchars($topic['thumbnail']) ?>" 
                                             alt="Topic Thumbnail" 
                                             class="img-fluid mb-2" 
                                             style="max-height: 150px;">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="remove_thumbnail" name="remove_thumbnail" value="1">
                                            <label class="form-check-label" for="remove_thumbnail">
                                                Remove thumbnail
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="custom-file">
                                        <input type="file" class="form-control" id="thumbnail" name="thumbnail" 
                                               accept="image/*" <?= empty($topic['thumbnail']) ? 'required' : '' ?>>
                                        <div class="form-text">
                                            Recommended size: 800x450px. Max size: 2MB
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Course</h6>
                            </div>
                            <div class="card-body">
                                <?php if ($course): ?>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($course['image'])): ?>
                                            <img src="../<?= htmlspecialchars($course['image']) ?>" 
                                                 alt="<?= htmlspecialchars($course['name']) ?>" 
                                                 class="rounded me-2" 
                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($course['name']) ?></h6>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($course['code'] ?? '') ?>
                                            </small>
                                        </div>
                                    </div>
                                    <input type="hidden" name="course_id" value="<?= $courseId ?>">
                                <?php else: ?>
                                    <select class="form-select" name="course_id" required>
                                        <option value="">Select Course</option>
                                        <?php 
                                        $courses = $courseObj->getAllCourses();
                                        foreach ($courses as $c): 
                                        ?>
                                            <option value="<?= $c['id'] ?>" 
                                                <?= ($courseId == $c['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">SEO Settings</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                    <input type="text" class="form-control" id="meta_keywords" 
                                           name="meta_keywords" 
                                           value="<?= htmlspecialchars($topic['meta_keywords'] ?? '') ?>">
                                    <div class="form-text">Separate keywords with commas</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="meta_description" class="form-label">Meta Description</label>
                                    <textarea class="form-control" id="meta_description" 
                                              name="meta_description" rows="3"><?= 
                                        htmlspecialchars($topic['meta_description'] ?? '') 
                                    ?></textarea>
                                    <div class="form-text">Recommended: 150-160 characters</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <?php if ($isEdit): ?>
                            <button type="button" class="btn btn-outline-danger me-2" id="deleteBtn">
                                <i class="fas fa-trash me-1"></i> Delete
                            </button>
                        <?php endif; ?>
                    </div>
                    <div>
                        <a href="topics.php<?= $courseId ? '?course_id=' . $courseId : ''; ?>" 
                           class="btn btn-secondary me-2">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> 
                            <?= $isEdit ? 'Update Topic' : 'Create Topic' ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<?php if ($isEdit): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the topic "<strong><?= htmlspecialchars($topic['title']) ?></strong>"? This action cannot be undone.</p>
                <p class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> All related data (lessons, materials) will also be removed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="topics.php?action=delete&id=<?= $topic['id'] ?>" class="btn btn-danger">
                    <i class="fas fa-trash me-1"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Include TinyMCE for rich text editor -->
<script src="https://cdn.tiny.cloud/1/your-tinymce-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>

<script>
// Initialize TinyMCE
tinymce.init({
    selector: '.rich-text-editor',
    plugins: 'print preview paste importcss searchreplace autolink autosave save directionality code visualblocks visualchars fullscreen image link media template codesample table charmap hr pagebreak nonbreaking anchor toc insertdatetime advlist lists wordcount imagetools textpattern noneditable help charmap quickbars emoticons',
    menubar: 'file edit view insert format tools table help',
    toolbar: 'undo redo | bold italic underline strikethrough | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | insertfile image media template link anchor codesample | ltr rtl',
    toolbar_sticky: true,
    autosave_ask_before_unload: true,
    autosave_interval: "30s",
    autosave_prefix: "{path}{query}-{id}-",
    autosave_restore_when_empty: false,
    autosave_retention: "2m",
    image_advtab: true,
    height: 400,
    image_caption: true,
    quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
    noneditable_noneditable_class: "mceNonEditable",
    toolbar_mode: 'sliding',
    contextmenu: "link image imagetools table",
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
    // Add upload handler for images
    images_upload_handler: function (blobInfo, success, failure) {
        var xhr, formData;
        
        xhr = new XMLHttpRequest();
        xhr.withCredentials = false;
        xhr.open('POST', 'upload.php');
        
        xhr.onload = function() {
            var json;
            
            if (xhr.status != 200) {
                failure('HTTP Error: ' + xhr.status);
                return;
            }
            
            json = JSON.parse(xhr.responseText);
            
            if (!json || typeof json.location != 'string') {
                failure('Invalid JSON: ' + xhr.responseText);
                return;
            }
            
            success(json.location);
        };
        
        formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
        formData.append('type', 'image');
        
        xhr.send(formData);
    }
});

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('topicForm');
    
    // Delete button handler
    const deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        });
    }
    
    // Form submission
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
    
    // Thumbnail preview
    const thumbnailInput = document.getElementById('thumbnail');
    const thumbnailPreview = document.querySelector('.thumbnail-preview');
    
    if (thumbnailInput && thumbnailPreview) {
        thumbnailInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    thumbnailPreview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview" class="img-fluid">
                    `;
                }
                
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Character counter for meta description
    const metaDesc = document.getElementById('meta_description');
    if (metaDesc) {
        const counter = document.createElement('div');
        counter.className = 'form-text text-end';
        metaDesc.parentNode.insertBefore(counter, metaDesc.nextSibling);
        
        function updateCounter() {
            const length = metaDesc.value.length;
            counter.textContent = `${length} characters`;
            counter.style.color = length >= 150 && length <= 160 ? 'green' : '';
        }
        
        metaDesc.addEventListener('input', updateCounter);
        updateCounter();
    }
});
</script>

<?php include('includes/footer.php'); ?>
