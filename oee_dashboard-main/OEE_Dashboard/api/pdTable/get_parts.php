<?php
header('Content-Type: application/json');
require_once("../../api/db.php");

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
$startRow = ($page - 1) * $limit + 1;
$endRow = $page * $limit;

$conditions = [];
$params = [];

$startDate = $_GET['startDate'] ?? null;
$endDate   = $_GET['endDate'] ?? null;
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;
$partNo    = $_GET['part_no'] ?? null;
$countType = $_GET['count_type'] ?? null;
$lotNo     = $_GET['lot_no'] ?? null;

if ($startDate && $endDate) {
    $conditions[] = "log_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}
if ($line) {
    $conditions[] = "LOWER(line) = LOWER(?)";
    $params[] = $line;
}
if ($model) {
    $conditions[] = "LOWER(model) = LOWER(?)";
    $params[] = $model;
}
if ($partNo) {
    $conditions[] = "LOWER(part_no) = LOWER(?)";
    $params[] = $partNo;
}
if ($countType) {
    $conditions[] = "LOWER(count_type) = LOWER(?)";
    $params[] = $countType;
}
if ($lotNo) {
    $conditions[] = "LOWER(lot_no) = LOWER(?)";
    $params[] = $lotNo;
}

$whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// --- Total Count ---
$totalSql = "SELECT COUNT(*) AS total FROM IOT_TOOLBOX_PARTS $whereClause";
$totalStmt = sqlsrv_query($conn, $totalSql, $params);
$totalRow = $totalStmt ? sqlsrv_fetch_array($totalStmt, SQLSRV_FETCH_ASSOC) : null;
$total = $totalRow ? (int)$totalRow['total'] : 0;

// --- Data with ROW_NUMBER for SQL Server 2012 ---
$dataSql = "
    WITH OrderedData AS (
        SELECT 
            id, log_date, log_time, line, model, part_no, lot_no, count_value, count_type, note,
            ROW_NUMBER() OVER (ORDER BY log_date DESC, log_time DESC, id DESC) AS RowNum
        FROM IOT_TOOLBOX_PARTS
        $whereClause
    )
    SELECT *
    FROM OrderedData
    WHERE RowNum BETWEEN ? AND ?
";
$dataStmt = sqlsrv_query($conn, $dataSql, [...$params, $startRow, $endRow]);
$data = [];
if ($dataStmt) {
    while ($row = sqlsrv_fetch_array($dataStmt, SQLSRV_FETCH_ASSOC)) {
        if ($row['log_date'] instanceof DateTime) {
            $row['log_date'] = $row['log_date']->format('Y-m-d');
        }
        if ($row['log_time'] instanceof DateTime) {
            $row['log_time'] = $row['log_time']->format('H:i:s');
        }
        $data[] = $row;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Data query failed.', 'error' => sqlsrv_errors()]);
    exit;
}

// --- Summary ---
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
        SUM(CASE WHEN count_type = 'ETC.' THEN count_value ELSE 0 END) AS ETC
    FROM IOT_TOOLBOX_PARTS
    $whereClause
    GROUP BY model, part_no, lot_no
    ORDER BY model, part_no, lot_no
";
$summaryStmt = sqlsrv_query($conn, $summarySql, $params);
$summary = [];
if ($summaryStmt) {
    while ($row = sqlsrv_fetch_array($summaryStmt, SQLSRV_FETCH_ASSOC)) {
        $summary[] = $row;
    }
}

// --- Grand Totals ---
$grandSql = "
    SELECT
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type = 'NG' THEN count_value ELSE 0 END) AS NG,
        SUM(CASE WHEN count_type = 'HOLD' THEN count_value ELSE 0 END) AS HOLD,
        SUM(CASE WHEN count_type = 'REWORK' THEN count_value ELSE 0 END) AS REWORK,
        SUM(CASE WHEN count_type = 'SCRAP' THEN count_value ELSE 0 END) AS SCRAP,
        SUM(CASE WHEN count_type = 'ETC.' THEN count_value ELSE 0 END) AS ETC
    FROM IOT_TOOLBOX_PARTS
    $whereClause
";
$grandStmt = sqlsrv_query($conn, $grandSql, $params);
$grandTotal = $grandStmt ? sqlsrv_fetch_array($grandStmt, SQLSRV_FETCH_ASSOC) : [];

// --- Final Output ---
echo json_encode([
    'success'     => true,
    'page'        => $page,
    'limit'       => $limit,
    'total'       => $total,
    'data'        => $data,
    'summary'     => $summary,
    'grand_total' => $grandTotal
]);

sqlsrv_free_stmt($dataStmt ?? null);
sqlsrv_free_stmt($summaryStmt ?? null);
sqlsrv_free_stmt($grandStmt ?? null);
sqlsrv_close($conn);
?>
