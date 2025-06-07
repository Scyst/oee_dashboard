<?php
require_once("../../api/db.php");
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents("php://input"), true);

switch ($action) {
    case 'read':
        $sql = "SELECT * FROM parameter ORDER BY updated_at DESC";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['updated_at'] = $row['updated_at']->format('Y-m-d H:i:s');
            $rows[] = $row;
        }

        echo json_encode($rows);
        break;

    case 'create':
        $sql = "INSERT INTO parameter (line, model, part_no, planned_output)
                VALUES (?, ?, ?, ?)";
        $params = [
            strtoupper($input['line']),
            strtoupper($input['model']),
            strtoupper($input['part_no']),
            (int)$input['planned_output']
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        echo json_encode(["success" => $stmt ? true : false]);
        break;

    case 'update':
        $sql = "UPDATE parameter
                SET line = ?, model = ?, part_no = ?, planned_output = ?, updated_at = GETDATE()
                WHERE id = ?";
        $params = [
            strtoupper($input['line']),
            strtoupper($input['model']),
            strtoupper($input['part_no']),
            (int)$input['planned_output'],
            (int)$input['id']
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        echo json_encode(["success" => $stmt ? true : false]);
        break;

    case 'delete':
        $id = $_GET['id'] ?? 0;
        $stmt = sqlsrv_query($conn, "DELETE FROM parameter WHERE id = ?", [(int)$id]);
        echo json_encode(["success" => $stmt ? true : false]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        break;
}
