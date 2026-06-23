<?php
include('db_config.php');

if (isset($_GET['institution'])) {
    $inst   = mysqli_real_escape_string($conn, $_GET['institution']);

    $map    = ['GNDEC' => '#E', 'GNDPC' => '#P', 'GNDITI' => '#I'];
    $prefix = $map[$inst] ?? '#';

    // Extract the highest numeric suffix already used for this institution.
    // CAST removes the prefix characters and converts the remainder to an integer,
    // so '#E2' → 2, '#E10' → 10, etc.
    // Using MAX instead of COUNT means deletions never cause a duplicate ID.
    $query  = "SELECT MAX(CAST(SUBSTRING(unique_id, 3) AS UNSIGNED)) AS max_num
               FROM students
               WHERE institution = '$inst'";
    $result = mysqli_query($conn, $query);
    $row    = mysqli_fetch_assoc($result);

    $next_num = ($row['max_num'] !== null) ? (int)$row['max_num'] + 1 : 1;
    $next_id  = $prefix . $next_num;

    echo $next_id;
}
?>