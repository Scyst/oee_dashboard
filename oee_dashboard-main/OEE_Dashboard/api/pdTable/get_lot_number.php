<?php
require_once("../../api/db.php");

$sql = "SELECT DISTINCT lot_no FROM parts WHERE lot_no IS NOT NULL AND lot_no <> '' ORDER BY lot_no";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<option value=\"" . htmlspecialchars($row['lot_no']) . "\">";
    }
}
?>
