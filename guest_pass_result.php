<?php
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No pass ID provided.");
}

$pass_id  = mysqli_real_escape_string($conn, urldecode($_GET['id']));
$res      = mysqli_query($conn, "SELECT * FROM guest_passes WHERE pass_id = '$pass_id'");
$pass     = mysqli_fetch_assoc($res);

if (!$pass) {
    die("
    <div style='text-align:center;padding:50px;font-family:sans-serif;'>
        <h1 style='color:#e74c3c;font-size:50px;'>❌ INVALID PASS</h1>
        <p style='font-size:18px;'>Pass ID <b>" . htmlspecialchars($pass_id) . "</b> does not exist.</p>
        <a href='guard_interface.php' style='display:inline-block;padding:10px 20px;background:#1e3d8f;color:white;text-decoration:none;border-radius:5px;margin-top:15px;'>Return to Gate Control</a>
    </div>");
}

$today     = date('Y-m-d');
$is_new    = isset($_GET['new']);
$is_active = (bool) $pass['is_active'];
$is_valid  = ($is_active && $today >= $pass['valid_from'] && $today <= $pass['valid_until']);
$is_expired = ($today > $pass['valid_until']);
$not_yet    = ($today < $pass['valid_from']);

if (!$is_active) {
    $status = 'REVOKED'; $status_class = 'status-revoked'; $status_icon = '🚫';
} elseif ($is_expired) {
    $status = 'EXPIRED'; $status_class = 'status-expired'; $status_icon = '⛔';
} elseif ($not_yet) {
    $status = 'NOT YET VALID'; $status_class = 'status-pending'; $status_icon = '⏳';
} else {
    $status = 'VALID — ALLOW ENTRY'; $status_class = 'status-valid'; $status_icon = '✅';
}

// ── Log scan into student_attendance (only on fresh scan, not on refresh) ────
$gate_no = isset($_GET['gate_no']) ? mysqli_real_escape_string($conn, $_GET['gate_no']) : 'Main Gate';

if (!isset($_GET['view'])) {
    // Only log if pass is valid
    if ($is_valid) {
        $safe_name = mysqli_real_escape_string($conn, $pass['guest_name']);
        $safe_inst = mysqli_real_escape_string($conn, $pass['institution']);
        mysqli_query($conn,
            "INSERT INTO student_attendance
                (student_id, student_name, institution, direction, gate_no, entry_type, log_time)
             VALUES
                ('$pass_id', '$safe_name', '$safe_inst', 'IN', '$gate_no', 'guest', NOW())"
        );
    }
    // PRG redirect to prevent duplicate log on refresh
    $params = "?id=" . urlencode($pass_id) . "&view=1&gate_no=" . urlencode($gate_no);
    if (isset($_GET['new']))  $params .= "&new=1";
    if (isset($_GET['mail'])) $params .= "&mail=" . urlencode($_GET['mail']);
    header("Location: guest_pass_result.php" . $params);
    exit();
}

// Fetch last 5 scan logs for this guest pass
$logs = mysqli_query($conn,
    "SELECT direction, gate_no, log_time FROM student_attendance
     WHERE student_id = '$pass_id' AND entry_type = 'guest'
     ORDER BY log_time DESC LIMIT 5"
);

$back_url = ($_SESSION['role'] === 'guard') ? 'guard_interface.php' : 'guest_passes.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Pass – <?php echo htmlspecialchars($pass['guest_name']); ?></title>
    <style>
        :root {
            --purple:      #7c3aed;
            --purple-dark: #4c1d95;
            --portal-blue: #1e3d8f;
            --portal-dark: #223a5e;
            --bg:          #f4f6f9;
            --border:      #e2e8f0;
            --text:        #1e293b;
            --muted:       #64748b;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f4f6f9 0%, #e2e8f0 100%);
            display: flex; justify-content: center; align-items: flex-start;
            min-height: 100vh; margin: 0; padding: 30px 20px; box-sizing: border-box;
        }
        .profile-card {
            width: 100%; max-width: 500px;
            background: white; border-radius: 16px; overflow: hidden;
            box-shadow: 0 15px 35px rgba(34,58,94,0.08);
            border: 1px solid var(--border);
        }

        /* ── Header ── */
        .card-header {
            background: linear-gradient(135deg, var(--purple), var(--purple-dark));
            color: white; padding: 28px 24px; text-align: center;
        }
        .guest-avatar {
            width: 72px; height: 72px; border-radius: 50%;
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; margin: 0 auto 12px;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .card-header h2 { margin: 0 0 6px; font-size: 22px; font-weight: 700; }
        .pass-id-chip {
            display: inline-block; background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 4px 14px; border-radius: 20px;
            font-size: 12px; font-weight: 600; letter-spacing: 1px;
            font-family: monospace;
        }

        /* ── Status banner ── */
        .status-banner {
            text-align: center; font-size: 16px; font-weight: 700;
            padding: 12px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .status-valid   { background:#d4edda; color:#155724; }
        .status-expired { background:#f8d7da; color:#721c24; }
        .status-revoked { background:#f8d7da; color:#721c24; }
        .status-pending { background:#fff3cd; color:#856404; }

        /* ── Mail banner ── */
        .mail-banner {
            padding: 10px 16px; font-size: 13px; font-weight: 600;
            text-align: center; border-bottom: 1px solid var(--border);
        }
        .mail-sent   { background:#f0fdf4; color:#166534; }
        .mail-failed { background:#fff7ed; color:#9a3412; }
        .mail-skip   { background:#f8fafc; color:#475569; }

        /* ── Validity strip ── */
        .validity-strip {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 0; border-bottom: 1px solid var(--border);
        }
        .validity-box {
            padding: 14px; text-align: center;
            border-right: 1px solid var(--border);
        }
        .validity-box:last-child { border-right: none; }
        .validity-box label {
            display: block; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--muted); margin-bottom: 4px;
        }
        .validity-box span { font-size: 14px; font-weight: 700; color: var(--text); }

        /* ── Details grid ── */
        .details { padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .detail-row {
            display: flex; gap: 10px; padding: 8px 0;
            border-bottom: 1px solid #f1f5f9; font-size: 14px;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-row b { color: var(--muted); min-width: 120px; font-weight: 600; flex-shrink: 0; }
        .detail-row span { color: var(--text); }

        /* ── Scan log table ── */
        .log-section { padding: 20px 24px; border-bottom: 1px solid var(--border); }
        .log-section h4 { margin: 0 0 12px; font-size: 14px; font-weight: 700; color: var(--text); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: var(--portal-dark); color: white; padding: 10px 12px; text-align: left; font-weight: 600; }
        th:first-child { border-top-left-radius: 6px; border-bottom-left-radius: 6px; }
        th:last-child  { border-top-right-radius: 6px; border-bottom-right-radius: 6px; }
        td { padding: 10px 12px; border-bottom: 1px solid var(--border); color: var(--text); }
        tr:last-child td { border-bottom: none; }
        .no-logs { text-align:center; color:var(--muted); font-style:italic; padding: 16px; }

        /* ── QR section ── */
        .qr-section {
            padding: 16px 24px; text-align: center;
            border-bottom: 1px solid var(--border);
        }
        .qr-section img {
            width: 110px; height: 110px;
            border: 2px solid var(--border); border-radius: 8px; padding: 6px;
        }
        .qr-section p { font-size: 11px; color: var(--muted); margin: 6px 0 0; }

        /* ── Buttons ── */
        .btn-row { display: flex; gap: 10px; padding: 20px 24px; }
        .btn {
            flex: 1; display: block; padding: 13px;
            border-radius: 8px; font-weight: 700; font-size: 14px;
            text-align: center; text-decoration: none; cursor: pointer;
            border: none; transition: all 0.2s; box-sizing: border-box;
        }
        .btn-primary { background: var(--purple); color: white; box-shadow: 0 4px 12px rgba(124,58,237,0.2); }
        .btn-primary:hover { background: var(--purple-dark); }
        .btn-print { background: #f1f5f9; color: #475569; border: 1px solid var(--border); }
        .btn-print:hover { background: #e2e8f0; }

        @media print {
            .btn-row { display: none; }
            body { background: white; padding: 0; }
            .profile-card { box-shadow: none; border: 1px solid #ccc; }
        }
    </style>
</head>
<body>
<div class="profile-card">

    <!-- Header -->
    <div class="card-header">
        <div class="guest-avatar">🧑</div>
        <h2><?php echo htmlspecialchars($pass['guest_name']); ?></h2>
        <div class="pass-id-chip"><?php echo htmlspecialchars($pass['pass_id']); ?></div>
    </div>

    <!-- Status -->
    <div class="status-banner <?php echo $status_class; ?>">
        <?php echo $status_icon . ' ' . $status; ?>
    </div>

    <!-- Mail delivery banner (only shown right after pass creation) -->
    <?php if (isset($_GET['new'])): ?>
        <?php if (isset($_GET['mail']) && $_GET['mail'] === 'sent'): ?>
            <div class="mail-banner mail-sent">📧 QR code emailed to the guest successfully.</div>
        <?php elseif (isset($_GET['mail']) && $_GET['mail'] === 'failed'): ?>
            <div class="mail-banner mail-failed">⚠️ Pass created but email could not be sent. Share the QR manually.</div>
        <?php elseif (isset($_GET['mail']) && $_GET['mail'] === 'skipped'): ?>
            <div class="mail-banner mail-skip">ℹ️ No email provided — share the QR below manually.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Validity strip -->
    <div class="validity-strip">
        <div class="validity-box">
            <label>Valid From</label>
            <span><?php echo date('d M Y', strtotime($pass['valid_from'])); ?></span>
        </div>
        <div class="validity-box">
            <label>Valid Until</label>
            <span><?php echo date('d M Y', strtotime($pass['valid_until'])); ?></span>
        </div>
    </div>

    <!-- Details -->
    <div class="details">
        <div class="detail-row"><b>Institution</b>  <span><?php echo htmlspecialchars($pass['institution']); ?></span></div>
        <div class="detail-row"><b>Phone</b>         <span><?php echo !empty($pass['guest_phone']) ? htmlspecialchars($pass['guest_phone']) : '—'; ?></span></div>
        <div class="detail-row"><b>Email</b>         <span><?php echo !empty($pass['guest_email']) ? htmlspecialchars($pass['guest_email']) : '—'; ?></span></div>
        <div class="detail-row"><b>Whom to Meet</b>  <span><?php echo !empty($pass['whom_to_meet']) ? htmlspecialchars($pass['whom_to_meet']) : '—'; ?></span></div>
        <div class="detail-row"><b>Purpose</b>       <span><?php echo !empty($pass['purpose']) ? htmlspecialchars($pass['purpose']) : '—'; ?></span></div>
        <div class="detail-row"><b>Issued By</b>     <span><?php echo htmlspecialchars($pass['issued_by']); ?></span></div>
        <div class="detail-row"><b>Issued At</b>     <span><?php echo date('d M Y, h:i A', strtotime($pass['issued_at'])); ?></span></div>
    </div>

    <!-- Scan log -->
    <div class="log-section">
        <h4>📋 Gate Scan History</h4>
        <?php if (mysqli_num_rows($logs) > 0): ?>
        <table>
            <thead><tr><th>Direction</th><th>Gate</th><th>Timestamp</th></tr></thead>
            <tbody>
                <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                <tr>
                    <td style="font-weight:700;color:#2e7d32;"><?php echo htmlspecialchars($log['direction']); ?></td>
                    <td style="color:#475569;"><?php echo htmlspecialchars($log['gate_no']); ?></td>
                    <td style="color:#475569;"><?php echo date('d M Y, h:i A', strtotime($log['log_time'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p class="no-logs">No gate scans recorded yet.</p>
        <?php endif; ?>
    </div>

    <!-- QR Code -->
    <?php if (!empty($pass['qr_path']) && file_exists($pass['qr_path'])): ?>
    <div class="qr-section">
        <img src="<?php echo htmlspecialchars($pass['qr_path']); ?>" alt="Guest Pass QR">
        <p>Scan to verify this pass at the gate</p>
    </div>
    <?php endif; ?>

    <!-- Buttons -->
    <div class="btn-row">
        <a href="<?php echo $back_url; ?>" class="btn btn-primary">
            <?php echo ($_SESSION['role'] === 'guard') ? '← Gate Control' : '← All Passes'; ?>
        </a>
        <button onclick="window.print()" class="btn btn-print">🖨 Print Pass</button>
    </div>

</div>
</body>
</html>