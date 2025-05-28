<?php
//call by scrapModal in OEE_Dashboard.php

require_once("../api/db.php");
$sql = "SELECT DISTINCT model FROM parts WHERE model IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['model']) . "\"></option>";
}
?>
