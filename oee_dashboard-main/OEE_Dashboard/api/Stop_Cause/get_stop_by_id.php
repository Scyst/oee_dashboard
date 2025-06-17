<?php
//call by pdTable.js in function editPart(id)
require_once '../../api/db.php';

header('Content-Type: application/json');

// Check for ID parameter
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing ID']);
    exit;
}

$id = intval($_GET['id']);

// Query to fetch the part by ID
$sql = "SELECT id, log_date, stop_begin, stop_end, line, machine, cause, recovered_by, note FROM IOT_TOOLBOX_STOP_CAUSES WHERE id = ?";
$params = array($id);

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Database query failed']);
    exit;
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Data not found']);
    exit;
}

// Format date and time for frontend
if ($row['log_date'] instanceof DateTime) {
    $row['log_date'] = $row['log_date']->format('Y-m-d');
}
if ($row['stop_begin'] instanceof DateTime) {
    $row['stop_begin'] = $row['stop_begin']->format('H:i:s');
}
if ($row['stop_end'] instanceof DateTime) {
    $row['stop_end'] = $row['stop_end']->format('H:i:s');
}

echo json_encode(['success' => true, 'data' => $row]);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
