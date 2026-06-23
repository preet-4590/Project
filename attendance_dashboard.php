<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('db_config.php');

if (!isset($_SESSION['clerk_user'])) {
    header("Location: login.php");
    exit;
}

$search_query = $_GET['search_query'] ?? $_GET['search'] ?? '';
$safe_search  = mysqli_real_escape_string($conn, trim($search_query));
$filter       = $_GET['filter'] ?? 'both'; // 'students', 'guests', 'both'

// ── Build unified query ───────────────────────────────────────────────────────
// Student entries: JOIN with students table
$student_query = "SELECT
                    a.attendance_id,
                    a.direction,
                    a.gate_no,
                    a.log_time,
                    a.entry_type,
                    s.name         AS entry_name,
                    s.unique_id    AS entry_id,
                    s.roll_no      AS entry_roll,
                    s.institution  AS entry_inst
                  FROM student_attendance a
                  INNER JOIN students s ON a.student_id = s.unique_id
                  WHERE a.entry_type = 'student'";

if (!empty($safe_search)) {
    $student_query .= " AND (s.name LIKE '%$safe_search%'
                        OR s.unique_id = '$safe_search'
                        OR s.roll_no LIKE '%$safe_search%'
                        OR s.institution LIKE '%$safe_search%')";
}

// Guest entries: JOIN with guest_passes table
$guest_query = "SELECT
                    a.attendance_id,
                    a.direction,
                    a.gate_no,
                    a.log_time,
                    a.entry_type,
                    g.guest_name   AS entry_name,
                    g.pass_id      AS entry_id,
                    NULL           AS entry_roll,
                    g.institution  AS entry_inst
                  FROM student_attendance a
                  INNER JOIN guest_passes g ON a.student_id = g.pass_id
                  WHERE a.entry_type = 'guest'";

if (!empty($safe_search)) {
    $guest_query .= " AND (g.guest_name LIKE '%$safe_search%'
                      OR g.pass_id LIKE '%$safe_search%'
                      OR g.institution LIKE '%$safe_search%')";
}

// Combine based on filter
if ($filter === 'students') {
    $final_query = $student_query . " ORDER BY a.log_time DESC";
} elseif ($filter === 'guests') {
    $final_query = $guest_query . " ORDER BY a.log_time DESC";
} else {
    $final_query = "(" . $student_query . ") UNION ALL (" . $guest_query . ") ORDER BY log_time DESC";
}

$logs = mysqli_query($conn, $final_query);
if (!$logs) {
    die("Database Query Failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Activity Log - Gate Entry Management System</title>
    <style>
        :root {
            --primary-dark: #1e3d8f;
            --secondary-dark: #152b66;
            --bg-gradient-start: #0f2042;
            --bg-gradient-end: #1e3d8f;
            --light-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            margin: 0;
            padding: 40px 20px;
            box-sizing: border-box;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .log-container {
            width: 100%;
            max-width: 1100px;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(15, 32, 66, 0.3);
            box-sizing: border-box;
        }

        .top-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .workspace-title {
            margin: 0;
            color: var(--text-main);
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            color: var(--primary-dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        .back-btn:hover {
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }

        .search-card {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 30px;
        }

        .search-form {
            display: flex;
            gap: 12px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            font-size: 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            outline: none;
            color: var(--text-main);
            transition: all 0.2s;
            background: #ffffff;
        }

        .search-input:focus {
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(30, 61, 143, 0.1);
        }

        .search-submit-btn {
            background-color: var(--primary-dark);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .search-submit-btn:hover {
            background-color: var(--secondary-dark);
        }

        .search-clear-btn {
            background-color: #e2e8f0;
            color: #475569;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s;
        }

        .search-clear-btn:hover {
            background-color: #cbd5e1;
        }

        .table-container {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f8fafc;
            color: var(--text-muted);
            text-align: left;
            padding: 16px 24px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            font-size: 14px;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .student-name {
            font-weight: 600;
            color: var(--text-main);
            display: block;
            margin-bottom: 2px;
        }

        .student-meta {
            font-size: 12px;
            color: var(--text-muted);
        }

        .status-pill {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
            min-width: 40px;
        }

        .status-IN {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-OUT {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .gate-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: #f1f5f9;
            color: #334155;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid var(--border-color);
        }

        .inst-text {
            font-weight: 600;
            color: var(--primary-dark);
        }

        .timestamp-date {
            font-weight: 600;
            color: var(--text-main);
            display: block;
        }

        .timestamp-time {
            font-size: 12px;
            color: var(--text-muted);
        }

        .no-data {
            text-align: center;
            padding: 50px !important;
            color: var(--text-muted);
            font-style: italic;
        }
    </style>
</head>

<body>

    <div class="log-container">

        <div class="top-navigation">
            <h2 class="workspace-title">Gate Activity Log</h2>
            <?php
                // Back button goes to the correct dashboard based on role
                $back_url = ($_SESSION['role'] === 'super_admin') ? 'admin_dashboard.php' : 'dashboard.php';
            ?>
        </div>

        <div class="search-card">
            <form method="GET" action="attendance_dashboard.php" class="search-form">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="text" name="search_query" class="search-input"
                    placeholder="Search logs by name, roll no, ID, or institution..."
                    value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="search-submit-btn">Search Records</button>
                <?php if (!empty($search_query)): ?>
                    <a href="attendance_dashboard.php?filter=<?php echo $filter; ?>" class="search-clear-btn">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Filter Tabs -->
        <div style="display:flex; gap:8px; margin-bottom:20px;">
            <?php
            $tabs = ['both' => '👥 All Entries', 'students' => '🎓 Students Only', 'guests' => '🪪 Guests Only'];
            foreach ($tabs as $key => $label):
                $active = ($filter === $key);
                $href   = "attendance_dashboard.php?filter={$key}" . (!empty($search_query) ? "&search_query=" . urlencode($search_query) : "");
            ?>
            <a href="<?php echo $href; ?>"
               style="display:inline-block; padding:9px 18px; border-radius:8px; font-size:13px;
                      font-weight:600; text-decoration:none; transition:all 0.2s;
                      <?php echo $active
                          ? 'background:#1e3d8f;color:white;box-shadow:0 4px 10px rgba(30,61,143,0.2);'
                          : 'background:white;color:#475569;border:1px solid #e2e8f0;'; ?>">
                <?php echo $label; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Name / ID</th>
                        <th>Institution</th>
                        <th>Movement</th>
                        <th>Gate</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($logs) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($logs)): ?>
                            <tr>
                                <td>
                                    <?php if ($row['entry_type'] === 'guest'): ?>
                                        <span style="display:inline-block;padding:4px 10px;background:#ede9fe;color:#5b21b6;border-radius:20px;font-size:11px;font-weight:700;">🪪 Guest</span>
                                    <?php else: ?>
                                        <span style="display:inline-block;padding:4px 10px;background:#dbeafe;color:#1e40af;border-radius:20px;font-size:11px;font-weight:700;">🎓 Student</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="student-name">
                                        <?php if ($row['entry_type'] === 'guest'): ?>
                                            <a href="guest_pass_result.php?id=<?php echo urlencode($row['entry_id']); ?>&view=1"
                                               style="color:#5b21b6;text-decoration:none;font-weight:600;">
                                                <?php echo htmlspecialchars($row['entry_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($row['entry_name']); ?>
                                        <?php endif; ?>
                                    </span>
                                    <div class="student-meta">
                                        <span>ID: <?php echo htmlspecialchars($row['entry_id']); ?></span>
                                        <?php if (!empty($row['entry_roll'])): ?>
                                            <span style="margin-left:8px;padding-left:8px;border-left:1px solid #cbd5e1;">
                                                Roll: <?php echo htmlspecialchars($row['entry_roll']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="inst-text"><?php echo htmlspecialchars($row['entry_inst']); ?></td>
                                <td>
                                    <span class="status-pill status-<?php echo $row['direction']; ?>">
                                        <?php echo $row['direction']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="gate-badge">
                                        <?php echo !empty($row['gate_no']) ? htmlspecialchars($row['gate_no']) : 'N/A'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="timestamp-date"><?php echo date('d M Y', strtotime($row['log_time'])); ?></span>
                                    <span class="timestamp-time"><?php echo date('h:i A', strtotime($row['log_time'])); ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">No gate activity log entries found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>

</html>