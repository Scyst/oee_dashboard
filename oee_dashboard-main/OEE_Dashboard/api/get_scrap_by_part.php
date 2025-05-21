<?php
require_once("../db.php");

$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;
$shift = $_GET['shift'] ?? null;
$line = $_GET['line'] ?? null;
$machine = $_GET['machine'] ?? null;

$params = [];
$where = [];

if ($start && $end) {
  $where[] = "date BETWEEN ? AND ?";
  $params[] = $start;
  $params[] = $end;
}

if ($shift) {
  $where[] = "shift = ?";
  $params[] = $shift;
}

if ($line) {
  $where[] = "line = ?";
  $params[] = $line;
}

if ($machine) {
  $where[] = "machine = ?";
  $params[] = $machine;
}

$whereClause = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
  SELECT part_no, SUM(total) AS total_scrap
  FROM scrap
  $whereClause
  GROUP BY part_no
  ORDER BY total_scrap DESC
";

$stmt = sqlsrv_query($conn, $sql, $params);
$data = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
  $data[] = $row;
}

echo json_encode($data);
?>
