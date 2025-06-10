<?php
require_once("../api/db.php");

$sql = "SELECT DISTINCT line FROM parameter ORDER BY line";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['line']) . "\"></option>";
}
?>
