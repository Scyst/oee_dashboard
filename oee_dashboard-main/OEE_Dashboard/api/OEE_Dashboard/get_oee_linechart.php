<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

$records = [];
$period = new DatePeriod(
    new DateTime($startDate),
    new DateInterval('P1D'),
    (new DateTime($endDate))->modify('+1 day')
);

foreach ($period as $date) {
    $logDate = $date->format('Y-m-d');

    // Part Query
    $partSql = "
        SELECT model, part_no, line,
            SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
            SUM(CASE WHEN count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN count_value ELSE 0 END) AS Defects
        FROM parts
        WHERE log_date = ?
        " . (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "") .
            (!empty($model) ? " AND LOWER(model) = LOWER(?)" : "") . "
        GROUP BY model, part_no, line
    ";
    $params = [$logDate];
    if (!empty($line))  $params[] = $line;
    if (!empty($model)) $params[] = $model;

    $partStmt = sqlsrv_query($conn, $partSql, $params);
    if (!$partStmt) continue;

    $FG = $Defects = $ActualOutput = $PlannedOutput = 0;
    $typesSeen = [];

    while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
        $fg = (int)$row['FG'];
        $def = (int)$row['Defects'];
        $modelVal = $row['model'];
        $partNo = $row['part_no'];
        $lineVal = $row['line'];

        $FG += $fg;
        $Defects += $def;
        $ActualOutput += ($fg + $def);
        $typesSeen[] = $modelVal . "|" . $partNo . "|" . $lineVal;

        // Get planned output from performance_parameter
        $planStmt = sqlsrv_query($conn,
            "SELECT planned_output FROM performance_parameter WHERE model = ? AND part_no = ? AND line = ?",
            [$modelVal, $partNo, $lineVal]
        );
        if ($planStmt && $planRow = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
            $PlannedOutput += (int)$planRow['planned_output'];
        }
    }

    // Fallback for planned output
    if ($PlannedOutput === 0 && count($typesSeen) > 0) {
        $PlannedOutput = count(array_unique($typesSeen)) * 100;
    }

    // Downtime
    $stopSql = "
        SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
        FROM stop_causes
        WHERE log_date = ?
        " . (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "");
    $stopParams = [$logDate];
    if (!empty($line)) $stopParams[] = $line;

    $stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
    $downtime = 0;
    if ($stopStmt && $stopRow = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
        $downtime = (int)$stopRow['downtime'];
    }

    $plannedTime = 480;
    $runtime = max(0, $plannedTime - $downtime);

    // Metrics
    $quality = ($FG + $Defects) > 0 ? ($FG / ($FG + $Defects)) * 100 : 0;
    $availability = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
    $performance = $PlannedOutput > 0 ? ($ActualOutput / $PlannedOutput) * 100 : 0;
    $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

    $records[] = [
        "date" => $date->format("d-m-y"),
        "availability" => round($availability, 1),
        "performance"  => round($performance, 1),
        "quality"      => round($quality, 1),
        "oee"          => round($oee, 1)
    ];
}

// Only keep last 15 days if more
if (count($records) > 15) {
    $records = array_slice($records, -15);
}

echo json_encode([
    "success" => true,
    "records" => $records
]);
