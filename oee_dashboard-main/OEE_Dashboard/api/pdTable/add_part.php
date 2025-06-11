<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

// Collect input
$log_date = $_POST['log_date'] ?? '';
$log_time = $_POST['log_time'] ?? '';
$part_no  = $_POST['part_no'] ?? '';
$model    = $_POST['model'] ?? '';
$line     = $_POST['line'] ?? '';
$count_type = $_POST['count_type'] ?? '';
$count_value = isset($_POST['count_value']) ? (int)$_POST['count_value'] : 0;
$note = $_POST['note'] ?? null;
$lot_no = $_POST['lot_no'] ?? '';

// Validate required fields
if (!$log_date || !$log_time || !$part_no || !$count_type || !$count_value || !$model || !$line) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Validate count type
$valid_types = ['FG', 'NG', 'HOLD', 'REWORK', 'SCRAP', 'ETC.'];
if (!in_array(strtoupper($count_type), $valid_types)) {
    echo json_encode(["status" => "error", "message" => "Invalid count type"]);
    exit;
}

// 🔄 Auto-generate lot_no using SAP No. if it's empty
if (!$lot_no) {
    $datePrefix = date('Ymd', strtotime($log_date)); // e.g. "20250610"

    // Get SAP No.
    $sapQuery = "SELECT sap_no FROM parameter WHERE line = ? AND model = ? AND part_no = ?";
    $sapStmt = sqlsrv_query($conn, $sapQuery, [$line, $model, $part_no]);

    if (!$sapStmt || !($sapRow = sqlsrv_fetch_array($sapStmt, SQLSRV_FETCH_ASSOC))) {
        echo json_encode(["status" => "error", "message" => "SAP No. not found for line/model/part."]);
        exit;
    }

    $sap_no = $sapRow['sap_no'];

    // Count existing entries for this sap_no + log_date
    $lotCountQuery = "SELECT COUNT(*) AS lot_count FROM parts WHERE part_no = ? AND log_date = ? AND lot_no LIKE ?";
    $likePattern = $sap_no . '-' . $datePrefix . '%';
    $countStmt = sqlsrv_query($conn, $lotCountQuery, [$part_no, $log_date, $likePattern]);

    if ($countStmt && ($countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC))) {
        $count = $countRow['lot_count'] + 1;
        $lot_no = $sap_no . '-' . $datePrefix . '-' . str_pad($count, 2, '0', STR_PAD_LEFT); // e.g., "P002345-20250610-01"
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to count existing lots"]);
        exit;
    }
}

// Final insert
$sql = "INSERT INTO parts (log_date, log_time, model, line, part_no, lot_no, count_type, count_value, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$params = [$log_date, $log_time, $model, $line, $part_no, $lot_no, $count_type, $count_value, $note];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "Insert failed", "details" => sqlsrv_errors()]);
    exit;
}

echo json_encode(["status" => "success", "message" => "Part inserted successfully", "lot_no" => $lot_no]);
exit;
