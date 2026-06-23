<?php
ob_start();
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user']) || $_SESSION['role'] === 'guard') {
    header("Location: login.php");
    ob_end_clean();
    exit;
}

include('phpqrcode/qrlib.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

if (!isset($_POST['save'])) {
    header("Location: issue_guest_pass.php");
    ob_end_clean();
    exit;
}

$guest_name   = mysqli_real_escape_string($conn, trim($_POST['guest_name']));
$guest_phone  = mysqli_real_escape_string($conn, trim($_POST['guest_phone']));
$guest_email  = mysqli_real_escape_string($conn, trim($_POST['guest_email'] ?? ''));
$purpose      = mysqli_real_escape_string($conn, trim($_POST['purpose']));
$whom_to_meet = mysqli_real_escape_string($conn, trim($_POST['whom_to_meet']));
$institution  = mysqli_real_escape_string($conn, trim($_POST['institution']));
$valid_from   = mysqli_real_escape_string($conn, $_POST['valid_from']);
$valid_until  = mysqli_real_escape_string($conn, $_POST['valid_until']);
$issued_by    = mysqli_real_escape_string($conn, $_SESSION['clerk_user']);

// Generate pass ID
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

if (!mysqli_query($conn, $sql)) {
    die("Database Error: " . mysqli_error($conn));
}

// Generate QR
$profile_url = $site_url . "/guest_pass_result.php?id=" . urlencode($pass_id);
QRcode::png($profile_url, $qr_path, 'L', 10, 2);

// Send email if provided
$mail_status = 'skipped';
if (!empty($guest_email)) {
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
        $mail->addAddress($guest_email, $guest_name);
        $mail->addAttachment($qr_path, 'GuestPass_' . $pass_id . '.png');
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
                <p style="font-size:14px;color:#475569;line-height:1.6;">Your guest pass for <strong>' . htmlspecialchars($institution) . '</strong> campus has been issued. Please find your QR code attached. Show it to the security guard at the gate.</p>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:5px solid #1e3d8f;border-radius:8px;padding:16px 20px;margin:20px 0;">
                    <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Pass ID:</b> ' . htmlspecialchars($pass_id) . '</p>
                    <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Valid From:</b> ' . date('d M Y', strtotime($valid_from)) . '</p>
                    <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Valid Until:</b> ' . date('d M Y', strtotime($valid_until)) . '</p>
                    <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Whom to Meet:</b> ' . htmlspecialchars($whom_to_meet ?: 'N/A') . '</p>
                    <p style="margin:6px 0;font-size:14px;color:#334155;"><b style="display:inline-block;width:120px;">Purpose:</b> ' . htmlspecialchars($purpose ?: 'N/A') . '</p>
                </div>
                <p style="font-size:12px;color:#94a3b8;margin-top:24px;">This pass is valid only for the dates shown above.</p>
            </div>
            <div style="background:#f8fafc;padding:16px;text-align:center;border-top:1px solid #e2e8f0;">
                <p style="margin:0;font-size:12px;color:#94a3b8;">CGEMS — College Gate Entry Management System</p>
            </div>
        </div>';
        $mail->AltBody = "Dear {$guest_name},\nYour guest pass ({$pass_id}) for {$institution} is attached.\nValid: " . date('d M Y', strtotime($valid_from)) . " to " . date('d M Y', strtotime($valid_until));
        $mail->send();
        $mail_status = 'sent';
    } catch (Exception $e) {
        $mail_status = 'failed';
    }
}

header("Location: guest_pass_result.php?id=" . urlencode($pass_id) . "&new=1&mail=" . $mail_status);
ob_end_clean();
exit;