<?php include_once("../auth/check_auth.php"); ?>
<?php if (!hasRole('admin')) { header("Location: ../page/OEE_Dashboard.php"); exit; } ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>User Logs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="../utils/libs/bootstrap.min.css">
  <style>
    body { background-color: #1e1e1e; color: white; padding: 20px; }
    .modal-header, .modal-footer { background-color: #2a2a2a; }
    .modal-content { background-color: #2a2a2a; color: white; }
    table { font-size: 0.9rem; }
  </style>
</head>
<body>
  <h4>User Activity Logs</h4>

  <div class="table-responsive">
    <table class="table table-dark table-striped table-sm">
      <thead>
        <tr>
          <th>ID</th>
          <th>Action By</th>
          <th>Action Type</th>
          <th>Target User</th>
          <th>Detail</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody id="logTableBody"></tbody>
    </table>
  </div>

  <script>
    async function loadLogs() {
      const res = await fetch('../api/userManage/userManage.php?action=logs');
      const logs = await res.json();
      const tbody = document.getElementById('logTableBody');
      tbody.innerHTML = '';

      logs.forEach(log => {
        tbody.innerHTML += `
          <tr>
            <td>${log.id}</td>
            <td>${log.action_by}</td>
            <td>${log.action_type}</td>
            <td>${log.target_user}</td>
            <td>${log.detail}</td>
            <td>${new Date(log.created_at).toLocaleString()}</td>
          </tr>`;
      });
    }

    loadLogs();
  </script>
</body>
</html>
