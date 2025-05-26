<?php
require_once("db.php");
$sql = "SELECT DISTINCT part_no FROM parts WHERE part_no IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['part_no']) . "\"></option>";
}
?>
