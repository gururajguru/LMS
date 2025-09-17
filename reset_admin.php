<?php
require_once 'include/database.php';

$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $db = new Database();
    $sql = "UPDATE users SET password = ? WHERE username = 'admin' AND user_type = 'Administrator'";
    $stmt = $db->prepare($sql);
    $db->execute_prepared($stmt, [$hashed_password]);
    
    echo "<h2>Admin Password Reset</h2>";
    echo "<p>Password for 'admin' has been reset to: <strong>admin123</strong></p>";
    echo "<p>New hash: $hashed_password</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color:red;'>Error: " . $e->getMessage() . "</div>";
}
?>
