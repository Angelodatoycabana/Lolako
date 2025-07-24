<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            if ($row['role'] == 'admin') {
                header("Location: dashboard_admin.php");
            } else {
                header("Location: dashboard_user.php");
            }
            exit();
        }
    }
    $error = "Invalid credentials!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #fff;
        }
        .container {
            display: flex;
            height: 100vh;
        }
        .left {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #fff;
        }
        .right {
            flex: 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .login-box {
            background: #f5f5f5;
            border: 3px solid #3d2fff;
            border-radius: 15px;
            padding: 40px 30px 30px 30px;
            width: 320px;
            box-sizing: border-box;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .login-box h3 {
            margin: 0 0 20px 0;
            font-weight: normal;
            text-align: center;
        }
        .login-box label {
            display: block;
            margin-bottom: 5px;
            font-size: 15px;
        }
        .login-box input[type="text"],
        .login-box input[type="password"] {
            width: 100%;
            padding: 8px 0;
            margin-bottom: 20px;
            border: none;
            border-bottom: 1px solid #222;
            background: transparent;
            font-size: 16px;
        }
        .login-box input:focus {
            outline: none;
            border-bottom: 2px solid #3d2fff;
        }
        .login-box .btn-row {
            display: flex;
            gap: 10px;
        }
        .login-box button {
            flex: 1;
            padding: 10px 0;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .login-box .login-btn {
            background: #3d2fff;
            color: #fff;
        }
        .login-box .signup-btn {
            background: #fff;
            color: #3d2fff;
            border: 2px solid #3d2fff;
        }
        .system-title {
            margin-top: 40px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }
        .system-title span {
            color: #3d2fff;
        }
        .bottom-bar {
            width: 80%;
            height: 10px;
            background: #3d2fff;
            border-radius: 5px;
            margin: 40px auto 0 auto;
        }
        @media (max-width: 900px) {
            .container { flex-direction: column; }
            .left, .right { flex: none; width: 100%; }
            .system-title { margin-top: 20px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="left">
        <div class="login-box">
            <h3>Welcome back!<br> Please log in your account.</h3>
            <?php if(isset($error)) echo '<p style="color:red; text-align:center;">'.$error.'</p>'; ?>
            <form method="post">
                <label>Username</label>
                <input type="text" name="username" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <div class="btn-row">
                    <button type="submit" class="login-btn">Log in</button>
                    <a href="register.php" style="flex:1; text-decoration:none;"><button type="button" class="signup-btn">Sign up</button></a>
                </div>
            </form>
        </div>
    </div>
    <div class="right">
        <div class="system-title">
            Management and Decision Support System for Senior Citizens in <br>
            <span>Manolo Fortich, Bukidnon</span>
        </div>
        <div class="bottom-bar"></div>
    </div>
</div>
</body>
</html> 