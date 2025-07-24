<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
echo "Welcome, " . $_SESSION['username'] . "! This is the admin dashboard.";
?>
<a href="logout.php">Logout</a> 