<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['USERID'])) {
    if ($_SESSION['TYPE'] == 'Administrator') {
        header('Location: admin/index.php');
    } else {
        header('Location: student/index.php');
    }
    exit;
}

// If not logged in, redirect to login page
header('Location: login.php');
exit;
?>

