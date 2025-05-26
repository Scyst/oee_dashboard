<?php
header('Content-Type: application/json');
require_once '../db.php'; // Assuming db.php is one directory level up

// Check if the connection from db.php was successful
if (!$conn) {
    // db.php would have already died if connection failed,
    // but as a good practice, if db.php were changed to return false on error:
    echo json_encode(['error' => 'Database connection failed. Check server logs.']);
    exit();
}

// --- Fetch Data ---
// Ensure table and column names match your SQL Server schema
$sql = "SELECT id, log_date, /*log_time,*/ line, model, part_no, count_value, count_type FROM parts ORDER BY log_date DESC, log_time DESC";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    // Log the detailed error on the server for debugging
    // error_log(print_r(sqlsrv_errors(), true));
    echo json_encode(['error' => 'Error fetching parts. SQL Server query failed.']);
    exit();
}

$parts = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // SQL Server DATE and TIME types are usually fetched as strings in expected formats
    // or as DateTime objects depending on connection/driver settings.
    // If log_date is a DateTime object, format it:
    if (isset($row['log_date']) && $row['log_date'] instanceof DateTime) {
        $row['log_date'] = $row['log_date']->format('Y-m-d'); // Or 'd/m/Y' if your JS expects that initially
    }
    // Ensure other data types are as expected by the frontend
    $parts[] = $row;
}

echo json_encode($parts);

sqlsrv_free_stmt($stmt);
// sqlsrv_close($conn); // Connection is typically closed when script ends, or manage as per your app structure.
?>