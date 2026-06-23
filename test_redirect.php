<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "This should not appear";
header("Location: login.php");
ob_end_clean();
exit();
