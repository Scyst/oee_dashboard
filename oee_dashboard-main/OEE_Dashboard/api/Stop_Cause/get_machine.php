<?php
require_once("../api/db.php");

$sql = "SELECT DISTINCT machine FROM IOT_TOOLBOX_STOP_CAUSES WHERE machine IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $machine = htmlspecialchars($row['machine'], ENT_QUOTES, 'UTF-8');
    echo "<option value=\"{$machine}\">{$machine}</option>";
}
?>
