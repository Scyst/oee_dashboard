<?php
// Used in Stop_Cause form submission
require_once("../../api/db.php");
header('Content-Type: application/json');

// Collect values from POST
$log_date     = $_POST['log_date'] ?? '';
$stop_begin   = $_POST['stop_begin'] ?? '';
$stop_end     = $_POST['stop_end'] ?? '';
$line         = $_POST['line'] ?? '';
$machine      = $_POST['machine'] ?? '';
$cause        = $_POST['cause'] ?? '';
$recovered_by = $_POST['recovered_by'] ?? '';
$note         = $_POST['note'] ?? null;

if (!$log_date || !$stop_begin || !$stop_end || !$line || !$machine || !$cause || !$recovered_by) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

// Auto-calculate stop duration (in minutes)
$start = DateTime::createFromFormat('H:i:s', $stop_begin);
$end = DateTime::createFromFormat('H:i:s', $stop_end);

if (!$start || !$end) {
    echo json_encode(["status" => "error", "message" => "Invalid time format"]);
    exit;
}

$interval = $start->diff($end);
$duration = ($interval->h * 60) + $interval->i + ($interval->s > 0 ? 1 : 0); // round up partial minutes

if ($duration < 0) {
    echo json_encode(["status" => "error", "message" => "Stop end must be after stop begin"]);
    exit;
}

// Insert query
$sql = "INSERT INTO stop_causes (
            log_date, stop_begin, stop_end, line, machine, cause, recovered_by, note
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$params = [$log_date, $stop_begin, $stop_end, $line, $machine, $cause, $recovered_by, $note];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode([
        "status" => "error",
        "message" => "Insert failed",
        "details" => sqlsrv_errors()
    ]);
    exit;
}

echo json_encode(["status" => "success", "message" => "Stop cause added successfully"]);
exit;
?>
