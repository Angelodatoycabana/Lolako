<?php
include 'db.php';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $username = $email; // Use email as username for login
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);
    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        $error = "Registration failed!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
            background: #3d2fff;
            border-top-right-radius: 40px;
            border-bottom-right-radius: 40px;
        }
        .right {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding-left: 80px;
        }
        .signup-title {
            color: #3d2fff;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
        }
        .signup-form {
            width: 100%;
            max-width: 350px;
        }
        .signup-form label {
            font-size: 15px;
            margin-bottom: 5px;
            display: block;
        }
        .signup-form input[type="text"],
        .signup-form input[type="email"],
        .signup-form input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 18px;
            border: none;
            border-radius: 4px;
            background: #f5f5f5;
            font-size: 16px;
        }
        .signup-form input:focus {
            outline: 2px solid #3d2fff;
        }
        .signup-form .row {
            display: flex;
            gap: 10px;
        }
        .signup-form .row input {
            flex: 1;
        }
        .signup-form button {
            width: 100%;
            padding: 12px 0;
            background: #3d2fff;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        @media (max-width: 900px) {
            .container { flex-direction: column; }
            .left, .right { flex: none; width: 100%; border-radius: 0; padding-left: 0; align-items: center; }
            .right { padding: 40px 0 0 0; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="left"></div>
    <div class="right">
        <div class="signup-title">Sign Up</div>
        <?php if(isset($error)) echo '<p style="color:red;">'.$error.'</p>'; ?>
        <form class="signup-form" method="post">
            <div class="row">
                <div style="flex:1;">
                    <label>First Name</label>
                    <input type="text" name="firstname" required>
                </div>
                <div style="flex:1;">
                    <label>Last Name</label>
                    <input type="text" name="lastname" required>
                </div>
            </div>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <label>Re-enter Password</label>
            <input type="password" name="repassword" required>
            <button type="submit">Sign up</button>
        </form>
    </div>
</div>
</body>
</html> 