<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Handle add, edit, delete (soft) actions
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$search = $_GET['search'] ?? '';

// Add Senior
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $age = $_POST['age'];
    $barangay = $_POST['barangay'];
    $number = $_POST['number'];
    $stmt = $conn->prepare("INSERT INTO seniors (firstname, middlename, lastname, age, barangay, number) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiss", $firstname, $middlename, $lastname, $age, $barangay, $number);
    $stmt->execute();
    header('Location: seniors_admin.php');
    exit();
}

// Edit Senior
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $age = $_POST['age'];
    $barangay = $_POST['barangay'];
    $number = $_POST['number'];
    $stmt = $conn->prepare("UPDATE seniors SET firstname=?, middlename=?, lastname=?, age=?, barangay=?, number=? WHERE id=?");
    $stmt->bind_param("sssissi", $firstname, $middlename, $lastname, $age, $barangay, $number, $id);
    $stmt->execute();
    header('Location: seniors_admin.php');
    exit();
}

// Soft Delete Senior
if ($action === 'delete' && $id) {
    $stmt = $conn->prepare("UPDATE seniors SET deleted=1 WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: seniors_admin.php');
    exit();
}

// Fetch seniors (not deleted)
$where = "WHERE deleted=0";
$params = [];
$types = '';
if ($search) {
    $where .= " AND (firstname LIKE ? OR middlename LIKE ? OR lastname LIKE ? OR barangay LIKE ? OR number LIKE ? OR age LIKE ? )";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
    $types = 'ssssss';
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
if ($action === 'edit' && $id) {
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
    <title>Seniors Management (Admin)</title>
    <style>
        .table-container { background: #fff; border-radius: 8px; box-shadow: 0 0 8px #e0e0e0; padding: 24px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        th { background: #f5f5f5; }
        tr:last-child td { border-bottom: none; }
        .actions { display: flex; gap: 8px; }
        .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-add { background: #2d1fff; color: #fff; float: right; margin-bottom: 10px; }
        .btn-edit { background: #ffc107; color: #222; }
        .btn-delete { background: #f44336; color: #fff; }
        .search-box { float: right; margin-bottom: 10px; }
        .search-box input { padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc; }
        .form-popup { background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 20px; max-width: 400px; margin: 20px auto; }
        .form-popup input { width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ccc; }
        .form-popup label { font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content" style="margin-left:200px; padding:40px;">
        <h2>Seniors Management (Admin)</h2>
        <?php if ($action === 'add' || ($action === 'edit' && $edit_senior)): ?>
            <div class="form-popup">
                <form method="post">
                    <label>First Name</label>
                    <input type="text" name="firstname" required value="<?= htmlspecialchars($edit_senior['firstname'] ?? '') ?>">
                    <label>Middle Name</label>
                    <input type="text" name="middlename" value="<?= htmlspecialchars($edit_senior['middlename'] ?? '') ?>">
                    <label>Last Name</label>
                    <input type="text" name="lastname" required value="<?= htmlspecialchars($edit_senior['lastname'] ?? '') ?>">
                    <label>Age</label>
                    <input type="number" name="age" required value="<?= htmlspecialchars($edit_senior['age'] ?? '') ?>">
                    <label>Barangay</label>
                    <input type="text" name="barangay" required value="<?= htmlspecialchars($edit_senior['barangay'] ?? '') ?>">
                    <label>Number</label>
                    <input type="text" name="number" required value="<?= htmlspecialchars($edit_senior['number'] ?? '') ?>">
                    <button class="btn btn-add" type="submit">Save</button>
                    <a href="seniors_admin.php" class="btn">Cancel</a>
                </form>
            </div>
        <?php else: ?>
            <div class="table-container">
                <form class="search-box" method="get" action="seniors_admin.php">
                    <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn" type="submit">Search</button>
                </form>
                <a href="seniors_admin.php?action=add" class="btn btn-add">+ Add Senior</a>
                <table>
                    <tr>
                        <th>#</th>
                        <th>First Name</th>
                        <th>Middle Name</th>
                        <th>Last Name</th>
                        <th>Age</th>
                        <th>Barangay</th>
                        <th>Number</th>
                        <th>Actions</th>
                    </tr>
                    <?php $i = 1; $found = false; while ($row = $seniors->fetch_assoc()): $found = true; ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['firstname']) ?></td>
                            <td><?= htmlspecialchars($row['middlename']) ?></td>
                            <td><?= htmlspecialchars($row['lastname']) ?></td>
                            <td><?= htmlspecialchars($row['age']) ?></td>
                            <td><?= htmlspecialchars($row['barangay']) ?></td>
                            <td><?= htmlspecialchars($row['number']) ?></td>
                            <td class="actions">
                                <a href="seniors_admin.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-edit">Edit</a>
                                <a href="seniors_admin.php?action=delete&id=<?= $row['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this senior?');">Delete</a>
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
</body>
</html> 