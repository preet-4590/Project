<?php
// START OF FILE
session_start(); 

// Check if any authorized user is logged in
if (!isset($_SESSION['clerk_user'])) {
    header("Location: login.php");
    exit();
}
include('db_config.php');

// Security check
if (empty($_SESSION['clerk_user'])) {
    header("Location: login.php");
    ob_end_clean();
    exit();
}

// Handle the student data fetching
if (isset($_GET['id'])) {
    $u_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Fetch student record matching unique_id
    $sql = "SELECT * FROM students WHERE unique_id='$u_id'";
    $res = mysqli_query($conn, $sql);
    $s = mysqli_fetch_assoc($res);

    if (!$s) {
        die("Student record not found.");
    }
} else {
    die("No ID provided.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ID Pass - <?php echo htmlspecialchars($s['name']); ?></title>
    <style>
        :root {
            --portal-dark: #223a5e;
            --portal-blue: #1e3d8f;
            --portal-bg: #f4f6f9;
            --text-main: #2d3748;
            --text-muted: #718096;
            --border-color: #e2e8f0;
        }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--portal-bg);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-main);
        }

        .profile-card {
            width: 480px;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(34, 58, 94, 0.08);
            border: 1px solid var(--border-color);
        }

        .header {
            background: var(--portal-dark);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }

        .header img.avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.2);
            object-fit: cover;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .header h2 {
            margin: 0 0 6px 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header .uid-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .content {
            padding: 28px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
        }

        .full-row {
            grid-column: span 2;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-item label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }

        .detail-item span {
            font-size: 14px;
            font-weight: 600;
            color: #1a202c;
        }

        .bottom-verification {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 20px;
            padding-top: 20px;
        }

        .signature-area {
            display: flex;
            flex-direction: column;
        }

        .signature-img {
            height: 45px;
            max-width: 160px;
            object-fit: contain;
            margin-top: 6px;
            filter: contrast(1.1);
        }

        .qr-area {
            background: #f8fafc;
            padding: 8px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .qr-area img {
            width: 85px;
            height: 85px;
            display: block;
        }

        .footer-action {
            text-align: center;
            padding: 0 28px 28px 28px;
        }

        .btn-print {
            background-color: var(--portal-blue);
            color: #ffffff;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 12px rgba(30, 61, 143, 0.15);
        }

        .btn-print:hover {
            background-color: #162e6f;
        }

        .btn-print:active {
            transform: scale(0.99);
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .profile-card {
                box-shadow: none;
                border: 1px solid #cbd5e0;
                margin: auto;
            }

            .btn-print {
                display: none;
            }
            
            .footer-action {
                padding: 0;
            }
        }
    </style>
</head>

<body>
    <div class="profile-card">
        <div class="header">
            <img src="uploads/photos/<?php echo htmlspecialchars($s['photo']); ?>" alt="Student Photo" class="avatar">
            <h2><?php echo htmlspecialchars($s['name']); ?></h2>
            <div class="uid-badge">ID: <?php echo htmlspecialchars($s['unique_id']); ?></div>
        </div>

        <div class="content">
            <div class="details-grid">
                <div class="detail-item">
                    <label>Father's Name</label>
                    <span><?php echo htmlspecialchars($s['father_name'] ?? 'N/A'); ?></span>
                </div>

                <div class="detail-item">
                    <label>Roll Number</label>
                    <span><?php echo htmlspecialchars($s['roll_no']); ?></span>
                </div>

                <div class="detail-item">
                    <label>Institution</label>
                    <span><?php echo htmlspecialchars($s['institution']); ?></span>
                </div>

                <div class="detail-item">
                    <label>Gender</label>
                    <span><?php echo htmlspecialchars($s['gender'] ?? 'N/A'); ?></span>
                </div>

                <div class="detail-item full-row">
                    <label>Course</label>
                    <span><?php echo htmlspecialchars($s['course']); ?></span>
                </div>

                <div class="detail-item">
                    <label>Phone No</label>
                    <span><?php echo htmlspecialchars($s['phone_no']); ?></span>
                </div>

                <div class="detail-item">
                    <label>Email Address</label>
                    <span><?php echo !empty($s['email']) ? htmlspecialchars($s['email']) : 'N/A'; ?></span>
                </div>

                <div class="detail-item">
                    <label>Academic Period</label>
                    <span>
                        <?php
                        $start = $s['admission_year'];
                        $end = $s['passing_year'];
                        echo (!empty($start) && $start > 0) ? htmlspecialchars($start) . " - " . htmlspecialchars($end) : "YYYY - YYYY";
                        ?>
                    </span>
                </div>

                <div class="detail-item full-row">
                    <label>Permanent Address</label>
                    <span><?php echo htmlspecialchars($s['address'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <div class="bottom-verification">
                <div class="signature-area">
                    <label style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.8px;">Authorized Signature</label>
                    <img src="uploads/signatures/<?php echo htmlspecialchars($s['signature']); ?>" alt="Signature" class="signature-img">
                </div>
                <div class="qr-area">
                    <img src="<?php echo htmlspecialchars($s['qr_path']); ?>" alt="Verification QR Code">
                </div>
            </div>
        </div>

        <div class="footer-action">
            <?php if (isset($_GET['mail']) && $_GET['mail'] === 'sent'): ?>
                <div style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;
                     border-radius:8px;padding:10px 16px;font-size:13px;font-weight:600;
                     margin-bottom:14px;text-align:center;" id="mail-flash">
                    📧 ID Pass emailed to <?php echo htmlspecialchars($s['email']); ?> successfully.
                </div>
                <script>
                    var mf = document.getElementById('mail-flash');
                    if (mf) { setTimeout(function(){ mf.style.opacity='0'; mf.style.transition='opacity 0.6s'; setTimeout(function(){ mf.style.display='none'; }, 600); }, 4000); }
                    if (window.history.replaceState) window.history.replaceState(null,'',window.location.pathname+'?id=<?php echo urlencode($s['unique_id']); ?>');
                </script>
            <?php elseif (isset($_GET['mail']) && $_GET['mail'] === 'failed'): ?>
                <div style="background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;
                     border-radius:8px;padding:10px 16px;font-size:13px;font-weight:600;
                     margin-bottom:14px;text-align:center;">
                    ⚠️ Email could not be sent. Check the student's email address.
                </div>
            <?php elseif (isset($_GET['mail']) && $_GET['mail'] === 'noemail'): ?>
                <div style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;
                     border-radius:8px;padding:10px 16px;font-size:13px;font-weight:600;
                     margin-bottom:14px;text-align:center;">
                    ⚠️ No email address on file for this student. Please edit the profile first.
                </div>
            <?php endif; ?>

            <div style="display:flex;gap:10px;">
                <button onclick="window.print()" class="btn-print" style="flex:1;">
                    🖨 Print ID Pass
                </button>
                <?php if (!empty($s['email'])): ?>
                    <a href="send_id_pass.php?id=<?php echo urlencode($s['unique_id']); ?>"
                       class="btn-print"
                       style="flex:1;background:#059669;color:white;text-decoration:none;
                              text-align:center;display:flex;align-items:center;justify-content:center;"
                       onclick="return confirm('Send ID Pass to <?php echo htmlspecialchars($s['email']); ?>?')">
                        📧 Send to Email
                    </a>
                <?php else: ?>
                    <a href="edit_student.php?id=<?php echo urlencode($s['unique_id']); ?>"
                       class="btn-print"
                       style="flex:1;background:#f59e0b;color:white;text-decoration:none;
                              text-align:center;display:flex;align-items:center;justify-content:center;">
                        ✏️ Add Email First
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>