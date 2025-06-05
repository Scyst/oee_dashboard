<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

// Get filters
$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

// -- WHERE clauses for parts and stops
$whereParts = ["log_time BETWEEN ? AND ?"];
$whereStops = ["stop_begin BETWEEN ? AND ?"];
$paramsParts = [$startDate, $endDate];
$paramsStops = [$startDate, $endDate];

if (!empty($line)) {
    $whereParts[] = "LOWER(line) = LOWER(?)";
    $paramsParts[] = $line;
    $whereStops[] = "LOWER(line) = LOWER(?)";
    $paramsStops[] = $line;
}
if (!empty($model)) {
    $whereParts[] = "LOWER(model) = LOWER(?)";
    $paramsParts[] = $model;
}

$wherePartsStr = "WHERE " . implode(" AND ", $whereParts);
$whereStopsStr = "WHERE " . implode(" AND ", $whereStops);

// --- 1. Get parts per hour ---
$partSql = "
    SELECT 
        FORMAT(log_time, 'yyyy-MM-dd HH:00') AS datetime_hour,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type IN ('NG','REWORK','HOLD','SCRAP','ETC.') THEN count_value ELSE 0 END) AS Defects
    FROM parts
    $wherePartsStr
    GROUP BY FORMAT(log_time, 'yyyy-MM-dd HH:00')
    ORDER BY datetime_hour
";
$partStmt = sqlsrv_query($conn, $partSql, $paramsParts);
$partData = [];
while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $hour = $row['datetime_hour'];
    $partData[$hour] = [
        'FG' => (int)$row['FG'],
        'Defects' => (int)$row['Defects']
    ];
}

// --- 2. Get stop time per hour ---
$stopSql = "
    SELECT 
        FORMAT(stop_begin, 'yyyy-MM-dd HH:00') AS datetime_hour,
        SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
    FROM stop_causes
    $whereStopsStr
    GROUP BY FORMAT(stop_begin, 'yyyy-MM-dd HH:00')
    ORDER BY datetime_hour
";
$stopStmt = sqlsrv_query($conn, $stopSql, $paramsStops);
$downtimeData = [];
while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
    $downtimeData[$row['datetime_hour']] = (int)$row['downtime'];
}

// --- 3. Get planned output from performance_parameter ---
$planSql = "
    SELECT model, part_no, planned_output
    FROM performance_parameter
    " . (!empty($line) ? "WHERE LOWER(line) = LOWER(?)" : "");
$planParams = !empty($line) ? [$line] : [];
$planStmt = sqlsrv_query($conn, $planSql, $planParams);
$planMap = [];
while ($row = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
    $key = strtolower(trim($row['model'])) . "|" . strtolower(trim($row['part_no']));
    $planMap[$key] = (int)$row['planned_output'];
}

// --- 4. Build timeline (merge all timestamps) ---
$allTimestamps = array_unique(array_merge(array_keys($partData), array_keys($downtimeData)));
sort($allTimestamps);

// --- 5. Build response ---
$records = [];
foreach ($allTimestamps as $hourKey) {
    $FG = $partData[$hourKey]['FG'] ?? 0;
    $Defects = $partData[$hourKey]['Defects'] ?? 0;
    $produced = $FG + $Defects;

    // Try to match specific planned output
    $plannedOutput = 0;
    if (!empty($model)) {
        foreach ($planMap as $key => $value) {
            if (str_starts_with($key, strtolower($model) . "|")) {
                $plannedOutput = $value;
                break;
            }
        }
    }

    if ($plannedOutput === 0 && count($planMap) > 0) {
        $plannedOutput = round(array_sum($planMap) / count($planMap));
    }

    $downtime = $downtimeData[$hourKey] ?? 0;
    $plannedTime = 60;
    $runtime = max(0, $plannedTime - $downtime);

    $availability = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
    $performance = $plannedOutput > 0 ? ($produced / $plannedOutput) * 100 : 0;
    $quality = $produced > 0 ? ($FG / $produced) * 100 : 0;
    $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

    $records[] = [
        "date" => $hourKey,
        "availability" => round($availability, 1),
        "performance"  => round($performance, 1),
        "quality"      => round($quality, 1),
        "oee"          => round($oee, 1)
    ];
}

echo json_encode(["success" => true, "records" => $records]);
?>