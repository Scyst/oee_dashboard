<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

$sql = "SELECT DISTINCT model FROM parameter WHERE model IS NOT NULL ORDER BY model";
$stmt = sqlsrv_query($conn, $sql);

$models = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $models[] = $row['model'];
}
echo json_encode($models);
?>