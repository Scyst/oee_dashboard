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
$totalPlannedOutput = 0;
$totalRuntime = 0;
$totalPlannedTime = 0;
$effectiveMinutes = 0;

function calculatePlannedTimeRange($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day');

    $plannedMinutes = 0;
    $breaks = [
        ['11:30', '12:30'],
        ['17:00', '17:30'],
        ['23:30', '00:30'],
        ['05:00', '05:30']
    ];

    for ($date = clone $start; $date < $end; $date->modify('+1 day')) {
        $dayOfWeek = $date->format('w');

        $workStart = new DateTime($date->format('Y-m-d') . ' 08:00:00');
        $workEnd = new DateTime($date->format('Y-m-d') . ' 17:00:00');
        $minutes = ($workEnd->getTimestamp() - $workStart->getTimestamp()) / 60;

        foreach ($breaks as [$bStart, $bEnd]) {
            $breakStart = new DateTime($date->format('Y-m-d') . ' ' . $bStart);
            $breakEnd = new DateTime($date->format('Y-m-d') . ' ' . $bEnd);
            if ($breakEnd < $breakStart) $breakEnd->modify('+1 day');

            $overlapStart = max($workStart, $breakStart);
            $overlapEnd = min($workEnd, $breakEnd);
            if ($overlapEnd > $overlapStart) {
                $minutes -= ($overlapEnd->getTimestamp() - $overlapStart->getTimestamp()) / 60;
            }
        }

        $plannedMinutes += max(0, $minutes);
    }

    return round($plannedMinutes);
}

$plannedTime = calculatePlannedTimeRange($startDate, $endDate);
$totalPlannedTime = $plannedTime;

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
            $effectiveMinutes += (($fg + $defects) / $plannedPerHour) * 60;
        }
    }
}

$stopSql = "
    SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
    FROM stop_causes
    $stopWhere
";
$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
$stopRow = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);
$downtime = (int) $stopRow['downtime'];

$runtime = max(0, $plannedTime - $downtime);
$availabilityPercent = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
$qualityPercent = ($totalFG + $totalDefects) > 0 ? ($totalFG / ($totalFG + $totalDefects)) * 100 : 0;

$performancePercent = $plannedTime > 0
    ? (100 - ((($plannedTime - $effectiveMinutes) / $plannedTime) * 100))
    : 0;

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
    "planned_time" => $plannedTime,
    "downtime" => $plannedTime - $runtime,
    "planned_output" => round($effectiveMinutes / 60, 2),
    "actual_output" => $totalActualOutput
]);
