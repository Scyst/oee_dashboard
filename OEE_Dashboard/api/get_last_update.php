<?php
require_once("../db.php");

// Combine updated_at from all tables
$sql = "
SELECT MAX(latest) AS last_update FROM (
  SELECT MAX(updated_at) AS latest FROM part_logs
  UNION
  SELECT MAX(updated_at) FROM stop_logs
  UNION
  SELECT MAX(updated_at) FROM scrap_logs
) AS updates
";

$stmt = sqlsrv_query($conn, $sql);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo json_encode([
    "last_update" => $row["last_update"] ? $row["last_update"]->format('Y-m-d H:i:s') : null
]);
?>
