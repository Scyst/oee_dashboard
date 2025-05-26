<?php
require_once("../db.php");

$log_date = $_GET['log_date'];
$shift = $_GET['shift'];

// QUALITY = FG / (FG + NG + Rework + Hold)
$qualitySql = "SELECT 
    SUM(fg_count) AS FG,
    SUM(ng_count) + SUM(rework_count) + SUM(hold_count) AS Defects
 FROM part
 WHERE log_date = ? AND shift = ?";
$qualityStmt = sqlsrv_query($conn, $qualitySql, [$log_date, $shift]);
$quality = sqlsrv_fetch_array($qualityStmt, SQLSRV_FETCH_ASSOC);

$totalParts = $quality['FG'] + $quality['Defects'];
$qualityPercent = ($totalParts > 0) ? ($quality['FG'] / $totalParts) * 100 : 0;

// AVAILABILITY = OT / (OT + DT) * 100
$availabilitySql = "SELECT 
    SUM(stop_time) AS DT
 FROM stop_logs
 WHERE log_date = ? AND shift = ?";
$availabilityStmt = sqlsrv_query($conn, $availabilitySql, [$log_date, $shift]);
$availability = sqlsrv_fetch_array($availabilityStmt, SQLSRV_FETCH_ASSOC);

$plannedTime = 480; // 8 hours = 480 minutes (adjust for your shift)
$runTime = $plannedTime - (int)$availability['DT'];
$availabilityPercent = ($plannedTime > 0) ? ($runTime / $plannedTime) * 100 : 0;

// PERFORMANCE = (FG + NG) / Ideal Cycle Time * Run Time
// Simple Version: Assume expected part rate is known (or fixed per shift)
$totalPartsProduced = $quality['FG'] + $quality['Defects'];
$idealParts = $runTime * 1; // If ideal is 1 part/min
$performancePercent = ($idealParts > 0) ? ($totalPartsProduced / $idealParts) * 100 : 0;

// OEE = A * P * Q
$oee = ($availabilityPercent / 100) * ($performancePercent / 100) * ($qualityPercent / 100) * 100;

echo json_encode([
    "quality" => round($qualityPercent, 1),
    "availability" => round($availabilityPercent, 1),
    "performance" => round($performancePercent, 1),
    "oee" => round($oee, 1),
    "fg" => $quality['FG'],
    "defects" => $quality['Defects'],
    "downtime" => $availability['DT'],
    "runtime" => $runTime
]);
?>
