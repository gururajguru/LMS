<?php
// Core initialization file for the LMS system

// Define root path
define('ROOT_PATH', dirname(__FILE__) . '/../');
define('LIB_PATH', ROOT_PATH . 'include/');
define('ADMIN_PATH', ROOT_PATH . 'Admin/');
define('STUDENT_PATH', ROOT_PATH . 'Student/');

// Set web root for URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];
$web_root = $protocol . '://' . $host . dirname($script_name) . '/';
define('web_root', $web_root);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load core classes
require_once(LIB_PATH . "database.php");
require_once(LIB_PATH . "users.php");
require_once(LIB_PATH . "students.php");
require_once(LIB_PATH . "lessons.php");
require_once(LIB_PATH . "exercises.php");
require_once(LIB_PATH . "autonumbers.php");
require_once(LIB_PATH . "courses.php");
require_once(LIB_PATH . "Topic.php");
require_once(LIB_PATH . "quizzes.php");
require_once(LIB_PATH . "tests.php");

// Initialize database connection
$mydb = new Database();

// Helper functions
function redirect($url) {
    header("Location: " . $url);
    exit();
}

function message($msg, $type = 'info') {
    $_SESSION['message'] = $msg;
    $_SESSION['message_type'] = $type;
}

function check_message() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $msg = $_SESSION['message'];
        unset($_SESSION['message'], $_SESSION['message_type']);
        
        $alert_class = 'alert-info';
        switch ($type) {
            case 'success': $alert_class = 'alert-success'; break;
            case 'error': $alert_class = 'alert-danger'; break;
            case 'warning': $alert_class = 'alert-warning'; break;
        }
        
        echo "<div class='alert {$alert_class} alert-dismissible fade show' role='alert'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

function is_logged_in() {
    return isset($_SESSION['USERID']) || isset($_SESSION['StudentID']);
}

function is_admin() {
    return isset($_SESSION['TYPE']) && $_SESSION['TYPE'] == 'Administrator';
}

function is_student() {
    return isset($_SESSION['StudentID']);
}

function get_user_name() {
    if (isset($_SESSION['NAME'])) {
        return $_SESSION['NAME'];
    } elseif (isset($_SESSION['StudentID'])) {
        return $_SESSION['StudentID']; // You might want to fetch actual student name
    }
    return 'Guest';
}

// Security functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// File upload functions
function upload_file($file, $destination, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return false;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $destination . '/' . $filename;
    
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

// Date formatting
function format_date($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    $time = ($time < 1) ? 1 : $time;
    $tokens = array(
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );
    
    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
    }
}
?>