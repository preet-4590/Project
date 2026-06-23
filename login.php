<?php 
session_start(); // Required to handle dashboard redirection routing roles safely
include('db_config.php'); 

// 1. CHOOSE REDIRECTION PROCESSES ABSOLUTELY FIRST BEFORE ANY HTML TEXT STRINGS GENERATE
$error_flag = false;

if (isset($_POST['login'])) {
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    $pass = $_POST['password']; // Raw — never put plain password in SQL query

    // Fetch by username only, then verify hash in PHP
    $res = mysqli_query($conn, "SELECT * FROM clerks WHERE username='$user'");
    $row = mysqli_fetch_assoc($res);

    // password_verify() safely compares against the bcrypt hash
    if ($row && password_verify($pass, $row['password'])) {
        $_SESSION['clerk_user'] = $row['username'];
        $_SESSION['role'] = $row['role']; 

        if ($row['role'] == 'super_admin') {
            header("Location: admin_dashboard.php");
            exit;
        }
        elseif ($row['role'] == 'guard') {
            $_SESSION['clerk_inst'] = $row['institution']; 
            header("Location: guard_interface.php");
            exit;
        }
        else {
            $_SESSION['clerk_inst'] = $row['institution'];
            header("Location: dashboard.php");
            exit;
        }
    } else {
        $error_flag = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System | Portal Login</title>
    <style>
        :root {
            /* Original Blue Palette Restored */
            --primary-blue: #1e3a8a; 
            --accent-blue: #3b82f6;  
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --bg-light: #f8fafc;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-light);
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Split Screen Container Layout */
        .login-wrapper {
            display: flex;
            width: 100%;
            height: 100%;
        }

        /* Left Branding Panel */
        .brand-side {
            flex: 1.0; 
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px;
            color: #ffffff;
            position: relative;
        }

        .brand-side::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.15) 0%, transparent 60%);
            z-index: 1;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            max-width: 420px;
        }

        .brand-badge {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 25px;
            color: #93c5fd;
        }

        .brand-side h1 {
            font-size: 40px;
            font-weight: 800;
            margin: 0 0 20px 0;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .brand-side p {
            font-size: 15px;
            color: #93c5fd;
            line-height: 1.6;
            margin: 0 0 40px 0;
            opacity: 0.95;
        }

        /* Campus Directory Tags */
        .campus-list {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .campus-tag {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #f3f4f6;
        }

        /* Right Form Panel - Covers more than half of the space */
        .form-side {
            flex: 1.2; 
            background: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px;
            box-shadow: -15px 0 40px rgba(0,0,0,0.05);
        }

        .login-box {
            width: 100%;
            max-width: 420px;
        }

        .login-header h2 {
            font-size: 34px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 0 0 8px 0;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-size: 15px;
            color: var(--text-muted);
            margin: 0 0 35px 0;
        }

        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 14px;
        }

        input[type="password"],
        select {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            box-sizing: border-box;
            outline: none;
            color: var(--text-dark);
            background-color: #ffffff;
            transition: all 0.2s ease;
        }

        input[type="password"]:focus,
        select:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        select {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg fill='%236b7280' height='24' viewBox='0 0 24 24' width='24' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/><path d='M0 0h24v24H0z' fill='none'/></svg>");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 20px;
            padding-right: 40px !important;
            cursor: pointer;
        }

        button {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.2);
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        button:hover {
            background-color: #1d4ed8;
        }

        button:active {
            transform: scale(0.99);
        }

        .error-message {
            background-color: #fef2f2;
            color: #991b1b;
            padding: 14px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 25px;
            font-weight: 500;
            border: 1px solid #fca5a5;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 950px) {
            .brand-side {
                display: none;
            }
            body {
                background-color: #ffffff;
            }
        }
    </style>
</head>

<body>

    <div class="login-wrapper">
        
        <div class="brand-side">
            <div class="brand-content">
                <span class="brand-badge">Institutional Gateway</span>
                <h1>Gate Entry Management System</h1>
                <p>Unified authentication terminal for authorized administrative clerks, supervisor roles, and secure campus checkpoint guards.</p>
                
                <div class="campus-list">
                    <span class="campus-tag">GNDEC</span>
                    <span class="campus-tag">GNDPC</span>
                    <span class="campus-tag">GNDITI</span>
                </div>
            </div>
        </div>

        <div class="form-side">
            <div class="login-box">
                <div class="login-header">
                    <h2>Portal Sign In</h2>
                    <p>Select your user profile configuration and enter password.</p>
                </div>

                <?php
                // 2. ONLY DISPLAY ERROR ALERTS LOCALLY INLINE AT THIS DESIGNATED CONTAINER DOM BLOCK
                if ($error_flag) {
                    echo '<div class="error-message">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Invalid Username or Password configuration.
                          </div>';
                }
                ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Authorized Profile</label>
                        <select name="username" id="username" required>
                            <option value="" disabled selected>Select assigned ID</option>
                            <?php
                            $user_query = "SELECT username FROM clerks ORDER BY username ASC";
                            $user_result = mysqli_query($conn, $user_query);
                            
                            if ($user_result && mysqli_num_rows($user_result) > 0) {
                                while ($user_row = mysqli_fetch_assoc($user_result)) {
                                    $uname = htmlspecialchars($user_row['username']);
                                    $selected = (isset($_POST['username']) && $_POST['username'] == $user_row['username']) ? 'selected' : '';
                                    echo "<option value=\"$uname\" $selected>$uname</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Security Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                    </div>

                    <button type="submit" name="login">Authenticate Session</button>
                </form>
            </div>
        </div>

    </div>

</body>

</html>