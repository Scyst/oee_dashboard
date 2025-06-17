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
$currentUser = $_SESSION['user']['username'];
$currentRole = $_SESSION['user']['role'];

function logAction($conn, $actor, $action, $target, $detail = null) {
    $sql = "INSERT INTO user_logs (action_by, action_type, target_user, detail, created_at)
            VALUES (?, ?, ?, ?, GETDATE())";
    sqlsrv_query($conn, $sql, [$actor, $action, $target, $detail]);
}

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

        if (!$username || !$password || $role === 'admin') {
            echo json_encode(["success" => false, "message" => "Invalid input or not allowed to create admin"]);
            break;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = sqlsrv_query($conn,
            "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, GETDATE())",
            [$username, $hashed, $role]
        );

        if ($stmt) {
            logAction($conn, $currentUser, 'create', $username, "role: $role");
        }

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

        $stmt = sqlsrv_query($conn, "SELECT * FROM users WHERE id = ?", [$id]);
        $targetUser = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$targetUser) {
            echo json_encode(["success" => false, "message" => "User not found"]);
            break;
        }

        // Restrict admin changes
        if ($targetUser['role'] === 'admin' && $targetUser['username'] !== $currentUser) {
            echo json_encode(["success" => false, "message" => "You cannot modify another admin"]);
            break;
        }

        if ($targetUser['role'] !== 'admin' && $role === 'admin') {
            echo json_encode(["success" => false, "message" => "Cannot promote user to admin"]);
            break;
        }

        $success = false;
        if ($password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = sqlsrv_query($conn,
                "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?",
                [$username, $hashed, $role, $id]
            );
        } else {
            $stmt = sqlsrv_query($conn,
                "UPDATE users SET username = ?, role = ? WHERE id = ?",
                [$username, $role, $id]
            );
        }

        if ($stmt) {
            logAction($conn, $currentUser, 'update', $targetUser['username'], "role: $role");
            $success = true;
        }

        echo json_encode(["success" => $success]);
        break;

    case 'delete':
        $id = (int)($_GET['id'] ?? 0);

        $stmt = sqlsrv_query($conn, "SELECT * FROM users WHERE id = ?", [$id]);
        $targetUser = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if (!$targetUser) {
            echo json_encode(["success" => false, "message" => "User not found"]);
            break;
        }

        if ($targetUser['role'] === 'admin') {
            echo json_encode(["success" => false, "message" => "Cannot delete another admin"]);
            break;
        }

        $stmt = sqlsrv_query($conn, "DELETE FROM users WHERE id = ?", [$id]);
        if ($stmt) {
            logAction($conn, $currentUser, 'delete', $targetUser['username']);
        }

        echo json_encode(["success" => $stmt ? true : false]);
        break;
    
    case 'logs':
        $stmt = sqlsrv_query($conn, "SELECT * FROM user_logs ORDER BY created_at DESC");
        $logs = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['created_at'] instanceof DateTime) {
                $row['created_at'] = $row['created_at']->format('Y-m-d H:i:s');
            }
            $logs[] = $row;
        }

        echo json_encode($logs);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        break;
}
?>
