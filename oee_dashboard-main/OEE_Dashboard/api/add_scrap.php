<?php
require_once("db.php");

$data = json_decode(file_get_contents("php://input"), true);

$log_date = $data['log_date'];
$log_time = $data['log_time'];
$line = $data['line'];
$model = $data['model'];
$part_no = $data['part_no'];
$scrap_type = $data['scrap_type'];
$scrap_count = intval($data['scrap_count']);

$sql = "INSERT INTO scrap (log_date, log_time, line, model, part_no, scrap_type, scrap_count)
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$params = [$log_date, $log_time, $line, $model, $part_no, $scrap_type, $scrap_count];

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => sqlsrv_errors()]);
}
?>


