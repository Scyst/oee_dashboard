<?php
//call by fetchParts() in pdTable.js & fetchPaginatedParts() in paginationTble.js

header('Content-Type: application/json');
require_once("../../api/db.php");

// Pagination params
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
$offset = ($page - 1) * $limit;

// Filters
$conditions = [];
$params = [];

$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;
$line = $_GET['line'] ?? null;
$model = $_GET['model'] ?? null;
$partNo = $_GET['part_no'] ?? null;
$countType = $_GET['count_type'] ?? null;
$lotNo = $_GET['lot_no'] ?? null;

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
if (!empty($lotNo)) {
    $conditions[] = "LOWER(lot_no) = LOWER(?)";
    $params[] = $lotNo;
}

$whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total for pagination
$totalSql = "SELECT COUNT(*) AS total FROM parts $whereClause";
$totalStmt = sqlsrv_query($conn, $totalSql, $params);
$totalRow = sqlsrv_fetch_array($totalStmt, SQLSRV_FETCH_ASSOC);
$total = $totalRow ? intval($totalRow['total']) : 0;

// Add pagination params to query
$paramsWithPagination = $params;
$paramsWithPagination[] = $offset;
$paramsWithPagination[] = $limit;

// Main data query
$sql = "
    SELECT id, log_date, log_time, line, model, part_no, lot_no, count_value, count_type, note
    FROM parts
    $whereClause
    ORDER BY log_date DESC, log_time DESC, id DESC
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";

$stmt = sqlsrv_query($conn, $sql, $paramsWithPagination);

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

// --- Summary Query ---
$summarySql = "
    SELECT
        model,
        part_no,
        lot_no,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type = 'NG' THEN count_value ELSE 0 END) AS NG,
        SUM(CASE WHEN count_type = 'HOLD' THEN count_value ELSE 0 END) AS HOLD,
        SUM(CASE WHEN count_type = 'REWORK' THEN count_value ELSE 0 END) AS REWORK,
        SUM(CASE WHEN count_type = 'SCRAP' THEN count_value ELSE 0 END) AS SCRAP,
        SUM(CASE WHEN count_type = 'ETC' THEN count_value ELSE 0 END) AS ETC
    FROM parts
    $whereClause
    GROUP BY model, part_no, lot_no
    ORDER BY model, part_no, lot_no
";

$summaryStmt = sqlsrv_query($conn, $summarySql, $params);
$summary = [];
while ($row = sqlsrv_fetch_array($summaryStmt, SQLSRV_FETCH_ASSOC)) {
    $summary[] = $row;
}

// --- Grand Total Summary ---
$grandSql = "
    SELECT
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type = 'NG' THEN count_value ELSE 0 END) AS NG,
        SUM(CASE WHEN count_type = 'HOLD' THEN count_value ELSE 0 END) AS HOLD,
        SUM(CASE WHEN count_type = 'REWORK' THEN count_value ELSE 0 END) AS REWORK,
        SUM(CASE WHEN count_type = 'SCRAP' THEN count_value ELSE 0 END) AS SCRAP,
        SUM(CASE WHEN count_type = 'ETC' THEN count_value ELSE 0 END) AS ETC
    FROM parts
    $whereClause
";

$grandStmt = sqlsrv_query($conn, $grandSql, $params);
$grandTotal = sqlsrv_fetch_array($grandStmt, SQLSRV_FETCH_ASSOC);

// Return JSON
echo json_encode([
    'success' => true,
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'data' => $data,
    'summary' => $summary,
    'grand_total' => $grandTotal
]);

sqlsrv_free_stmt($stmt);
?>
