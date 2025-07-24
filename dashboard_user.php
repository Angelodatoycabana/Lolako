<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}
echo "Welcome, " . $_SESSION['username'] . "! This is the user dashboard.";
?>
<a href="logout.php">Logout</a> 