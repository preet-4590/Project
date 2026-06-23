<?php
ob_start();
session_start();
include('db_config.php');

if (!isset($_SESSION['clerk_user'])) {
    header("Location: login.php");
    ob_end_clean();
    exit;
}

if (!isset($_POST['update'])) {
    header("Location: dashboard.php");
    ob_end_clean();
    exit;
}

// ─── 1. Sanitize inputs ───────────────────────────────────────────────────────
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

// ─── 2. Fetch current file names (fallback if no new file uploaded) ───────────
$cur = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT photo, signature FROM students WHERE unique_id = '$u_id'")
);
$photo     = $cur['photo'];
$signature = $cur['signature'];

// ─── 3. Handle optional photo replacement ────────────────────────────────────
if (!empty($_FILES['photo']['name'])) {
    $new_photo = $_FILES['photo']['name'];
    if (move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/photos/" . $new_photo)) {
        // Delete the old file if it's different
        if (!empty($photo) && $photo !== $new_photo && file_exists("uploads/photos/" . $photo)) {
            unlink("uploads/photos/" . $photo);
        }
        $photo = mysqli_real_escape_string($conn, $new_photo);
    }
}

// ─── 4. Handle optional signature replacement ────────────────────────────────
if (!empty($_FILES['signature']['name'])) {
    $new_sig = $_FILES['signature']['name'];
    if (move_uploaded_file($_FILES['signature']['tmp_name'], "uploads/signatures/" . $new_sig)) {
        if (!empty($signature) && $signature !== $new_sig && file_exists("uploads/signatures/" . $signature)) {
            unlink("uploads/signatures/" . $signature);
        }
        $signature = mysqli_real_escape_string($conn, $new_sig);
    }
}

// ─── 5. Run the UPDATE query ──────────────────────────────────────────────────
$sql = "UPDATE students SET
            name          = '$name',
            father_name   = '$father_name',
            roll_no       = '$roll',
            course        = '$course',
            gender        = '$gender',
            phone_no      = '$phone',
            email         = '$email',
            admission_year = '$adm_year',
            passing_year  = '$pass_year',
            address       = '$address',
            photo         = '$photo',
            signature     = '$signature'
        WHERE unique_id = '$u_id'";

if (mysqli_query($conn, $sql)) {
    $redirect = ($_SESSION['role'] === 'super_admin') ? 'admin_dashboard.php' : 'dashboard.php';
    header("Location: {$redirect}?msg=updated");
    ob_end_clean();
    exit;
} else {
    echo "Database Error: " . mysqli_error($conn);
}