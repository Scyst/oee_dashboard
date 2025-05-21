<?php
require_once("../db.php");

$log_date = $_POST['log_date'];
$shift = $_POST['shift'];
$log_time = $_POST['log_time'];
$part_no = $_POST['part_no'];
$scrap_count = $_POST['scrap_count'];

$params = [$log_date, $shift, $log_time, $part_no];
$checkSql = "SELECT * FROM scrap_logs WHERE log_date = ? AND shift = ? AND log_time = ? AND part_no = ?";
$stmt = sqlsrv_query($conn, $checkSql, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

if (sqlsrv_has_rows($stmt)) {
    $updateSql = "UPDATE scrap_logs SET scrap_count = scrap_count + ?
                  WHERE log_date = ? AND shift = ? AND log_time = ? AND part_no = ?";
    $updateParams = [$scrap_count, $log_date, $shift, $log_time, $part_no];
    sqlsrv_query($conn, $updateSql, $updateParams);
    echo json_encode(["status" => "success", "message" => "Scrap data updated"]);
} else {
    $insertSql = "INSERT INTO scrap_logs (log_date, shift, log_time, part_no, scrap_count)
                  VALUES (?, ?, ?, ?, ?)";
    $insertParams = [$log_date, $shift, $log_time, $part_no, $scrap_count];
    sqlsrv_query($conn, $insertSql, $insertParams);
    echo json_encode(["status" => "success", "message" => "Scrap data inserted"]);
}
?>

