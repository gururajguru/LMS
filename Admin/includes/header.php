<?php
require_once("../include/initialize.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    redirect(web_root . "login.php");
}

$pageTitle = isset($pageTitle) ? $pageTitle : 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>LMS Admin - <?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" as="style">
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- App CSS -->
    <link rel="stylesheet" href="../assets/css/admin-styles.css">
    <link rel="stylesheet" href="../assets/css/admin-forms.css">
    
    <!-- Critical CSS -->
    <style>
        :root {
            /* Modern Color Palette */
            --primary: #2563EB;
            --primary-hover: #1D4ED8;
            --primary-light: #EFF6FF;
            --secondary: #64748B;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --info: #3B82F6;
            --light: #F8FAFC;
            --dark: #0F172A;
            --text-primary: #111827;
            --text-secondary: #4B5563;
            --text-muted: #6B7280;
            --border-color: #E5E7EB;
            --card-bg: #FFFFFF;
            --input-bg: #F9FAFB;
            
            /* Design Tokens */
            --border-radius: 0.75rem;
            --border-radius-sm: 0.5rem;
            --border-radius-lg: 1rem;
            --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --box-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%);
            --gradient-accent: linear-gradient(135deg, #10B981 0%, #059669 100%);
        }

        :root {
            --sidebar-width: 280px;
            --header-height: 60px;
            --bg-sidebar: #1E293B;
            --bg-body: #F9FAFB;
            --text-light: rgba(255, 255, 255, 0.95);
            --text-light-secondary: rgba(255, 255, 255, 0.7);
            --transition: 0.2s ease-in-out;
        }

        html {
            height: 100%;
            font-size: 16px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            font-size: 0.9375rem;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            padding-left: var(--sidebar-width);
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-sidebar);
            color: var(--text-light);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Main content */
        .main-content {
            flex: 1;
            padding: 2rem;
            width: 100%;
            min-height: 100vh;
            background-color: var(--bg-body);
            box-sizing: border-box;
        }
        
        /* Loading Screen */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #fff;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 1;
            visibility: visible;
            transition: opacity var(--transition), visibility var(--transition);
        }

        .js-loaded .loading {
            opacity: 0;
            visibility: hidden;
        }
        
        /* Layout Container */
        .admin-container {
            display: flex;
            min-height: 100vh;
            background-color: var(--bg-body);
            position: relative;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--bg-sidebar);
            color: var(--text-light);
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: transform var(--transition);
            z-index: 1040;
            position: fixed;
            top: 0;
            left: 0;
            box-shadow: 4px 0 8px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 2rem 1.5rem 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 0.5rem;
        }

        .sidebar-header h4 {
            margin: 0;
            color: var(--text-light);
            font-size: 1.375rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: -0.01em;
        }

        .sidebar-header p {
            margin: 0.5rem 0 0;
            font-size: 0.875rem;
            color: var(--text-light-secondary);
            font-weight: 400;
        }

        .sidebar-nav {
            padding: 0.5rem 0;
            flex: 1;
            overflow-y: auto;
        }

        .nav-link {
            padding: 1rem 1.75rem;
            color: var(--text-light-secondary);
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            transition: all var(--transition);
            border-left: 3px solid transparent;
            font-size: 0.9375rem;
            font-weight: 500;
            position: relative;
        }

        .nav-link:hover {
            color: var(--text-light);
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(4px);
        }

        .nav-link.active {
            color: var(--text-light);
            background: rgba(37, 99, 235, 0.2);
            border-left-color: var(--primary);
            font-weight: 600;
        }

        .nav-link i {
            width: 1.5rem;
            text-align: center;
            font-size: 1.125rem;
            opacity: 0.9;
            transition: var(--transition);
        }

        .nav-link:hover i,
        .nav-link.active i {
            opacity: 1;
            transform: scale(1.1);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            padding: 2rem;
            background-color: var(--bg-body);
            box-sizing: border-box;
            position: relative;
            transition: all 0.3s ease;
        }

        .container-fluid {
            width: 100%;
            max-width: 100%;
            padding: 0 1.5rem;
            margin: 0 auto;
        }
        
        body {
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Ensure content is not hidden behind sidebar */
        @media (max-width: 991.98px) {
            .admin-container {
                padding-left: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar-backdrop {
                display: block;
            }
        }
        
        /* Mobile sidebar toggle */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 1.5rem;
            }
            
            .sidebar {
                transform: translateX(-100%);
                box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
            }
            
            .admin-container.sidebar-visible .sidebar {
                transform: translateX(0);
            }

            #sidebarToggle {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                width: 3.5rem !important;
                height: 3.5rem !important;
                border-radius: 50% !important;
                box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
                background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%) !important;
                border: none !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                color: white !important;
                z-index: 1060;
                transition: var(--transition);
            }

            #sidebarToggle:hover {
                transform: scale(1.1);
                box-shadow: 0 12px 35px rgba(37, 99, 235, 0.4);
            }

            #sidebarToggle i {
                font-size: 1.25rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .card-body {
                padding: 1.5rem;
            }
        }

        @media (min-width: 768px) and (max-width: 991.98px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Loading Screen */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--bg-body);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            visibility: visible;
            transition: opacity var(--transition), visibility var(--transition);
        }

        .loading .spinner-border {
            width: 3rem;
            height: 3rem;
            color: var(--primary) !important;
        }

        .js-loaded .loading {
            opacity: 0;
            visibility: hidden;
        }

        /* Scrollbars */
        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Utilities */
        .fade-in {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            margin-bottom: 2rem;
        }

        /* Typography */
        h1, .h1 {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            line-height: 1.2;
            letter-spacing: -0.025em;
        }

        h2, .h2 {
            font-size: 1.875rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        h3, .h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .welcome-text {
            font-size: 1rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
            margin-bottom: 2rem;
        }

        /* Dashboard Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--box-shadow-lg);
        }

        .stat-card .stat-icon {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--text-light);
            background: var(--gradient-primary);
            box-shadow: var(--box-shadow);
        }

        .stat-card .stat-title {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin: 0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-card .stat-value {
            color: var(--text-primary);
            font-size: 2.25rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
            letter-spacing: -0.025em;
        }

        /* Tables */
        .table-section {
            background: var(--card-bg);
            border-radius: 0.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }

        .table-header {
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .table-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .table {
            margin: 0;
        }

        .table th {
            font-weight: 500;
            color: var(--text-muted);
            border-bottom-color: var(--border-color);
            padding: 1rem 1.5rem;
        }

        .table td {
            padding: 1rem 1.5rem;
            color: var(--text-primary);
            vertical-align: middle;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: rgba(5, 150, 105, 0.1);
            color: var(--success);
        }
        
        /* Calendar Section */
        .calendar-section {
            background: var(--card-bg);
            border-radius: 0.5rem;
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
        }

        .no-events {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }

        .no-events i {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Recent Section */
        .recent-section {
            background: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
        }

        .recent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .recent-title {
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .recent-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-link {
            color: var(--text-primary);
            text-decoration: none;
        }

        .recent-link:hover {
            color: var(--primary);
        }
    </style>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle sidebar on mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                const icon = this.querySelector('i');
                if (sidebar.classList.contains('show')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth < 768) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnToggle = (sidebarToggle && (sidebarToggle === event.target || sidebarToggle.contains(event.target)));
                
                if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    const icon = sidebarToggle.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
        
        // Handle window resize
        function handleResize() {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('show');
                const icon = sidebarToggle?.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        }
        
        window.addEventListener('resize', handleResize);
        
        // Hide loading screen when page is loaded
        document.documentElement.classList.add('js-loaded');
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    </script>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Mobile Toggle Button -->
    <button class="sidebar-toggle d-lg-none">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Sidebar Backdrop -->
    <div class="sidebar-backdrop"></div>
    
    <!-- Admin Layout -->
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header d-flex align-items-center p-3">
                <div class="sidebar-logo me-2">
                    <i class="fas fa-graduation-cap text-primary" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <h4 class="mb-0">LMS Admin</h4>
                    <small class="text-muted"><?php echo htmlspecialchars($_SESSION['TYPE'] ?? 'Admin'); ?></small>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <?php 
                $current_page = basename($_SERVER['PHP_SELF']);
                $nav_items = [
                    ['path' => 'index.php', 'icon' => 'tachometer-alt', 'text' => 'Dashboard'],
                    ['path' => 'courses.php', 'icon' => 'book', 'text' => 'Courses'],
                    ['path' => 'students.php', 'icon' => 'users', 'text' => 'Students'],
                    ['path' => 'topics.php', 'icon' => 'folder-open', 'text' => 'Topics'],
                    ['path' => 'quizzes.php', 'icon' => 'question-circle', 'text' => 'Quizzes'],
                    ['path' => 'tests.php', 'icon' => 'file-alt', 'text' => 'Tests'],
                    ['path' => 'users.php', 'icon' => 'user-cog', 'text' => 'Users'],
                    ['path' => '../logout.php', 'icon' => 'sign-out-alt', 'text' => 'Logout']
                ];
                
                foreach ($nav_items as $item):
                    $is_active = $current_page === basename($item['path']);
                ?>
                <a href="<?php echo $item['path']; ?>" 
                   class="nav-link<?php echo $is_active ? ' active' : ''; ?>">
                    <i class="fas fa-<?php echo $item['icon']; ?> fa-fw"></i>
                    <span><?php echo $item['text']; ?></span>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Mobile Toggle -->
            <button class="btn btn-primary d-md-none position-fixed" 
                    style="bottom: 1.5rem; right: 1.5rem; z-index: 1050; width: 45px; height: 45px; padding: 0; border-radius: 50%;"
                    id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Page Content -->
            <div class="container-fluid">
                <?php if (!empty($pageTitle)): ?>
                <div class="mb-4">
                    <h1 class="h4 mb-1"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <p class="text-muted small mb-0">Overview of your dashboard</p>
                </div>
                <?php endif; ?>