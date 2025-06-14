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

// 1. GET PARTS DATA PER LINE
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

function calculateWorkingDays($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day');
    $days = 0;
    for ($date = clone $start; $date < $end; $date->modify('+1 day')) {
        if ($date->format('w') != 0) $days++; // skip Sunday
    }
    return $days;
}

$workingDays = calculateWorkingDays($startDate, $endDate);

$totalFG = 0;
$totalDefects = 0;
$totalActualOutput = 0;
$totalTheoreticalOutput = 0;
$totalPotentialTime = 0;
$totalPlannedTime = 0;
$totalUnplanned = 0;

$lines = [];

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
            $idealCycleTime = 60 / $plannedPerHour; // minutes per part
            $lines[$lineVal]['theoreticalOutput'] += ($fg + $defects) / $plannedPerHour * 60;
        }
    }

    $lines[$lineVal]['fg'] += $fg;
    $lines[$lineVal]['defects'] += $defects;
    $lines[$lineVal]['actual'] += $fg + $defects;
    $lines[$lineVal]['potentialTime'] = $workingDays * 480;
    $lines[$lineVal]['plannedStops'] = $workingDays * 60;
}

// 2. GET DOWNTIME BY LINE
$stopSql = "
    SELECT line, SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
    FROM stop_causes
    $stopWhere
    GROUP BY line
";

$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
if ($stopStmt) {
    while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
        $lines[$row['line']]['unplannedStops'] = (int)$row['downtime'];
    }
}

// 3. Calculate availability per line
foreach ($lines as $lineData) {
    $pt = $lineData['potentialTime'] ?? 0;
    $planned = $lineData['plannedStops'] ?? 0;
    $unplanned = $lineData['unplannedStops'] ?? 0;
    $runtime = max(0, $pt - $planned - $unplanned);
    $availability = $pt > 0 ? ($runtime / $pt) * 100 : 0;

    $totalPotentialTime += $pt;
    $totalUnplanned += $unplanned;
    $totalPlannedTime += $pt + $planned;
    $totalTheoreticalOutput += $lineData['theoreticalOutput'] ?? 0;
}

$qualityPercent = ($totalFG + $totalDefects) > 0 ? ($totalFG / ($totalFG + $totalDefects)) * 100 : 0;
$availabilityPercent = $totalPotentialTime > 0 ? (($totalPotentialTime - $totalUnplanned) / $totalPotentialTime) * 100 : 0;
$performancePercent = $totalTheoreticalOutput > 0 ? ($totalActualOutput / ($totalTheoreticalOutput / 60)) * 100 : 0;

$oee = ($availabilityPercent / 100) * ($performancePercent / 100) * ($qualityPercent / 100) * 100;

echo json_encode([
    "success" => true,
    "quality" => round($qualityPercent, 1),
    "availability" => round($availabilityPercent, 1),
    "performance" => round($performancePercent, 1),
    "oee" => round($oee, 1),
    "fg" => $totalFG,
    "defects" => $totalDefects,
    "runtime" => round($totalPotentialTime - $totalUnplanned),
    "planned_time" => round($totalPlannedTime),
    "downtime" => round($totalUnplanned),
    "planned_output" => round($totalTheoreticalOutput / 60, 2),
    "actual_output" => $totalActualOutput
]);
