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

// Function to check if a column exists in a table
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result->num_rows > 0;
}

// Check courses table structure
$required_columns = [
    'id', 'name', 'code', 'description', 'duration_weeks', 'level', 'status',
    'created_at', 'updated_at'
];

echo "<h2>Courses Table Structure:</h2>";
$result = $conn->query("DESCRIBE courses");

if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
        $existing_columns[] = $row['Field'];
    }
    echo "</table>";
    
    // Check for missing columns
    $missing_columns = array_diff($required_columns, $existing_columns);
    
    if (count($missing_columns) > 0) {
        echo "<h3 style='color:red;'>Missing Columns:</h3>";
        echo "<ul>";
        foreach ($missing_columns as $column) {
            echo "<li>$column</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:green;'>All required columns exist in the courses table.</p>";
    }
    
    // Check for sample data
    $result = $conn->query("SELECT COUNT(*) as count FROM courses");
    $row = $result->fetch_assoc();
    $count = $row['count'];
    
    if ($count == 0) {
        echo "<h3 style='color:orange;'>No courses found in the database.</h3>";
        echo "<p>This could be why you're not seeing any courses in the admin panel.</p>";
    } else {
        echo "<p>Found $count courses in the database.</p>";
        
        // Show first few courses as sample
        $result = $conn->query("SELECT id, name, code, status FROM courses LIMIT 5");
        echo "<h3>Sample Courses:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Status</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['code']) . "</td>";
            echo "<td>" . $row['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "Error describing table: " . $conn->error;
}

$conn->close();
?>
