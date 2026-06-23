<?php
ob_start();
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user'])) {
    header("Location: login.php");
    ob_end_clean();
    exit;
}

include('phpqrcode/qrlib.php');

// ─── RESTORE ACTION ──────────────────────────────────────────────────────────
if (isset($_POST['restore_id'])) {
    $restore_id = mysqli_real_escape_string($conn, $_POST['restore_id']);
    mysqli_query($conn, "UPDATE students SET is_deleted = 0 WHERE unique_id = '$restore_id'");

    $redirect = ($_SESSION['role'] === 'super_admin') ? 'admin_dashboard.php' : 'dashboard.php';
    header("Location: {$redirect}?msg=restored");
    ob_end_clean();
    exit;
}

// ─── NORMAL SAVE ─────────────────────────────────────────────────────────────
if (isset($_POST['save'])) {

    $u_id        = mysqli_real_escape_string($conn, $_POST['u_id']);
    $name        = mysqli_real_escape_string($conn, $_POST['name']);
    $father_name = mysqli_real_escape_string($conn, $_POST['father_name']);
    $roll        = mysqli_real_escape_string($conn, $_POST['roll']);
    $course      = mysqli_real_escape_string($conn, $_POST['course']);
    $gender      = mysqli_real_escape_string($conn, $_POST['gender']);
    $phone       = mysqli_real_escape_string($conn, $_POST['phone']);
    $email       = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $adm_year    = mysqli_real_escape_string($conn, $_POST['adm_year']);
    $pass_year   = mysqli_real_escape_string($conn, $_POST['pass_year']);
    $address     = mysqli_real_escape_string($conn, $_POST['address']);
    $inst        = mysqli_real_escape_string($conn, $_POST['institution']);

    // ── DUPLICATE CHECK ───────────────────────────────────────────────────────
    $dup_check = mysqli_query($conn,
        "SELECT unique_id, name, course, admission_year, passing_year
         FROM students
         WHERE roll_no = '$roll'
           AND institution = '$inst'
           AND is_deleted = 1
         LIMIT 1"
    );

    if (mysqli_num_rows($dup_check) > 0) {
        $dup = mysqli_fetch_assoc($dup_check);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Duplicate Student Detected</title>
            <style>
                :root { --portal-bg: #f4f6f9; --border-color: #cbd5e0; }
                body {
                    font-family: 'Segoe UI', sans-serif;
                    background: var(--portal-bg);
                    display: flex; justify-content: center; align-items: center;
                    min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box;
                }
                .card {
                    background: #fff; max-width: 540px; width: 100%;
                    border-radius: 14px; box-shadow: 0 10px 30px rgba(34,58,94,0.08);
                    border: 1px solid var(--border-color); overflow: hidden;
                }
                .card-header {
                    background: linear-gradient(135deg, #92400e, #b45309);
                    color: white; padding: 24px 28px;
                    display: flex; align-items: center; gap: 14px;
                }
                .card-header .icon { font-size: 36px; }
                .card-header h2 { margin: 0; font-size: 20px; font-weight: 700; }
                .card-header p  { margin: 4px 0 0; font-size: 13px; opacity: 0.9; }
                .card-body { padding: 28px; }
                .info-box {
                    background: #fffbeb; border: 1px solid #fde68a;
                    border-left: 5px solid #f59e0b; border-radius: 8px;
                    padding: 16px 20px; margin-bottom: 18px;
                }
                .info-box h4 { margin: 0 0 12px; color: #92400e; font-size: 13px;
                    text-transform: uppercase; letter-spacing: 0.5px; }
                .info-box p  { margin: 7px 0; font-size: 14px; color: #78350f; }
                .info-box p b { color: #451a03; display: inline-block; width: 130px; }
                .new-info-box {
                    background: #f0fdf4; border: 1px solid #bbf7d0;
                    border-left: 5px solid #22c55e; border-radius: 8px;
                    padding: 16px 20px; margin-bottom: 24px;
                }
                .new-info-box h4 { margin: 0 0 12px; color: #14532d; font-size: 13px;
                    text-transform: uppercase; letter-spacing: 0.5px; }
                .new-info-box p  { margin: 7px 0; font-size: 14px; color: #166534; }
                .new-info-box p b { color: #14532d; display: inline-block; width: 130px; }
                .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
                .btn {
                    padding: 13px; border: none; border-radius: 8px;
                    font-size: 14px; font-weight: 700; cursor: pointer;
                    text-decoration: none; text-align: center; display: block;
                    transition: all 0.2s; box-sizing: border-box;
                }
                .btn-restore { background: #1e3d8f; color: white; box-shadow: 0 4px 12px rgba(30,61,143,0.2); }
                .btn-restore:hover { background: #162e6f; }
                .btn-cancel  { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
                .btn-cancel:hover { background: #e2e8f0; }
                .note { font-size: 12px; color: #94a3b8; text-align: center; margin-top: 16px; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="card-header">
                    <div class="icon">⚠️</div>
                    <div>
                        <h2>Duplicate Student Detected</h2>
                        <p>A deleted record with Roll No <strong><?php echo htmlspecialchars($roll); ?></strong> already exists in <?php echo htmlspecialchars($inst); ?>.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-box">
                        <h4>🗂 Existing Deleted Record</h4>
                        <p><b>Unique ID:</b>  <?php echo htmlspecialchars($dup['unique_id']); ?></p>
                        <p><b>Name:</b>       <?php echo htmlspecialchars($dup['name']); ?></p>
                        <p><b>Roll No:</b>    <?php echo htmlspecialchars($roll); ?></p>
                        <p><b>Course:</b>     <?php echo htmlspecialchars($dup['course']); ?></p>
                        <p><b>Batch:</b>      <?php echo htmlspecialchars($dup['admission_year']); ?> – <?php echo htmlspecialchars($dup['passing_year']); ?></p>
                    </div>
                    <div class="new-info-box">
                        <h4>🆕 You Are Trying to Register</h4>
                        <p><b>Name:</b>    <?php echo htmlspecialchars($name); ?></p>
                        <p><b>Roll No:</b> <?php echo htmlspecialchars($roll); ?></p>
                        <p><b>Course:</b>  <?php echo htmlspecialchars($course); ?></p>
                        <p><b>Batch:</b>   <?php echo htmlspecialchars($adm_year); ?> – <?php echo htmlspecialchars($pass_year); ?></p>
                    </div>
                    <div class="actions">
                        <form method="POST" action="save_student.php">
                            <input type="hidden" name="restore_id" value="<?php echo htmlspecialchars($dup['unique_id']); ?>">
                            <button type="submit" class="btn btn-restore">♻️ Restore Deleted Record</button>
                        </form>
                        <a href="add_student.php" class="btn btn-cancel">← Go Back / Cancel</a>
                    </div>
                    <p class="note">Restoring makes the existing record visible again with all original data and gate logs intact.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        ob_end_flush();
        exit;
    }
    // ── END DUPLICATE CHECK ───────────────────────────────────────────────────

    // File uploads
    $qr_path   = "uploads/qrcodes/" . str_replace('#', '', $u_id) . ".png";
    $photo     = $_FILES['photo']['name'];
    $signature = $_FILES['signature']['name'];

    move_uploaded_file($_FILES['photo']['tmp_name'],     "uploads/photos/"     . $photo);
    move_uploaded_file($_FILES['signature']['tmp_name'], "uploads/signatures/" . $signature);

    // INSERT all 14 columns
    $sql = "INSERT INTO students
                (unique_id, roll_no, name, father_name, institution, course,
                 gender, phone_no, email, admission_year, passing_year,
                 address, photo, signature, qr_path)
            VALUES
                ('$u_id', '$roll', '$name', '$father_name', '$inst', '$course',
                 '$gender', '$phone', '$email', '$adm_year', '$pass_year',
                 '$address', '$photo', '$signature', '$qr_path')";

    if (mysqli_query($conn, $sql)) {
        $profile_url = $site_url . "/guard_scan_result.php?id=" . urlencode($u_id);
        if (!file_exists('uploads/qrcodes')) { mkdir('uploads/qrcodes', 0777, true); }
        QRcode::png($profile_url, $qr_path, 'L', 10, 2);

        $redirect = ($_SESSION['role'] == 'super_admin') ? 'admin_dashboard.php' : 'dashboard.php';
        header("Location: {$redirect}?success=1");
        ob_end_clean();
        exit;
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}