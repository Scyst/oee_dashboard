<?php
header('Content-Type: application/json');
require_once 'db.php'; // Assuming db.php is one directory level up

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// --- Delete Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $id = $_POST['id']; // SQL Server driver handles sanitization with parameterized queries

        // Validate if id is numeric if it's an integer key in DB
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            echo json_encode(['success' => false, 'message' => 'Invalid Part ID format.']);
            exit();
        }

        $sql = "DELETE FROM parts WHERE id = ?";
        $params = array($id);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            // error_log(print_r(sqlsrv_errors(), true)); // Server-side logging
            echo json_encode(['success' => false, 'message' => 'Delete operation failed. SQL error.']);
        } else {
            $rows_affected = sqlsrv_rows_affected($stmt);
            if ($rows_affected > 0) {
                echo json_encode(['success' => true, 'message' => 'Part deleted successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Part not found or no rows deleted.']);
            }
        }
        sqlsrv_free_stmt($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Part ID not provided.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// sqlsrv_close($conn);
?>