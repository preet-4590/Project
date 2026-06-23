<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: login.php");
    exit;
}

$today = date('Y-m-d');

$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM students WHERE is_deleted = 0"))['total'];
$in_today       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM student_attendance WHERE direction='IN' AND entry_type='student' AND DATE(log_time)='$today'"))['total'];
$out_today      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM student_attendance WHERE direction='OUT' AND entry_type='student' AND DATE(log_time)='$today'"))['total'];
$active_guests  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM guest_passes WHERE is_active=1 AND '$today' BETWEEN valid_from AND valid_until"))['total'];
$total_gndec    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM students WHERE institution='GNDEC' AND is_deleted=0"))['total'];
$total_gndpc    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM students WHERE institution='GNDPC' AND is_deleted=0"))['total'];
$total_gnditi   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM students WHERE institution='GNDITI' AND is_deleted=0"))['total'];

$guest_passes_result = mysqli_query($conn, "SELECT * FROM guest_passes ORDER BY issued_at DESC LIMIT 5");

$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
$query  = "SELECT * FROM students WHERE is_deleted = 0";
if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR roll_no LIKE '%$search%' OR unique_id LIKE '%$search%' OR institution LIKE '%$search%')";
}
$query .= " ORDER BY institution ASC, unique_id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard | CGEMS</title>
    <style>
        :root {
            --navy:   #0f172a;
            --blue:   #1e3d8f;
            --blue2:  #2563eb;
            --bg:     #f1f5f9;
            --white:  #ffffff;
            --border: #e2e8f0;
            --text:   #1e293b;
            --muted:  #64748b;
            --green:  #10b981;
            --red:    #ef4444;
            --yellow: #f59e0b;
            --purple: #7c3aed;
            --gold:   #d97706;
            --sidebar:240px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar); background: var(--navy);
            min-height: 100vh; display: flex; flex-direction: column;
            position: fixed; top:0; left:0; z-index:100;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
        }
        .sidebar-brand { padding:24px 20px; border-bottom:1px solid rgba(255,255,255,0.08); }
        .sidebar-brand h1 { color:white; font-size:20px; font-weight:800; letter-spacing:-0.5px; }
        .sidebar-brand p  { color:rgba(255,255,255,0.4); font-size:11px; margin-top:3px; text-transform:uppercase; letter-spacing:1px; }

        .super-badge {
            margin:14px 16px; padding:10px 14px;
            background:linear-gradient(135deg,rgba(245,158,11,0.2),rgba(217,119,6,0.15));
            border:1px solid rgba(245,158,11,0.3);
            border-radius:10px; display:flex; align-items:center; gap:8px;
        }
        .super-badge span { color:#fbbf24; font-size:12px; font-weight:700; }
        .super-badge small { color:rgba(255,255,255,0.4); font-size:10px; display:block; }

        .sidebar-nav { padding:8px 12px; flex:1; }
        .nav-section-label { color:rgba(255,255,255,0.3); font-size:10px; text-transform:uppercase; letter-spacing:1px; padding:12px 8px 6px; font-weight:600; }
        .nav-item {
            display:flex; align-items:center; gap:10px;
            padding:11px 12px; border-radius:8px;
            color:rgba(255,255,255,0.6); text-decoration:none;
            font-size:13px; font-weight:500; transition:all 0.2s; margin-bottom:2px;
        }
        .nav-item:hover { background:rgba(255,255,255,0.08); color:white; }
        .nav-item.active { background:var(--blue); color:white; font-weight:600; }
        .nav-item .icon { font-size:16px; width:20px; text-align:center; }
        .nav-item .badge { margin-left:auto; background:var(--green); color:white; font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px; }
        .nav-item .badge.gold { background:var(--gold); }
        .nav-item.logout { color:rgba(239,68,68,0.8); }
        .nav-item.logout:hover { background:rgba(239,68,68,0.1); color:var(--red); }

        .sidebar-bottom { padding:12px; border-top:1px solid rgba(255,255,255,0.08); }
        .sidebar-user { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; background:rgba(255,255,255,0.05); margin-bottom:8px; }
        .user-avatar { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,var(--gold),#92400e); display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; color:white; flex-shrink:0; }
        .user-info-side small { color:rgba(255,255,255,0.4); font-size:10px; display:block; }
        .user-info-side span  { color:white; font-size:12px; font-weight:600; }

        /* ── Main ── */
        .main { margin-left:var(--sidebar); flex:1; display:flex; flex-direction:column; min-height:100vh; }

        .topbar {
            background:var(--white); border-bottom:1px solid var(--border);
            padding:0 32px; height:64px; display:flex;
            align-items:center; justify-content:space-between;
            position:sticky; top:0; z-index:50;
            box-shadow:0 1px 3px rgba(0,0,0,0.06);
        }
        .topbar-left h2 { font-size:18px; font-weight:700; }
        .topbar-left p  { font-size:12px; color:var(--muted); margin-top:1px; }
        .topbar-right { display:flex; gap:10px; }
        .btn-topbar { display:inline-flex; align-items:center; gap:6px; padding:9px 16px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; border:none; cursor:pointer; transition:all 0.2s; }
        .btn-primary { background:var(--blue); color:white; box-shadow:0 2px 8px rgba(30,61,143,0.2); }
        .btn-primary:hover { background:#162e6f; transform:translateY(-1px); }
        .btn-secondary { background:#f8fafc; color:var(--muted); border:1px solid var(--border); }
        .btn-secondary:hover { background:#e2e8f0; }

        .content { padding:28px 32px; flex:1; }

        /* Flash */
        .flash { padding:14px 18px; border-radius:10px; font-size:14px; font-weight:500; margin-bottom:24px; display:flex; align-items:center; gap:10px; transition:opacity 0.6s; }
        .flash-blue  { background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; }

        /* ── Stats row 1 — main KPIs ── */
        .stats-main { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:18px; }
        .stat-card { background:var(--white); border-radius:14px; padding:20px 22px; border:1px solid var(--border); box-shadow:0 2px 8px rgba(0,0,0,0.04); display:flex; align-items:center; gap:16px; transition:transform 0.2s,box-shadow 0.2s; }
        .stat-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,0.08); }
        .stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .stat-icon.blue   { background:#eff6ff; }
        .stat-icon.green  { background:#f0fdf4; }
        .stat-icon.red    { background:#fef2f2; }
        .stat-icon.purple { background:#f5f3ff; }
        .stat-icon.gold   { background:#fffbeb; }
        .stat-body h4 { font-size:12px; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
        .stat-body span { font-size:28px; font-weight:800; color:var(--text); line-height:1; }

        /* ── Stats row 2 — institution breakdown ── */
        .stats-inst { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; margin-bottom:28px; }
        .inst-card { background:var(--white); border-radius:12px; padding:16px 20px; border:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 4px rgba(0,0,0,0.04); }
        .inst-card-label { font-size:13px; font-weight:700; color:var(--text); }
        .inst-card-sub   { font-size:11px; color:var(--muted); margin-top:2px; }
        .inst-card-count { font-size:26px; font-weight:800; color:var(--blue); }

        /* Section header */
        .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
        .section-header h3 { font-size:16px; font-weight:700; display:flex; align-items:center; gap:8px; }
        .section-header a  { font-size:13px; color:var(--blue2); font-weight:600; text-decoration:none; }
        .section-header a:hover { text-decoration:underline; }

        /* Search */
        .search-bar { background:var(--white); border:1px solid var(--border); border-radius:10px; padding:12px 16px; display:flex; gap:10px; margin-bottom:16px; box-shadow:0 1px 4px rgba(0,0,0,0.04); }
        .search-bar input { flex:1; border:none; outline:none; font-size:14px; color:var(--text); background:transparent; }
        .search-bar button { background:var(--blue); color:white; border:none; padding:8px 20px; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer; }
        .search-bar button:hover { background:#162e6f; }
        .search-bar .btn-clear { background:#f1f5f9; color:var(--muted); border:1px solid var(--border); }

        /* Table */
        .table-card { background:var(--white); border-radius:14px; border:1px solid var(--border); overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.04); margin-bottom:28px; }
        table { width:100%; border-collapse:collapse; }
        thead { background:linear-gradient(90deg,var(--navy),var(--blue)); }
        th { color:white; padding:14px 18px; text-align:left; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; }
        td { padding:14px 18px; border-bottom:1px solid #f1f5f9; font-size:13px; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#fafbff; }

        .id-chip { background:#eff6ff; color:var(--blue); padding:4px 10px; border-radius:6px; font-family:monospace; font-size:13px; font-weight:700; }
        .inst-badge { display:inline-block; background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; }
        .qr-img { width:52px; height:52px; border-radius:8px; border:1px solid var(--border); object-fit:cover; transition:transform 0.2s; }
        .qr-img:hover { transform:scale(1.15); }

        .btn-action { display:inline-flex; align-items:center; padding:6px 12px; border-radius:7px; font-size:12px; font-weight:600; text-decoration:none; transition:all 0.2s; margin-right:4px; white-space:nowrap; }
        .btn-view:hover { background:#2563eb; color:white; }
        .btn-view  { background:#eff6ff; color:#2563eb; }
        .btn-log   { background:#f0fdf4; color:#059669; }
        .btn-log:hover  { background:#059669; color:white; }
        .btn-edit  { background:#fefce8; color:#854d0e; }
        .btn-edit:hover { background:#f59e0b; color:white; }

        .badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge-active   { background:#d1fae5; color:#065f46; }
        .badge-expired  { background:#fee2e2; color:#991b1b; }
        .badge-upcoming { background:#fff3cd; color:#856404; }
        .badge-revoked  { background:#f1f5f9; color:#475569; }

        .empty-row td { text-align:center; padding:48px; color:var(--muted); font-style:italic; }

        @media(max-width:1024px) { .stats-main,.stats-inst { grid-template-columns:repeat(2,1fr); } }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <h1>CGEMS</h1>
        <p>Gate Entry System</p>
    </div>

    <div class="super-badge">
        <span>👑</span>
        <div>
            <span>Main Administrator</span>
            <small>All institutions access</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Overview</div>
        <a href="admin_dashboard.php" class="nav-item active">
            <span class="icon">🏠</span> Dashboard
        </a>
        <a href="attendance_dashboard.php" class="nav-item">
            <span class="icon">📋</span> Gate Activity Log
        </a>

        <div class="nav-section-label">Guest Passes</div>
        <a href="guest_passes.php" class="nav-item">
            <span class="icon">🪪</span> All Guest Passes
            <?php if ($active_guests > 0): ?>
                <span class="badge gold"><?php echo $active_guests; ?></span>
            <?php endif; ?>
        </a>
        <a href="issue_guest_pass.php" class="nav-item">
            <span class="icon">➕</span> Issue Guest Pass
        </a>

        <div class="nav-section-label">Account</div>
        <a href="change_password.php" class="nav-item">
            <span class="icon">🔐</span> Change Password
        </a>
        <a href="logout.php" class="nav-item logout">
            <span class="icon">🚪</span> Logout
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-user">
            <div class="user-avatar">👑</div>
            <div class="user-info-side">
                <small>Logged in as</small>
                <span><?php echo htmlspecialchars($_SESSION['clerk_user']); ?></span>
            </div>
        </div>
    </div>
</aside>

<!-- Main -->
<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <h2>Main Admin Dashboard</h2>
            <p><?php echo date('l, d F Y'); ?> — All Institutions</p>
        </div>
        <div class="topbar-right">
            <a href="add_student.php" class="btn-topbar btn-primary">➕ Register Student</a>
        </div>
    </header>

    <div class="content">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div id="flash-msg" class="flash flash-blue">✅ Student details updated successfully.</div>
        <script>
            var fm = document.getElementById('flash-msg');
            if (fm) {
                if (window.history.replaceState) window.history.replaceState(null,'',window.location.pathname);
                setTimeout(function(){ fm.style.opacity='0'; setTimeout(function(){ fm.style.display='none'; },600); },3500);
            }
        </script>
        <?php endif; ?>

        <!-- Main KPI stats -->
        <div class="stats-main">
            <div class="stat-card">
                <div class="stat-icon blue">🎓</div>
                <div class="stat-body"><h4>Total Students</h4><span><?php echo $total_students; ?></span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✅</div>
                <div class="stat-body"><h4>IN Today</h4><span><?php echo $in_today; ?></span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">🚪</div>
                <div class="stat-body"><h4>OUT Today</h4><span><?php echo $out_today; ?></span></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">🪪</div>
                <div class="stat-body"><h4>Active Guests</h4><span><?php echo $active_guests; ?></span></div>
            </div>
        </div>

        <!-- Institution breakdown -->
        <div class="stats-inst">
            <div class="inst-card">
                <div><div class="inst-card-label">GNDEC</div><div class="inst-card-sub">Engineering College</div></div>
                <div class="inst-card-count"><?php echo $total_gndec; ?></div>
            </div>
            <div class="inst-card">
                <div><div class="inst-card-label">GNDPC</div><div class="inst-card-sub">Polytechnic College</div></div>
                <div class="inst-card-count"><?php echo $total_gndpc; ?></div>
            </div>
            <div class="inst-card">
                <div><div class="inst-card-label">GNDITI</div><div class="inst-card-sub">Industrial Training Inst.</div></div>
                <div class="inst-card-count"><?php echo $total_gnditi; ?></div>
            </div>
        </div>

        <!-- Students table -->
        <div class="section-header">
            <h3>🎓 All Registered Students</h3>
        </div>

        <div class="search-bar">
            <form method="GET" style="display:flex;gap:10px;width:100%;">
                <input type="text" name="search" placeholder="Search by institution, name, roll number or unique ID..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
                <?php if (!empty($search)): ?>
                    <button type="button" class="btn-clear" onclick="window.location='admin_dashboard.php'">Clear</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Institution</th>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Roll No</th>
                        <th>QR Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)):
                        $enc = urlencode($row['unique_id']);
                ?>
                    <tr>
                        <td><span class="inst-badge"><?php echo htmlspecialchars($row['institution']); ?></span></td>
                        <td><span class="id-chip"><?php echo htmlspecialchars($row['unique_id']); ?></span></td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                            <small style="color:var(--muted);"><?php echo htmlspecialchars($row['course']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['roll_no']); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($row['qr_path']); ?>" target="_blank">
                                <img src="<?php echo htmlspecialchars($row['qr_path']); ?>" class="qr-img" alt="QR">
                            </a>
                        </td>
                        <td>
                            <a href="view_profile.php?id=<?php echo $enc; ?>" class="btn-action btn-view">👤 Profile</a>
                            <a href="attendance_dashboard.php?search=<?php echo $enc; ?>" class="btn-action btn-log">📋 Logs</a>
                            <a href="edit_student.php?id=<?php echo $enc; ?>" class="btn-action btn-edit">✏️ Edit</a>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr class="empty-row"><td colspan="6">No student records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Guest passes -->
        <div class="section-header">
            <h3>🪪 Recent Guest Passes
                <?php if ($active_guests > 0): ?>
                    <span style="background:var(--purple);color:white;font-size:11px;padding:3px 10px;border-radius:20px;">
                        <?php echo $active_guests; ?> Active
                    </span>
                <?php endif; ?>
            </h3>
            <a href="guest_passes.php">View All →</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Pass ID</th><th>Guest Name</th><th>Institution</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php
                $today_check = date('Y-m-d');
                if (mysqli_num_rows($guest_passes_result) > 0):
                    while ($gp = mysqli_fetch_assoc($guest_passes_result)):
                        if (!$gp['is_active'])                        { $bc='badge-revoked';  $bl='Revoked'; }
                        elseif ($today_check > $gp['valid_until'])    { $bc='badge-expired';  $bl='Expired'; }
                        elseif ($today_check < $gp['valid_from'])     { $bc='badge-upcoming'; $bl='Upcoming'; }
                        else                                           { $bc='badge-active';   $bl='Active'; }
                        $eg = urlencode($gp['pass_id']);
                ?>
                    <tr>
                        <td><strong style="font-family:monospace;font-size:12px;"><?php echo htmlspecialchars($gp['pass_id']); ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($gp['guest_name']); ?></strong></td>
                        <td><span class="inst-badge"><?php echo htmlspecialchars($gp['institution']); ?></span></td>
                        <td><span class="badge <?php echo $bc; ?>"><?php echo $bl; ?></span></td>
                        <td><a href="guest_pass_result.php?id=<?php echo $eg; ?>&view=1" class="btn-action btn-view">👤 View Profile</a></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr class="empty-row"><td colspan="5">No guest passes issued yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>
</body>
</html>