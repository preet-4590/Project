<?php
// Prevent direct directory listing and redirect all unauthorized access to login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is logged in, redirect to their appropriate dashboard based on role
if (isset($_SESSION['clerk_user']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'super_admin') {
        header("Location: admin_dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'guard') {
        header("Location: guard_interface.php");
        exit;
    } else {
        header("Location: dashboard.php");
        exit;
    }
}

// Not logged in - redirect to login page
header("Location: login.php");
exit;