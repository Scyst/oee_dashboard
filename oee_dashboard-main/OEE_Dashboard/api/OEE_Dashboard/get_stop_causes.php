<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

// Handle date range from GET or default to today
$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');

$params = [$startDate, $endDate];
$where  = "WHERE log_date BETWEEN ? AND ?";

// --- STOP CAUSES CHART ---
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

while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
    $stopLabels[] = $row['cause'];
    $stopValues[] = round(($row['total_seconds'] ?? 0) / 60, 1); // convert to minutes
}

// --- PARTS CHART: GOOD vs BAD JOBS per PART NO ---
$partSql = "
    SELECT part_no,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS good_jobs,
        SUM(CASE WHEN count_type IN ('NG','HOLD','REWORK','SCRAP','ETC') THEN count_value ELSE 0 END) AS bad_jobs
    FROM parts
    $where
    GROUP BY part_no
    ORDER BY part_no
";

$partStmt = sqlsrv_query($conn, $partSql, $params);
$partLabels = [];
$partGood = [];
$partBad = [];

while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $partLabels[] = $row['part_no'];
    $partGood[] = (int) $row['good_jobs'];
    $partBad[] = (int) $row['bad_jobs'];
}

// Final output
echo json_encode([
    "stopCause" => [
        "labels" => $stopLabels,
        "values" => $stopValues
    ],
    "parts" => [
        "labels" => $partLabels,
        "good"   => $partGood,
        "bad"    => $partBad
    ]
]);
