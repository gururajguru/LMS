<?php
// Database connection settings
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'lms_db';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get list of tables
$tables = [];
$result = $conn->query("SHOW TABLES");

if ($result) {
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    echo "<h2>Tables in $db database:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Check for required tables
    $required_tables = [
        'courses', 'lessons', 'topics', 'quizzes', 'tests', 
        'students', 'users', 'course_enrollments', 'lesson_progress'
    ];
    
    echo "<h3>Missing Tables:</h3>";
    echo "<ul>";
    $all_tables_exist = true;
    foreach ($required_tables as $table) {
        if (!in_array($table, $tables)) {
            echo "<li style='color:red;'>$table (Missing)</li>";
            $all_tables_exist = false;
        }
    }
    echo "</ul>";
    
    if ($all_tables_exist) {
        echo "<p style='color:green;'>All required tables exist!</p>";
    } else {
        echo "<p style='color:red;'>Some required tables are missing. Please check your database setup.</p>";
    }
    
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
