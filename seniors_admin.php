<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}
include "db.php";

// Handle add, edit, delete (soft) actions
$action = $_GET["action"] ?? "";
$id = $_GET["id"] ?? "";
$search = $_GET["search"] ?? "";
$filter = $_GET["filter"] ?? "";

// Add Senior
if ($action === "add" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $firstname = $_POST["firstname"];
    $middlename = $_POST["middlename"];
    $lastname = $_POST["lastname"];
    $age = $_POST["age"];
    $gender = $_POST["gender"];
    $barangay = $_POST["barangay"];
    $number = $_POST["number"];
    $stmt = $conn->prepare("INSERT INTO seniors (firstname, middlename, lastname, age, gender, barangay, number) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisss", $firstname, $middlename, $lastname, $age, $gender, $barangay, $number);
    $stmt->execute();
    header("Location: seniors_admin.php");
    exit();
}

// Edit Senior
if ($action === "edit" && $_SERVER["REQUEST_METHOD"] === "POST" && $id) {
    $firstname = $_POST["firstname"];
    $middlename = $_POST["middlename"];
    $lastname = $_POST["lastname"];
    $age = $_POST["age"];
    $gender = $_POST["gender"];
    $barangay = $_POST["barangay"];
    $number = $_POST["number"];
    $stmt = $conn->prepare("UPDATE seniors SET firstname=?, middlename=?, lastname=?, age=?, gender=?, barangay=?, number=? WHERE id=?");
    $stmt->bind_param("sssisssi", $firstname, $middlename, $lastname, $age, $gender, $barangay, $number, $id);
    $stmt->execute();
    header("Location: seniors_admin.php");
    exit();
}

// Soft Delete Senior
if ($action === "delete" && $id) {
    $stmt = $conn->prepare("UPDATE seniors SET deleted=1 WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: seniors_admin.php");
    exit();
}

// Fetch seniors (not deleted) with optional filter
$where = "WHERE deleted=0";
$params = [];
$types = "";

// Add gender filter if specified
if ($filter === "male" || $filter === "female") {
    $where .= " AND gender = ?";
    $params[] = ucfirst($filter);
    $types .= "s";
}

// Add search filter
if ($search) {
    $where .= " AND (firstname LIKE ? OR middlename LIKE ? OR lastname LIKE ? OR barangay LIKE ? OR number LIKE ? OR age LIKE ? OR gender LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "sssssss";
}

$sql = "SELECT * FROM seniors $where ORDER BY id ASC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$seniors = $stmt->get_result();

// Fetch single senior for edit
$edit_senior = null;
if ($action === "edit" && $id) {
    $stmt = $conn->prepare("SELECT * FROM seniors WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_senior = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seniors Management (Admin)</title>
    <style>
        .table-container { background: #fff; border-radius: 8px; box-shadow: 0 0 8px #e0e0e0; padding: 16px; margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 720px; }
        th, td { padding: 15px 12px; border-bottom: 1px solid #e0e0e0; text-align: left; white-space: nowrap; }
        th { background: #f5f5f5; font-weight: bold; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8f9fa; }
        .actions { display: flex; gap: 8px; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-add { background: #f97316; color: #fff; margin-bottom: 0; }
        .btn-edit { background: #ffc107; color: #222; }
        .btn-delete { background: #f44336; color: #fff; }
        .search-box { margin: 0; }
        .search-box input { padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc; }
        .form-popup { background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 20px; max-width: 400px; margin: 20px auto; }
        .form-popup input, .form-popup select { width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ccc; box-sizing: border-box; }
        .form-popup label { font-weight: bold; }
        .filter-info { background: #e3f2fd; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .table-actions { display:flex; align-items:center; justify-content: space-between; gap:12px; margin-bottom:12px; }
        .table-actions-left { display:flex; align-items:center; gap:12px; }
        .table-actions-right { display:flex; align-items:center; gap:8px; }
        
        /* Sidebar responsive behavior */
        .main-content.sidebar-open {
            margin-left: 220px !important;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
            }
            .table-actions { flex-direction: column; align-items: stretch; }
            .table-actions-right, .table-actions-left { width:100%; }
            .search-box { width:100%; display:flex; flex-direction:column; gap:8px; }
            .search-box input { width:100%; }
            .btn { width:100%; text-align:center; }
            table { min-width: 600px; }
            th, td { padding: 12px 10px; font-size: 14px; }
        }

        /* Extra-small stacked card layout to preserve all columns */
        @media (max-width: 480px) {
            .table-container { overflow-x: visible; }
            thead { display: none; }
            table { min-width: 100%; }
            table, tbody, tr, td { display: block; width: 100%; }
            tr { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 14px; overflow: hidden; border: 1px solid #eee; }
            /* Hide index column */
            td:nth-child(1) { display: none; }
            td { padding: 12px 14px; border-bottom: 1px solid #f0f0f0; white-space: normal; }
            td:last-child { border-bottom: none; }
            td::before { content: attr(data-label); display: block; font-weight: 600; color: #64748b; margin-bottom: 4px; }
        }
    </style>
</head>
<body>
    <?php include "admin_sidebar.php"; ?>
    <div class="main-content" style="margin-left:0; padding:80px 40px 40px 40px; transition: margin-left 0.3s ease;" id="mainContent">
        <h2>Seniors Management (Admin)</h2>
        
        <?php if ($filter): ?>
            <div class="filter-info">
                <strong>Filtered by: <?php echo ucfirst($filter); ?> Seniors</strong>
                <a href="seniors_admin.php" style="float: right; color: #ea580c;">Clear Filter</a>
            </div>
        <?php endif; ?>
        
        <?php if ($action === "add" || ($action === "edit" && $edit_senior)): ?>
            <div class="form-popup">
                <form method="post">
                    <label>First Name</label>
                    <input type="text" name="firstname" required value="<?= htmlspecialchars($edit_senior["firstname"] ?? "") ?>">
                    
                    <label>Middle Name</label>
                    <input type="text" name="middlename" value="<?= htmlspecialchars($edit_senior["middlename"] ?? "") ?>">
                    
                    <label>Last Name</label>
                    <input type="text" name="lastname" required value="<?= htmlspecialchars($edit_senior["lastname"] ?? "") ?>">
                    
                    <label>Age</label>
                    <input type="number" name="age" required value="<?= htmlspecialchars($edit_senior["age"] ?? "") ?>">
                    
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?= (isset($edit_senior["gender"]) && $edit_senior["gender"] === "Male") ? "selected" : "" ?>>Male</option>
                        <option value="Female" <?= (isset($edit_senior["gender"]) && $edit_senior["gender"] === "Female") ? "selected" : "" ?>>Female</option>
                    </select>
                    
                    <label>Barangay</label>
                    <input type="text" name="barangay" required value="<?= htmlspecialchars($edit_senior["barangay"] ?? "") ?>">
                    
                    <label>Number</label>
                    <input type="text" name="number" required value="<?= htmlspecialchars($edit_senior["number"] ?? "") ?>">
                    
                    <button class="btn btn-add" type="submit">Save</button>
                    <a href="seniors_admin.php" class="btn">Cancel</a>
                </form>
            </div>
        <?php else: ?>
            <div class="table-container">
                <div class="table-actions">
                    <div class="table-actions-left">
                        <form class="search-box" method="get" action="seniors_admin.php">
                            <?php if ($filter): ?>
                                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                            <?php endif; ?>
                            <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn" type="submit">Search</button>
                        </form>
                    </div>
                    <div class="table-actions-right">
                        <a href="seniors_admin.php?action=add" class="btn btn-add">+ Add Senior</a>
                    </div>
                </div>
                <table>
                    <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Age</th>
                        <th>Gender</th>
                        <th>Barangay</th>
                        <th>Number</th>
                        <th>Actions</th>
                    </tr>
                    <?php $i = 1; $found = false; while ($row = $seniors->fetch_assoc()): $found = true; ?>
                        <tr>
                            <td data-label="#"><?= $i++ ?></td>
                            <td data-label="First Name"><?= htmlspecialchars($row["firstname"]) ?></td>
                            <td data-label="Middle Name"><?= htmlspecialchars($row["middlename"]) ?></td>
                            <td data-label="Last Name"><?= htmlspecialchars($row["lastname"]) ?></td>
                            <td data-label="Age"><?= htmlspecialchars($row["age"]) ?></td>
                            <td data-label="Gender"><?= htmlspecialchars($row["gender"]) ?></td>
                            <td data-label="Barangay"><?= htmlspecialchars($row["barangay"]) ?></td>
                            <td data-label="Number"><?= htmlspecialchars($row["number"]) ?></td>
                            <td class="actions" data-label="Actions">
                                <a href="seniors_admin.php?action=edit&id=<?= $row["id"] ?>" class="btn btn-edit">Edit</a>
                                <a href="seniors_admin.php?action=delete&id=<?= $row["id"] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this senior?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
                <?php if (!$found): ?>
                    <p>No seniors found.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Sidebar Toggle Function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('show');
            
            // Adjust content margin based on sidebar state
            if (window.innerWidth > 768) {
                mainContent.classList.toggle('sidebar-open');
            }
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const headerBurger = document.querySelector('.header-burger');
            const mainContent = document.getElementById('mainContent');
            
            if (!sidebar.contains(event.target) && 
                !headerBurger.contains(event.target)) {
                sidebar.classList.remove('show');
                
                // Adjust content margin
                if (window.innerWidth > 768) {
                    mainContent.classList.remove('sidebar-open');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth <= 768) {
                // On mobile, always remove margin
                mainContent.classList.remove('sidebar-open');
            } else if (sidebar.classList.contains('show')) {
                // On desktop, add margin if sidebar is open
                mainContent.classList.add('sidebar-open');
            }
        });
    </script>
</body>
</html>
