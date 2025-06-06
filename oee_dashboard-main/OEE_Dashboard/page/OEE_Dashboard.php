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

if ($diffDays < 15) {
    $start = (clone $end)->modify('-14 days');
}

$period = new DatePeriod(
    $start,
    new DateInterval('P1D'),
    (clone $end)->modify('+1 day')
);

$records = [];

function calculatePlannedTime($dateStr, $latestTimeStr) {
    $startWork = new DateTime("$dateStr 08:00:00");
    $latest = new DateTime("$dateStr $latestTimeStr");
    
    if ($latest <= $startWork) return 0;

    // Round up to next hour
    $latest->modify('+59 minutes');
    $latest->setTime($latest->format('H'), 0);

    $plannedMinutes = ($latest->getTimestamp() - $startWork->getTimestamp()) / 60;

    // Subtract break durations
    $breaks = [
        ['11:30', '12:30'],
        ['17:00', '17:30'],
        ['23:30', '00:30'],
        ['05:00', '05:30']
    ];

    foreach ($breaks as [$bStart, $bEnd]) {
        $breakStart = new DateTime("$dateStr $bStart");
        $breakEnd = new DateTime("$dateStr $bEnd");

        // handle overnight breaks (e.g. 23:30 - 00:30)
        if ($breakEnd < $breakStart) {
            $breakEnd->modify('+1 day');
        }

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

foreach ($period as $dateObj) {
    $dateStr = $dateObj->format('Y-m-d');
    $dateLabel = $dateObj->format('d-m-y');

    // ---------- Check latest log time ----------
    $logTimeSql = "
        SELECT MAX(latest_time) AS max_time FROM (
            SELECT MAX(log_time) AS latest_time FROM parts WHERE log_date = ?
            UNION ALL
            SELECT MAX(stop_end) FROM stop_causes WHERE log_date = ?
        ) AS all_times";

    $logStmt = sqlsrv_query($conn, $logTimeSql, [$dateStr, $dateStr]);
    $logRow = sqlsrv_fetch_array($logStmt, SQLSRV_FETCH_ASSOC);
    $latestTimeStr = $logRow['max_time'] ? $logRow['max_time']->format('H:i:s') : null;

    if (!$latestTimeStr) {
        $records[] = [
            "date" => $dateLabel,
            "availability" => 0,
            "performance" => 0,
            "quality" => 0,
            "oee" => 0
        ];
        continue;
    }

    $plannedTime = calculatePlannedTime($dateStr, $latestTimeStr);

    // ---------- Part Data ----------
    $partSql = "
        SELECT model, part_no, line,
            SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
            SUM(CASE WHEN count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN count_value ELSE 0 END) AS Defects
        FROM parts
        WHERE log_date = ?" .
        (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "") .
        (!empty($model) ? " AND LOWER(model) = LOWER(?)" : "") .
        " GROUP BY model, part_no, line";

    $params = [$dateStr];
    if (!empty($line)) $params[] = $line;
    if (!empty($model)) $params[] = $model;

    $stmt = sqlsrv_query($conn, $partSql, $params);

    $totalFG = 0;
    $totalDefects = 0;
    $totalActualOutput = 0;
    $totalPlannedOutput = 0;

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $fg = (int)$row['FG'];
        $defects = (int)$row['Defects'];
        $modelVal = $row['model'];
        $partNo = $row['part_no'];
        $lineVal = $row['line'];

        $totalFG += $fg;
        $totalDefects += $defects;
        $totalActualOutput += ($fg + $defects);

        $planStmt = sqlsrv_query(
            $conn,
            "SELECT planned_output FROM performance_parameter WHERE model = ? AND part_no = ? AND line = ?",
            [$modelVal, $partNo, $lineVal]
        );
        if ($planStmt && $planRow = sqlsrv_fetch_array($planStmt, SQLSRV_FETCH_ASSOC)) {
            $hourlyOutput = (int)$planRow['planned_output'];
            $totalPlannedOutput += $hourlyOutput * ($plannedTime / 60); // planned per active hour
        }
    }

    // ---------- Downtime ----------
    $stopSql = "SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime FROM stop_causes WHERE log_date = ?" .
        (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "");
    $stopParams = [$dateStr];
    if (!empty($line)) $stopParams[] = $line;

    $stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
    $stopData = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC);
    $downtime = (int) $stopData['downtime'];
    $runtime = max(0, $plannedTime - $downtime);

    $quality = ($totalFG + $totalDefects) > 0 ? ($totalFG / ($totalFG + $totalDefects)) * 100 : 0;
    $availability = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;

    if ($totalPlannedOutput === 0 && $plannedTime > 0) {
        $countSql = "SELECT COUNT(DISTINCT model + '|' + part_no + '|' + line) AS count FROM parts WHERE log_date = ?" .
            (!empty($line) ? " AND LOWER(line) = LOWER(?)" : "") .
            (!empty($model) ? " AND LOWER(model) = LOWER(?)" : "");
        $countParams = [$dateStr];
        if (!empty($line)) $countParams[] = $line;
        if (!empty($model)) $countParams[] = $model;

        $countStmt = sqlsrv_query($conn, $countSql, $countParams);
        $countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
        $typeCount = (int) $countRow['count'];

        $totalPlannedOutput = $typeCount * 100 * ($plannedTime / 60);
    }

    $performance = $totalPlannedOutput > 0 ? ($totalActualOutput / $totalPlannedOutput) * 100 : 0;
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
