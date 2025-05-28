<?php
//use in OEE_Dashboard.php

require_once("../db.php");

header('Content-Type: application/json');

// Get values from POST
$log_date = $_POST['log_date'] ?? '';
$log_time = $_POST['log_time'] ?? '';
$part_no  = $_POST['part_no'] ?? '';
$model    = $_POST['model'] ?? '';
$line     = $_POST['line'] ?? '';
$count_type = $_POST['count_type'] ?? '';
$count_value = isset($_POST['count_value']) ? (int)$_POST['count_value'] : 0;

if (!$log_date || !$log_time || !$part_no || !$count_type || !$count_value || !$model || !$line) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Prepare field to insert
$valid_types = ['FG', 'NG', 'Hold', 'Rework'];
if (!in_array($count_type, $valid_types)) {
    echo json_encode(["status" => "error", "message" => "Invalid count type"]);
    exit;
}

// Insert into database
$sql = "INSERT INTO parts (log_date, log_time, model, line, part_no, count_type, count_value, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$params = [$log_date, $log_time, $model, $line, $part_no, $count_type, $count_value, $note];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "Insert failed", "details" => sqlsrv_errors()]);
    exit;
}

echo json_encode(["status" => "success", "message" => "Part inserted successfully"]);
header("Location: ../OEE_Dashboard.php"); // adjust path as needed
exit;

?>
