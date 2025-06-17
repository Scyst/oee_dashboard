<?php
require_once("../api/db.php");

$sql = "SELECT DISTINCT line FROM IOT_TOOLBOX_PARAMETER WHERE line IS NOT NULL ORDER BY line";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $line = htmlspecialchars($row['line']);
    echo "<option value=\"$line\">$line</option>";
}
?>