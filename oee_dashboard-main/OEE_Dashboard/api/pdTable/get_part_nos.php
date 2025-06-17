<?php
require_once("../api/db.php");

// Set Content-Type in case it's consumed via AJAX
header('Content-Type: text/html; charset=UTF-8');

$sql = "SELECT DISTINCT part_no FROM IOT_TOOLBOX_PARAMETER WHERE part_no IS NOT NULL ORDER BY part_no";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo '<option value="' . htmlspecialchars($row['part_no'], ENT_QUOTES, 'UTF-8') . '"></option>';
    }
} else {
    // Optional: helpful in debugging during development
    echo "<!-- SQL query failed -->";
}
?>
