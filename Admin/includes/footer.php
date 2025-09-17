            </div> <!-- End of container-fluid -->
        </div> <!-- End of main-content -->
    </div> <!-- End of admin-container -->

    <!-- Modal Placeholder -->
    <div class="modal fade" id="mainModal" tabindex="-1" aria-labelledby="mainModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mainModalLabel">Modal title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="mainModalBody">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="mainModalSaveBtn">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
        <div id="toastContainer" class="toast-container"></div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script src="assets/js/admin-common.js"></script>
    
    <script>
        // Toggle sidebar
        document.getElementById('menu-toggle').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('toggled');
        });

        // Show toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast show align-items-center text-white bg-${type}`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            document.getElementById('toastContainer').appendChild(toast);
            
            // Auto remove toast after 5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 150);
            }, 5000);
        }

        // Show modal with dynamic content
        function showModal(title, content, onSave = null) {
            const modal = new bootstrap.Modal(document.getElementById('mainModal'));
            document.getElementById('mainModalLabel').textContent = title;
            document.getElementById('mainModalBody').innerHTML = content;
            
            const saveBtn = document.getElementById('mainModalSaveBtn');
            if (onSave && typeof onSave === 'function') {
                saveBtn.style.display = 'block';
                saveBtn.onclick = onSave;
            } else {
                saveBtn.style.display = 'none';
            }
            
            modal.show();
        }

        // Handle any success/error messages from PHP
        <?php if (isset($_SESSION['message'])): ?>
            showToast('<?php echo addslashes($_SESSION['message']['text']); ?>', '<?php echo $_SESSION['message']['type']; ?>');
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
