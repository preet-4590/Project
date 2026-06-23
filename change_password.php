<?php
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user'])) {
    header("Location: login.php");
    exit;
}

$error   = '';
$success = false;

if (isset($_POST['change'])) {
    $current  = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    // 1. Fetch current hash
    $user = mysqli_real_escape_string($conn, $_SESSION['clerk_user']);
    $res  = mysqli_query($conn, "SELECT password FROM clerks WHERE username = '$user'");
    $row  = mysqli_fetch_assoc($res);

    if (!$row || !password_verify($current, $row['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new_pass) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new_pass !== $confirm) {
        $error = "New password and confirmation do not match.";
    } elseif (password_verify($new_pass, $row['password'])) {
        $error = "New password cannot be the same as your current password.";
    } else {
        // 2. Hash and save
        $hashed   = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $safe_hash = mysqli_real_escape_string($conn, $hashed);
        mysqli_query($conn, "UPDATE clerks SET password = '$safe_hash' WHERE username = '$user'");
        $success = true;
    }
}

// Back link depends on role
$back_url = ($_SESSION['role'] === 'super_admin') ? 'admin_dashboard.php' : 'dashboard.php';
if ($_SESSION['role'] === 'guard') $back_url = 'guard_interface.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Gate Entry System</title>
    <style>
        :root {
            --portal-dark: #223a5e;
            --portal-blue: #1e3d8f;
            --portal-bg:   #f4f6f9;
            --border-color:#cbd5e0;
            --text-main:   #2d3748;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--portal-bg);
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box;
        }
        .card {
            background: #fff; width: 100%; max-width: 440px;
            border-radius: 14px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(34,58,94,0.08);
            border: 1px solid #e2e8f0;
        }
        .card-header {
            background: linear-gradient(135deg, var(--portal-blue), var(--portal-dark));
            color: white; padding: 28px;
        }
        .card-header h2 { margin: 0 0 4px; font-size: 20px; font-weight: 700; }
        .card-header p  { margin: 0; font-size: 13px; opacity: 0.85; }
        .card-body { padding: 28px; }

        .form-group { margin-bottom: 20px; }
        label {
            display: block; font-size: 13px; font-weight: 600;
            color: #4a5568; margin-bottom: 7px;
        }
        .input-wrap { position: relative; }
        input[type="password"], input[type="text"] {
            width: 100%; padding: 11px 44px 11px 14px;
            border: 1px solid var(--border-color); border-radius: 6px;
            box-sizing: border-box; font-size: 14px;
            color: var(--text-main); outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
        }
        .toggle-eye {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%); cursor: pointer;
            font-size: 16px; user-select: none; color: #94a3b8;
        }
        .toggle-eye:hover { color: var(--portal-blue); }

        .strength-bar {
            height: 4px; border-radius: 2px; margin-top: 6px;
            background: #e2e8f0; overflow: hidden;
        }
        .strength-fill {
            height: 100%; width: 0%; border-radius: 2px;
            transition: width 0.3s, background 0.3s;
        }
        .strength-label {
            font-size: 11px; color: #94a3b8; margin-top: 3px;
        }

        .alert {
            padding: 12px 16px; border-radius: 8px;
            font-size: 14px; font-weight: 500; margin-bottom: 20px;
        }
        .alert-error   { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        .alert-success { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }

        .btn-submit {
            width: 100%; padding: 13px; background: var(--portal-blue);
            color: white; border: none; border-radius: 6px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            transition: background 0.2s;
            box-shadow: 0 4px 12px rgba(30,61,143,0.15);
        }
        .btn-submit:hover { background: #162e6f; }

        .back-link {
            display: block; text-align: center; margin-top: 16px;
            font-size: 13px; color: #64748b; text-decoration: none;
        }
        .back-link:hover { color: var(--portal-blue); }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h2>🔐 Change Password</h2>
        <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['clerk_user']); ?></strong></p>
    </div>
    <div class="card-body">

        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ Password changed successfully. Use your new password next time you log in.
            </div>
            <a href="<?php echo $back_url; ?>" class="btn-submit" style="display:block;text-align:center;text-decoration:none;line-height:1.8;">
                Back to Dashboard
            </a>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="input-wrap">
                        <input type="password" name="current_password" id="cur_pass" required placeholder="Enter current password">
                        <span class="toggle-eye" onclick="toggleVis('cur_pass', this)">👁</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrap">
                        <input type="password" name="new_password" id="new_pass" required
                               placeholder="Min 6 characters" oninput="checkStrength(this.value)">
                        <span class="toggle-eye" onclick="toggleVis('new_pass', this)">👁</span>
                    </div>
                    <div class="strength-bar"><div class="strength-fill" id="str-fill"></div></div>
                    <div class="strength-label" id="str-label"></div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrap">
                        <input type="password" name="confirm_password" id="conf_pass" required
                               placeholder="Re-enter new password">
                        <span class="toggle-eye" onclick="toggleVis('conf_pass', this)">👁</span>
                    </div>
                </div>

                <button type="submit" name="change" class="btn-submit">Update Password</button>
            </form>

        <?php endif; ?>

        <a href="<?php echo $back_url; ?>" class="back-link">← Back to Dashboard</a>
    </div>
</div>

<script>
function toggleVis(id, icon) {
    var input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '👁';
    }
}

function checkStrength(val) {
    var fill  = document.getElementById('str-fill');
    var label = document.getElementById('str-label');
    var score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var levels = [
        { w: '0%',   c: '#e2e8f0', t: '' },
        { w: '25%',  c: '#ef4444', t: 'Weak' },
        { w: '50%',  c: '#f59e0b', t: 'Fair' },
        { w: '75%',  c: '#3b82f6', t: 'Good' },
        { w: '100%', c: '#10b981', t: 'Strong' },
    ];
    var l = levels[Math.min(score, 4)];
    fill.style.width      = l.w;
    fill.style.background = l.c;
    label.textContent     = l.t;
    label.style.color     = l.c;
}
</script>
</body>
</html>