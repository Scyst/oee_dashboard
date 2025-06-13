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

// -----------------------------
// STOP CAUSES CHART (X = line, bars = causes)
// -----------------------------
$stopSql = "
    SELECT line, cause, SUM(DATEDIFF(SECOND, stop_begin, stop_end)) AS total_seconds
    FROM stop_causes
    $stopWhere
    GROUP BY line, cause
";

$stopStmt = sqlsrv_query($conn, $stopSql, $stopParams);
$lineMap = [];        // line => [cause => minutes]
$lineDurations = [];  // line => total minutes
$allCauses = [];

if ($stopStmt) {
    while ($row = sqlsrv_fetch_array($stopStmt, SQLSRV_FETCH_ASSOC)) {
        $line = $row['line'];
        $cause = $row['cause'];
        $minutes = round(($row['total_seconds'] ?? 0) / 60, 1);

        $allCauses[$cause] = true;
        $lineMap[$line][$cause] = $minutes;
        $lineDurations[$line] = ($lineDurations[$line] ?? 0) + $minutes;
    }
} else {
    echo json_encode(["error" => "Stop cause query failed.", "sql_error" => print_r(sqlsrv_errors(), true)]);
    exit;
}

$allCausesList = array_keys($allCauses);
sort($allCausesList);

// 🔽 Sort lines by total stop duration (descending)
uksort($lineMap, function ($a, $b) use ($lineDurations) {
    return ($lineDurations[$b] ?? 0) <=> ($lineDurations[$a] ?? 0);
});
$lineList = array_keys($lineMap); // ✅ Define lineList for use in final output


$colorPalette = ["#42a5f5", "#66bb6a", "#ff7043", "#ab47bc", "#ffa726", "#26c6da", "#d4e157", "#8d6e63", "#78909c", "#ec407a"];
$stopDatasets = [];
$colorIndex = 0;

foreach ($lineMap as $lineName => $causeData) {
    $dataset = [
        "label" => $lineName,
        "data" => [],
        "backgroundColor" => $colorPalette[$colorIndex % count($colorPalette)],
        "borderRadius" => 4,
        "tooltipInfo" => formatMinutes($lineDurations[$lineName] ?? 0) // 👈 Pass readable duration
    ];

    foreach ($allCausesList as $causeName) {
        $dataset['data'][] = $causeData[$causeName] ?? 0;
    }
    $stopDatasets[] = $dataset;
    $colorIndex++;
}

// 🔧 Format helper: convert minutes to "Xh Ym"
function formatMinutes($minutes) {
    $hours = floor($minutes / 60);
    $mins = round($minutes % 60);
    return sprintf("%dh %02dm", $hours, $mins);
}

// -----------------------------
// PARTS QUERY
// -----------------------------
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

// -----------------------------
// FINAL OUTPUT
// -----------------------------
echo json_encode([
    "stopCause" => [
        "labels" => $lineList,      // ✅ x-axis is now LINE
        "datasets" => $stopDatasets // ✅ bars = causes
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
