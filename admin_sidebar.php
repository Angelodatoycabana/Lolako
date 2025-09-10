<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 280px;
        height: 100vh;
        background: linear-gradient(180deg, #f59e0b 0%, #ea580c 100%);
        color: #fff;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        padding-top: 20px;
        z-index: 1000;
        transition: transform 0.3s ease;
        box-shadow: 2px 0 6px rgba(0,0,0,0.08);
        transform: translateX(-100%); /* Hidden by default */
    }
    
    .sidebar.show {
        transform: translateX(0); /* Show when toggled */
    }

    /* Sidebar overlay for mobile */
    .sidebar-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.35);
        display: none;
        z-index: 998;
    }
    .sidebar-overlay.show { display: block; }
    
    .sidebar-header {
        width: 100%;
        text-align: center;
        padding: 25px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.2);
        margin-bottom: 15px;
    }
    
    .sidebar-title {
        font-size: 28px;
        font-weight: bold;
        color: #fff;
        margin: 0;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        letter-spacing: 1px;
    }
    
    .sidebar a {
        text-decoration: none;
        color: #fff;
        width: 100%;
    }
    .sidebar .nav-item {
        padding: 14px 16px;
        width: calc(100% - 20px);
        margin: 2px 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        background: none;
        color: #ffffff;
        text-align: left;
        transition: background 0.2s ease, color 0.2s ease;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        border-radius: 6px;
    }
    .sidebar .nav-item:hover {
        background: rgba(255,255,255,0.14);
    }
    
    .user-icon {
        position: relative;
        margin-left: auto;
        width: 40px;
        height: 40px;
        background: #f97316;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.12);
        transition: background 0.2s ease;
    }
    
    .user-icon:hover { background: #ea580c; }
    
    .user-icon span { color: #fff; font-size: 20px; }
    
    .user-dropdown {
        position: absolute;
        top: 48px;
        right: 0;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        min-width: 150px;
        display: none;
        z-index: 1002;
    }
    
    .user-dropdown.show {
        display: block;
    }
    
    .user-dropdown-item {
        padding: 12px 16px;
        color: #333;
        text-decoration: none;
        display: block;
        transition: background 0.2s;
        border-bottom: 1px solid #eee;
    }
    
    .user-dropdown-item:last-child {
        border-bottom: none;
    }
    
    .user-dropdown-item:hover {
        background: #f8f9fa;
    }
    
    .user-dropdown-item.logout {
        color: #dc3545;
    }
    
    .user-dropdown-item.logout:hover {
        background: #f8d7da;
    }
    
    /* Top Header */
    .admin-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 60px;
        background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
        color: #fff;
        display: flex;
        align-items: center;
        padding: 0 16px;
        z-index: 999;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    
    .header-burger {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: background 0.2s;
        margin-right: 15px;
    }
    
    .header-burger:hover {
        background: rgba(255,255,255,0.1);
    }
    
    .header-title {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
        letter-spacing: 0.5px;
    }
    
    .header-spacer {
        flex: 1;
    }
    
    /* Burger button always visible */
    .header-burger {
        display: block;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .user-icon { display: none; }
        .sidebar { width: 80vw; }
    }
</style>
<!-- Top Header -->
<div class="admin-header">
    <button class="header-burger" onclick="toggleSidebar()">☰</button>
    <h1 class="header-title">Lolako</h1>
    <div class="header-spacer"></div>
    <div class="user-icon" onclick="toggleUserDropdown()">
        <span>&#128100;</span>
        <div class="user-dropdown" id="userDropdown">
            <a href="#" class="user-dropdown-item">Profile</a>
            <a href="#" class="user-dropdown-item">Settings</a>
            <a href="logout.php" class="user-dropdown-item logout">Logout</a>
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="sidebar" id="appSidebar">
    <div class="sidebar-header">
        <h1 class="sidebar-title">Lolako</h1>
    </div>
    <a href="dashboard_admin.php"><div class="nav-item">Dashboard</div></a>
    <a href="seniors_admin.php"><div class="nav-item">Senior Citizens</div></a>
    <a href="reports_admin.php"><div class="nav-item">Barangays</div></a>
    <a href="GenerateID_admin.php"><div class="nav-item">Generate Seniors ID</div></a>
    <a href="users_admin.php"><div class="nav-item">Users</div></a>
    <a href="settings_admin.php"><div class="nav-item">Settings</div></a>
</div>

 

<script>
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const userIcon = document.querySelector('.user-icon');
    const dropdown = document.getElementById('userDropdown');
    
    if (!userIcon.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Enhance sidebar toggle with overlay
const originalToggle = window.toggleSidebar;
window.toggleSidebar = function(){
    const sidebar = document.getElementById('appSidebar') || document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar || !overlay) { return originalToggle ? originalToggle() : null; }
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Close when clicking overlay
document.addEventListener('click', function(e){
    const overlay = document.getElementById('sidebarOverlay');
    const sidebar = document.getElementById('appSidebar');
    const burger = document.querySelector('.header-burger');
    if (!overlay || !sidebar) return;
    if (overlay.classList.contains('show') && (e.target === overlay)){
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }
    // Also close if user taps outside (not burger, not sidebar)
    if (overlay.classList.contains('show') && !sidebar.contains(e.target) && !burger.contains(e.target)){
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }
});
</script> 
