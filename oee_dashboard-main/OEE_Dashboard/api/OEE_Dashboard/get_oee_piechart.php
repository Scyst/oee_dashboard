<?php
require_once __DIR__ . '/../db.php';

function calculatePlannedTimeRange($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $end->modify('+1 day');
    $plannedMinutes = 0;
    $breaks = [['11:30', '12:30'], ['17:00', '17:30'], ['23:30', '00:30'], ['05:00', '05:30']];
    for ($date = clone $start; $date < $end; $date->modify('+1 day')) {
        $workStart = new DateTime($date->format('Y-m-d') . ' 08:00:00');
        $workEnd = new DateTime($date->format('Y-m-d') . ' 17:00:00');
        $minutes = ($workEnd->getTimestamp() - $workStart->getTimestamp()) / 60;
        foreach ($breaks as [$bStart, $bEnd]) {
            $breakStart = new DateTime($date->format('Y-m-d') . ' ' . $bStart);
            $breakEnd = new DateTime($date->format('Y-m-d') . ' ' . $bEnd);
            if ($breakEnd < $breakStart) $breakEnd->modify('+1 day');
            $overlapStart = max($workStart, $breakStart);
            $overlapEnd = min($workEnd, $breakEnd);
            if ($overlapEnd > $overlapStart) {
                $minutes -= ($overlapEnd->getTimestamp() - $overlapStart->getTimestamp()) / 60;
            }
        }
        $plannedMinutes += max(0, $minutes);
    }
    return round($plannedMinutes);
}

try {
    $startDate = $_GET['startDate'] ?? date('Y-m-d');
    $endDate   = $_GET['endDate'] ?? date('Y-m-d');
    $line      = $_GET['line'] ?? null;
    $model     = $_GET['model'] ?? null;

    $plannedTime = calculatePlannedTimeRange($startDate, $endDate);

    $stopConditions = ["log_date BETWEEN ? AND ?"];
    $stopParams = [$startDate, $endDate];
    if (!empty($line)) {
        $stopConditions[] = "LOWER(line) = LOWER(?)";
        $stopParams[] = $line;
    }
    $stopWhere = "WHERE " . implode(" AND ", $stopConditions);
    $stopSql = "SELECT SUM(DATEDIFF(MINUTE, stop_begin, stop_end)) AS downtime FROM IOT_TOOLBOX_STOP_CAUSES $stopWhere";
    
    $stopStmt = $pdo->prepare($stopSql);
    $stopStmt->execute($stopParams);
    $stopRow = $stopStmt->fetch();
    $downtime = (int)($stopRow['downtime'] ?? 0);
    $runtime = max(0, $plannedTime - $downtime);

    $partConditions = ["p.log_date BETWEEN ? AND ?"];
    $partParams = [$startDate, $endDate];
    if (!empty($line)) {
        $partConditions[] = "LOWER(p.line) = LOWER(?)";
        $partParams[] = $line;
    }
    if (!empty($model)) {
        $partConditions[] = "LOWER(p.model) = LOWER(?)";
        $partParams[] = $model;
    }
    $partWhere = "WHERE " . implode(" AND ", $partConditions);

    $partSql = "
        SELECT
            p.model, p.part_no, p.line,
            SUM(CASE WHEN p.count_type = 'FG' THEN ISNULL(p.count_value, 0) ELSE 0 END) AS FG,
            SUM(CASE WHEN p.count_type IN ('NG', 'REWORK', 'HOLD', 'SCRAP', 'ETC.') THEN ISNULL(p.count_value, 0) ELSE 0 END) AS Defects,
            MAX(param.planned_output) AS hourly_output
        FROM IOT_TOOLBOX_PARTS p
        LEFT JOIN IOT_TOOLBOX_PARAMETER param ON p.model = param.model AND p.part_no = param.part_no AND p.line = param.line
        $partWhere
        GROUP BY p.model, p.part_no, p.line
    ";
    
    $partStmt = $pdo->prepare($partSql);
    $partStmt->execute($partParams);
    $partResults = $partStmt->fetchAll();

    $totalFG = 0;
    $totalDefects = 0;
    $totalTheoreticalMinutes = 0;

    foreach ($partResults as $row) {
        $fg = (int)$row['FG'];
        $defects = (int)$row['Defects'];
        $actualOutputForPart = $fg + $defects;
        
        $totalFG += $fg;
        $totalDefects += $defects;
        
        $hourlyOutput = (int)($row['hourly_output'] ?? 0);
        if ($hourlyOutput > 0) {
            $idealCycleTimeMinutes = 60 / $hourlyOutput;
            $totalTheoreticalMinutes += $actualOutputForPart * $idealCycleTimeMinutes;
        }
    }
    $totalActualOutput = $totalFG + $totalDefects;

    $qualityPercent = $totalActualOutput > 0 ? ($totalFG / $totalActualOutput) * 100 : 0;
    $availabilityPercent = $plannedTime > 0 ? ($runtime / $plannedTime) * 100 : 0;
    $performancePercent = $runtime > 0 ? ($totalTheoreticalMinutes / $runtime) * 100 : 0;
    $oee = ($availabilityPercent / 100) * ($performancePercent / 100) * ($qualityPercent / 100) * 100;

    echo json_encode([
        "success" => true,
        "quality" => round($qualityPercent, 1),
        "availability" => round($availabilityPercent, 1),
        "performance" => round($performancePercent, 1),
        "oee" => round($oee, 1),
        "fg" => $totalFG,
        "defects" => $totalDefects,
        "runtime" => $runtime,
        "planned_time" => $plannedTime,
        "downtime" => $downtime,
        "actual_output" => $totalActualOutput,
        "debug_info" => [
            "total_theoretical_minutes" => round($totalTheoreticalMinutes, 2)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query failed: ' . $e->getMessage()]);
    error_log("Error in get_oee_piechart.php: " . $e->getMessage());
}
?>