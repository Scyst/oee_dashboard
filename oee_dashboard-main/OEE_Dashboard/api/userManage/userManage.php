<?php
require_once("../../api/db.php");
require_once("../../auth/check_auth.php");
header('Content-Type: application/json');

if (!allowRoles(['admin'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents("php://input"), true);

switch ($action) {
    case 'read':
        $stmt = sqlsrv_query($conn, "SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
        $users = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['created_at'] = $row['created_at']->format('Y-m-d H:i:s');
            $users[] = $row;
        }
        echo json_encode($users);
        break;

    case 'create':
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $role = trim($input['role'] ?? 'operator');

        if (!$username || !$password) {
            echo json_encode(["success" => false, "message" => "Username and password required"]);
            break;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = sqlsrv_query($conn, "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, GETDATE())", [$username, $hashed, $role]);
        echo json_encode(["success" => $stmt ? true : false]);
        break;

    case 'update':
        $id = (int)($input['id'] ?? 0);
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');
        $role = trim($input['role'] ?? '');

        if (!$id || !$username || !$role) {
            echo json_encode(["success" => false, "message" => "Invalid input"]);
            break;
        }

        if ($password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = sqlsrv_query($conn, "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?", [$username, $hashed, $role, $id]);
        } else {
            $stmt = sqlsrv_query($conn, "UPDATE users SET username = ?, role = ? WHERE id = ?", [$username, $role, $id]);
        }

        echo json_encode(["success" => $stmt ? true : false]);
        break;

    case 'delete':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = sqlsrv_query($conn, "DELETE FROM users WHERE id = ?", [$id]);
        echo json_encode(["success" => $stmt ? true : false]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
}
?>
