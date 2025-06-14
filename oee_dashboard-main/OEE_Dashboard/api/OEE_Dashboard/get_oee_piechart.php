<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

$stopConditions = ["log_date BETWEEN ? AND ?"];
$stopParams = [$startDate, $endDate];
$partConditions = ["log_date BETWEEN ? AND ?"];
$partParams = [$startDate, $endDate];

if (!empty($line)) {
    $stopConditions[] = "LOWER(line) = LOWER(?)";
    $stopParams[] = $line;
    $partConditions[] = "LOWER(line) = LOWER(?)";
    $partParams[] = $line;
}
if (!empty($model)) {
    $partConditions[] = "LOWER(model) = LOWER(?)";
    $partParams[] = $model;
}

$stopWhere = "WHERE " . implode(" AND ", $stopConditions);
$partWhere = "WHERE " . implode(" AND ", $partConditions);

$partSql = "
    SELECT model, part_no, line,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN count_value ELSE 0 END) AS Defects
    FROM parts
    $partWhere
    GROUP BY model, part_no, line
";

$partStmt = sqlsrv_query($conn, $partSql, $partParams);
if (!$partStmt) {
    echo json_encode(["success" => false, "message" => "Part query failed.", "sql_error" => sqlsrv_errors()]);
    exit;
}

$totalFG = 0;
$totalDefects = 0;
$totalActualOutput = 0;
$theoreticalOutput = 0;

function calculateWorkingDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day');

    $workingDays = 0;
    for ($date = clone $start; $date < $end; $date->modify('+1 day')) {
        if ($date->format('w') != 0) { // skip Sundays
            $workingDays++;
        }
    }
    return $workingDays;
}

$workingDays = calculateWorkingDays($startDate, $endDate);
$plannedTime = $workingDays * 480;     // 8 hours/day × 60 minutes
$plannedStops = $workingDays * 60;     // 1 hour break/day
$potentialTime = $plannedTime;

// 🛠 Unplanned Stops
$stopSql = "
    SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
    FROM stop_causes
    $stopWhere
";
$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
$stopRow = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);
$unplannedStops = (int) $stopRow['downtime'];

$actualRuntime = max(0, $potentialTime - $plannedStops - $unplannedStops);

// 🔄 Part Summary & Ideal Cycle Time
while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $fg = (int)$row['FG'];
    $defects = (int)$row['Defects'];
    $modelVal = $row['model'];
    $partNo = $row['part_no'];
    $lineVal = $row['line'];

    $totalFG += $fg;
    $totalDefects += $defects;
    $totalActualOutput += ($fg + $defects);

    $planSql = "SELECT planned_output FROM parameter WHERE model = ? AND part_no = ? AND line = ?";
    $planStmt = sqlsrv_query($conn, $planSql, [$modelVal, $partNo, $lineVal]);
    if ($planStmt && $planRow = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
        $plannedPerHour = (int)$planRow['planned_output'];
        if ($plannedPerHour > 0) {
            $idealCycleTime = 60 / $plannedPerHour; // in minutes
            $theoreticalOutput += ($actualRuntime / $idealCycleTime);
        }
    }
}

$qualityPercent = ($totalFG + $totalDefects) > 0 ? ($totalFG / ($totalFG + $totalDefects)) * 100 : 0;
$availabilityPercent = $potentialTime > 0 ? ($actualRuntime / $potentialTime) * 100 : 0;
$performancePercent = $theoreticalOutput > 0 ? ($totalActualOutput / $theoreticalOutput) * 100 : 0;

$oee = ($availabilityPercent / 100) * ($performancePercent / 100) * ($qualityPercent / 100) * 100;

echo json_encode([
    "success" => true,
    "quality" => round($qualityPercent, 1),
    "availability" => round($availabilityPercent, 1),
    "performance" => round($performancePercent, 1),
    "oee" => round($oee, 1),
    "fg" => $totalFG,
    "defects" => $totalDefects,
    "runtime" => $actualRuntime,
    "planned_time" => $plannedTime,
    "downtime" => $unplannedStops,
    "planned_output" => round($theoreticalOutput, 2),
    "actual_output" => $totalActualOutput
]);
