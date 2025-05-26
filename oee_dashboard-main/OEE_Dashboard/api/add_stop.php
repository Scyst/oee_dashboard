<?php
require_once("db.php");

$log_date = $_POST['log_date'];
$shift = $_POST['shift'];
$log_time = $_POST['log_time'];
$stop_cause = $_POST['stop_cause'];
$stop_time = $_POST['stop_time'];
$note = $_POST['note'];

$params = [$log_date, $shift, $log_time, $stop_cause];
$checkSql = "SELECT * FROM stop_cause WHERE log_date = ? AND shift = ? AND log_time = ? AND stop_cause = ?";
$stmt = sqlsrv_query($conn, $checkSql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

if (sqlsrv_has_rows($stmt)) {
    $updateSql = "UPDATE stop_logs SET stop_time = stop_time + ?, note = ?
                  WHERE log_date = ? AND shift = ? AND log_time = ? AND stop_cause = ?";
    $updateParams = [$stop_time, $note, $log_date, $shift, $log_time, $stop_cause];
    sqlsrv_query($conn, $updateSql, $updateParams);
    echo json_encode(["status" => "success", "message" => "Stop cause updated"]);
} else {
    $insertSql = "INSERT INTO stop_logs (log_date, shift, log_time, stop_cause, stop_time, note)
                  VALUES (?, ?, ?, ?, ?, ?)";
    $insertParams = [$log_date, $shift, $log_time, $stop_cause, $stop_time, $note];
    sqlsrv_query($conn, $insertSql, $insertParams);
    echo json_encode(["status" => "success", "message" => "Stop cause inserted"]);
}
?>

