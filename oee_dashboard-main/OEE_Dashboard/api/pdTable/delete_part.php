<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../../api/db.php';

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$input = $_SERVER['CONTENT_TYPE'] === 'application/json'
    ? json_decode(file_get_contents('php://input'), true)
    : $_POST;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$id = $input['id'] ?? null;

if (!$id || !filter_var($id, FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing Part ID.']);
    exit();
}

$sql = "DELETE FROM IOT_TOOLBOX_PARTS WHERE id = ?";
$params = [$id];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'SQL execution failed.',
        'error' => sqlsrv_errors()
    ]);
    exit();
}

$rows = sqlsrv_rows_affected($stmt);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

if ($rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Part deleted successfully.']);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Part not found or already deleted.']);
}
?>
