<?php
//call by pdTable.js

header('Content-Type: application/json');
require_once '../../api/db.php';

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $required_fields = ['id', 'log_date', 'stop_begin', 'stop_end', 'line', 'machine', 'cause', 'recovered_by'];
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
    $stop_begin = $_POST['stop_begin'];
    $stop_end = $_POST['stop_end'];
    $line = $_POST['line'];
    $machine = $_POST['machine'];
    $cause = $_POST['cause'];
    $recovered_by = $_POST['recovered_by'];
   $note = $_POST['note'] ?? null;

    // Validate time format
    $time_fields = ['stop_begin', 'stop_end'];
    foreach ($time_fields as $timeField) {
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $_POST[$timeField])) {
            echo json_encode(['success' => false, 'message' => "Invalid $timeField format. Expected HH:MM or HH:MM:SS."]);
            exit();
        }
    }

    if (!filter_var($id, FILTER_VALIDATE_INT)) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID format.']);
        exit();
    }
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $log_date)) {
         echo json_encode(['success' => false, 'message' => 'Invalid log_date format. Expected YYYY-MM-DD.']);
        exit();
    }

    $sql = "UPDATE IOT_TOOLBOX_STOP_CAUSES SET log_date = ?, stop_begin = ?, stop_end = ?, line = ?, machine = ?, cause = ?, recovered_by = ?, note = ? WHERE id = ?";
    $params = array($log_date, $stop_begin, $stop_end, $line, $machine, $cause, $recovered_by, $note, $id);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Update operation failed. SQL error. Details: ' . print_r(sqlsrv_errors(), true) ]); // Include error details for debugging if needed, remove for production
    } else {
        $rows_affected = sqlsrv_rows_affected($stmt);
        if ($rows_affected > 0) {
            echo json_encode(['success' => true, 'message' => 'Data updated successfully.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes made or data not found.']);
        }
    }
    sqlsrv_free_stmt($stmt);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// sqlsrv_close($conn);
?>