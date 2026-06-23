<?php
ob_start();
include('db_config.php');

echo "<h2>Session Debug</h2>";
echo "<b>Session ID:</b> " . session_id() . "<br>";
echo "<b>Session Status:</b> " . session_status() . "<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<b>PHP Version:</b> " . phpversion() . "<br>";
echo "<b>Headers Sent:</b> " . (headers_sent($file, $line) ? "YES — at $file line $line" : "NO") . "<br>";
?>