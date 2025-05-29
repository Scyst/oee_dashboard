<?php
require_once("../api/db.php"); // Ensure this path is correct for your setup

$sql = "SELECT DISTINCT cause FROM stop_causes WHERE cause IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    // Optional: log error or output fallback
    // error_log(print_r(sqlsrv_errors(), true));
    echo "<option value=\"Error fetching causes\"></option>";
    return; // Exit to prevent further execution
}

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if (!empty($row['cause'])) {
        echo "<option value=\"" . htmlspecialchars($row['cause'], ENT_QUOTES, 'UTF-8') . "\"></option>";
    }
}
?>
