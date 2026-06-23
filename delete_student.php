<?php
session_start();
include('db_config.php');

// Only clerks and admins can delete — guards cannot
if (!isset($_SESSION['clerk_user']) || $_SESSION['role'] === 'guard') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {

    $u_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Verify the student exists and is not already soft-deleted
    $fetch = mysqli_query($conn, "SELECT unique_id FROM students WHERE unique_id = '$u_id' AND is_deleted = 0");

    if (mysqli_num_rows($fetch) === 0) {
        echo "<script>alert('Student record not found.'); window.location='dashboard.php';</script>";
        exit;
    }

    // Soft delete: flag the row, leave all data and files intact
    $soft_delete = mysqli_query($conn, "UPDATE students SET is_deleted = 1 WHERE unique_id = '$u_id'");

    if ($soft_delete) {
        $redirect = ($_SESSION['role'] === 'super_admin') ? 'admin_dashboard.php' : 'dashboard.php';
        echo "<script>
                alert('Student record removed from the portal. Data is retained in the database.');
                window.location='{$redirect}';
              </script>";
    } else {
        echo "Database error: " . mysqli_error($conn);
    }

} else {
    echo "No valid ID provided.";
}
?>