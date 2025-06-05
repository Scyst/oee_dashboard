<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

// Get filters
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

// ---------- Step 1: Output (FG + NG + etc.) ----------
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

while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $fg = (int)$row['FG'];
    $defects = (int)$row['Defects'];
    $modelVal = $row['model'];
    $partNo = $row['part_no'];
    $lineVal = $row['line'];

    $totalFG += $fg;
    $totalDefects += $defects;
    $totalActualOutput += ($fg + $defects);

    // Get planned output
    $planSql = "SELECT planned_output FROM performance_parameter WHERE model = ? AND part_no = ? AND line = ?";
    $planStmt = sqlsrv_query($conn, $planSql, [$modelVal, $partNo, $lineVal]);
    if ($planStmt && $planRow = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
        $totalPlannedOutput += (int)$planRow['planned_output'];
    }
}

// ---------- Step 2: Downtime ----------
$stopSql = "
    SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
    FROM stop_causes
    $stopWhere
";

$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
$stopData = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);
$downtime = (int) $stopData['downtime'];
$days = (new DateTime($startDate))->diff(new DateTime($endDate))->days + 1;
$plannedTime = 480 * $days; // 8 hours/day
$runtime = max(0, $plannedTime - $downtime);

// ---------- Step 3: Metrics ----------
$qualityPercent = ($totalFG + $totalDefects) > 0 ? ($totalFG / ($totalFG + $totalDefects)) * 100 : 0;

$availabilityPercent = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;

if ($totalPlannedOutput === 0) {
    // Count distinct part types (model + part + line)
    $distinctPartSql = "
        SELECT COUNT(DISTINCT model + '|' + part_no + '|' + line) AS type_count
        FROM parts
        WHERE log_date BETWEEN ? AND ?
        " . (!empty($line) ? " AND line = ?" : "") . (!empty($model) ? " AND model = ?" : "");

    $distinctParams = [$startDate, $endDate];
    if (!empty($line))  $distinctParams[] = $line;
    if (!empty($model)) $distinctParams[] = $model;

    $distinctStmt = sqlsrv_query($conn, $distinctPartSql, $distinctParams);
    $distinctRow = sqlsrv_fetch_array($distinctStmt, SQLSRV_FETCH_ASSOC);
    $typeCount = (int) $distinctRow['type_count'];

    $totalPlannedOutput = $typeCount * 100; // fallback planned output
}

$performancePercent = $totalPlannedOutput > 0 ? ($totalActualOutput / $totalPlannedOutput) * 100 : 0;

$oee = ($availabilityPercent / 100) * ($performancePercent / 100) * ($qualityPercent / 100) * 100;

// ---------- Final Output ----------
echo json_encode([
    "success" => true,
    "quality" => round($qualityPercent, 1),
    "availability" => round($availabilityPercent, 1),
    "performance" => round($performancePercent, 1),
    "oee" => round($oee, 1),
    "fg" => $totalFG,
    "defects" => $totalDefects,
    "downtime" => $downtime,
    "runtime" => $runtime,
    "planned_output" => $totalPlannedOutput,
    "actual_output" => $totalActualOutput
]);
?>
