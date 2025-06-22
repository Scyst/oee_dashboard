<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../auth/check_auth.php'; 
require_once __DIR__ . '/../logger.php'; 

$action = $_REQUEST['action'] ?? '';
$input = json_decode(file_get_contents("php://input"), true);

if (!hasRole(['admin', 'creator'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $currentUser = $_SESSION['user'];

    switch ($action) {
        case 'read':
            $stmt = $pdo->query("SELECT id, username, role, created_at FROM IOT_TOOLBOX_USERS WHERE role != 'creator' ORDER BY id ASC");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as &$user) {
                if ($user['created_at']) $user['created_at'] = (new DateTime($user['created_at']))->format('Y-m-d H:i:s');
            }
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        case 'create':
            if (!hasRole(['admin', 'creator'])) {
                 throw new Exception("Permission denied.");
            }
            $username = trim($input['username'] ?? '');
            $password = trim($input['password'] ?? '');
            $role = trim($input['role'] ?? '');

            if (empty($username) || empty($password) || empty($role)) {
                throw new Exception("Username, password, and role are required.");
            }
            if ($role === 'creator') {
                 throw new Exception("Cannot create a user with the 'creator' role.");
            }
            if ($role === 'admin' && !hasRole('creator')) {
                throw new Exception("Only creators can create admin users.");
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO IOT_TOOLBOX_USERS (username, password, role) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashedPassword, $role]);

            logAction($pdo, $currentUser['username'], 'CREATE USER', $username, "Role: $role");
            echo json_encode(['success' => true, 'message' => 'User created successfully.']);
            break;

        case 'update':
            $id = (int)($input['id'] ?? 0);
            $username = trim($input['username'] ?? '');
            $role = trim($input['role'] ?? '');
            $password = trim($input['password'] ?? '');

            if (!$id || !$username || !$role) throw new Exception("ID, username, and role are required for update.");

            $stmt = $pdo->prepare("SELECT username, role FROM IOT_TOOLBOX_USERS WHERE id = ?");
            $stmt->execute([$id]);
            $targetUser = $stmt->fetch();
            if (!$targetUser) throw new Exception("Target user not found.");

            $isEditingSelf = ($id === (int)$currentUser['id']);
            $isCreator = hasRole('creator');

            if ($targetUser['role'] === 'creator') throw new Exception("Creator accounts cannot be modified.");
            
            if (!$isEditingSelf) { // --- ตรรกะเมื่อแก้ไขผู้ใช้อื่น
                if ($targetUser['role'] === 'admin' && !$isCreator) {
                    throw new Exception("Only creators can modify other admins.");
                }
                if ($role === 'admin' && !$isCreator) {
                    throw new Exception("Only creators can promote users to admin.");
                }
            }
            
            $updateFields = [];
            $params = [];
            
            if ($isEditingSelf && ($username !== $currentUser['username'] || $role !== $currentUser['role'])) {
                throw new Exception("You can only change your own password.");
            } else {
                $updateFields[] = "username = ?";
                $params[] = $username;
                $updateFields[] = "role = ?";
                $params[] = $role;
            }
            
            if (!empty($password)) {
                $updateFields[] = "password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            if (empty($updateFields)) {
                 echo json_encode(['success' => true, 'message' => 'No changes were made.']);
                 break;
            }

            $sql = "UPDATE IOT_TOOLBOX_USERS SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            logAction($pdo, $currentUser['username'], 'UPDATE USER', $username, "Role: $role");
            echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
            break;

        case 'delete':
            $id = (int)($_GET['id'] ?? 0);
            if ($id === (int)$currentUser['id']) {
                throw new Exception("You cannot delete your own account.");
            }

            $stmt = $pdo->prepare("SELECT username, role FROM IOT_TOOLBOX_USERS WHERE id = ?");
            $stmt->execute([$id]);
            $targetUser = $stmt->fetch();
            if (!$targetUser) {
                throw new Exception("User not found.");
            }
            if ($targetUser['role'] === 'creator' || $targetUser['role'] === 'admin') {
                if (!hasRole('creator')) {
                    throw new Exception("Permission denied. Only creators can delete admins.");
                }
            }

            $deleteStmt = $pdo->prepare("DELETE FROM IOT_TOOLBOX_USERS WHERE id = ?");
            $deleteStmt->execute([$id]);

            logAction($pdo, $currentUser['username'], 'DELETE USER', $targetUser['username']);
            echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
            break;
        
        case 'logs':
            $stmt = $pdo->query("SELECT TOP 500 * FROM IOT_TOOLBOX_USER_LOGS ORDER BY created_at DESC");
            
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($logs as &$log) {
                if ($log['created_at']) {
                    $log['created_at'] = (new DateTime($log['created_at']))->format('Y-m-d H:i:s');
                }
            }
            echo json_encode(['success' => true, 'data' => $logs]);
            break;

        default:
            http_response_code(400);
            throw new Exception("Invalid action specified for User Management.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    error_log("Error in userManage.php: " . $e->getMessage());
}
?>