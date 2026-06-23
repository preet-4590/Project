<?php
session_start();
include('db_config.php');

// Guards only
if (!isset($_SESSION['clerk_user']) || $_SESSION['role'] !== 'guard') {
    header("Location: login.php");
    exit;
}

include('phpqrcode/qrlib.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$error       = '';
$mail_status = ''; // 'sent', 'skipped', 'failed'

if (isset($_POST['save'])) {

    $guest_name   = mysqli_real_escape_string($conn, trim($_POST['guest_name']));
    $guest_phone  = mysqli_real_escape_string($conn, trim($_POST['guest_phone']));
    $guest_email  = mysqli_real_escape_string($conn, trim($_POST['guest_email']));
    $purpose      = mysqli_real_escape_string($conn, trim($_POST['purpose']));
    $whom_to_meet = mysqli_real_escape_string($conn, trim($_POST['whom_to_meet']));
    $valid_from   = mysqli_real_escape_string($conn, $_POST['valid_from']);
    $valid_until  = mysqli_real_escape_string($conn, $_POST['valid_until']);
    $issued_by    = mysqli_real_escape_string($conn, $_SESSION['clerk_user']);
    $institution  = mysqli_real_escape_string($conn, trim($_POST['institution']));

    if (empty($guest_name)) {
        $error = "Guest name is required.";
    } elseif ($valid_until < $valid_from) {
        $error = "Valid Until cannot be before Valid From.";
    } else {
        // Generate unique pass ID
        $count_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM guest_passes"));
        $pass_num  = str_pad($count_res['total'] + 1, 4, '0', STR_PAD_LEFT);
        $pass_id   = 'GP-' . date('Ymd') . '-' . $pass_num;

        $qr_filename = 'GP' . date('Ymd') . $pass_num . '.png';
        $qr_path     = 'uploads/qrcodes/guests/' . $qr_filename;

        if (!file_exists('uploads/qrcodes/guests')) {
            mkdir('uploads/qrcodes/guests', 0777, true);
        }

        $sql = "INSERT INTO guest_passes
                    (pass_id, guest_name, guest_phone, guest_email, purpose, whom_to_meet,
                     institution, valid_from, valid_until, issued_by, qr_path)
                VALUES
                    ('$pass_id', '$guest_name', '$guest_phone', '$guest_email', '$purpose', '$whom_to_meet',
                     '$institution', '$valid_from', '$valid_until', '$issued_by', '$qr_path')";

        if (mysqli_query($conn, $sql)) {

            // Generate QR code image
            $profile_url = $site_url . "/guest_pass_result.php?id=" . urlencode($pass_id);
            QRcode::png($profile_url, $qr_path, 'L', 10, 2);

            // ── Send email if address was provided ────────────────────────────
            if (!empty($guest_email)) {
                try {
                    $mail = new PHPMailer(true);

                    // SMTP config from db_config.php constants
                    $mail->isSMTP();
                    $mail->Host       = MAIL_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = MAIL_USERNAME;
                    $mail->Password   = MAIL_PASSWORD;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = MAIL_PORT;

                    // Sender and recipient
                    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                    $mail->addAddress($guest_email, $guest_name);

                    // Attach the QR code PNG
                    $mail->addAttachment($qr_path, 'GuestPass_' . $pass_id . '.png');

                    // Email content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Guest Pass – ' . $institution . ' Campus';
                    $mail->Body    = '
                    <div style="font-family:Segoe UI,sans-serif;max-width:520px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                        <div style="background:linear-gradient(135deg,#1e3d8f,#223a5e);color:white;padding:28px;text-align:center;">
                            <h2 style="margin:0;font-size:22px;">🪪 Guest Campus Pass</h2>
                            <p style="margin:8px 0 0;opacity:0.85;font-size:14px;">' . htmlspecialchars($institution) . '</p>
                        </div>
                        <div style="padding:28px;">
                            <p style="font-size:15px;color:#334155;">Dear <strong>' . htmlspecialchars($guest_name) . '</strong>,</p>
                            <p style="font-size:14px;color:#475569;line-height:1.6;">
                                Your guest pass for <strong>' . htmlspecialchars($institution) . '</strong> campus has been issued.
                                Please find your QR code attached to this email. Show it to the security guard at the gate.
                            </p>
                            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:5px solid #1e3d8f;border-radius:8px;padding:16px 20px;margin:20px 0;">
                                <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Pass ID:</b> ' . htmlspecialchars($pass_id) . '</p>
                                <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Valid From:</b> ' . date('d M Y', strtotime($valid_from)) . '</p>
                                <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Valid Until:</b> ' . date('d M Y', strtotime($valid_until)) . '</p>
                                <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Whom to Meet:</b> ' . htmlspecialchars($whom_to_meet ?: 'N/A') . '</p>
                                <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Purpose:</b> ' . htmlspecialchars($purpose ?: 'N/A') . '</p>
                                <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Issued By:</b> ' . htmlspecialchars($issued_by) . '</p>
                            </div>
                            <p style="font-size:12px;color:#94a3b8;margin-top:24px;">
                                This pass is valid only for the dates shown above. Please carry a printed or digital copy when visiting the campus.
                            </p>
                        </div>
                        <div style="background:#f8fafc;padding:16px;text-align:center;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;">CGEMS — College Gate Entry Management System</p>
                        </div>
                    </div>';

                    $mail->AltBody = "Dear {$guest_name},\n\nYour guest pass ({$pass_id}) for {$institution} campus is attached.\nValid: " . date('d M Y', strtotime($valid_from)) . " to " . date('d M Y', strtotime($valid_until)) . "\n\nPlease show the QR code at the gate.";

                    $mail->send();
                    $mail_status = 'sent';

                } catch (Exception $e) {
                    // Don't block the pass — just flag email failed
                    $mail_status = 'failed';
                }
            } else {
                $mail_status = 'skipped'; // No email provided
            }

            // Redirect to pass view with mail status
            header("Location: guest_pass_result.php?id=" . urlencode($pass_id) . "&new=1&mail=" . $mail_status);
            exit;

        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Guest Pass | Guard</title>
    <style>
        :root {
            --portal-blue: #1e3d8f;
            --portal-dark: #223a5e;
            --bg-light:    #f4f6f9;
            --text-main:   #2d3748;
            --border-color:#e2e8f0;
            --purple:      #7c3aed;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0; padding: 20px;
            background: linear-gradient(135deg, #f4f6f9 0%, #e2e8f0 100%);
            display: flex; justify-content: center; align-items: flex-start;
            min-height: 100vh; color: var(--text-main); box-sizing: border-box;
        }
        .container {
            background: #fff; width: 100%; max-width: 460px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(34,58,94,0.08);
            overflow: hidden; border: 1px solid var(--border-color);
        }
        .card-header {
            background: linear-gradient(135deg, var(--purple) 0%, #4c1d95 100%);
            color: white; padding: 24px; text-align: center;
            border-bottom: 4px solid #a78bfa;
        }
        .card-header h2 { margin: 0 0 6px; font-size: 20px; font-weight: 700; }
        .card-header p  { margin: 0; font-size: 13px; opacity: 0.85; }
        .card-body { padding: 24px; }

        .alert-error {
            background: #fef2f2; color: #991b1b;
            border: 1px solid #fecaca; border-radius: 8px;
            padding: 12px 16px; font-size: 14px; margin-bottom: 18px;
        }
        .form-group { margin-bottom: 16px; }
        label {
            display: block; font-size: 12px; font-weight: 700;
            color: var(--portal-dark); text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 6px;
        }
        .optional-tag {
            font-size: 10px; font-weight: 500; color: #94a3b8;
            text-transform: none; letter-spacing: 0; margin-left: 4px;
        }
        input[type="text"], input[type="date"], input[type="email"],
        input[type="tel"], select, textarea {
            width: 100%; padding: 11px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px; box-sizing: border-box;
            font-size: 14px; color: var(--text-main);
            background: #fff; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
        }
        textarea { resize: vertical; min-height: 65px; }
        .date-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        hr.divider { border: none; border-top: 1px solid var(--border-color); margin: 18px 0; }

        .email-hint {
            font-size: 11px; color: #64748b; margin-top: 5px;
            display: flex; align-items: center; gap: 5px;
        }

        .btn-submit {
            width: 100%; background: var(--purple); color: white;
            border: none; padding: 14px; border-radius: 8px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            margin-top: 6px; transition: background 0.2s;
            box-shadow: 0 4px 12px rgba(124,58,237,0.25);
        }
        .btn-submit:hover { background: #6d28d9; }
        .btn-back {
            display: block; text-align: center; margin-top: 14px;
            font-size: 13px; color: #64748b; text-decoration: none;
        }
        .btn-back:hover { color: var(--portal-blue); }
    </style>
</head>
<body>
<div class="container">
    <div class="card-header">
        <h2>🪪 Issue Guest Pass</h2>
        <p>Issued by: <strong><?php echo htmlspecialchars($_SESSION['clerk_user']); ?></strong></p>
    </div>

    <div class="card-body">

        <?php if ($error): ?>
            <div class="alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Guest Full Name *</label>
                <input type="text" name="guest_name" placeholder="Enter full name"
                       value="<?php echo htmlspecialchars($_POST['guest_name'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Phone Number <span class="optional-tag">(optional)</span></label>
                <input type="tel" name="guest_phone" placeholder="10-digit number"
                       value="<?php echo htmlspecialchars($_POST['guest_phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Email Address <span class="optional-tag">(QR will be sent here)</span></label>
                <input type="email" name="guest_email" placeholder="guest@example.com"
                       value="<?php echo htmlspecialchars($_POST['guest_email'] ?? ''); ?>">
                <p class="email-hint">📧 Leave blank if you don't want to send the QR by email.</p>
            </div>

            <div class="form-group">
                <label>Institution *</label>
                <select name="institution" required>
                    <option value="">-- Select --</option>
                    <option value="GNDEC"  <?php echo (($_POST['institution'] ?? '') === 'GNDEC')  ? 'selected' : ''; ?>>GNDEC</option>
                    <option value="GNDPC"  <?php echo (($_POST['institution'] ?? '') === 'GNDPC')  ? 'selected' : ''; ?>>GNDPC</option>
                    <option value="GNDITI" <?php echo (($_POST['institution'] ?? '') === 'GNDITI') ? 'selected' : ''; ?>>GNDITI</option>
                </select>
            </div>

            <div class="form-group">
                <label>Whom to Meet <span class="optional-tag">(optional)</span></label>
                <input type="text" name="whom_to_meet" placeholder="Faculty / Staff name"
                       value="<?php echo htmlspecialchars($_POST['whom_to_meet'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Purpose of Visit <span class="optional-tag">(optional)</span></label>
                <textarea name="purpose" placeholder="e.g. Attending seminar, Maintenance work..."><?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?></textarea>
            </div>

            <hr class="divider">

            <div class="date-row">
                <div class="form-group">
                    <label>Valid From *</label>
                    <input type="date" name="valid_from"
                           value="<?php echo $_POST['valid_from'] ?? $today; ?>"
                           min="<?php echo $today; ?>" required>
                </div>
                <div class="form-group">
                    <label>Valid Until *</label>
                    <input type="date" name="valid_until"
                           value="<?php echo $_POST['valid_until'] ?? $today; ?>"
                           min="<?php echo $today; ?>" required>
                </div>
            </div>

            <button type="submit" name="save" class="btn-submit">
                Generate Pass & Send QR
            </button>
        </form>

        <a href="guard_interface.php" class="btn-back">← Back to Gate Control</a>
    </div>
</div>

<script>
    document.querySelector('[name="valid_from"]').addEventListener('change', function() {
        var until = document.querySelector('[name="valid_until"]');
        until.min = this.value;
        if (until.value < this.value) until.value = this.value;
    });
</script>
</body>
</html>