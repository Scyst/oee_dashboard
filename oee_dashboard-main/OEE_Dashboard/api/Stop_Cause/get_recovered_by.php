<?php
require_once("../api/db.php");

$sql = "SELECT DISTINCT recovered_by FROM IOT_TOOLBOX_STOP_CAUSES WHERE recovered_by IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $recovered_by = htmlspecialchars($row['recovered_by'], ENT_QUOTES, 'UTF-8');
    echo "<option value=\"{$recovered_by}\">{$recovered_by}</option>";
}
?>
