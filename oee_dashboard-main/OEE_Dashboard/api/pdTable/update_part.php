<?php
header('Content-Type: application/json');
require_once '../../api/db.php';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $required_fields = ['id', 'log_date', 'log_time', 'line', 'model', 'part_no', 'count_value', 'count_type'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing_fields)]);
        exit();
    }

    $id = $_POST['id'];
    $log_date = $_POST['log_date'];
    $log_time = $_POST['log_time'];
    $line = strtoupper(trim($_POST['line']));
    $model = strtoupper(trim($_POST['model']));
    $part_no = strtoupper(trim($_POST['part_no']));
    $lot_no = $_POST['lot_no']; // Keep original lot_no unchanged
    $count_value = $_POST['count_value'];
    $count_type = strtoupper(trim($_POST['count_type']));
    $note = $_POST['note'];

    if (!filter_var($id, FILTER_VALIDATE_INT)) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID format.']);
        exit();
    }
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $log_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid log_date format. Expected YYYY-MM-DD.']);
        exit();
    }
    if (!filter_var($count_value, FILTER_VALIDATE_INT)) {
        echo json_encode(['success' => false, 'message' => 'Invalid count_value format. Expected an integer.']);
        exit();
    }

    $sql = "UPDATE IOT_TOOLBOX_PARTS 
            SET log_date = ?, log_time = ?, line = ?, model = ?, part_no = ?, lot_no = ?, count_value = ?, count_type = ?, note = ? 
            WHERE id = ?";
    $params = [$log_date, $log_time, $line, $model, $part_no, $lot_no, $count_value, $count_type, $note, $id];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Update operation failed. SQL error.',
            'details' => print_r(sqlsrv_errors(), true)
        ]);
    } else {
        $rows_affected = sqlsrv_rows_affected($stmt);
        if ($rows_affected > 0) {
            echo json_encode(['success' => true, 'message' => 'Part updated successfully.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes made or part not found.']);
        }
    }

    sqlsrv_free_stmt($stmt);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
