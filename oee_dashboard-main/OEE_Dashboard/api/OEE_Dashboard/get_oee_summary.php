<?php
require_once("../api/db.php");
header('Content-Type: application/json');

$log_date = $_GET['log_date'] ?? date('Y-m-d');
$shift    = $_GET['shift'] ?? 'A';

// Quality = FG / (FG + NG + REWORK + HOLD + SCRAP + ETC)
$qualitySql = "
    SELECT
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN count_value ELSE 0 END) AS Defects
    FROM parts
    WHERE log_date = ? AND shift = ?
";

$qualityStmt = sqlsrv_query($conn, $qualitySql, [$log_date, $shift]);
$qualityData = sqlsrv_fetch_array($qualityStmt, SQLSRV_FETCH_ASSOC);

$FG      = (int) $qualityData['FG'];
$defects = (int) $qualityData['Defects'];
$totalParts = $FG + $defects;
$qualityPercent = $totalParts > 0 ? ($FG / $totalParts) * 100 : 0;

// Availability = Run Time / Planned Time × 100
$stopSql = "
    SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
    FROM stop_causes
    WHERE log_date = ? AND shift = ?
";
$stopStmt = sqlsrv_query($conn, $stopSql, [$log_date, $shift]);
$stopData = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);

$downtime = (int) $stopData['downtime'];
$plannedTime = 480; // 8 hours
$runtime = $plannedTime - $downtime;
$availabilityPercent = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;

// Performance = Actual Output / Planned Output × 100
$planSql = "
    SELECT SUM(planned_output) AS planned_output
    FROM production_plan
    WHERE log_date = ? AND shift = ?
";
$planStmt = sqlsrv_query($conn, $planSql, [$log_date, $shift]);
$planData = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC);

$plannedOutput = (int) $planData['planned_output'];
$actualOutput = $totalParts;
$performancePercent = $plannedOutput > 0 ? ($actualOutput / $plannedOutput) * 100 : 0;

// OEE = A × P × Q
$oee = ($availabilityPercent / 100) * ($performancePercent / 100) * ($qualityPercent / 100) * 100;

// Output
echo json_encode([
    "success" => true,
    "quality" => round($qualityPercent, 1),
    "availability" => round($availabilityPercent, 1),
    "performance" => round($performancePercent, 1),
    "oee" => round($oee, 1),
    "fg" => $FG,
    "defects" => $defects,
    "downtime" => $downtime,
    "runtime" => $runtime,
    "planned_output" => $plannedOutput,
    "actual_output" => $actualOutput
]);

?>
