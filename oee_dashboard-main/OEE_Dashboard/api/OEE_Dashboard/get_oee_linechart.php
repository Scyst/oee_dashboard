<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

$startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-14 days'));
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

// Adjust range to at least 15 days
$actualStart = new DateTime($startDate);
$actualEnd = new DateTime($endDate);
$interval = $actualEnd->diff($actualStart)->days;

if ($interval < 14) {
    $startDate = (clone $actualEnd)->modify('-14 days')->format('Y-m-d');
}

// Prepare maps
$records = [];
$planMap = [];

// Get planned outputs
$planSql = "
    SELECT model, part_no, planned_output
    FROM performance_parameter
    " . (!empty($line) ? "WHERE LOWER(line) = LOWER(?)" : "");
$planParams = !empty($line) ? [$line] : [];
$planStmt = sqlsrv_query($conn, $planSql, $planParams);
while ($row = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
    $key = strtolower(trim($row['model'])) . "|" . strtolower(trim($row['part_no']));
    $planMap[$key] = (int)$row['planned_output'];
}

// Build date list
$datePeriod = new DatePeriod(
    new DateTime($startDate),
    new DateInterval('P1D'),
    (new DateTime($endDate))->modify('+1 day')
);

foreach ($datePeriod as $dateObj) {
    $logDate = $dateObj->format('Y-m-d');
    $displayDate = $dateObj->format('d-m-y');

    // PARTS
    $partSql = "
        SELECT model, part_no,
            SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
            SUM(CASE WHEN count_type IN ('NG','REWORK','HOLD','SCRAP','ETC.') THEN count_value ELSE 0 END) AS Defects
        FROM parts
        WHERE log_date = ?
        " . (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "") .
            (!empty($model) ? " AND LOWER(model) = LOWER(?)" : "") . "
        GROUP BY model, part_no
    ";

    $partParams = [$logDate];
    if (!empty($line)) $partParams[] = $line;
    if (!empty($model)) $partParams[] = $model;

    $partStmt = sqlsrv_query($conn, $partSql, $partParams);
    $FG = $defects = $actualOutput = $plannedOutput = 0;

    while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
        $fg = (int)$row['FG'];
        $ng = (int)$row['Defects'];
        $FG += $fg;
        $defects += $ng;
        $actualOutput += ($fg + $ng);

        $key = strtolower(trim($row['model'])) . "|" . strtolower(trim($row['part_no']));
        $plannedOutput += $planMap[$key] ?? 0;
    }

    // STOPS
    $stopSql = "
        SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
        FROM stop_causes
        WHERE log_date = ?
        " . (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "");
    $stopParams = [$logDate];
    if (!empty($line)) $stopParams[] = $line;

    $stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
    $stopData = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);
    $downtime = (int) ($stopData['downtime'] ?? 0);
    $plannedTime = 480;
    $runtime = max(0, $plannedTime - $downtime);

    // CALCULATE %
    $availability = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
    $performance  = $plannedOutput > 0 ? ($actualOutput / $plannedOutput) * 100 : 0;
    $quality      = ($FG + $defects) > 0 ? ($FG / ($FG + $defects)) * 100 : 0;
    $oee          = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

    $records[] = [
        "date"         => $displayDate,
        "availability" => round($availability, 1),
        "performance"  => round($performance, 1),
        "quality"      => round($quality, 1),
        "oee"          => round($oee, 1)
    ];
}

echo json_encode(["success" => true, "records" => $records]);
