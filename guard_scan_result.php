<?php
session_start();

// ─── SECURITY WALL ────────────────────────────────────────────────────────────
if (!isset($_SESSION['clerk_user']) || $_SESSION['role'] !== 'guard') {
    die("
    <div style='text-align:center; padding:50px; font-family:sans-serif;'>
        <h1 style='color:#e74c3c; font-size:50px;'>❌ ACCESS DENIED</h1>
        <p style='font-size:18px; color:#555;'>
            This endpoint is restricted to official security guards only.<br>
            Please log into your assigned terminal gateway dashboard.
        </p>
        <a href='login.php'
           style='display:inline-block; padding:10px 20px; background:#1e3d8f;
                  color:white; text-decoration:none; border-radius:5px;
                  margin-top:15px; font-weight:bold;'>
            Go to Login
        </a>
    </div>");
}

include('db_config.php');
// $pdo is now available from db_config.php

// ─── 1. VALIDATE INPUT ────────────────────────────────────────────────────────
if (!isset($_GET['id']) || trim($_GET['id']) === '') {
    die("Error: No Student ID scanned or entered. <a href='guard_interface.php'>Back</a>");
}

$student_id = trim(urldecode($_GET['id']));
$gate_no    = isset($_GET['gate_no']) ? trim($_GET['gate_no']) : 'Main Gate';

// ─── GUEST PASS ROUTING ───────────────────────────────────────────────────────
// If the scanned ID is a guest pass (starts with GP-), hand off to guest_pass_result.php
if (strpos($student_id, 'GP-') === 0) {
    header("Location: guest_pass_result.php?id=" . urlencode($student_id));
    exit();
}

// ─── 2. FETCH STUDENT (PDO prepared statement) ────────────────────────────────
$stmt = $pdo->prepare(
    "SELECT name, institution, photo, passing_year
     FROM students
     WHERE unique_id = ?"
);
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die("
    <div style='text-align:center; padding:50px; font-family:sans-serif;'>
        <h1 style='color:#e74c3c; font-size:50px;'>❌ ACCESS DENIED</h1>
        <p style='font-size:18px;'>
            Student ID <b>" . htmlspecialchars($student_id) . "</b> is not registered in the system.
        </p>
        <a href='guard_interface.php'
           style='display:inline-block; padding:10px 20px; background:#1e3d8f;
                  color:white; text-decoration:none; border-radius:5px; margin-top:15px;'>
            Return to Gate Control
        </a>
    </div>");
}

$student_name  = $student['name'];
$institution   = $student['institution'];
$student_photo = trim($student['photo']);
$passing_year  = (int) $student['passing_year'];

// ─── 3. QR EXPIRY CHECK ───────────────────────────────────────────────────────
// A student's access is deactivated if their passing year is in the past.
// We compare against the current calendar year.
$current_year = (int) date('Y');

if ($passing_year > 0 && $current_year > $passing_year) {
    // Log the blocked attempt for the admin audit trail
    $block_stmt = $pdo->prepare(
        "INSERT INTO student_attendance
            (student_id, student_name, institution, direction, gate_no, log_time)
         VALUES (?, ?, ?, 'BLOCKED', ?, NOW())"
    );
    $block_stmt->execute([$student_id, $student_name, $institution, $gate_no]);

    // Show a distinct expired-access screen
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Expired</title>
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                background: linear-gradient(135deg, #f4f6f9 0%, #e2e8f0 100%);
                display: flex; justify-content: center; align-items: center;
                min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box;
            }
            .card {
                background: #fff; max-width: 460px; width: 100%;
                border-radius: 16px; padding: 40px 30px; text-align: center;
                box-shadow: 0 10px 30px rgba(34,58,94,0.08);
                border-top: 6px solid #dc2626;
            }
            .icon { font-size: 60px; margin-bottom: 10px; }
            h1 { color: #991b1b; font-size: 26px; margin: 0 0 10px; }
            .sub { color: #64748b; font-size: 15px; margin-bottom: 25px; line-height: 1.6; }
            .info-box {
                background: #fef2f2; border: 1px solid #fecaca;
                border-radius: 10px; padding: 16px; margin-bottom: 25px;
                text-align: left;
            }
            .info-box p { margin: 8px 0; font-size: 14px; color: #7f1d1d; }
            .info-box p b { color: #450a0a; display: inline-block; width: 120px; }
            .year-badge {
                display: inline-block; background: #dc2626; color: white;
                padding: 4px 14px; border-radius: 20px; font-size: 13px;
                font-weight: 700; margin-bottom: 20px;
            }
            .btn {
                display: block; width: 100%; padding: 14px;
                background: #1e3d8f; color: white; text-decoration: none;
                border-radius: 8px; font-weight: 700; font-size: 15px;
                box-sizing: border-box; transition: background 0.2s;
            }
            .btn:hover { background: #223a5e; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="icon">🚫</div>
            <h1>Access Expired</h1>
            <div class="year-badge">Passed Out: <?php echo $passing_year; ?></div>
            <p class="sub">
                This student's campus access has been automatically deactivated
                because their graduation year has passed.
            </p>
            <div class="info-box">
                <p><b>Name:</b> <?php echo htmlspecialchars($student_name); ?></p>
                <p><b>ID:</b> <?php echo htmlspecialchars($student_id); ?></p>
                <p><b>Institution:</b> <?php echo htmlspecialchars($institution); ?></p>
                <p><b>Passing Year:</b> <?php echo $passing_year; ?></p>
                <p><b>Current Year:</b> <?php echo $current_year; ?></p>
                <p><b>Gate:</b> <?php echo htmlspecialchars($gate_no); ?></p>
            </div>
            <a href="guard_interface.php" class="btn">Return to Gate Control</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// ─── 4. PRG: fresh scan → log the entry → redirect ───────────────────────────
if (!isset($_GET['view'])) {

    // Determine direction toggle
    $last_stmt = $pdo->prepare(
        "SELECT direction FROM student_attendance
         WHERE student_id = ?
         ORDER BY log_time DESC LIMIT 1"
    );
    $last_stmt->execute([$student_id]);
    $last_log      = $last_stmt->fetch();
    $next_direction = ($last_log && $last_log['direction'] === 'IN') ? 'OUT' : 'IN';

    // Insert new gate log
    $insert_stmt = $pdo->prepare(
        "INSERT INTO student_attendance
            (student_id, student_name, institution, direction, gate_no, log_time)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $insert_stmt->execute([
        $student_id,
        $student_name,
        $institution,
        $next_direction,
        $gate_no
    ]);

    // PRG redirect — prevents duplicate entry on page refresh
    header(
        "Location: guard_scan_result.php?id=" . urlencode($student_id)
        . "&gate_no=" . urlencode($gate_no)
        . "&view=success"
    );
    exit();
}

// ─── 5. VIEW=SUCCESS: read current state and render result ────────────────────
$current_stmt = $pdo->prepare(
    "SELECT direction FROM student_attendance
     WHERE student_id = ?
     ORDER BY log_time DESC LIMIT 1"
);
$current_stmt->execute([$student_id]);
$current_log    = $current_stmt->fetch();
$current_action = $current_log ? $current_log['direction'] : 'IN';

// Last 5 activity records for the history table
$history_stmt = $pdo->prepare(
    "SELECT direction, gate_no, log_time
     FROM student_attendance
     WHERE student_id = ?
     ORDER BY log_time DESC LIMIT 5"
);
$history_stmt->execute([$student_id]);
$history_rows = $history_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Action Result</title>
    <style>
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f4f6f9 0%, #e2e8f0 100%);
            padding: 20px; 
            margin: 0;
            display: flex; 
            justify-content: center; 
            align-items: center;
            min-height: 100vh; 
            box-sizing: border-box;
        }
        .result-card {
            background: white; 
            width: 100%; 
            max-width: 480px;
            padding: 30px; 
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(34,58,94,0.08);
            border: 1px solid #e2e8f0; 
            text-align: center;
        }
        .status-header {
            font-size: 22px; 
            font-weight: 700; 
            letter-spacing: -0.5px;
            margin-bottom: 25px; 
            padding: 12px; 
            border-radius: 8px;
            text-transform: uppercase;
        }
        .status-IN-style  { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
        .status-OUT-style { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

        /* ── QR Expiry warning banner (shown only when near expiry) ── */
        .expiry-warning {
            background: #fff7ed; border: 1px solid #fed7aa;
            border-radius: 8px; padding: 10px 14px;
            font-size: 13px; color: #9a3412; font-weight: 600;
            margin-bottom: 16px; text-align: left;
        }

        .photo-verification-container {
            margin-bottom: 20px; display: flex;
            justify-content: center; position: relative;
        }
        .avatar-wrapper {
            width: 140px; height: 140px; border-radius: 50%;
            border: 4px solid #ffffff;
            box-shadow: 0 4px 15px rgba(34,58,94,0.15);
            background-color: #f1f5f9; overflow: hidden;
            display: flex; justify-content: center; align-items: center;
        }
        .student-face-avatar { width:100%; height:100%; object-fit:cover; }
        .avatar-fallback { display:none; width:100%; height:100%; padding:15px; box-sizing:border-box; }

        .details {
            text-align: left; background: #f8fafc;
            padding: 20px; border-radius: 10px; margin: 20px 0;
            border-left: 5px solid #1e3d8f;
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .details p { margin: 10px 0; font-size: 15px; color: #334155; }
        .details p b { color:#1e293b; display:inline-block; width:130px; }

        h3 { color:#1e293b; font-size:16px; font-weight:700; margin-top:25px; margin-bottom:12px; text-align:left; }

        table { width:100%; border-collapse:collapse; text-align:left; margin-bottom:10px; }
        th, td { padding:12px 10px; border-bottom:1px solid #e2e8f0; font-size:14px; }
        th { background:#223a5e; color:white; font-weight:600; }
        th:first-child { border-top-left-radius:6px; border-bottom-left-radius:6px; }
        th:last-child  { border-top-right-radius:6px; border-bottom-right-radius:6px; }

        .btn {
            display:inline-block; width:100%; padding:14px;
            background:#1e3d8f; color:white; text-decoration:none;
            border-radius:8px; font-weight:700; font-size:16px;
            margin-top:20px; box-sizing:border-box;
            transition:background 0.2s;
            box-shadow:0 4px 12px rgba(30,61,143,0.2);
        }
        .btn:hover { background:#223a5e; }
    </style>
</head>
<body>
<div class="result-card">

    <div class="status-header <?php echo ($current_action === 'IN') ? 'status-IN-style' : 'status-OUT-style'; ?>">
        <?php echo ($current_action === 'IN') ? '✅ ALLOWED IN' : '✅ ALLOWED OUT'; ?>
    </div>

    <?php
    // ── Near-expiry warning: show if student graduates THIS year ──────────────
    if ($passing_year > 0 && $current_year === $passing_year):
    ?>
    <div class="expiry-warning">
        ⚠️ Notice: This student's access expires at the end of <?php echo $passing_year; ?>.
    </div>
    <?php endif; ?>

    <div class="photo-verification-container">
        <div class="avatar-wrapper">
            <?php if (!empty($student_photo)): ?>
                <img id="studentImg"
                     src="<?php echo htmlspecialchars($student_photo); ?>"
                     alt="Student Photo"
                     class="student-face-avatar"
                     onerror="tryAlternativePath(this, '<?php echo htmlspecialchars($student_photo); ?>')">
            <?php else: ?>
                <svg class="avatar-fallback" style="display:block;" viewBox="0 0 24 24" fill="none">
                    <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="#94a3b8"/>
                </svg>
            <?php endif; ?>
            <svg id="svgFallback" class="avatar-fallback" viewBox="0 0 24 24" fill="none">
                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="#94a3b8"/>
            </svg>
        </div>
    </div>

    <div class="details">
        <p><b>Name:</b>           <?php echo htmlspecialchars($student_name); ?></p>
        <p><b>ID:</b>             <?php echo htmlspecialchars($student_id); ?></p>
        <p><b>Institution:</b>    <?php echo htmlspecialchars($institution); ?></p>
        <p><b>Gate Processed:</b> <?php echo htmlspecialchars($gate_no); ?></p>
        <p><b>Access Valid Till:</b> End of <?php echo $passing_year > 0 ? $passing_year : 'N/A'; ?></p>
    </div>

    <h3>Recent Activity Logs</h3>
    <table>
        <thead>
            <tr><th>Action</th><th>Gate</th><th>Timestamp</th></tr>
        </thead>
        <tbody>
            <?php foreach ($history_rows as $row): ?>
            <tr>
                <td style="font-weight:700; color:<?php echo ($row['direction'] === 'IN') ? '#2e7d32' : '#c62828'; ?>">
                    <?php echo htmlspecialchars($row['direction']); ?>
                </td>
                <td style="color:#475569;"><?php echo htmlspecialchars($row['gate_no']); ?></td>
                <td style="color:#475569;"><?php echo date('d M Y, h:i A', strtotime($row['log_time'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="guard_interface.php" class="btn">Return to Gate Control</a>
</div>

<script>
function tryAlternativePath(imgElement, originalFilename) {
    if (imgElement.src.includes('uploads/photos/')) {
        imgElement.style.display = 'none';
        document.getElementById('svgFallback').style.display = 'block';
    } else {
        var filenameOnly = originalFilename.replace(/^.*[\\\/]/, '');
        imgElement.src = "uploads/photos/" + filenameOnly;
    }
}
</script>
</body>
</html>