<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

$log_date = $_GET['log_date'] ?? date('Y-m-d');
$line = $_GET['line'] ?? null;
$model = $_GET['model'] ?? null;

// Build filters
$whereParts = ["log_date = ?"];
$whereStops = ["log_date = ?"];
$paramsParts = [$log_date];
$paramsStops = [$log_date];

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

// 1. Get hourly aggregated part counts
$partSql = "
    SELECT 
        DATEPART(HOUR, log_time) AS hour,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type IN ('NG','REWORK','HOLD','SCRAP','ETC.') THEN count_value ELSE 0 END) AS Defects
    FROM parts
    $wherePartsStr
    GROUP BY DATEPART(HOUR, log_time)
    ORDER BY hour
";
$partStmt = sqlsrv_query($conn, $partSql, $paramsParts);
$partData = [];
while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
    $partData[(int)$row['hour']] = [
        'FG' => (int)$row['FG'],
        'Defects' => (int)$row['Defects']
    ];
}

// 2. Get hourly downtime
$stopSql = "
    SELECT 
        DATEPART(HOUR, stop_begin) AS hour,
        SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
    FROM stop_causes
    $whereStopsStr
    GROUP BY DATEPART(HOUR, stop_begin)
";
$stopStmt = sqlsrv_query($conn, $stopSql, $paramsStops);
$downtimeData = [];
while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
    $downtimeData[(int)$row['hour']] = (int)$row['downtime'];
}

// 3. Get planned output from performance_parameter
$planSql = "
    SELECT model, part_no, planned_output
    FROM performance_parameter
    " . (!empty($line) ? "WHERE LOWER(line) = LOWER(?)" : "");
$planParams = !empty($line) ? [$line] : [];
$planStmt = sqlsrv_query($conn, $planSql, $planParams);
$planMap = [];
while ($row = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
    $key = strtolower(trim($row['model'])) . '|' . strtolower(trim($row['part_no']));
    $planMap[$key] = (int)$row['planned_output'];
}

// 4. Build hourly OEE breakdown
$hourly = [];
for ($hour = 0; $hour < 24; $hour++) {
    $FG = $partData[$hour]['FG'] ?? 0;
    $Defects = $partData[$hour]['Defects'] ?? 0;
    $totalProduced = $FG + $Defects;

    // Assume actual model/part_no combination is not unique per hour,
    // so we use average planned_output as fallback
    $plannedOutput = 0;
    if ($totalProduced > 0 && !empty($model) && isset($planMap[strtolower($model) . '|'])) {
        $plannedOutput = $planMap[strtolower($model) . '|'];
    } elseif (!empty($planMap)) {
        $plannedOutput = round(array_sum($planMap) / count($planMap)); // fallback average
    }

    $downtime = $downtimeData[$hour] ?? 0;
    $plannedTime = 60; // 1 hour = 60 min
    $runtime = max($plannedTime - $downtime, 0);

    $availability = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
    $performance = $plannedOutput > 0 ? ($totalProduced / $plannedOutput) * 100 : 0;
    $quality = ($totalProduced > 0) ? ($FG / $totalProduced) * 100 : 0;
    $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

    $hourly[] = [
        "hour" => $hour,
        "availability" => round($availability, 1),
        "performance" => round($performance, 1),
        "quality" => round($quality, 1),
        "oee" => round($oee, 1)
    ];
}

echo json_encode(["success" => true, "data" => $hourly]);
