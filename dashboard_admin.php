<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Get counts
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM seniors WHERE deleted=0");
$stmt->execute();
$active_seniors = $stmt->get_result()->fetch_assoc()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM seniors WHERE deleted=1");
$stmt->execute();
$deleted_seniors = $stmt->get_result()->fetch_assoc()['total'];

// Get deleted seniors for modal
$search = $_GET['search'] ?? '';
$where = "WHERE deleted=1";
$params = [];
$types = '';
if ($search) {
    $where .= " AND (firstname LIKE ? OR middlename LIKE ? OR lastname LIKE ? OR barangay LIKE ? OR number LIKE ? OR age LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $types = 'ssssss';
}
$sql = "SELECT * FROM seniors $where ORDER BY id DESC";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$deleted_list = $stmt->get_result();

// Get seniors count by barangay
$stmt = $conn->prepare("SELECT barangay, COUNT(*) as count FROM seniors WHERE deleted=0 GROUP BY barangay ORDER BY count DESC");
$stmt->execute();
$barangay_data = $stmt->get_result();
$barangays = [];
$counts = [];
while ($row = $barangay_data->fetch_assoc()) {
    $barangays[] = $row['barangay'];
    $counts[] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; border-radius: 8px; box-shadow: 0 0 8px #e0e0e0; padding: 20px; flex: 1; text-align: center; cursor: pointer; }
        .stat-card:hover { box-shadow: 0 0 12px #ccc; }
        .stat-number { font-size: 2em; font-weight: bold; color: #2d1fff; }
        .stat-label { color: #666; margin-top: 5px; }
        .chart-container { 
            background: #fff; 
            border-radius: 8px; 
            box-shadow: 0 0 8px #e0e0e0; 
            padding: 20px; 
            margin-bottom: 20px; 
            width: 400px; 
            height: 300px; 
            float: left; 
        }
        .bar-chart-container { 
            background: #2c2c2c; 
            border-radius: 8px; 
            box-shadow: 0 0 8px #e0e0e0; 
            padding: 20px; 
            margin-bottom: 20px; 
            width: 600px; 
            height: 400px; 
            float: left; 
            margin-right: 20px;
        }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background: #fff; margin: 5% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 800px; max-height: 70vh; overflow-y: auto; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        .search-box { margin-bottom: 15px; }
        .search-box input { padding: 8px; border-radius: 4px; border: 1px solid #ccc; width: 200px; }
        .search-box button { padding: 8px 15px; background: #2d1fff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        th { background: #f5f5f5; }
        .logout-link { color: #2d1fff; text-decoration: none; font-weight: bold; margin-top: 20px; display: inline-block; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content" style="margin-left:200px; padding:40px;">
        <h2>Admin Dashboard</h2>
        <p>Welcome, <?php echo $_SESSION['username']; ?>!</p>
        
        <div class="stats-container">
            <div class="stat-card" onclick="showDeletedSeniors()">
                <div class="stat-number"><?= $deleted_seniors ?></div>
                <div class="stat-label">Deleted Seniors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $active_seniors ?></div>
                <div class="stat-label">Active Seniors</div>
            </div>
        </div>
        
        <div class="bar-chart-container">
            <canvas id="barChart" width="600" height="400"></canvas>
        </div>
        
        <div class="chart-container">
            <canvas id="seniorsChart" width="400" height="200"></canvas>
        </div>
        
        <a href="logout.php" class="logout-link">Logout</a>
    </div>

    <!-- Modal for deleted seniors -->
    <div id="deletedModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Deleted Senior Citizens</h3>
            
            <form class="search-box" method="get" action="dashboard_admin.php">
                <input type="text" name="search" placeholder="Search deleted seniors..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
            </form>
            
            <table>
                <tr>
                    <th>#</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Last Name</th>
                    <th>Age</th>
                    <th>Barangay</th>
                    <th>Number</th>
                </tr>
                <?php $i = 1; $found = false; while ($row = $deleted_list->fetch_assoc()): $found = true; ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['firstname']) ?></td>
                        <td><?= htmlspecialchars($row['middlename']) ?></td>
                        <td><?= htmlspecialchars($row['lastname']) ?></td>
                        <td><?= htmlspecialchars($row['age']) ?></td>
                        <td><?= htmlspecialchars($row['barangay']) ?></td>
                        <td><?= htmlspecialchars($row['number']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <?php if (!$found): ?>
                <p>No deleted seniors found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Chart
        const ctx = document.getElementById('seniorsChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active Seniors', 'Deleted Seniors'],
                datasets: [{
                    data: [<?= $active_seniors ?>, <?= $deleted_seniors ?>],
                    backgroundColor: ['#4CAF50', '#f44336'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($barangays) ?>,
                datasets: [{
                    label: 'Number of Seniors',
                    data: <?= json_encode($counts) ?>,
                    backgroundColor: 'rgba(52, 12, 233, 0.8)', // Green
                    borderColor: 'rgb(52, 12, 233)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Seniors'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Barangay'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // Modal functions
        function showDeletedSeniors() {
            document.getElementById('deletedModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('deletedModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deletedModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html> 