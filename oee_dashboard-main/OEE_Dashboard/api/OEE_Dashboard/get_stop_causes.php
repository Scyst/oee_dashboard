<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

// Shared date filters
$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');

// Extract shared filters
$line  = $_GET['line'] ?? null;
$model = $_GET['model'] ?? null;

// -----------------------------
// STOP CAUSE FILTERING
// -----------------------------
$stopConditions = ["log_date BETWEEN ? AND ?"];
$stopParams = [$startDate, $endDate];

if (!empty($line)) {
    $stopConditions[] = "LOWER(line) = LOWER(?)";
    $stopParams[] = $line;
}
if (!empty($model)) {
    $stopConditions[] = "LOWER(machine) = LOWER(?)"; // 'model' mapped to machine
    $stopParams[] = $model;
}

$stopWhere = "WHERE " . implode(" AND ", $stopConditions);

// -----------------------------
// PART FILTERING
// -----------------------------
$partConditions = ["log_date BETWEEN ? AND ?"];
$partParams = [$startDate, $endDate];

if (!empty($line)) {
    $partConditions[] = "LOWER(line) = LOWER(?)";
    $partParams[] = $line;
}
if (!empty($model)) {
    $partConditions[] = "LOWER(model) = LOWER(?)";
    $partParams[] = $model;
}

$partWhere = "WHERE " . implode(" AND ", $partConditions);

// -----------------------------
// STOP CAUSES QUERY
// -----------------------------
$stopSql = "
    SELECT cause, SUM(DATEDIFF(SECOND, stop_begin, stop_end)) AS total_seconds
    FROM stop_causes
    $stopWhere
    GROUP BY cause
    ORDER BY total_seconds DESC
";

$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
$stopLabels = [];
$stopValues = [];

if ($stopStmt) {
    while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
        $stopLabels[] = $row['cause'];
        $stopValues[] = round(($row['total_seconds'] ?? 0) / 60, 1); // in minutes
    }
} else {
    echo json_encode(["error" => "Stop cause query failed.", "sql_error" => print_r(sqlsrv_errors(), true)]);
    exit;
}

// -----------------------------
// PARTS QUERY
// -----------------------------
$partSql = "
    SELECT TOP 50 part_no,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type = 'NG' THEN count_value ELSE 0 END) AS NG,
        SUM(CASE WHEN count_type = 'HOLD' THEN count_value ELSE 0 END) AS HOLD,
        SUM(CASE WHEN count_type = 'REWORK' THEN count_value ELSE 0 END) AS REWORK,
        SUM(CASE WHEN count_type = 'SCRAP' THEN count_value ELSE 0 END) AS SCRAP,
        SUM(CASE WHEN count_type = 'ETC.' THEN count_value ELSE 0 END) AS ETC
    FROM parts
    $partWhere
    GROUP BY part_no
    ORDER BY SUM(count_value) DESC
";

$partStmt = sqlsrv_query($conn, $partSql, $partParams);
$partLabels = [];
$FG = [];
$NG = [];
$HOLD = [];
$REWORK = [];
$SCRAP = [];
$ETC = [];

if ($partStmt) {
    while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
        $partLabels[] = $row['part_no'];
        $FG[] = (int) $row['FG'];
        $NG[] = (int) $row['NG'];
        $HOLD[] = (int) $row['HOLD'];
        $REWORK[] = (int) $row['REWORK'];
        $SCRAP[] = (int) $row['SCRAP'];
        $ETC[] = (int) $row['ETC'];
    }
} else {
    echo json_encode(["error" => "Parts query failed.", "sql_error" => print_r(sqlsrv_errors(), true)]);
    exit;
}

// -----------------------------
// FINAL OUTPUT
// -----------------------------
echo json_encode([
    "stopCause" => [
        "labels" => $stopLabels,
        "values" => $stopValues
    ],
    "parts" => [
        "labels"  => $partLabels,
        "FG"      => $FG,
        "NG"      => $NG,
        "HOLD"    => $HOLD,
        "REWORK"  => $REWORK,
        "SCRAP"   => $SCRAP,
        "ETC"     => $ETC
    ]
]);
?>