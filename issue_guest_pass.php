<?php
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user']) || $_SESSION['role'] === 'guard') {
    header("Location: login.php");
    exit;
}

$is_admin    = ($_SESSION['role'] === 'super_admin');
$default_inst = $is_admin ? "" : $_SESSION['clerk_inst'];
$today        = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Guest Pass | Gate Entry System</title>
    <style>
        :root {
            --portal-dark:  #223a5e;
            --portal-blue:  #1e3d8f;
            --portal-bg:    #f4f6f9;
            --border-color: #cbd5e0;
            --text-main:    #2d3748;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--portal-bg);
            margin: 0; padding: 50px 20px;
            display: flex; justify-content: center;
            align-items: flex-start; min-height: 100vh;
            box-sizing: border-box; color: var(--text-main);
        }
        .form-container {
            width: 100%; max-width: 660px;
            background: #fff; padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        .form-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 32px;
        }
        .form-header h2 {
            margin: 0; color: var(--portal-dark);
            font-size: 22px; font-weight: 700;
        }
        .back-btn {
            font-size: 13px; color: var(--portal-blue);
            text-decoration: none; font-weight: 600;
            border: 1px solid var(--border-color);
            padding: 7px 14px; border-radius: 6px;
            background: #f8fafc; transition: all 0.2s;
        }
        .back-btn:hover { background: #e2e8f0; }

        .grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 20px; }
        .full-width { grid-column: span 2; }

        .section-label {
            grid-column: span 2;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px;
            color: #94a3b8; margin-bottom: -6px;
        }
        hr.divider {
            grid-column: span 2; border: none;
            border-top: 1px solid #e2e8f0; margin: 4px 0;
        }

        label {
            font-weight: 600; color: #4a5568;
            display: block; margin-bottom: 7px; font-size: 13px;
        }
        input[type="text"], input[type="date"],
        input[type="tel"], select, textarea {
            width: 100%; padding: 11px 14px;
            border: 1px solid var(--border-color);
            border-radius: 6px; box-sizing: border-box;
            font-size: 14px; color: var(--text-main);
            background: #fff; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.15);
        }
        textarea { resize: vertical; min-height: 70px; }

        .validity-row {
            display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
        }

        .btn-submit {
            width: 100%; background: var(--portal-blue); color: #fff;
            border: none; padding: 13px; border-radius: 6px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            margin-top: 28px; transition: background 0.2s;
            box-shadow: 0 4px 12px rgba(30,61,143,0.15);
        }
        .btn-submit:hover { background: #162e6f; }
    </style>
</head>
<body>
<div class="form-container">
    <div class="form-header">
        <h2>🪪 Issue Guest Pass</h2>
        <a href="<?php echo $is_admin ? 'admin_dashboard.php' : 'dashboard.php'; ?>" class="back-btn">← Back</a>
    </div>

    <form action="save_guest_pass.php" method="POST">
        <div class="grid">

            <p class="section-label">Guest Information</p>
            <hr class="divider">

            <div>
                <label>Guest Full Name</label>
                <input type="text" name="guest_name" placeholder="Enter full name" required>
            </div>
            <div>
                <label>Phone Number</label>
                <input type="tel" name="guest_phone" placeholder="10-digit number">
            </div>
            <div>
                <label>Email Address <span style="font-size:11px;font-weight:400;color:#94a3b8;">(QR will be sent here)</span></label>
                <input type="email" name="guest_email" placeholder="guest@example.com">
            </div>
            <div>
                <label>Whom to Meet</label>
                <input type="text" name="whom_to_meet" placeholder="Faculty / Staff name">
            </div>
            <div>
                <label>Institution</label>
                <?php if ($is_admin): ?>
                    <select name="institution" required>
                        <option value="">-- Select --</option>
                        <option value="GNDEC">GNDEC</option>
                        <option value="GNDPC">GNDPC</option>
                        <option value="GNDITI">GNDITI</option>
                    </select>
                <?php else: ?>
                    <input type="text" value="<?php echo htmlspecialchars($default_inst); ?>" readonly
                           style="background:#f8fafc; color:#718096;">
                    <input type="hidden" name="institution" value="<?php echo htmlspecialchars($default_inst); ?>">
                <?php endif; ?>
            </div>

            <div class="full-width">
                <label>Purpose of Visit</label>
                <textarea name="purpose" placeholder="e.g. Attending seminar, Meeting with HOD, Maintenance work..."></textarea>
            </div>

            <p class="section-label">Pass Validity</p>
            <hr class="divider">

            <div>
                <label>Valid From</label>
                <input type="date" name="valid_from" value="<?php echo $today; ?>" min="<?php echo $today; ?>" required>
            </div>
            <div>
                <label>Valid Until</label>
                <input type="date" name="valid_until" value="<?php echo $today; ?>" min="<?php echo $today; ?>" required>
            </div>

        </div>

        <button type="submit" name="save" class="btn-submit">Generate Guest Pass & QR Code</button>
    </form>
</div>

<script>
    // Auto-set valid_until min to match valid_from selection
    document.querySelector('[name="valid_from"]').addEventListener('change', function() {
        document.querySelector('[name="valid_until"]').min = this.value;
        if (document.querySelector('[name="valid_until"]').value < this.value) {
            document.querySelector('[name="valid_until"]').value = this.value;
        }
    });
</script>
</body>
</html>