<?php include_once("../auth/check_auth.php"); ?>
<?php if (!hasRole('admin')) { header("Location: ../page/OEE_Dashboard.php"); exit; } ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Manager</title>
  <script src="../utils/libs/bootstrap.bundle.min.js"></script>
  <script src="../utils/libs/xlsx.full.min.js"></script>
  
  <link rel="stylesheet" href="../utils/libs/bootstrap.min.css">
  <link rel="stylesheet" href="../style/dropdown.css">
</head>
<body class="bg-dark text-white p-4">
  <?php include('components/nav_dropdown.php'); ?>
  <div class="container">
    <h2>User Manager</h2>

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

  <script>
    let users = [];

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
      if (!confirm('Delete this user?')) return;
      await fetch(`../api/userManage/userManage.php?action=delete&id=${id}`);
      loadUsers();
    }

    document.getElementById('addBtn').addEventListener('click', async () => {
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const role = document.getElementById('role').value;

      if (!username || !password || !role) return alert('All fields are required.');

      const res = await fetch('../api/userManage/userManage.php?action=create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ username, password, role })
      });

      const result = await res.json();
      if (result.success) {
        loadUsers();
        document.getElementById('userForm').reset();
      } else {
        alert('Failed to create user.');
      }
    });

    document.getElementById('updateBtn').addEventListener('click', async () => {
      const id = document.getElementById('userId').value;
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const role = document.getElementById('role').value;

      if (!id || !username || !role) return alert('Username and role are required.');

      const payload = { id, username, role };
      if (password) payload.password = password;

      const res = await fetch('../api/userManage/userManage.php?action=update', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
      });

      const result = await res.json();
      if (result.success) {
        loadUsers();
        document.getElementById('userForm').reset();
        document.getElementById('addBtn').classList.remove('d-none');
        document.getElementById('updateBtn').classList.add('d-none');
      } else {
        alert('Failed to update user.');
      }
    });

    loadUsers();
  </script>
</body>
</html>
