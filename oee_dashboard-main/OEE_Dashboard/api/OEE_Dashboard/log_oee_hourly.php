<?php
require_once("../api/db.php");
date_default_timezone_set("Asia/Bangkok"); // Adjust your timezone

$log_date = date('Y-m-d');
$log_time = date('H:00:00'); // current hour, e.g., 14:00:00
$line     = $_GET['line'] ?? null;
$model    = $_GET['model'] ?? null;

// --- Build WHERE for parts and stop_causes ---
$conditions = ["log_date = ?"];
$params = [$log_date];

if (!empty($line)) {
    $conditions[] = "line = ?";
    $params[] = $line;
}
if (!empty($model)) {
    $conditions[] = "model = ?";
    $params[] = $model;
}
$whereClause = "WHERE " . implode(" AND ", $conditions);

// --- Query total FG, Defects, and group info ---
$qualitySql = "
    SELECT model, part_no, line,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type IN ('NG','REWORK','HOLD','SCRAP','ETC.') THEN count_value ELSE 0 END) AS Defects
    FROM parts
    $whereClause
    GROUP BY model, part_no, line
";
$qualityStmt = sqlsrv_query($conn, $qualitySql, $params);

$totalFG = $totalDefects = $actualOutput = $plannedOutput = 0;

while ($row = sqlsrv_fetch_array($qualityStmt, SQLSRV_FETCH_ASSOC)) {
    $fg      = (int) $row['FG'];
    $defects = (int) $row['Defects'];
    $modelVal = $row['model'];
    $partNo   = $row['part_no'];
    $lineVal  = $row['line'];

    $totalFG += $fg;
    $totalDefects += $defects;
    $actualOutput += $fg + $defects;

    $planSql = "SELECT planned_output FROM performance_parameter WHERE model = ? AND part_no = ? AND line = ?";
    $planStmt = sqlsrv_query($conn, $planSql, [$modelVal, $partNo, $lineVal]);
    if ($planStmt && $planRow = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
        $plannedOutput += (int)$planRow['planned_output'];
    }
}

// --- Downtime ---
$stopSql = "SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime FROM stop_causes $whereClause";
$stopStmt = sqlsrv_query($conn, $stopSql, $params);
$stopRow = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);
$downtime = (int) $stopRow['downtime'];
$plannedTime = 480;
$runtime = $plannedTime - $downtime;

// --- Calculate ---
$quality = ($totalFG + $totalDefects) > 0 ? $totalFG / ($totalFG + $totalDefects) * 100 : 0;
$availability = $plannedTime > 0 ? $runtime / $plannedTime * 100 : 0;
$performance = $plannedOutput > 0 ? $actualOutput / $plannedOutput * 100 : 0;
$oee = ($quality / 100) * ($availability / 100) * ($performance / 100) * 100;

// --- INSERT to oee_history ---
$insertSql = "
    INSERT INTO oee_history (log_date, log_time, oee_percent, quality_percent, performance_percent, availability_percent)
    VALUES (?, ?, ?, ?, ?, ?)
";

$insertStmt = sqlsrv_query($conn, $insertSql, [
    $log_date,
    $log_time,
    round($oee, 2),
    round($quality, 2),
    round($performance, 2),
    round($availability, 2)
]);

if ($insertStmt) {
    echo json_encode(["success" => true, "message" => "OEE logged successfully."]);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Failed to insert into oee_history.",
        "sql_error" => print_r(sqlsrv_errors(), true)
    ]);
}
?>
