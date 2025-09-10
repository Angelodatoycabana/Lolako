<?php
session_start();
if (!isset($_SESSION["username"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}
include "db.php";

// Get search parameter
$search = $_GET["search"] ?? "";

// Fetch male seniors only
$where = "WHERE deleted=0 AND gender = 'Male'";
$params = [];
$types = "";

// Add search filter if provided
if ($search) {
    $where .= " AND (firstname LIKE ? OR middlename LIKE ? OR lastname LIKE ? OR barangay LIKE ? OR number LIKE ? OR age LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $types = "ssssss";
}

$sql = "SELECT * FROM seniors $where ORDER BY id ASC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$seniors = $stmt->get_result();

// Get total count for male seniors
$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM seniors WHERE deleted=0 AND gender = 'Male'");
$count_stmt->execute();
$total_count = $count_stmt->get_result()->fetch_assoc()["total"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Male Senior Citizens</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .header {
            background: linear-gradient(135deg, #fb923c, #f97316);
            color: white;
            padding: 20px;
            margin-left: 200px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            margin-left: 200px;
            padding: 20px;
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
            background: #f97316;
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
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
        }
        
        .search-box button {
            padding: 8px 15px;
            background: #f97316;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-box button:hover {
            opacity: 0.9;
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
        }
        
        tr:hover {
            background-color: #f8f9fa;
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
    
    <div class="header">
        <h1> Male Senior Citizens</h1>
        <p>Complete list of male senior citizens in the system</p>
    </div>
    
    <div class="container">
        <a href="dashboard_admin.php" class="back-btn"> Back to Dashboard</a>
        
        <div class="stats-bar">
            <div class="stats-info">
                <div class="gender-icon"></div>
                <div class="count-info">
                    <h3>Male Seniors</h3>
                    <p>Total: <?php echo $total_count; ?> <?php echo $total_count === 1 ? 'person' : 'people'; ?></p>
                </div>
            </div>
            
            <form class="search-box" method="get" action="male_seniors.php">
                <input type="text" name="search" placeholder="Search male seniors..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
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
                                <td><?php echo $i++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row["firstname"]); ?></strong>
                                    <?php if (!empty($row["middlename"])): ?>
                                        <?php echo htmlspecialchars($row["middlename"]); ?>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($row["lastname"]); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row["age"]); ?> years old</td>
                                <td>
                                    <span style="display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; background: #e3f2fd; color: #1976d2;">
                                        Male
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row["number"]); ?></td>
                                <td><?php echo htmlspecialchars($row["barangay"]); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon"></div>
                    <h3>No male seniors found</h3>
                    <p>
                        <?php if ($search): ?>
                            No male seniors match your search criteria.
                        <?php else: ?>
                            There are currently no male senior citizens in the system.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
