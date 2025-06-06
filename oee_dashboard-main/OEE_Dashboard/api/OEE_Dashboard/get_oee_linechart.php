<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

// WHERE clauses for parts and stops
$whereParts = ["log_date BETWEEN ? AND ?"];
$whereStops = ["log_date BETWEEN ? AND ?"];
$paramsParts = [$startDate, $endDate];
$paramsStops = [$startDate, $endDate];

if (!empty($line)) {
    $whereParts[] = "LOWER(line) = LOWER(?)";
    $paramsParts[] = $line;
    $whereStops[] = "LOWER(line) = LOWER(?)";
    $paramsStops[] = $line;
}

if (!empty($model)) {
    $whereParts[] = "LOWER(model) = LOWER(?)";
    $paramsParts[] = $model;
}

$wherePartsStr = "WHERE " . implode(" AND ", $whereParts);
$whereStopsStr = "WHERE " . implode(" AND ", $whereStops);

// --- Get Part Data Per Day ---
$partSql = "
    SELECT 
        CAST(log_date AS DATE) AS date,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type IN ('NG','REWORK','HOLD','SCRAP','ETC.') THEN count_value ELSE 0 END) AS Defects
    FROM parts
    $wherePartsStr
    GROUP BY CAST(log_date AS DATE)
    ORDER BY CAST(log_date AS DATE)
";
$partStmt = sqlsrv_query($conn, $partSql, $paramsParts);
$partMap = [];
while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $date = $row['date']->format('Y-m-d');
    $partMap[$date] = [
        'FG' => (int)$row['FG'],
        'Defects' => (int)$row['Defects']
    ];
}

// --- Get Downtime Per Day ---
$stopSql = "
    SELECT 
        CAST(log_date AS DATE) AS date,
        SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
    FROM stop_causes
    $whereStopsStr
    GROUP BY CAST(log_date AS DATE)
";
$stopStmt = sqlsrv_query($conn, $stopSql, $paramsStops);
$downtimeMap = [];
while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
    $date = $row['date']->format('Y-m-d');
    $downtimeMap[$date] = (int)$row['downtime'];
}

// --- Fallback: Count distinct part types for fallback planned output ---
$distinctSql = "
    SELECT COUNT(DISTINCT model + '|' + part_no + '|' + line) AS part_type_count
    FROM parts
    WHERE log_date BETWEEN ? AND ?" .
    (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "") .
    (!empty($model) ? " AND LOWER(model) = LOWER(?)" : "");

$distinctParams = [$startDate, $endDate];
if (!empty($line)) $distinctParams[] = $line;
if (!empty($model)) $distinctParams[] = $model;

$distinctStmt = sqlsrv_query($conn, $distinctSql, $distinctParams);
$distinctRow = sqlsrv_fetch_array($distinctStmt, SQLSRV_FETCH_ASSOC);
$fallbackPlannedOutput = ((int)$distinctRow['part_type_count']) * 100;

// --- Build daily records ---
$records = [];
$start = new DateTime($startDate);
$end   = new DateTime($endDate);
$end->modify('+1 day');

while ($start < $end) {
    $dateStr = $start->format('Y-m-d');
    $displayDate = $start->format('d-m-y');

    $FG = $partMap[$dateStr]['FG'] ?? 0;
    $Defects = $partMap[$dateStr]['Defects'] ?? 0;
    $produced = $FG + $Defects;

    $plannedTime = 480; // 8 hours per day
    $downtime = $downtimeMap[$dateStr] ?? 0;
    $runtime = max(0, $plannedTime - $downtime);

    $availability = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
    $performance = $fallbackPlannedOutput > 0 ? ($produced / $fallbackPlannedOutput) * 100 : 0;
    $quality = $produced > 0 ? ($FG / $produced) * 100 : 0;
    $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

    $records[] = [
        "date" => $displayDate,
        "availability" => round($availability, 1),
        "performance"  => round($performance, 1),
        "quality"      => round($quality, 1),
        "oee"          => round($oee, 1)
    ];

    $start->modify('+1 day');
}

echo json_encode(["success" => true, "records" => $records]);
