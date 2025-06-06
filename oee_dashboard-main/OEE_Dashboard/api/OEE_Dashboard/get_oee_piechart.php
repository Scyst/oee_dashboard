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

// Determine latest activity time (production or stop)
$latestTimeSql = "
    SELECT MAX(latest_time) AS max_time FROM (
        SELECT MAX(log_time) AS latest_time FROM parts WHERE log_date BETWEEN ? AND ?
        UNION ALL
        SELECT MAX(stop_end) FROM stop_causes WHERE log_date BETWEEN ? AND ?
    ) AS all_times";
$latestStmt = sqlsrv_query($conn, $latestTimeSql, [$startDate, $endDate, $startDate, $endDate]);
$latestRow = sqlsrv_fetch_array($latestStmt, SQLSRV_FETCH_ASSOC);
$latestTimeStr = $latestRow['max_time'] ? $latestRow['max_time']->format('H:i:s') : null;

function calculatePlannedTime($dateStr, $latestTimeStr) {
    $startWork = new DateTime("$dateStr 08:00:00");
    $latest = new DateTime("$dateStr $latestTimeStr");

    if ($latest <= $startWork) return 0;

    $latest->modify('+59 minutes');
    $latest->setTime($latest->format('H'), 0);
    $plannedMinutes = ($latest->getTimestamp() - $startWork->getTimestamp()) / 60;

    $breaks = [
        ['11:30', '12:30'],
        ['17:00', '17:30'],
        ['23:30', '00:30'],
        ['05:00', '05:30']
    ];

    foreach ($breaks as [$bStart, $bEnd]) {
        $breakStart = new DateTime("$dateStr $bStart");
        $breakEnd = new DateTime("$dateStr $bEnd");
        if ($breakEnd < $breakStart) $breakEnd->modify('+1 day');

        if ($latest > $breakStart) {
            $overlapStart = max($startWork, $breakStart);
            $overlapEnd = min($latest, $breakEnd);
            if ($overlapEnd > $overlapStart) {
                $plannedMinutes -= ($overlapEnd->getTimestamp() - $overlapStart->getTimestamp()) / 60;
            }
        }
    }

    return max(0, round($plannedMinutes));
}

$plannedTime = $latestTimeStr ? calculatePlannedTime($startDate, $latestTimeStr) : 0;

$partSql = "
    SELECT model, part_no, line,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN count_value ELSE 0 END) AS Defects
    FROM parts
    $partWhere
    GROUP BY model, part_no, line";

$partStmt = sqlsrv_query($conn, $partSql, $partParams);
$totalFG = 0;
$totalDefects = 0;
$totalActualOutput = 0;
$totalPlannedOutput = 0;

while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $fg = (int)$row['FG'];
    $defects = (int)$row['Defects'];
    $modelVal = $row['model'];
    $partNo = $row['part_no'];
    $lineVal = $row['line'];

    $totalFG += $fg;
    $totalDefects += $defects;
    $totalActualOutput += ($fg + $defects);

    $planSql = "SELECT planned_output FROM performance_parameter WHERE model = ? AND part_no = ? AND line = ?";
    $planStmt = sqlsrv_query($conn, $planSql, [$modelVal, $partNo, $lineVal]);
    if ($planStmt && $planRow = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
        $hourlyOutput = (int)$planRow['planned_output'];
        $totalPlannedOutput += $hourlyOutput * ($plannedTime / 60);
    }
}

$stopSql = "SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime FROM stop_causes $stopWhere";
$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
$stopData = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);
$downtime = (int) $stopData['downtime'];
$runtime = max(0, $plannedTime - $downtime);

$qualityPercent = ($totalFG + $totalDefects) > 0 ? ($totalFG / ($totalFG + $totalDefects)) * 100 : 0;
$availabilityPercent = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;

if ($totalPlannedOutput === 0 && $plannedTime > 0) {
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

    $totalPlannedOutput = $typeCount * 100 * ($plannedTime / 60);
}

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
    "planned_time" => $plannedTime,
    "downtime" => $downtime,
    "planned_output" => $totalPlannedOutput,
    "actual_output" => $totalActualOutput
]);
