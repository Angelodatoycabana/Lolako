<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}
include "db.php";

// Get filter parameter
$filter = $_GET["filter"] ?? "";
$search = $_GET["search"] ?? "";

// Validate filter
if (!in_array($filter, ["male", "female", "all"])) {
    header("Location: dashboard_admin.php");
    exit();
}

// Fetch seniors based on filter
if ($filter === "all") {
    $where = "WHERE deleted=0";
    $params = [];
    $types = "";
} else {
    $where = "WHERE deleted=0 AND gender = ?";
    $params = [ucfirst($filter)];
    $types = "s";
}

// Add search filter if provided
if ($search) {
    $where .= " AND (firstname LIKE ? OR middlename LIKE ? OR lastname LIKE ? OR barangay LIKE ? OR number LIKE ? OR age LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "ssssss";
}

$sql = "SELECT * FROM seniors $where ORDER BY id ASC";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$seniors = $stmt->get_result();

// Get total count for this gender
$count_stmt = $conn->prepare(
    $filter === "all"
        ? "SELECT COUNT(*) as total FROM seniors WHERE deleted=0"
        : "SELECT COUNT(*) as total FROM seniors WHERE deleted=0 AND gender = ?"
);
if ($filter === "all") {
    // no bind needed
} else {
    $genderParam = ucfirst($filter);
    $count_stmt->bind_param("s", $genderParam);
}
$count_stmt->execute();
$total_count = $count_stmt->get_result()->fetch_assoc()["total"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($filter); ?> Seniors List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, <?php echo $filter === 'male' ? '#fb923c, #f97316' : '#f59e0b, #ea580c'; ?>);
            color: white;
            padding: 20px;
            margin-left: 0;
            margin-top: 60px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: margin-left 0.3s ease;
        }
        
        .header.sidebar-open {
            margin-left: 220px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .header p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .container {
            margin-left: 0;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }
        
        .container.sidebar-open {
            margin-left: 220px;
        }
        
        .stats-bar {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stats-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .gender-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: <?php echo $filter === 'male' ? '#f97316' : '#ea580c'; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }
        
        .count-info h3 {
            margin: 0;
            color: #333;
        }
        
        .count-info p {
            margin: 5px 0 0 0;
            color: #666;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 280px;
        }
        
        .search-box button {
            padding: 10px 14px;
            background: #ea580c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .search-box button:hover {
            opacity: 0.9;
        }

        .print-btn {
            padding: 8px 14px;
            background: #334155;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .print-btn:hover { opacity: 0.9; }

        .print-meta { display: none; margin: 10px 0; color: #555; }

        @media print {
            .sidebar, .back-btn, .search-box, .print-btn { display: none !important; }
            .header, .container { margin-left: 0 !important; }
            .print-meta { display: block; }
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            white-space: nowrap;
        }
        
        tr:hover { background-color: #f8f9fa; }

        /* Mobile tweaks */
        @media (max-width: 768px) {
            .header { margin-top: 60px; }
            .stats-bar { flex-direction: column; align-items: stretch; gap: 12px; }
            .search-box { flex-direction: column; align-items: stretch; gap: 8px; }
            .search-box input { width: 100%; }
            .search-box button { width: 100%; }
            .back-btn { width: 100%; text-align: center; margin-bottom: 10px; }
            table { min-width: 600px; }
            th, td { padding: 12px 10px; }
            /* Hide Barangay on small screens */
            td:nth-child(6), th:nth-child(6) { display: none; }
        }

        /* Extra-small screens: optimize table visibility */
        @media (max-width: 480px) {
            table { min-width: 100%; }
            th, td { padding: 10px 8px; font-size: 13px; }
            /* Switch to stacked cards so all fields remain visible */
            thead { display: none; }
            table, tbody, tr, td { display: block; width: 100%; }
            tr { background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 14px; overflow: hidden; border: 1px solid #eee; }
            /* Hide the index number row for clarity */
            td:nth-child(1) { display: none; }
            td { padding: 12px 14px; border-bottom: 1px solid #f0f0f0; }
            td:last-child { border-bottom: none; }
            /* Label above value for clean stacking */
            td::before { content: attr(data-label); display: block; font-weight: 600; color: #64748b; margin-bottom: 4px; }
            /* Allow wrapping for long fields */
            td { white-space: normal; word-break: break-word; }
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-data-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include "admin_sidebar.php"; ?>
    
    <div class="header" id="pageHeader">
        <h1><?php echo ucfirst($filter); ?> Senior Citizens</h1>
        <p>Complete list of <?php echo strtolower($filter); ?> senior citizens in the system</p>
    </div>
    
    <div class="container" id="pageContainer">
        <a href="dashboard_admin.php" class="back-btn"> Back to Dashboard</a>
        <div class="print-meta">Printed on: <span id="printedAt"></span></div>
        
        <div class="stats-bar">
            <div class="stats-info">
                <div class="gender-icon">
                    <?php echo $filter === 'male' ? '' : ''; ?>
                </div>
                <div class="count-info">
                    <h3><?php echo ucfirst($filter); ?> Seniors</h3>
                    <p>Total: <?php echo $total_count; ?> <?php echo $total_count === 1 ? 'person' : 'people'; ?></p>
                </div>
            </div>
            
            <form class="search-box" method="get" action="seniors_view.php">
                <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                <input type="text" name="search" placeholder="Search <?php echo $filter; ?> seniors..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
                <button type="button" class="print-btn" onclick="printSeniors()">🖨️ Print</button>
            </form>
        </div>
        
        <div class="table-container">
            <?php if ($seniors->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Phone Number</th>
                            <th>Barangay Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = $seniors->fetch_assoc()): ?>
                            <tr>
                                <td data-label="#"><?php echo $i++; ?></td>
                                <td data-label="Full Name">
                                    <strong><?php echo htmlspecialchars($row["firstname"]); ?></strong>
                                    <?php if (!empty($row["middlename"])): ?>
                                        <?php echo htmlspecialchars($row["middlename"]); ?>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($row["lastname"]); ?>
                                </td>
                                <td data-label="Age"><?php echo htmlspecialchars($row["age"]); ?> years old</td>
                                <td data-label="Gender">
                                    <span style="display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; background: <?php echo $row["gender"] === 'Male' ? '#e3f2fd' : '#f3e5f5'; ?>; color: <?php echo $row["gender"] === 'Male' ? '#1976d2' : '#7b1fa2'; ?>;">
                                        <?php echo htmlspecialchars($row["gender"]); ?>
                                    </span>
                                </td>
                                <td data-label="Phone Number"><?php echo htmlspecialchars($row["number"]); ?></td>
                                <td data-label="Barangay Location"><?php echo htmlspecialchars($row["barangay"]); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon"><?php echo $filter === 'male' ? '' : ''; ?></div>
                    <h3>No <?php echo $filter; ?> seniors found</h3>
                    <p>
                        <?php if ($search): ?>
                            No <?php echo $filter; ?> seniors match your search criteria.
                        <?php else: ?>
                            There are currently no <?php echo $filter; ?> senior citizens in the system.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function printSeniors() {
            var el = document.getElementById('printedAt');
            if (el) { el.textContent = new Date().toLocaleString(); }
            window.print();
        }
        
        // Sidebar Toggle Function
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const pageHeader = document.getElementById('pageHeader');
            const pageContainer = document.getElementById('pageContainer');
            
            sidebar.classList.toggle('show');
            
            // Adjust content margin based on sidebar state
            if (window.innerWidth > 768) {
                pageHeader.classList.toggle('sidebar-open');
                pageContainer.classList.toggle('sidebar-open');
            }
        }

        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const headerBurger = document.querySelector('.header-burger');
            const pageHeader = document.getElementById('pageHeader');
            const pageContainer = document.getElementById('pageContainer');
            
            if (!sidebar.contains(event.target) && 
                !headerBurger.contains(event.target)) {
                sidebar.classList.remove('show');
                
                // Adjust content margin
                if (window.innerWidth > 768) {
                    pageHeader.classList.remove('sidebar-open');
                    pageContainer.classList.remove('sidebar-open');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.querySelector('.sidebar');
            const pageHeader = document.getElementById('pageHeader');
            const pageContainer = document.getElementById('pageContainer');
            
            if (window.innerWidth <= 768) {
                // On mobile, always remove margin
                pageHeader.classList.remove('sidebar-open');
                pageContainer.classList.remove('sidebar-open');
            } else if (sidebar.classList.contains('show')) {
                // On desktop, add margin if sidebar is open
                pageHeader.classList.add('sidebar-open');
                pageContainer.classList.add('sidebar-open');
            }
        });
    </script>
</body>
</html>
