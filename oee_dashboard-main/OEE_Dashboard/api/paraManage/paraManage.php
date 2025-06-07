<?php
require_once("../api/paraManage/db.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'read':
        $sql = "SELECT * FROM parameter ORDER BY updated_at DESC";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['updated_at'] = $row['updated_at'] ? $row['updated_at']->format('Y-m-d H:i:s') : null;
            $rows[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        break;

    case 'create':
        $line = $_POST['line'] ?? '';
        $model = $_POST['model'] ?? '';
        $part_no = $_POST['part_no'] ?? '';
        $planned_output = (int) ($_POST['planned_output'] ?? 0);

        $sql = "INSERT INTO parameter (line, model, part_no, planned_output) VALUES (?, ?, ?, ?)";
        $params = [$line, $model, $part_no, $planned_output];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            echo json_encode(['success' => true, 'message' => 'Created successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Insert failed', 'error' => sqlsrv_errors()]);
        }
        break;

    case 'update':
        $id = (int) ($_POST['id'] ?? 0);
        $line = $_POST['line'] ?? '';
        $model = $_POST['model'] ?? '';
        $part_no = $_POST['part_no'] ?? '';
        $planned_output = (int) ($_POST['planned_output'] ?? 0);

        $sql = "UPDATE parameter SET line = ?, model = ?, part_no = ?, planned_output = ?, updated_at = GETDATE() WHERE id = ?";
        $params = [$line, $model, $part_no, $planned_output, $id];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed', 'error' => sqlsrv_errors()]);
        }
        break;

    case 'delete':
        $id = (int) ($_POST['id'] ?? 0);
        $sql = "DELETE FROM parameter WHERE id = ?";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        if ($stmt) {
            echo json_encode(['success' => true, 'message' => 'Deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed', 'error' => sqlsrv_errors()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
