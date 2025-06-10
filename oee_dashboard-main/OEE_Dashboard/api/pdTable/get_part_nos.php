<?php
require_once("../api/db.php");

$sql = "SELECT DISTINCT part_no FROM parameter ORDER BY part_no";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['part_no']) . "\"></option>";
}
?>
