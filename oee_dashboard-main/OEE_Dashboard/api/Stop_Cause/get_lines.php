<?php
//call by scrapModal in OEE_Dashboard.php

require_once("../api/db.php");
$sql = "SELECT DISTINCT line FROM stop_causes WHERE line IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['line']) . "\"></option>";
}
?>
