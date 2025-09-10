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
    <title>Barangays (Admin)</title>
    <style>
        body { margin:0; padding:0; padding-top:60px; font-family: Arial, sans-serif; }
        .main-content { margin-left:0; padding:80px 40px 40px 40px; transition: margin-left 0.3s ease; }
        .main-content.sidebar-open { margin-left:220px; }
        @media (max-width:768px) { .main-content { margin-left:0 !important; padding:80px 20px 20px 20px; } }
    </style>
    
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content" id="mainContent">
        <h2>Barangays (Admin)</h2>
    </div>
    <script>
        function toggleSidebar(){
            const sidebar=document.querySelector('.sidebar');
            const main=document.getElementById('mainContent');
            sidebar.classList.toggle('show');
            if (window.innerWidth>768) { main.classList.toggle('sidebar-open'); }
        }
        document.addEventListener('click',function(e){
            const sidebar=document.querySelector('.sidebar');
            const burger=document.querySelector('.header-burger');
            const main=document.getElementById('mainContent');
            if(!sidebar.contains(e.target) && !burger.contains(e.target)){
                sidebar.classList.remove('show');
                if(window.innerWidth>768){ main.classList.remove('sidebar-open'); }
            }
        });
        window.addEventListener('resize',function(){
            const sidebar=document.querySelector('.sidebar');
            const main=document.getElementById('mainContent');
            if(window.innerWidth<=768){ main.classList.remove('sidebar-open'); }
            else if(sidebar.classList.contains('show')){ main.classList.add('sidebar-open'); }
        });
    </script>
</body>
</html> 