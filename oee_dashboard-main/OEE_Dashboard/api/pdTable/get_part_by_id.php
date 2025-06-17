<?php
require_once '../../api/db.php';
header('Content-Type: application/json');

// Validate the ID
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing ID']);
    exit;
}

// Fetch the part by ID
$sql = "SELECT id, log_date, log_time, line, model, part_no, lot_no, count_value, count_type, note
        FROM IOT_TOOLBOX_PARTS WHERE id = ?";
$stmt = sqlsrv_query($conn, $sql, [$id]);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database query failed', 'errors' => sqlsrv_errors()]);
    exit;
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Part not found']);
    exit;
}

// Format datetime fields for frontend compatibility
if (!empty($row['log_date']) && $row['log_date'] instanceof DateTime) {
    $row['log_date'] = $row['log_date']->format('Y-m-d');
}
if (!empty($row['log_time']) && $row['log_time'] instanceof DateTime) {
    $row['log_time'] = $row['log_time']->format('H:i:s');
}

echo json_encode(['success' => true, 'data' => $row]);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
