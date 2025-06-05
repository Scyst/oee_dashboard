<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

// ---------- GET FILTERS ----------
$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

// ---------- BUILD WHERE CLAUSE ----------
$conditions = ["log_date BETWEEN ? AND ?"];
$params = [$startDate, $endDate];

if (!empty($line)) {
    $conditions[] = "line = ?";
    $params[] = $line;
}
if (!empty($model)) {
    $conditions[] = "model = ?";
    $params[] = $model;
}

$whereClause = "WHERE " . implode(" AND ", $conditions);

// ---------- STEP 1: GATHER OUTPUT DATA ----------
$qualitySql = "
    SELECT model, part_no, line,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN count_value ELSE 0 END) AS Defects
    FROM parts
    $whereClause
    GROUP BY model, part_no, line
";
$qualityStmt = sqlsrv_query($conn, $qualitySql, $params);

$totalFG = 0;
$totalDefects = 0;
$totalActualOutput = 0;
$totalPlannedOutput = 0;

while ($row = sqlsrv_fetch_array($qualityStmt, SQLSRV_FETCH_ASSOC)) {
    $fg      = (int)$row['FG'];
    $defects = (int)$row['Defects'];
    $modelVal = $row['model'];
    $partNo   = $row['part_no'];
    $lineVal  = $row['line'];

    $totalFG += $fg;
    $totalDefects += $defects;
    $totalActualOutput += $fg + $defects;

    // Fetch planned output
    $planSql = "SELECT planned_output FROM performance_parameter WHERE model = ? AND part_no = ? AND line = ?";
    $planStmt = sqlsrv_query($conn, $planSql, [$modelVal, $partNo, $lineVal]);

    if ($planStmt && $planRow = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
        $totalPlannedOutput += (int)$planRow['planned_output'];
    }
}

// ---------- STEP 2: DOWNTIME ----------
$stopSql = "
    SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
    FROM stop_causes
    $whereClause
";
$stopStmt = sqlsrv_query($conn, $stopSql, $params);
$stopData = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);

$downtime = (int) $stopData['downtime'];
$plannedTime = 480; // 8 hours default
$runtime = max($plannedTime - $downtime, 0);

// ---------- STEP 3: CALCULATE OEE ----------
$qualityPercent = ($totalFG + $totalDefects) > 0 ? ($totalFG / ($totalFG + $totalDefects)) * 100 : 0;
$availabilityPercent = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
$performancePercent = $totalPlannedOutput > 0 ? ($totalActualOutput / $totalPlannedOutput) * 100 : 0;

$oee = ($availabilityPercent / 100) * ($performancePercent / 100) * ($qualityPercent / 100) * 100;

// ---------- STEP 4: INSERT TO oee_history (OPTIONAL) ----------
$checkSql = "
    SELECT 1 FROM oee_history
    WHERE log_date = ? AND (line = ? OR ? IS NULL) AND (model = ? OR ? IS NULL)
";
$checkStmt = sqlsrv_query($conn, $checkSql, [$startDate, $line, $line, $model, $model]);
$exists = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);

if (!$exists) {
    $insertSql = "
        INSERT INTO oee_history (log_date, line, model, oee_percent, quality_percent, availability_percent, performance_percent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE())
    ";
    $insertParams = [
        $startDate,
        $line,
        $model,
        round($oee, 1),
        round($qualityPercent, 1),
        round($availabilityPercent, 1),
        round($performancePercent, 1)
    ];
    sqlsrv_query($conn, $insertSql, $insertParams);
}

// ---------- FINAL OUTPUT ----------
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
