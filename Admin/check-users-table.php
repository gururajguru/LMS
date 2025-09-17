<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database class
require_once("../include/initialize.php");

// Set content type to plain text
header('Content-Type: text/plain');

// Check if user is logged in and is admin
if (!isset($_SESSION['USERID']) || ($_SESSION['TYPE'] != 'Administrator' && $_SESSION['TYPE'] != 'admin')) {
    echo "Unauthorized access";
    exit();
}

try {
    $database = new Database();
    $db = $database->conn;
    
    // Show the structure of the users table
    echo "=== Users Table Structure ===\n";
    $result = $db->query("DESCRIBE users");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
        }
    } else {
        echo "Error describing users table: " . $db->error . "\n";
    }
    
    // Show sample data
    echo "\n=== Sample User Data (first 5 rows) ===\n";
    $result = $db->query("SELECT * FROM users LIMIT 5");
    if ($result) {
        if ($result->num_rows > 0) {
            // Print headers
            $row = $result->fetch_assoc();
            echo implode(" | ", array_keys($row)) . "\n";
            echo str_repeat("-", 50) . "\n";
            
            // Print the first row
            echo implode(" | ", array_map(function($value) {
                return is_null($value) ? 'NULL' : substr(strval($value), 0, 30);
            }, $row));
            
            // Print remaining rows
            while ($row = $result->fetch_assoc()) {
                echo "\n" . implode(" | ", array_map(function($value) {
                    return is_null($value) ? 'NULL' : substr(strval($value), 0, 30);
                }, $row));
            }
        } else {
            echo "No users found in the database\n";
        }
    } else {
        echo "Error fetching users: " . $db->error . "\n";
    }
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
}
?>
