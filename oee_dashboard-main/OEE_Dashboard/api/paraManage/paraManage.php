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
        $sql = "INSERT INTO parameter (line, model, part_no, sap_no, planned_output, updated_at)
                VALUES (?, ?, ?, ?, ?, GETDATE())";
        $params = [
            strtoupper($input['line']),
            strtoupper($input['model']),
            strtoupper($input['part_no']),
            strtoupper($input['sap_no']),
            (int)$input['planned_output']
        ];
        $stmt = sqlsrv_query($conn, $sql, $params);
        echo json_encode(["success" => $stmt ? true : false]);
        break;

    case 'update':
        $sql = "UPDATE parameter
                SET line = ?, model = ?, part_no = ?, sap_no = ?, planned_output = ?, updated_at = GETDATE()
                WHERE id = ?";
        $params = [
            strtoupper($input['line']),
            strtoupper($input['model']),
            strtoupper($input['part_no']),
            strtoupper($input['sap_no']),
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

    case 'bulk_import':
        if (!is_array($input)) {
            echo json_encode(["success" => false, "message" => "Invalid data"]);
            break;
        }

        $success = true;
        $imported = 0;
        $errors = [];

        foreach ($input as $i => $row) {
            $line = strtoupper(trim($row['line'] ?? ''));
            $model = strtoupper(trim($row['model'] ?? ''));
            $part_no = strtoupper(trim($row['part_no'] ?? ''));
            $sap_no = strtoupper(trim($row['sap_no'] ?? ''));
            $planned_output = (int)($row['planned_output'] ?? 0);

            if (!$line || !$model || !$part_no || !$sap_no || !$planned_output) {
                $errors[] = "Row " . ($i + 2) . " has missing or invalid data.";
                continue;
            }

            $check = sqlsrv_query($conn, "SELECT id FROM parameter WHERE line = ? AND model = ? AND part_no = ?", [$line, $model, $part_no]);
            if ($check && $existing = sqlsrv_fetch_array($check, SQLSRV_FETCH_ASSOC)) {
                $stmt = sqlsrv_query($conn, "
                    UPDATE parameter SET sap_no = ?, planned_output = ?, updated_at = GETDATE() WHERE id = ?",
                    [$sap_no, $planned_output, $existing['id']]
                );
            } else {
                $stmt = sqlsrv_query($conn, "
                    INSERT INTO parameter (line, model, part_no, sap_no, planned_output, updated_at)
                    VALUES (?, ?, ?, ?, ?, GETDATE())",
                    [$line, $model, $part_no, $sap_no, $planned_output]
                );
            }

            if ($stmt) {
                $imported++;
            } else {
                $errors[] = "Row " . ($i + 2) . " failed: " . print_r(sqlsrv_errors(), true);
                $success = false;
            }
        }

        echo json_encode([
            "success" => $success,
            "imported" => $imported,
            "message" => $success
                ? "Imported $imported row(s) successfully."
                : "Import completed with some errors.",
            "errors" => $errors
        ]);
        break;

    case 'inline_update':
        $id = (int)($input['id'] ?? 0);
        $field = $input['field'] ?? '';
        $value = $input['value'] ?? '';

        $allowedFields = ['line', 'model', 'part_no', 'sap_no', 'planned_output'];
        if (!in_array($field, $allowedFields) || !$id) {
            echo json_encode(["success" => false, "message" => "Invalid update request"]);
            break;
        }

        $value = strtoupper(trim($value));
        if ($field === 'planned_output') {
            $value = (int)$value;
        }

        $sql = "UPDATE parameter SET $field = ?, updated_at = GETDATE() WHERE id = ?";
        $stmt = sqlsrv_query($conn, $sql, [$value, $id]);

        echo json_encode(["success" => $stmt ? true : false]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        break;
}
