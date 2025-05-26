<?php
require_once("../db.php");

$log_date = $_GET['log_date'];
$shift = $_GET['shift'];

$sql = "SELECT stop_cause, SUM(stop_time) AS total_time
        FROM stop_cause
        WHERE log_date = ? AND shift = ?
        GROUP BY stop_cause";
$stmt = sqlsrv_query($conn, $sql, [$log_date, $shift]);

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
}
echo json_encode($data);
?>
