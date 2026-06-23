<?php
session_start();
include('db_config.php');

// If already logged in and scanning a QR, redirect immediately
if (isset($_SESSION['gate_user']) && isset($_GET['id'])) {
    header("Location: view_profile.php?id=" . urlencode($_GET['id']));
    exit;
}

if (isset($_POST['login'])) {
    $user = trim($_POST['user']);
    $pass = trim($_POST['pass']);

    // Check credentials (using your current gate123 password)
    if ($user === 'gate' && $pass === 'gate123') {
        $_SESSION['gate_user'] = true;

        // PHP-side redirect is more reliable than JavaScript for phone browsers
        if (isset($_GET['id'])) {
            header("Location: view_profile.php?id=" . urlencode($_GET['id']));
        } else {
            // If they just opened the login page manually
            echo "<script>alert('Access Granted'); window.location='gate_login.php';</script>";
        }
        exit;
    } else {
        $error = "Invalid Access Credentials. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: sans-serif;
            display: flex;
            justify-content: center;
            padding-top: 50px;
            background: #eee;
        }

        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="box">
        <h3>Login</h3>
        <form method="POST">
            <input type="text" name="user" placeholder="Username" required>
            <input type="password" name="pass" placeholder="Password" required>
            <button type="submit" name="login">Authorize Device</button>
        </form>
    </div>
</body>

</html>