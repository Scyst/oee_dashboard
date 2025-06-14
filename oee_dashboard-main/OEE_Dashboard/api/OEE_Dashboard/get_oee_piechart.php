<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

// --- Input Parameters ---
$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

// --- Calculate Planned Time (Your function is kept as is, but consider making it more dynamic later) ---
function calculatePlannedTimeRange($startDate, $endDate) {
    // This function remains the same as your original code.
    // For future improvement, consider storing shift/break schedules in the database.
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day');
    $plannedMinutes = 0;
    $breaks = [['11:30', '12:30'], ['17:00', '17:30'], ['23:30', '00:30'], ['05:00', '05:30']];
    for ($date = clone $start; $date < $end; $date->modify('+1 day')) {
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

// --- Calculate Downtime ---
$stopConditions = ["log_date BETWEEN ? AND ?"];
$stopParams = [$startDate, $endDate];
if (!empty($line)) {
    $stopConditions[] = "LOWER(line) = LOWER(?)";
    $stopParams[] = $line;
}
$stopWhere = "WHERE " . implode(" AND ", $stopConditions);
$stopSql = "SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime FROM stop_causes $stopWhere";
$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
$stopRow = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);
$downtime = (int) ($stopRow['downtime'] ?? 0);
$runtime = max(0, $plannedTime - $downtime);

// --- Main Calculation Query (FIXED: Using LEFT JOIN) ---
$partConditions = ["p.log_date BETWEEN ? AND ?"];
$partParams = [$startDate, $endDate];
if (!empty($line)) {
    $partConditions[] = "LOWER(p.line) = LOWER(?)";
    $partParams[] = $line;
}
if (!empty($model)) {
    $partConditions[] = "LOWER(p.model) = LOWER(?)";
    $partParams[] = $model;
}
$partWhere = "WHERE " . implode(" AND ", $partConditions);

// **MODIFIED SQL**: Joined 'parts' and 'parameter' tables
$partSql = "
    SELECT
        p.model, p.part_no, p.line,
        SUM(CASE WHEN p.count_type = 'FG' THEN p.count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN p.count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN p.count_value ELSE 0 END) AS Defects,
        MAX(param.planned_output) AS hourly_output
    FROM parts p
    LEFT JOIN parameter param ON p.model = param.model AND p.part_no = param.part_no AND p.line = param.line
    $partWhere
    GROUP BY p.model, p.part_no, p.line
";

$partStmt = sqlsrv_query($conn, $partSql, $partParams);
if (!$partStmt) {
    echo json_encode(["success" => false, "message" => "Main query failed.", "sql_error" => sqlsrv_errors()]);
    exit;
}

$totalFG = 0;
$totalDefects = 0;
$totalActualOutput = 0;
$totalTheoreticalMinutes = 0; // **NEW**: This will be the basis for Performance

while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $fg = (int)$row['FG'];
    $defects = (int)$row['Defects'];
    $actualOutputForPart = $fg + $defects;

    $totalFG += $fg;
    $totalDefects += $defects;
    $totalActualOutput += $actualOutputForPart;
    
    // **MODIFIED LOGIC**: Calculate theoretical time based on actual output
    $hourlyOutput = (int)($row['hourly_output'] ?? 0);
    if ($hourlyOutput > 0) {
        $idealCycleTimeMinutes = 60 / $hourlyOutput; // Time in minutes to produce 1 part
        $totalTheoreticalMinutes += $actualOutputForPart * $idealCycleTimeMinutes;
    }
}

// --- OEE Calculation ---
$qualityPercent = $totalActualOutput > 0 ? ($totalFG / $totalActualOutput) * 100 : 0;
$availabilityPercent = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
// **MODIFIED PERFORMANCE CALCULATION**
$performancePercent = $runtime > 0 ? ($totalTheoreticalMinutes / $runtime) * 100 : 0;

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
    "actual_output" => $totalActualOutput,
    "debug_info" => [
        "total_theoretical_minutes" => round($totalTheoreticalMinutes, 2)
    ]
]);