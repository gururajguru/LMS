/**
 * Admin Dashboard JavaScript
 * Handles sidebar toggle, dropdowns, and other UI interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Sidebar toggle functionality
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebarBackdrop = document.createElement('div');
    sidebarBackdrop.className = 'sidebar-backdrop';
    document.body.appendChild(sidebarBackdrop);

    // Toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        sidebarBackdrop.classList.toggle('show');
        document.body.classList.toggle('sidebar-open');
    }

    // Close sidebar when clicking outside on mobile
    function closeSidebar() {
        if (window.innerWidth <= 991.98) {
            sidebar.classList.remove('show');
            sidebarBackdrop.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    }

    // Toggle sidebar when button is clicked
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Close sidebar when clicking on backdrop
    sidebarBackdrop.addEventListener('click', function() {
        closeSidebar();
    });

    // Close sidebar when clicking on nav links (for mobile)
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 991.98) {
                closeSidebar();
            }
        });
    });

    // Handle window resize
    function handleResize() {
        if (window.innerWidth > 991.98) {
            // On desktop, ensure sidebar is visible
            sidebar.classList.add('show');
            sidebarBackdrop.classList.remove('show');
        } else {
            // On mobile, ensure sidebar is hidden by default
            if (!sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        }
    }

    // Initial check
    handleResize();

    // Add resize event listener
    window.addEventListener('resize', handleResize);

    // Handle dropdown submenus
    const dropdownToggles = document.querySelectorAll('.nav-item.dropdown > .nav-link');
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 991.98) {
                e.preventDefault();
                e.stopPropagation();
                const parent = this.parentElement;
                parent.classList.toggle('show');
                
                // Toggle icon rotation
                const icon = this.querySelector('.dropdown-toggle-icon');
                if (icon) {
                    icon.style.transform = parent.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0)';
                }
            }
        });
    });

    // Initialize active state for current page
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    const navItems = document.querySelectorAll('.sidebar .nav-link');
    
    navItems.forEach(function(item) {
        const href = item.getAttribute('href');
        if (href && (href === currentPath || (currentPath === '' && href === 'index.php'))) {
            item.classList.add('active');
            // Also activate parent dropdown if exists
            const parentDropdown = item.closest('.dropdown');
            if (parentDropdown) {
                parentDropdown.classList.add('active');
                const toggle = parentDropdown.querySelector('.dropdown-toggle');
                if (toggle) {
                    toggle.classList.add('active');
                    const icon = toggle.querySelector('.dropdown-toggle-icon');
                    if (icon) {
                        icon.style.transform = 'rotate(180deg)';
                    }
                }
            }
        }
    });

    // Hide loading screen when everything is loaded
    window.addEventListener('load', function() {
        document.body.classList.add('js-loaded');
    });
});
