<?php
require_once '../config.php'; // Adjust path based on your project structure

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $log_date = $_POST['log_date'] ?? '';
    $log_time = $_POST['log_time'] ?? '';
    $count_type = $_POST['count_type'] ?? '';
    $part_no = $_POST['part_no'] ?? '';
    $count_value = isset($_POST['count_value']) ? intval($_POST['count_value']) : 0;

    if ($id && $log_date && $log_time && $count_type && $part_no) {
        $stmt = $conn->prepare("UPDATE parts SET log_date = ?, log_time = ?, count_type = ?, part_no = ?, count_value = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $log_date, $log_time, $count_type, $part_no, $count_value, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Part updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update part.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
