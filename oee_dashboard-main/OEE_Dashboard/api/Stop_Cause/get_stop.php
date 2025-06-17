<?php
header('Content-Type: application/json');
require_once("../../api/db.php");

// Pagination params
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
$offset = ($page - 1) * $limit;

// Collect filters
$conditions = [];
$params = [];

$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$line = $_GET['line'] ?? null;
$machine = $_GET['machine'] ?? null;
$cause = $_GET['cause'] ?? null;

if ($startDate && $endDate) {
    $conditions[] = "log_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}
if (!empty($line)) {
    $conditions[] = "LOWER(line) = LOWER(?)";
    $params[] = $line;
}
if (!empty($machine)) {
    $conditions[] = "LOWER(machine) = LOWER(?)";
    $params[] = $machine;
}
if (!empty($cause)) {
    $conditions[] = "LOWER(cause) = LOWER(?)";
    $params[] = $cause;
}

$whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total count
$totalSql = "SELECT COUNT(*) AS total FROM IOT_TOOLBOX_STOP_CAUSES $whereClause";
$totalStmt = sqlsrv_query($conn, $totalSql, $params);
if ($totalStmt === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to count total records.', 'error' => sqlsrv_errors()]);
    exit();
}
$totalRow = sqlsrv_fetch_array($totalStmt, SQLSRV_FETCH_ASSOC);
$total = $totalRow ? intval($totalRow['total']) : 0;

// Pagination using ROW_NUMBER()
$startRow = $offset + 1;
$endRow = $offset + $limit;

$sql = "
    WITH OrderedData AS (
        SELECT 
            id, log_date, stop_begin, stop_end, line, machine, cause, recovered_by, note,
            ROW_NUMBER() OVER (ORDER BY log_date DESC, stop_begin DESC, id DESC) AS RowNum
        FROM IOT_TOOLBOX_STOP_CAUSES
        $whereClause
    )
    SELECT id, log_date, stop_begin, stop_end, line, machine, cause, recovered_by, note
    FROM OrderedData
    WHERE RowNum BETWEEN ? AND ?
";

$pagedParams = array_merge($params, [$startRow, $endRow]);
$stmt = sqlsrv_query($conn, $sql, $pagedParams);

if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'SQL query failed.', 'error' => sqlsrv_errors()]);
    exit();
}

$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    foreach (['log_date', 'stop_begin', 'stop_end'] as $field) {
        if ($row[$field] instanceof DateTime) {
            $format = ($field === 'log_date') ? 'Y-m-d' : 'H:i:s';
            $row[$field] = $row[$field]->format($format);
        }
    }
    $data[] = $row;
}

// Summary aggregation grouped by line
$summarySql = "
    SELECT line, COUNT(*) AS count,
           SUM(DATEDIFF(SECOND, stop_begin, stop_end)) AS total_seconds
    FROM IOT_TOOLBOX_STOP_CAUSES
    $whereClause
    GROUP BY line
    ORDER BY total_seconds DESC
";

$summaryStmt = sqlsrv_query($conn, $summarySql, $params);
if ($summaryStmt === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch summary.', 'error' => sqlsrv_errors()]);
    exit();
}

$summary = [];
$totalSeconds = 0;

while ($row = sqlsrv_fetch_array($summaryStmt, SQLSRV_FETCH_ASSOC)) {
    $totalSeconds += $row['total_seconds'] ?? 0;
    $summary[] = [
        'line' => $row['line'],
        'count' => $row['count'],
        'total_seconds' => $row['total_seconds']
    ];
}

echo json_encode([
    'success' => true,
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'data' => $data,
    'summary' => $summary,
    'grand_total_seconds' => $totalSeconds
]);

sqlsrv_free_stmt($stmt);
sqlsrv_free_stmt($totalStmt);
sqlsrv_free_stmt($summaryStmt);
sqlsrv_close($conn);
?>
