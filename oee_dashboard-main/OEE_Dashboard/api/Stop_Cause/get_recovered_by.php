<?php
//call by OEE_dashboard 

require_once("../api/db.php");
$sql = "SELECT DISTINCT recovered_by FROM stop_causes WHERE recovered_by IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['recovered_by']) . "\"></option>";
}
?>
