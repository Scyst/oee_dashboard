<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

$start = new DateTime($startDate);
$end = new DateTime($endDate);
$diffDays = (int)$start->diff($end)->format('%a') + 1;

// Ensure at least 15 days of data
if ($diffDays < 15) {
    $start = (clone $end)->modify('-14 days');
}

$period = new DatePeriod(
    $start,
    new DateInterval('P1D'),
    (clone $end)->modify('+1 day')
);

$records = [];

foreach ($period as $dateObj) {
    $dateStr = $dateObj->format('Y-m-d');
    $dateLabel = $dateObj->format('d-m-y');

    // ---------- Part Data ----------
    $partSql = "
        SELECT model, part_no, line,
            SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
            SUM(CASE WHEN count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN count_value ELSE 0 END) AS Defects,
            DATEPART(HOUR, log_time) AS hour
        FROM parts
        WHERE log_date = ?" .
        (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "") .
        (!empty($model) ? " AND LOWER(model) = LOWER(?)" : "") .
        " GROUP BY model, part_no, line, DATEPART(HOUR, log_time)";

    $params = [$dateStr];
    if (!empty($line)) $params[] = $line;
    if (!empty($model)) $params[] = $model;

    $stmt = sqlsrv_query($conn, $partSql, $params);

    $totalFG = 0;
    $totalDefects = 0;
    $totalActualOutput = 0;
    $totalPlannedOutput = 0;
    $activeHours = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $fg = (int)$row['FG'];
        $defects = (int)$row['Defects'];
        $modelVal = $row['model'];
        $partNo = $row['part_no'];
        $lineVal = $row['line'];
        $hour = (int)$row['hour'];

        $totalFG += $fg;
        $totalDefects += $defects;
        $totalActualOutput += ($fg + $defects);
        $activeHours[$hour] = true;

        $planStmt = sqlsrv_query(
            $conn,
            "SELECT planned_output FROM performance_parameter WHERE model = ? AND part_no = ? AND line = ?",
            [$modelVal, $partNo, $lineVal]
        );
        if ($planStmt && $planRow = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
            $totalPlannedOutput += ((int)$planRow['planned_output']); // already hourly
        }
    }

    // ---------- Stop Data ----------
    $stopSql = "SELECT DATEPART(HOUR, stop_begin) AS hour,
                    SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime
                FROM stop_causes
                WHERE log_date = ?" .
        (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "") .
        " GROUP BY DATEPART(HOUR, stop_begin)";

    $stopParams = [$dateStr];
    if (!empty($line)) $stopParams[] = $line;

    $stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
    $downtime = 0;
    while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
        $downtime += (int)$row['downtime'];
        $activeHours[(int)$row['hour']] = true;
    }

    $plannedTime = count($activeHours) * 60; // 60 minutes per active hour
    $runtime = max(0, $plannedTime - $downtime);

    $quality = ($totalFG + $totalDefects) > 0 ? ($totalFG / ($totalFG + $totalDefects)) * 100 : 0;
    $availability = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;

    // ---------- Fallback for Planned Output ----------
    if ($totalPlannedOutput === 0) {
        $countSql = "SELECT COUNT(DISTINCT model + '|' + part_no + '|' + line) AS count FROM parts WHERE log_date = ?" .
            (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "") .
            (!empty($model) ? " AND LOWER(model) = LOWER(?)" : "");
        $countParams = [$dateStr];
        if (!empty($line)) $countParams[] = $line;
        if (!empty($model)) $countParams[] = $model;

        $countStmt = sqlsrv_query($conn, $countSql, $countParams);
        $countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $typeCount = (int) $countRow['count'];

        $totalPlannedOutput = $typeCount * 100; // fallback: 100/hr
    }

    $expectedOutput = $totalPlannedOutput * count($activeHours); // convert hourly to daily
    $performance = $expectedOutput > 0 ? ($totalActualOutput / $expectedOutput) * 100 : 0;
    $oee = ($availability / 100) * ($performance / 100) * ($quality / 100) * 100;

    $records[] = [
        "date" => $dateLabel,
        "availability" => round($availability, 1),
        "performance" => round($performance, 1),
        "quality" => round($quality, 1),
        "oee" => round($oee, 1)
    ];
}

echo json_encode(["success" => true, "records" => $records]);
