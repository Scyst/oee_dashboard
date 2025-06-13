<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

// Shared date filters
$startDate = $_GET['startDate'] ?? date('Y-m-d');
$endDate   = $_GET['endDate'] ?? date('Y-m-d');
$line      = $_GET['line'] ?? null;
$model     = $_GET['model'] ?? null;

$stopConditions = ["log_date BETWEEN ? AND ?"];
$stopParams = [$startDate, $endDate];
$partConditions = ["log_date BETWEEN ? AND ?"];
$partParams = [$startDate, $endDate];

if (!empty($line)) {
    $stopConditions[] = "LOWER(line) = LOWER(?)";
    $stopParams[] = $line;
    $partConditions[] = "LOWER(line) = LOWER(?)";
    $partParams[] = $line;
}
if (!empty($model)) {
    $partConditions[] = "LOWER(model) = LOWER(?)";
    $partParams[] = $model;
}

$stopWhere = "WHERE " . implode(" AND ", $stopConditions);
$partWhere = "WHERE " . implode(" AND ", $partConditions);

// 🔄 STOP CAUSES CHART (X = line, bars = cause stacks)
$stopSql = "
    SELECT cause, line, SUM(DATEDIFF(SECOND, stop_begin, stop_end)) AS total_seconds
    FROM stop_causes
    $stopWhere
    GROUP BY cause, line
";

$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
$causeMap = [];   // cause => [line => minutes]
$lineTotals = []; // line => total_minutes
$lineSet = [];

if ($stopStmt) {
    while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
        $cause = $row['cause'];
        $line = $row['line'];
        $minutes = round(($row['total_seconds'] ?? 0) / 60, 1);

        $causeMap[$cause][$line] = $minutes;
        $lineTotals[$line] = ($lineTotals[$line] ?? 0) + $minutes;
        $lineSet[$line] = true;
    }
} else {
    echo json_encode(["error" => "Stop cause query failed.", "sql_error" => print_r(sqlsrv_errors(), true)]);
    exit;
}

$lineList = array_keys($lineSet);
usort($lineList, fn($a, $b) => ($lineTotals[$b] ?? 0) <=> ($lineTotals[$a] ?? 0)); // Sort by total time

$colorPalette = ["#42a5f5", "#66bb6a", "#ff7043", "#ab47bc", "#ffa726", "#26c6da", "#d4e157", "#8d6e63", "#78909c", "#ec407a"];
$stopDatasets = [];
$colorIndex = 0;

foreach ($causeMap as $causeName => $lineData) {
    $dataset = [
        "label" => $causeName, // ✅ Each stack = cause
        "data" => [],
        "backgroundColor" => $colorPalette[$colorIndex++ % count($colorPalette)],
        "borderRadius" => 4
    ];

    foreach ($lineList as $lineName) {
        $dataset["data"][] = $lineData[$lineName] ?? 0;
    }

    $stopDatasets[] = $dataset;
}

// 🔧 Tooltip text helper (for optional UI display)
$lineTooltipInfo = [];
foreach ($lineList as $line) {
    $lineTooltipInfo[$line] = formatMinutes($lineTotals[$line] ?? 0);
}

function formatMinutes($minutes) {
    $h = floor($minutes / 60);
    $m = round($minutes % 60);
    return sprintf("%dh %02dm", $h, $m);
}

// PARTS QUERY (unchanged)
$partSql = "
    SELECT TOP 50 part_no,
        SUM(CASE WHEN count_type = 'FG' THEN count_value ELSE 0 END) AS FG,
        SUM(CASE WHEN count_type = 'NG' THEN count_value ELSE 0 END) AS NG,
        SUM(CASE WHEN count_type = 'HOLD' THEN count_value ELSE 0 END) AS HOLD,
        SUM(CASE WHEN count_type = 'REWORK' THEN count_value ELSE 0 END) AS REWORK,
        SUM(CASE WHEN count_type = 'SCRAP' THEN count_value ELSE 0 END) AS SCRAP,
        SUM(CASE WHEN count_type = 'ETC.' THEN count_value ELSE 0 END) AS ETC
    FROM parts
    $partWhere
    GROUP BY part_no
    ORDER BY SUM(count_value) DESC
";

$partStmt = sqlsrv_query($conn, $partSql, $partParams);
$partLabels = [];
$FG = [];
$NG = [];
$HOLD = [];
$REWORK = [];
$SCRAP = [];
$ETC = [];

if ($partStmt) {
    while ($row = sqlsrv_fetch_array($partStmt, SQLSRV_FETCH_ASSOC)) {
        $partLabels[] = $row['part_no'];
        $FG[] = (int) $row['FG'];
        $NG[] = (int) $row['NG'];
        $HOLD[] = (int) $row['HOLD'];
        $REWORK[] = (int) $row['REWORK'];
        $SCRAP[] = (int) $row['SCRAP'];
        $ETC[] = (int) $row['ETC'];
    }
} else {
    echo json_encode(["error" => "Parts query failed.", "sql_error" => print_r(sqlsrv_errors(), true)]);
    exit;
}

// FINAL OUTPUT
echo json_encode([
    "stopCause" => [
        "labels" => $lineList,
        "datasets" => $stopDatasets,
        "tooltipInfo" => $lineTooltipInfo
    ],
    "parts" => [
        "labels"  => $partLabels,
        "FG"      => $FG,
        "NG"      => $NG,
        "HOLD"    => $HOLD,
        "REWORK"  => $REWORK,
        "SCRAP"   => $SCRAP,
        "ETC"     => $ETC
    ]
]);
?>
