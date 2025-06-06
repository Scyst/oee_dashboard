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
        MIN(log_time) AS first_time,
        MAX(log_time) AS last_time,
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
$totalPlannedOutput = 0;
$latestEndTime = null;
$hasProduction = false;

$shiftStart = new DateTime("08:00");
$breaks = [
    ['11:30', '12:30'],
    ['17:00', '17:30'],
    ['23:30', '00:30'],
    ['05:00', '05:30']
];

while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $fg = (int)$row['FG'];
    $defects = (int)$row['Defects'];
    $modelVal = $row['model'];
    $partNo = $row['part_no'];
    $lineVal = $row['line'];

    $totalFG += $fg;
    $totalDefects += $defects;
    $totalActualOutput += ($fg + $defects);

    if ($fg + $defects > 0) {
        $hasProduction = true;
    }

    if ($row['last_time']) {
        $lastTime = $row['last_time']->format('H:i');
        if (!$latestEndTime || $lastTime > $latestEndTime) {
            $latestEndTime = $lastTime;
        }
    }

    $planSql = "SELECT planned_output FROM performance_parameter WHERE model = ? AND part_no = ? AND line = ?";
    $planStmt = sqlsrv_query($conn, $planSql, [$modelVal, $partNo, $lineVal]);
    if ($planStmt && $planRow = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
        $hourlyOutput = (int)$planRow['planned_output'];
        $totalPlannedOutput += $hourlyOutput; // We'll multiply later by hour count
    }
}

$stopSql = "
    SELECT MAX(stop_end) AS last_stop
    FROM stop_causes
    $stopWhere
";
$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
$stopRow = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);
if ($stopRow && $stopRow['last_stop']) {
    $stopTime = $stopRow['last_stop']->format('H:i');
    if (!$latestEndTime || $stopTime > $latestEndTime) {
        $latestEndTime = $stopTime;
    }
}

$plannedHours = 0;
if ($hasProduction) {
    $shiftStartTime = new DateTime("08:00");
    $endTime = $latestEndTime ? new DateTime($latestEndTime) : new DateTime("17:00");
    if ($endTime < $shiftStartTime) {
        $endTime = new DateTime("17:00");
    }

    $plannedHours = (int)ceil(($endTime->getTimestamp() - $shiftStartTime->getTimestamp()) / 3600);

    foreach ($breaks as [$start, $end]) {
        $breakStart = new DateTime($start);
        $breakEnd = new DateTime($end);

        if ($breakEnd < $shiftStartTime) continue;
        if ($breakStart > $endTime) break;

        $actualStart = max($shiftStartTime, $breakStart);
        $actualEnd = min($endTime, $breakEnd);

        if ($actualEnd > $actualStart) {
            $plannedHours -= ceil(($actualEnd->getTimestamp() - $actualStart->getTimestamp()) / 3600);
        }
    }
}

$plannedTime = max(0, $plannedHours * 60);

$runtime = $plannedTime; // For simplicity

$qualityPercent = ($totalFG + $totalDefects) > 0 ? ($totalFG / ($totalFG + $totalDefects)) * 100 : 0;
$availabilityPercent = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;

if ($totalPlannedOutput === 0) {
    $distinctPartSql = "
        SELECT COUNT(DISTINCT model + '|' + part_no + '|' + line) AS type_count
        FROM parts
        WHERE log_date BETWEEN ? AND ?"
        . (!empty($line) ? " AND line = ?" : "")
        . (!empty($model) ? " AND model = ?" : "");

    $distinctParams = [$startDate, $endDate];
    if (!empty($line))  $distinctParams[] = $line;
    if (!empty($model)) $distinctParams[] = $model;

    $distinctStmt = sqlsrv_query($conn, $distinctPartSql, $distinctParams);
    $distinctRow = sqlsrv_fetch_array($distinctStmt, SQLSRV_FETCH_ASSOC);
    $typeCount = (int) $distinctRow['type_count'];

    $totalPlannedOutput = $typeCount * 100; // hourly
}

$totalPlannedOutput *= $plannedHours;
$performancePercent = $totalPlannedOutput > 0 ? ($totalActualOutput / $totalPlannedOutput) * 100 : 0;
$oee = ($availabilityPercent / 100) * ($performancePercent / 100) * ($qualityPercent / 100) * 100;

echo json_encode([
    "success" => true,
    "quality" => round($qualityPercent, 1),
    "availability" => round($availabilityPercent, 1),
    "performance" => round($performancePercent, 1),
    "oee" => round($oee, 1),
    "fg" => $totalFG,
    "defects" => $totalDefects,
    "runtime" => $runtime,
    "planned_time" => $plannedTime, // ✅ add this
    "downtime" => $downtime,        // ✅ add this
    "planned_output" => $totalPlannedOutput,
    "actual_output" => $totalActualOutput
]);
