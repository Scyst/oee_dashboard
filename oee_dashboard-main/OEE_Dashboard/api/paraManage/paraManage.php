<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../logger.php'; 
session_start(); 

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents("php://input"), true);

try {
    $currentUser = $_SESSION['user']['username'] ?? 'system'; 

    switch ($action) {
        case 'read':
            $stmt = $pdo->query("SELECT * FROM IOT_TOOLBOX_PARAMETER ORDER BY updated_at DESC");
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                if ($row['updated_at']) {
                    $row['updated_at'] = (new DateTime($row['updated_at']))->format('Y-m-d H:i:s');
                }
            }
            echo json_encode(['success' => true, 'data' => $rows]);
            break;

        case 'create':
            $sql = "INSERT INTO IOT_TOOLBOX_PARAMETER (line, model, part_no, sap_no, planned_output, updated_at) VALUES (?, ?, ?, ?, ?, GETDATE())";
            $params = [
                strtoupper($input['line']),
                strtoupper($input['model']),
                strtoupper($input['part_no']),
                strtoupper($input['sap_no']),
                (int)$input['planned_output']
            ];
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($params);

            if ($success) {
                $detail = "{$params[0]}-{$params[1]}-{$params[2]}";
                logAction($pdo, $currentUser, 'CREATE PARAMETER', null, $detail);
            }

            echo json_encode(['success' => $success, 'message' => 'Parameter created.']);
            break;

        case 'update':
            $id = $input['id'] ?? null;
            if (!$id) throw new Exception("Missing ID");
            
            $stmt = $pdo->prepare("SELECT * FROM IOT_TOOLBOX_PARAMETER WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) throw new Exception("Parameter not found");

            $line = strtoupper($input['line'] ?? $row['line']);
            $model = strtoupper($input['model'] ?? $row['model']);
            $part_no = strtoupper($input['part_no'] ?? $row['part_no']);
            $sap_no = strtoupper($input['sap_no'] ?? $row['sap_no']);
            $planned_output = isset($input['planned_output']) ? (int)$input['planned_output'] : (int)$row['planned_output'];

            $updateSql = "UPDATE IOT_TOOLBOX_PARAMETER SET line = ?, model = ?, part_no = ?, sap_no = ?, planned_output = ?, updated_at = GETDATE() WHERE id = ?";
            $params = [$line, $model, $part_no, $sap_no, $planned_output, $id];
            $stmt = $pdo->prepare($updateSql);
            $success = $stmt->execute($params);

            if ($success) {
                $detail = "ID: $id, Data: {$line}-{$model}-{$part_no}";
                logAction($pdo, $currentUser, 'UPDATE PARAMETER', $id, $detail);
            }

            echo json_encode(["success" => $success, 'message' => 'Parameter updated.']);
            break;

        case 'delete':
            $id = $_GET['id'] ?? 0;
            if (!$id) throw new Exception("Missing ID");

            $stmt = $pdo->prepare("DELETE FROM IOT_TOOLBOX_PARAMETER WHERE id = ?");
            $success = $stmt->execute([(int)$id]);
            
            if ($success && $stmt->rowCount() > 0) {
                 logAction($pdo, $currentUser, 'DELETE PARAMETER', $id);
            }

            echo json_encode(["success" => $success, 'message' => 'Parameter deleted.']);
            break;

        case 'bulk_import':
            if (!is_array($input) || empty($input)) throw new Exception("Invalid data");
            
            $pdo->beginTransaction();
            
            $checkSql = "SELECT id FROM IOT_TOOLBOX_PARAMETER WHERE line = ? AND model = ? AND part_no = ?";
            $checkStmt = $pdo->prepare($checkSql);

            $updateSql = "UPDATE IOT_TOOLBOX_PARAMETER SET sap_no = ?, planned_output = ?, updated_at = GETDATE() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);

            $insertSql = "INSERT INTO IOT_TOOLBOX_PARAMETER (line, model, part_no, sap_no, planned_output, updated_at) VALUES (?, ?, ?, ?, ?, GETDATE())";
            $insertStmt = $pdo->prepare($insertSql);

            $imported = 0;
            foreach ($input as $row) {
                $line = strtoupper(trim($row['line'] ?? ''));
                $model = strtoupper(trim($row['model'] ?? ''));
                $part_no = strtoupper(trim($row['part_no'] ?? ''));
                $sap_no = strtoupper(trim($row['sap_no'] ?? ''));
                $planned_output = (int)($row['planned_output'] ?? 0);

                if (empty($line) || empty($model) || empty($part_no)) continue;

                $checkStmt->execute([$line, $model, $part_no]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    $updateStmt->execute([$sap_no, $planned_output, $existing['id']]);
                } else {
                    $insertStmt->execute([$line, $model, $part_no, $sap_no, $planned_output]);
                }
                $imported++;
            }

            $pdo->commit();
            logAction($pdo, $currentUser, 'BULK IMPORT PARAMETER', null, "Imported $imported rows");

            echo json_encode(["success" => true, "imported" => $imported, "message" => "Imported $imported row(s) successfully."]);
            break;

        default:
            http_response_code(400);
            throw new Exception("Invalid action specified.");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    error_log("Error in paraManage.php: " . $e->getMessage());
}
?>