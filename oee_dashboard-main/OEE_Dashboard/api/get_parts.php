<?php
// api/get_parts.php
require '../config/db.php';

$sql = "SELECT * FROM parts ORDER BY log_date DESC, log_time DESC";
$result = $conn->query($sql);

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

header('Content-Type: application/json');
echo json_encode($rows);
