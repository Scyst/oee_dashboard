<?php include_once("../../auth/check_auth.php"); ?>
<?php 
    if (!hasRole(['admin', 'creator'])) { 
        header("Location: ../OEE_Dashboard/OEE_Dashboard.php"); 
        exit; 
    }
    $isAdmin = hasRole(['admin', 'creator']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>User Manager</title>
    <script src="../../utils/libs/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../../utils/libs/bootstrap.min.css">
    <link rel="stylesheet" href="../../style/dropdown.css">
    <link rel="stylesheet" href="../../style/userManageUI.css">
</head>

<body class="bg-dark text-white p-4">
    <?php include('../components/nav_dropdown.php'); ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>User Manager</h2>
            <button class="btn btn-secondary btn-sm" onclick="openLogsModal()">View Logs</button>
        </div>

        <form id="userForm" class="row g-3 mb-4">
            <input type="hidden" name="id" id="userId">
            <div class="col-md-3">
                <input type="text" name="username" class="form-control" id="username" placeholder="Username" required autocomplete="off">
            </div>
            <div class="col-md-3">
                <input type="password" name="password" class="form-control" id="password" placeholder="Password" autocomplete="new-password">
            </div>
            <div class="col-md-3">
                <select class="form-control" name="role" id="role" required>
                    <option value="">Select Role</option>
                    <option value="admin" <?php if (!hasRole('creator')) echo 'disabled'; ?>>Admin</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="operator">Operator</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" id="addBtn" class="btn btn-success w-50">Add</button>
                <button type="submit" id="updateBtn" class="btn btn-warning w-50 d-none">Update</button>
                <button type="button" id="cancelBtn" class="btn btn-secondary" style="flex-grow: 1;">Cancel</button>
            </div>
        </form>

        <table class="table table-dark table-striped table-hover">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Role</th><th>Created at</th><th>Actions</th></tr>
            </thead>
            <tbody id="userTable"></tbody>
        </table>
    </div>

    <div id="toast"></div>

    <?php include('components/logsModal.php'); ?>

    <script>
        const isAdmin = <?php echo json_encode($isAdmin); ?>;
        const currentUserId = <?php echo json_encode($_SESSION['user']['id'] ?? 0); ?>;
    </script>
    <script src="../../page/auto_logout.js"></script>
    <script src="script/userManage.js"></script>  
    <script src="script/modal_handler.js"></script>
</body>
</html>