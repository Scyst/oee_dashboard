<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

// Default date range (today)
$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

// Build WHERE clause
$conditions = ["log_date BETWEEN ? AND ?"];
$params = [$startDate, $endDate];

if (!empty($line)) {
    $conditions[] = "LOWER(line) = LOWER(?)";
    $params[] = $line;
}

if (!empty($model)) {
    $conditions[] = "LOWER(machine) = LOWER(?)"; // adjust if you have a `model` field instead
    $params[] = $model;
}

$where = "WHERE " . implode(" AND ", $conditions);

// STOP CAUSES
$stopSql = "
    SELECT cause, SUM(DATEDIFF(SECOND, stop_begin, stop_end)) AS total_seconds
    FROM stop_causes
    $where
    GROUP BY cause
    ORDER BY total_seconds DESC
";

$stopStmt = sqlsrv_query($conn, $stopSql, $params);
$stopLabels = [];
$stopValues = [];

if ($stopStmt) {
    while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
        $stopLabels[] = $row['cause'];
        $stopValues[] = round(($row['total_seconds'] ?? 0) / 60, 1); // convert to minutes
    }
} else {
    echo json_encode(["error" => "Stop cause query failed."]);
    exit;
}

while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
    $stopLabels[] = $row['cause'];
    $stopValues[] = round(($row['total_seconds'] ?? 0) / 60, 1); // convert to minutes
}

$partSql = "
    SELECT TOP 50 part_no,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type = 'NG' THEN count_value ELSE 0 END) AS NG,
        SUM(CASE WHEN count_type = 'HOLD' THEN count_value ELSE 0 END) AS HOLD,
        SUM(CASE WHEN count_type = 'REWORK' THEN count_value ELSE 0 END) AS REWORK,
        SUM(CASE WHEN count_type = 'SCRAP' THEN count_value ELSE 0 END) AS SCRAP,
        SUM(CASE WHEN count_type = 'ETC.' THEN count_value ELSE 0 END) AS ETC
    FROM parts
    $where
    GROUP BY part_no
    ORDER BY SUM(count_value) DESC
";

$partStmt = sqlsrv_query($conn, $partSql, $params);
$partLabels = [];
$FG = [];
$NG = [];
$HOLD = [];
$REWORK = [];
$SCRAP = [];
$ETC = [];

while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $partLabels[] = $row['part_no'];
    $FG[] = (int) $row['FG'];
    $NG[] = (int) $row['NG'];
    $HOLD[] = (int) $row['HOLD'];
    $REWORK[] = (int) $row['REWORK'];
    $SCRAP[] = (int) $row['SCRAP'];
    $ETC[] = (int) $row['ETC'];
}


// Final output
echo json_encode([
    "stopCause" => [
        "labels" => $stopLabels,
        "values" => $stopValues
    ],
    "parts" => [
        "labels" => $partLabels,
        "FG"     => $FG,
        "NG"     => $NG,
        "HOLD"   => $HOLD,
        "REWORK" => $REWORK,
        "SCRAP"  => $SCRAP,
        "ETC"    => $ETC
    ]
]);
