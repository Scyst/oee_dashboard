<?php
require_once("../api/db.php");

$sql = "SELECT DISTINCT model FROM parameter ORDER BY model";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['model']) . "\"></option>";
}
?>
