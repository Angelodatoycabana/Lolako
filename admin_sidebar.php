<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 200px;
        height: 100vh;
        background: #2d1fff;
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding-top: 30px;
    }
    .sidebar a {
        text-decoration: none;
        color: #fff;
        width: 100%;
    }
    .sidebar .nav-item {
        padding: 18px 30px;
        width: 100%;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        border: none;
        background: none;
        color: #fff;
        text-align: left;
        transition: background 0.2s;
    }
    .sidebar .nav-item:hover {
        background: #3d2fff;
    }
    .user-icon {
        position: absolute;
        top: 20px;
        right: 30px;
        width: 40px;
        height: 40px;
        background: #2d1fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .user-icon span {
        color: #fff;
        font-size: 28px;
    }
</style>
<div class="sidebar">
    <a href="dashboard_admin.php"><div class="nav-item">Dashboard</div></a>
    <a href="seniors_admin.php"><div class="nav-item">Seniors</div></a>
    <a href="registration_admin.php"><div class="nav-item">Registration</div></a>
    <a href="reports_admin.php"><div class="nav-item">Reports</div></a>
    <a href="support_admin.php"><div class="nav-item">Support</div></a>
    <a href="users_admin.php"><div class="nav-item">Users</div></a>
    <a href="settings_admin.php"><div class="nav-item">Settings</div></a>
</div>
<div class="user-icon" style="right: 30px; top: 20px; position: fixed;">
    <span>&#128100;</span>
</div> 