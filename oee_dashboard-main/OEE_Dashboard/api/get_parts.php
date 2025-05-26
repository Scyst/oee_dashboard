<?php
require_once '../db.php'; // Make sure this connects using SQLSRV

header('Content-Type: application/json');

$sql = "SELECT id, log_date, log_time, line, model, part_no, count_value, count_type FROM parts ORDER BY log_date DESC, log_time DESC";
$stmt = sqlsrv_query($conn, $sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Query failed']);
    exit;
}

$parts = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Convert date/time fields
    $row['log_date'] = $row['log_date']->format('Y-m-d');
    $row['log_time'] = $row['log_time']->format('H:i');
    $parts[] = $row;
}

echo json_encode($parts);
?>
