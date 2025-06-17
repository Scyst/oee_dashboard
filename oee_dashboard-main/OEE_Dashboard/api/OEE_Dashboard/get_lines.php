<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

$sql = "SELECT DISTINCT line FROM IOT_TOOLBOX_PARAMETER WHERE line IS NOT NULL ORDER BY line";
$stmt = sqlsrv_query($conn, $sql);

$lines = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $lines[] = $row['line'];
}
echo json_encode($lines);
?>