<?php
require_once("../api/db.php"); 

$sql = "SELECT DISTINCT cause FROM IOT_TOOLBOX_STOP_CAUSES WHERE cause IS NOT NULL";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo "<option value=\"\">Error fetching causes</option>";
    return;
}

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if (!empty($row['cause'])) {
        $cause = htmlspecialchars($row['cause'], ENT_QUOTES, 'UTF-8');
        echo "<option value=\"{$cause}\">{$cause}</option>";
    }
}
?>
