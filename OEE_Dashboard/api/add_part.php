<?php
require_once("../db.php");

// Get values from POST, with fallback to default
$log_date = $_POST['log_date'] ?? '';
$shift = $_POST['shift'] ?? '';
$log_time = $_POST['log_time'] ?? '';
$part_no = $_POST['part_no'] ?? '';
$fg_count = 0;
$ng_count = 0;
$rework_count = 0;
$hold_count = 0;

$count_type = $_POST['count_type'];
$count_value = (int)$_POST['count_value'];

switch ($count_type) {
    case 'fg_count':
        $fg_count = $count_value;
        break;
    case 'ng_count':
        $ng_count = $count_value;
        break;
    case 'rework_count':
        $rework_count = $count_value;
        break;
    case 'hold_count':
        $hold_count = $count_value;
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Invalid count type"]);
        exit;
}


// First check if record exists
$checkSql = "SELECT * FROM part_logs WHERE log_date = ? AND shift = ? AND log_time = ? AND part_no = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ssss", $log_date, $shift, $log_time, $part_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update if record existss
    $updateSql = "UPDATE part_logs 
                  SET fg_count = fg_count + ?, ng_count = ng_count + ?, 
                      rework_count = rework_count + ?, hold_count = hold_count + ?
                  WHERE log_date = ? AND shift = ? AND log_time = ? AND part_no = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("iiiissss", $fg_count, $ng_count, $rework_count, $hold_count, $log_date, $shift, $log_time, $part_no);
    $updateStmt->execute();

    echo json_encode(["status" => "success", "message" => "Part data updated"]);
} else {
    // Insert new record
    $insertSql = "INSERT INTO part_logs (log_date, shift, log_time, part_no, fg_count, ng_count, rework_count, hold_count)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("ssssiiii", $log_date, $shift, $log_time, $part_no, $fg_count, $ng_count, $rework_count, $hold_count);
    $insertStmt->execute();

    echo json_encode(["status" => "success", "message" => "Part data inserted"]);
}

$conn->close();
?>
