<?php
require_once("../db.php");

$log_date = $_GET['log_date'];
$shift = $_GET['shift'];

$sql = "SELECT part_no, SUM(scrap_count) AS count
        FROM scrap_logs
        WHERE log_date = ? AND shift = ?
        GROUP BY part_no";
$stmt = sqlsrv_query($conn, $sql, [$log_date, $shift]);

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
}
echo json_encode($data);
?>
