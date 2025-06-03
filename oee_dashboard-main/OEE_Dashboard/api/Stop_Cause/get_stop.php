<?php
//fetchPaginatedParts() in paginationTble.js

header('Content-Type: application/json');
require_once("../../api/db.php");

// Pagination params
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
$offset = ($page - 1) * $limit;

// Date filter
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

// Get total for pagination
$totalSql = "SELECT COUNT(*) AS total FROM stop_causes $whereClause";
$totalStmt = sqlsrv_query($conn, $totalSql, $params);
$totalRow = sqlsrv_fetch_array($totalStmt, SQLSRV_FETCH_ASSOC);
$total = $totalRow ? intval($totalRow['total']) : 0;

// Add pagination params to query
$params[] = $offset;
$params[] = $limit;

// Main data query
$sql = "
    SELECT id, log_date, stop_begin, stop_end, line, machine, cause, recovered_by, note
    FROM stop_causes
    $whereClause
    ORDER BY log_date DESC, stop_begin DESC, id DESC
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

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
    if ($row['stop_begin'] instanceof DateTime) {
        $row['stop_begin'] = $row['stop_begin']->format('H:i:s');
    }
    if ($row['stop_end'] instanceof DateTime) {
        $row['stop_end'] = $row['stop_end']->format('H:i:s');
    }
    $data[] = $row;
}

$summarySql = "
    SELECT line, COUNT(*) AS count,
        SUM(DATEDIFF(SECOND, stop_begin, stop_end)) AS total_seconds
    FROM stop_causes
    $whereClause
    GROUP BY line
    ORDER BY total_seconds DESC
";

$summaryStmt = sqlsrv_query($conn, $summarySql, $params);
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
?>
