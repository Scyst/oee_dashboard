<?php
require_once("../api/db.php");

$sql = "SELECT DISTINCT model FROM IOT_TOOLBOX_PARAMETER WHERE model IS NOT NULL ORDER BY model";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $model = htmlspecialchars($row['model']);
        echo "<option value=\"$model\">$model</option>";
    }
}
?>