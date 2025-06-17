<?php include_once("../auth/check_auth.php"); ?>
<?php if (!hasRole('admin')) { header("Location: ../page/OEE_Dashboard.php"); exit; } ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Manager</title>
  <script src="../utils/libs/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="../utils/libs/bootstrap.min.css">
  <link rel="stylesheet" href="../style/dropdown.css">
  <style>
    #toast {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: #333;
      color: white;
      padding: 10px 16px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.3);
      opacity: 0;
      transition: opacity 0.4s ease, transform 0.4s ease;
      z-index: 9999;
      transform: translateY(20px);
    }
  </style>
</head>
<body class="bg-dark text-white p-4">
  <?php include('components/nav_dropdown.php'); ?>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>User Manager</h2>
      <button class="btn btn-secondary btn-sm" onclick="openLogs()">View Logs</button>
    </div>

    <form id="userForm" class="row g-3 mb-4">
      <input type="hidden" id="userId">
      <div class="col-md-3">
        <input type="text" class="form-control" id="username" placeholder="Username" required>
      </div>
      <div class="col-md-3">
        <input type="password" class="form-control" id="password" placeholder="Password">
      </div>
      <div class="col-md-3">
        <select class="form-control" id="role" required>
          <option value="">Select Role</option>
          <option value="admin">Admin</option>
          <option value="supervisor">Supervisor</option>
          <option value="operator">Operator</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="button" id="addBtn" class="btn btn-success w-50">Add</button>
        <button type="button" id="updateBtn" class="btn btn-warning w-50 d-none">Update</button>
      </div>
    </form>

    <table class="table table-dark table-striped">
      <thead>
        <tr>
          <th>ID</th><th>Username</th><th>Role</th><th>Created At</th><th>Actions</th>
        </tr>
      </thead>
      <tbody id="userTable"></tbody>
    </table>
  </div>

  <div id="toast"></div>

  <script>
    let users = [];
    function openLogs() {
      const w = 1000;
      const h = 600;
      const left = (screen.width - w) / 2;
      const top = (screen.height - h) / 2;
      window.open('userLogs.php', 'UserLogs', `width=${w},height=${h},top=${top},left=${left},scrollbars=yes`);
    }

    async function loadUsers() {
      const res = await fetch('../api/userManage/userManage.php?action=read');
      users = await res.json();
      renderTable();
    }

    function renderTable() {
      const tbody = document.getElementById('userTable');
      tbody.innerHTML = '';
      users.forEach(user => {
        tbody.innerHTML += `
          <tr>
            <td>${user.id}</td>
            <td>${user.username}</td>
            <td>${user.role}</td>
            <td>${user.created_at}</td>
            <td>
              <button class="btn btn-sm btn-warning" onclick='editUser(${JSON.stringify(user)})'>Edit</button>
              <button class="btn btn-sm btn-danger" onclick='deleteUser(${user.id})'>Delete</button>
            </td>
          </tr>`;
      });
    }

    function editUser(user) {
      document.getElementById('userId').value = user.id;
      document.getElementById('username').value = user.username;
      document.getElementById('role').value = user.role;
      document.getElementById('password').value = '';
      document.getElementById('addBtn').classList.add('d-none');
      document.getElementById('updateBtn').classList.remove('d-none');
    }

    async function deleteUser(id) {
      if (!confirm('Are you sure you want to delete this user?')) return;
      const res = await fetch(`../api/userManage/userManage.php?action=delete&id=${id}`);
      const result = await res.json();
      if (result.success) {
        showToast("User deleted successfully");
        loadUsers();
      } else {
        showToast("Delete failed", '#dc3545');
      }
    }

    function showToast(message, bg = '#28a745') {
      const toast = document.getElementById('toast');
      toast.innerText = message;
      toast.style.backgroundColor = bg;
      toast.style.opacity = 1;
      toast.style.transform = 'translateY(0)';
      setTimeout(() => {
        toast.style.opacity = 0;
        toast.style.transform = 'translateY(20px)';
      }, 3000);
    }

    document.getElementById('addBtn').addEventListener('click', async () => {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const role = document.getElementById('role').value;

      if (!username || !password || !role) {
        showToast("All fields are required", "#dc3545");
        return;
      }

      if (!confirm(`Are you sure you want to create user "${username}" with role "${role}"?`)) return;

      const res = await fetch('../api/userManage/userManage.php?action=create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ username, password, role })
      });

      const result = await res.json();
      if (result.success) {
        showToast("User created successfully");
        document.getElementById('userForm').reset();
        loadUsers();
      } else {
        showToast(result.message || "Failed to create user", "#dc3545");
      }
    });

    document.getElementById('updateBtn').addEventListener('click', async () => {
      const id = document.getElementById('userId').value;
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const role = document.getElementById('role').value;

      if (!id || !username || !role) {
        showToast("Username and role are required", "#dc3545");
        return;
      }

      const payload = { id, username, role };
      if (password) payload.password = password;

      const res = await fetch('../api/userManage/userManage.php?action=update', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });

      const result = await res.json();
      if (result.success) {
        showToast("User updated successfully");
        document.getElementById('userForm').reset();
        document.getElementById('addBtn').classList.remove('d-none');
        document.getElementById('updateBtn').classList.add('d-none');
        loadUsers();
      } else {
        showToast(result.message || "Failed to update user", "#dc3545");
      }
    });

    loadUsers();
  </script>
</body>
</html>
