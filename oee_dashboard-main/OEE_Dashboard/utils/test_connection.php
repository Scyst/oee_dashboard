<?php
require_once '../api/db.php'; // make sure db.php returns $conn

if ($conn) {
    echo "✅ Connected!";
} else {
    echo "❌ Failed:<br>";
    die(print_r(sqlsrv_errors(), true));
}
?>
