<?php
header('Content-Type: application/json');
require_once '../db.php'; // Assuming db.php is one directory level up

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// --- Update Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure all expected fields are present
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
    // Your JS formatDate function converts to "yyyy-mm-dd", which is suitable for SQL Server DATE type
    $log_date = $_POST['log_date'];
    $log_time = $_POST['log_time'];
    $line = $_POST['line'];
    $model = $_POST['model'];
    $part_no = $_POST['part_no'];
    $count_value = $_POST['count_value'];
    $count_type = $_POST['count_type'];
    $note = $_POST['note'];

    // Basic Validation (you might want more robust validation)
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
    // Add more validation for other fields as necessary

    $sql = "UPDATE parts SET log_date = ?, log_time = ?, line = ?, model = ?, part_no = ?, count_value = ?, count_type = ?, note = ? WHERE id = ?";
    $params = array($log_date, $log_time, $line, $model, $part_no, $count_value, $count_type, $note, $id);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        // error_log(print_r(sqlsrv_errors(), true)); // Server-side logging
        echo json_encode(['success' => false, 'message' => 'Update operation failed. SQL error. Details: ' . print_r(sqlsrv_errors(), true) ]); // Include error details for debugging if needed, remove for production
    } else {
        $rows_affected = sqlsrv_rows_affected($stmt);
        if ($rows_affected > 0) {
            echo json_encode(['success' => true, 'message' => 'Part updated successfully.']);
        } else {
            // This can also mean the data submitted was the same as what's already in the DB,
            // or the part_id was not found.
            // You might want to check if the part exists first for a more specific message.
            echo json_encode(['success' => true, 'message' => 'No changes made or part not found.']);
        }
    }
    sqlsrv_free_stmt($stmt);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// sqlsrv_close($conn);
?>