<?php
require_once '../../api/db.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing ID']);
    exit;
}

$id = intval($_GET['id']);

// Query to fetch the part by ID
$sql = "SELECT id, log_date, log_time, line, model, part_no, lot_no, count_value, count_type, note FROM parts WHERE id = ?";
$params = array($id);

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Database query failed']);
    exit;
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Part not found']);
    exit;
}

// Format date and time for frontend
if ($row['log_date'] instanceof DateTime) {
    $row['log_date'] = $row['log_date']->format('Y-m-d');
}
if ($row['log_time'] instanceof DateTime) {
    $row['log_time'] = $row['log_time']->format('H:i:s');
}

echo json_encode(['success' => true, 'data' => $row]);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
