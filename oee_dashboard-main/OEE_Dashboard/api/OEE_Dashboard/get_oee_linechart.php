<?php
require_once __DIR__ . '/../db.php';

function calculatePlannedTime($dateStr, $latestTimeStr) {
    if (!$latestTimeStr) return 0;
    
    $startWork = new DateTime("$dateStr 08:00:00");
    $latest = new DateTime("$dateStr $latestTimeStr");
    
    if ($latest <= $startWork) return 0;

    $latest->modify('+59 minutes');
    $latest->setTime($latest->format('H'), 0);

    $plannedMinutes = ($latest->getTimestamp() - $startWork->getTimestamp()) / 60;

    $breaks = [['11:30', '12:30'], ['17:00', '17:30'], ['23:30', '00:30'], ['05:00', '05:30']];

    foreach ($breaks as [$bStart, $bEnd]) {
        $breakStart = new DateTime("$dateStr $bStart");
        $breakEnd = new DateTime("$dateStr $bEnd");

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

try {
    $startDate = $_GET['startDate'] ?? date('Y-m-d');
    $endDate = $_GET['endDate'] ?? date('Y-m-d');
    $line = $_GET['line'] ?? null;
    $model = $_GET['model'] ?? null;

    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    if (($start->diff($end)->days + 1) < 15) {
        $start = (clone $end)->modify('-14 days');
    }

    $period = new DatePeriod($start, new DateInterval('P1D'), (clone $end)->modify('+1 day'));
    $records = [];

    $paramSql = "SELECT line, model, part_no, planned_output FROM IOT_TOOLBOX_PARAMETER";
    $paramStmt = $pdo->query($paramSql);
    $allPlannedOutputs = [];
    foreach ($paramStmt->fetchAll() as $row) {
        $allPlannedOutputs[$row['line']][$row['model']][$row['part_no']] = (int)$row['planned_output'];
    }

    foreach ($period as $dateObj) {
        $dateStr = $dateObj->format('Y-m-d');
        $dateLabel = $dateObj->format('d-m-y');

        $logTimeSql = "
            SELECT MAX(latest_time) AS max_time FROM (
                SELECT MAX(log_time) AS latest_time FROM IOT_TOOLBOX_PARTS WHERE log_date = ?
                UNION ALL
                SELECT MAX(stop_end) FROM IOT_TOOLBOX_STOP_CAUSES WHERE log_date = ?
            ) AS all_times";
        $logStmt = $pdo->prepare($logTimeSql);
        $logStmt->execute([$dateStr, $dateStr]);
        $logRow = $logStmt->fetch();
        $rawLatestTime = $logRow['max_time'] ?? null;

        // ตรวจสอบและดึงเฉพาะส่วนเวลาออกมา
        if ($rawLatestTime) {
            // สร้าง DateTime object จากค่าที่ได้มา
            $dateTimeObj = new DateTime($rawLatestTime);
            // จัดรูปแบบให้เหลือเฉพาะส่วนเวลา (H:i:s)
            $latestTimeStr = $dateTimeObj->format('H:i:s');
        } else {
            $latestTimeStr = null;
        }
        
        $plannedTime = calculatePlannedTime($dateStr, $latestTimeStr);

        if ($plannedTime <= 0) {
            $records[] = ["date" => $dateLabel, "availability" => 0, "performance" => 0, "quality" => 0, "oee" => 0];
            continue;
        }

        $partConditions = "WHERE log_date = ?";
        $partParams = [$dateStr];
        if (!empty($line)) { $partConditions .= " AND LOWER(line) = LOWER(?)"; $partParams[] = $line; }
        if (!empty($model)) { $partConditions .= " AND LOWER(model) = LOWER(?)"; $partParams[] = $model; }

        $partSql = "
            SELECT model, part_no, line,
                SUM(CASE WHEN count_type = 'FG' THEN ISNULL(count_value, 0) ELSE 0 END) AS FG,
                SUM(CASE WHEN count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN ISNULL(count_value, 0) ELSE 0 END) AS Defects
            FROM IOT_TOOLBOX_PARTS
            $partConditions
            GROUP BY model, part_no, line";
        $partStmt = $pdo->prepare($partSql);
        $partStmt->execute($partParams);
        $partResults = $partStmt->fetchAll();

        $totalFG = 0;
        $totalDefects = 0;
        $totalPlannedOutput = 0;
        
        foreach ($partResults as $row) {
            $fg = (int)$row['FG'];
            $defects = (int)$row['Defects'];
            $totalFG += $fg;
            $totalDefects += $defects;

            $lineVal = $row['line'];
            $modelVal = $row['model'];
            $partNo = $row['part_no'];
            
            if (isset($allPlannedOutputs[$lineVal][$modelVal][$partNo])) {
                $hourlyOutput = $allPlannedOutputs[$lineVal][$modelVal][$partNo];
                $totalPlannedOutput += $hourlyOutput * ($plannedTime / 60);
            }
        }
        $totalActualOutput = $totalFG + $totalDefects;

        $stopConditions = "WHERE log_date = ?";
        $stopParams = [$dateStr];
        if (!empty($line)) { $stopConditions .= " AND LOWER(line) = LOWER(?)"; $stopParams[] = $line; }

        $stopSql = "SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime FROM IOT_TOOLBOX_STOP_CAUSES $stopConditions";
        $stopStmt = $pdo->prepare($stopSql);
        $stopStmt->execute($stopParams);
        $downtime = (int)($stopStmt->fetch()['downtime'] ?? 0);
        
        $runtime = max(0, $plannedTime - $downtime);
        $quality = $totalActualOutput > 0 ? ($totalFG / $totalActualOutput) * 100 : 0;
        $availability = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
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

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $e->getMessage()]);
    error_log("Error in get_oee_linechart.php: " . $e->getMessage());
}
?>