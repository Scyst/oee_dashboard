<?php
//call by fetchParts() in pdTable.js & fetchPaginatedParts() in paginationTble.js

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
$model = $_GET['model'] ?? null;
$partNo = $_GET['part_no'] ?? null;
$countType = $_GET['count_type'] ?? null;

if ($startDate && $endDate) {
    $conditions[] = "log_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}
if (!empty($line)) {
    $conditions[] = "LOWER(line) = LOWER(?)";
    $params[] = $line;
}
if (!empty($model)) {
    $conditions[] = "LOWER(model) = LOWER(?)";
    $params[] = $model;
}
if (!empty($partNo)) {
    $conditions[] = "LOWER(part_no) = LOWER(?)";
    $params[] = $partNo;
}
if (!empty($countType)) {
    $conditions[] = "LOWER(count_type) = LOWER(?)";
    $params[] = $countType;
}

$whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total for pagination
$totalSql = "SELECT COUNT(*) AS total FROM parts $whereClause";
$totalStmt = sqlsrv_query($conn, $totalSql, $params);
$totalRow = sqlsrv_fetch_array($totalStmt, SQLSRV_FETCH_ASSOC);
$total = $totalRow ? intval($totalRow['total']) : 0;

// Add pagination params to query
$params[] = $offset;
$params[] = $limit;

// Main data query
$sql = "
    SELECT id, log_date, log_time, line, model, part_no, count_value, count_type, note
    FROM parts
    $whereClause
    ORDER BY log_date DESC, log_time DESC, id DESC
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
    if ($row['log_time'] instanceof DateTime) {
        $row['log_time'] = $row['log_time']->format('H:i:s');
    }
    $data[] = $row;
}

// Return JSON
echo json_encode([
    'success' => true,
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'data' => $data
]);

sqlsrv_free_stmt($stmt);
?>
