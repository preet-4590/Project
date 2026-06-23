<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── MySQLi connection (kept for legacy files not yet migrated) ───────────────
$conn = mysqli_connect("localhost", "root", "", "gate_pass");

if (!$conn) {
    die("MySQLi connection failed: " . mysqli_connect_error());
}

// ─── PDO connection (used by all new / migrated files) ───────────────────────
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=gate_pass;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // TRUE parameterized queries
        ]
    );
} catch (PDOException $e) {
    die("PDO connection failed: " . $e->getMessage());
}

// ─── Site base URL (update this when your ngrok URL changes) ─────────────────
$site_url = "https://glandular-barcode-kitten.ngrok-free.dev/gate_pass";

// ─── PHPMailer SMTP config ────────────────────────────────────────────────────
if (!defined('MAIL_HOST')) {
    define('MAIL_HOST',      'smtp.gmail.com');
    define('MAIL_USERNAME',  'preetm4590@gmail.com');
    define('MAIL_PASSWORD',  'jqepmkcjfzbijenh');
    define('MAIL_PORT',      587);
    define('MAIL_FROM',      'preetm4590@gmail.com');
    define('MAIL_FROM_NAME', 'CGEMS – Gate Entry System');
}