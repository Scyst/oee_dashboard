<?php
require_once("db.php");
$sql = "SELECT DISTINCT line FROM parts WHERE line IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<option value=\"" . htmlspecialchars($row['line']) . "\"></option>";
}
?>
