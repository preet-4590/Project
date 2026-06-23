<?php
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user']) || $_SESSION['role'] === 'guard') {
    header("Location: login.php");
    exit;
}

$is_admin = ($_SESSION['role'] === 'super_admin');
$today    = date('Y-m-d');

// ─── Handle revoke action ─────────────────────────────────────────────────────
if (isset($_GET['revoke'])) {
    $rid = mysqli_real_escape_string($conn, $_GET['revoke']);
    mysqli_query($conn, "UPDATE guest_passes SET is_active = 0 WHERE pass_id = '$rid'");
    header("Location: guest_passes.php?msg=revoked");
    exit;
}

// ─── Fetch passes ─────────────────────────────────────────────────────────────
$search = mysqli_real_escape_string($conn, trim($_GET['search'] ?? ''));

$query = "SELECT * FROM guest_passes";
$where = [];

if (!$is_admin) {
    $inst    = mysqli_real_escape_string($conn, $_SESSION['clerk_inst']);
    $where[] = "institution = '$inst'";
}
if (!empty($search)) {
    $where[] = "(guest_name LIKE '%$search%' OR pass_id LIKE '%$search%' OR guest_phone LIKE '%$search%')";
}
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " ORDER BY issued_at DESC";
$result = mysqli_query($conn, $query);

$back_url = $is_admin ? 'admin_dashboard.php' : 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Passes | Gate Entry System</title>
    <style>
        :root {
            --portal-dark: #0f172a;
            --portal-blue: #1e3a8a;
            --bg: #f8fafc;
            --text: #1e293b;
            --success: #10b981;
            --danger:  #ef4444;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--bg); color:var(--text); }

        .nav {
            background: linear-gradient(90deg, var(--portal-dark), var(--portal-blue));
            color: white; padding: 20px 40px;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .nav h3 { font-size: 20px; font-weight: 700; }
        .nav-links { display: flex; gap: 12px; }
        .btn-nav {
            padding: 10px 18px; border-radius: 8px; font-weight: 600;
            font-size: 13px; text-decoration: none; transition: all 0.2s;
        }
        .btn-issue  { background: var(--success); color: white; }
        .btn-issue:hover { background: #059669; }
        .btn-back   { background: rgba(255,255,255,0.12); color: white;
                      border: 1px solid rgba(255,255,255,0.2); }
        .btn-back:hover { background: rgba(255,255,255,0.2); }

        .container { max-width: 1200px; margin: auto; padding: 30px; }

        .flash {
            padding: 14px 20px; border-radius: 8px; margin-bottom: 20px;
            font-size: 14px; font-weight: 500;
            transition: opacity 0.6s;
        }
        .flash-revoked { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }

        .search-bar {
            background: white; padding: 16px 20px; border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px;
            border: 1px solid #e2e8f0; display: flex; gap: 12px;
        }
        .search-bar input {
            flex: 1; padding: 11px 16px; border: 1px solid #e2e8f0;
            border-radius: 8px; font-size: 14px; outline: none;
        }
        .search-bar input:focus { border-color: #1e3a8a; }
        .search-bar button {
            background: var(--portal-blue); color: white; border: none;
            padding: 0 22px; border-radius: 8px; font-weight: 600;
            cursor: pointer; font-size: 14px;
        }

        .table-wrap {
            background: white; border-radius: 14px; overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
        }
        table { width: 100%; border-collapse: collapse; }
        thead { background: linear-gradient(90deg, var(--portal-dark), var(--portal-blue)); }
        th { color: white; padding: 16px 18px; text-align: left; font-size: 13px; font-weight: 600; }
        td { padding: 15px 18px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafbff; }

        .badge {
            display: inline-block; padding: 4px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 700; text-transform: uppercase;
        }
        .badge-valid   { background:#d4edda; color:#155724; }
        .badge-expired { background:#f8d7da; color:#721c24; }
        .badge-revoked { background:#e2e8f0; color:#475569; }
        .badge-pending { background:#fff3cd; color:#856404; }

        .action-btn {
            display: inline-block; padding: 6px 12px; border-radius: 6px;
            font-size: 12px; font-weight: 600; text-decoration: none;
            transition: all 0.2s; margin-right: 4px;
        }
        .btn-view   { background:#eff6ff; color:#2563eb; }
        .btn-view:hover { background:#2563eb; color:white; }
        .btn-revoke { background:#fef2f2; color:#dc2626; }
        .btn-revoke:hover { background:#dc2626; color:white; }

        .empty { text-align:center; padding:50px; color:#94a3b8; font-style:italic; }
    </style>
</head>
<body>

<div class="nav">
    <h3>🪪 Guest Passes</h3>
    <div class="nav-links">
        <a href="issue_guest_pass.php" class="btn-nav btn-issue">+ Issue New Pass</a>
        <a href="<?php echo $back_url; ?>" class="btn-nav btn-back">← Dashboard</a>
    </div>
</div>

<div class="container">

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'revoked'): ?>
        <div id="flash-msg" class="flash flash-revoked">
            <strong>Pass Revoked:</strong> The guest pass has been deactivated and will no longer grant entry.
        </div>
        <script>
            var f = document.getElementById('flash-msg');
            if (f) {
                if (window.history.replaceState) window.history.replaceState(null,'',window.location.pathname);
                setTimeout(function(){ f.style.opacity='0'; setTimeout(function(){ f.style.display='none'; },600); }, 3000);
            }
        </script>
    <?php endif; ?>

    <div class="search-bar">
        <form method="GET" style="display:flex;gap:12px;width:100%;">
            <input type="text" name="search" placeholder="Search by guest name, pass ID or phone..."
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Pass ID</th>
                    <th>Guest Name</th>
                    <th>Phone</th>
                    <th>Institution</th>
                    <th>Whom to Meet</th>
                    <th>Valid From</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (mysqli_num_rows($result) > 0):
                while ($row = mysqli_fetch_assoc($result)):
                    // Determine status
                    if (!$row['is_active']) {
                        $badge = 'badge-revoked'; $label = 'Revoked';
                    } elseif ($today > $row['valid_until']) {
                        $badge = 'badge-expired'; $label = 'Expired';
                    } elseif ($today < $row['valid_from']) {
                        $badge = 'badge-pending'; $label = 'Upcoming';
                    } else {
                        $badge = 'badge-valid'; $label = 'Active';
                    }
                    $enc_id = urlencode($row['pass_id']);
            ?>
                <tr>
                    <td><strong style="font-family:monospace;"><?php echo htmlspecialchars($row['pass_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($row['guest_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['guest_phone'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($row['institution']); ?></td>
                    <td><?php echo htmlspecialchars($row['whom_to_meet'] ?? '—'); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['valid_from'])); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['valid_until'])); ?></td>
                    <td><span class="badge <?php echo $badge; ?>"><?php echo $label; ?></span></td>
                    <td>
                        <a href="guest_pass_result.php?id=<?php echo $enc_id; ?>" class="action-btn btn-view">View</a>
                        <?php if ($row['is_active'] && $today <= $row['valid_until']): ?>
                            <a href="guest_passes.php?revoke=<?php echo $enc_id; ?>"
                               class="action-btn btn-revoke"
                               onclick="return confirm('Revoke this guest pass? The guest will no longer be allowed entry.')">
                                Revoke
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9" class="empty">No guest passes issued yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>