<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings (Admin)</title>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content" style="margin-left:200px; padding:40px;">
        <h2>Settings (Admin)</h2>
    </div>
</body>
</html> 