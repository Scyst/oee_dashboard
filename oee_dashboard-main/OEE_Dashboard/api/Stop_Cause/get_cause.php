<?php
//call by scrapModal in OEE_Dashboard.php

require_once("../api/db.php");
$sql = "SELECT DISTINCT cause FROM parts WHERE cause IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['cause']) . "\"></option>";
}
?>
