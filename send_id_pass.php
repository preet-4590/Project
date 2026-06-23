<?php
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No student ID provided.");
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$u_id = mysqli_real_escape_string($conn, $_GET['id']);
$res  = mysqli_query($conn, "SELECT * FROM students WHERE unique_id = '$u_id' AND is_deleted = 0");
$s    = mysqli_fetch_assoc($res);

if (!$s) {
    header("Location: dashboard.php");
    exit;
}

// No email on file
if (empty($s['email'])) {
    header("Location: view_profile.php?id=" . urlencode($u_id) . "&mail=noemail");
    exit;
}

// ── Build the ID pass HTML email body ─────────────────────────────────────────
$photo_cid = 'student_photo';
$qr_cid    = 'student_qr';
$sig_cid   = 'student_sig';

$batch = (!empty($s['admission_year']) && $s['admission_year'] > 0)
    ? htmlspecialchars($s['admission_year']) . ' – ' . htmlspecialchars($s['passing_year'])
    : 'N/A';

$email_body = '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:Segoe UI,sans-serif;background:#f4f6f9;margin:0;padding:30px;">
<div style="max-width:480px;margin:auto;background:white;border-radius:16px;overflow:hidden;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);border:1px solid #e2e8f0;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#223a5e,#1e3d8f);color:white;padding:30px 20px;text-align:center;">
        <img src="cid:' . $photo_cid . '" alt="Photo"
             style="width:90px;height:90px;border-radius:50%;object-fit:cover;
                    border:4px solid rgba(255,255,255,0.25);margin-bottom:12px;display:block;margin-left:auto;margin-right:auto;">
        <h2 style="margin:0 0 6px;font-size:22px;font-weight:700;">' . htmlspecialchars($s['name']) . '</h2>
        <div style="display:inline-block;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);
                    padding:4px 14px;border-radius:20px;font-size:13px;font-weight:600;letter-spacing:0.5px;">
            ID: ' . htmlspecialchars($s['unique_id']) . '
        </div>
    </div>

    <!-- Details -->
    <div style="padding:24px;">
        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:9px 4px;color:#64748b;font-weight:600;width:140px;">Father\'s Name</td>
                <td style="padding:9px 4px;color:#1e293b;font-weight:500;">' . htmlspecialchars($s['father_name'] ?? 'N/A') . '</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:9px 4px;color:#64748b;font-weight:600;">Roll Number</td>
                <td style="padding:9px 4px;color:#1e293b;font-weight:500;">' . htmlspecialchars($s['roll_no']) . '</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:9px 4px;color:#64748b;font-weight:600;">Institution</td>
                <td style="padding:9px 4px;color:#1e293b;font-weight:500;">' . htmlspecialchars($s['institution']) . '</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:9px 4px;color:#64748b;font-weight:600;">Course</td>
                <td style="padding:9px 4px;color:#1e293b;font-weight:500;">' . htmlspecialchars($s['course']) . '</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:9px 4px;color:#64748b;font-weight:600;">Gender</td>
                <td style="padding:9px 4px;color:#1e293b;font-weight:500;">' . htmlspecialchars($s['gender'] ?? 'N/A') . '</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:9px 4px;color:#64748b;font-weight:600;">Phone</td>
                <td style="padding:9px 4px;color:#1e293b;font-weight:500;">' . htmlspecialchars($s['phone_no']) . '</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:9px 4px;color:#64748b;font-weight:600;">Email</td>
                <td style="padding:9px 4px;color:#1e293b;font-weight:500;">' . htmlspecialchars($s['email']) . '</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:9px 4px;color:#64748b;font-weight:600;">Batch</td>
                <td style="padding:9px 4px;color:#1e293b;font-weight:500;">' . $batch . '</td>
            </tr>
            <tr>
                <td style="padding:9px 4px;color:#64748b;font-weight:600;">Address</td>
                <td style="padding:9px 4px;color:#1e293b;font-weight:500;">' . htmlspecialchars($s['address'] ?? 'N/A') . '</td>
            </tr>
        </table>

        <!-- Signature + QR row -->
        <div style="display:flex;justify-content:space-between;align-items:center;
                    margin-top:20px;padding-top:16px;border-top:1px solid #e2e8f0;">
            <div>
                <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;
                            letter-spacing:0.8px;font-weight:700;margin-bottom:6px;">Signature</div>
                <img src="cid:' . $sig_cid . '" alt="Signature"
                     style="height:40px;max-width:150px;object-fit:contain;">
            </div>
            <div style="background:#f8fafc;padding:8px;border-radius:10px;border:1px solid #e2e8f0;">
                <img src="cid:' . $qr_cid . '" alt="QR Code"
                     style="width:80px;height:80px;display:block;">
                <div style="font-size:10px;color:#94a3b8;text-align:center;margin-top:4px;">Gate QR Code</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="background:#f8fafc;padding:14px;text-align:center;border-top:1px solid #e2e8f0;">
        <p style="margin:0;font-size:12px;color:#94a3b8;">
            CGEMS — College Gate Entry Management System<br>
            ' . htmlspecialchars($s['institution']) . ', Ludhiana
        </p>
    </div>

</div>
</body>
</html>';

// ── Send via PHPMailer ────────────────────────────────────────────────────────
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($s['email'], $s['name']);

    $mail->isHTML(true);
    $mail->Subject = 'Your Student ID Pass – ' . $s['institution'];
    $mail->Body    = $email_body;
    $mail->AltBody = "Dear {$s['name']},\n\nYour Student ID Pass for {$s['institution']} is attached.\nID: {$s['unique_id']}\nRoll No: {$s['roll_no']}\nCourse: {$s['course']}\n\nKindly use the QR code at the campus gate.\n\nCGEMS";

    // Embed photo
    if (!empty($s['photo']) && file_exists("uploads/photos/" . $s['photo'])) {
        $mail->addEmbeddedImage("uploads/photos/" . $s['photo'], $photo_cid, $s['photo']);
    }
    // Embed QR code
    if (!empty($s['qr_path']) && file_exists($s['qr_path'])) {
        $mail->addEmbeddedImage($s['qr_path'], $qr_cid, 'qr.png');
    }
    // Embed signature
    if (!empty($s['signature']) && file_exists("uploads/signatures/" . $s['signature'])) {
        $mail->addEmbeddedImage("uploads/signatures/" . $s['signature'], $sig_cid, $s['signature']);
    }

    $mail->send();
    header("Location: view_profile.php?id=" . urlencode($u_id) . "&mail=sent");

} catch (Exception $e) {
    header("Location: view_profile.php?id=" . urlencode($u_id) . "&mail=failed");
}
exit;