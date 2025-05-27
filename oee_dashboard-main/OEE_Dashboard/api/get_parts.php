<?php
header('Content-Type: application/json');
require_once("../db.php");

// Default pagination params
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
$offset = ($page - 1) * $limit;

// Get total record count for pagination
$totalSql = "SELECT COUNT(*) AS total FROM parts";
$totalStmt = sqlsrv_query($conn, $totalSql);
$totalRow = sqlsrv_fetch_array($totalStmt, SQLSRV_FETCH_ASSOC);
$total = $totalRow ? intval($totalRow['total']) : 0;

// Fetch paginated data
$sql = "
    SELECT id, log_date, log_time, line, model, part_no, count_value, count_type, note
    FROM parts
    ORDER BY log_date DESC, log_time DESC
    OFFSET ? ROWS
    FETCH NEXT ? ROWS ONLY
";
$params = [$offset, $limit];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'SQL query failed.']);
    exit();
}

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if ($row['log_date'] instanceof DateTime) {
        $row['log_date'] = $row['log_date']->format('Y-m-d');
    }
    if ($row['log_time'] instanceof DateTime) {
        $row['log_time'] = $row['log_time']->format('H:i:s');
    }
    $data[] = $row;
}

// Return JSON with pagination info
echo json_encode([
    'success' => true,
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'data' => $data
]);

sqlsrv_free_stmt($stmt);
?>