<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Parameter Manager</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-dark text-white p-4">
  <div class="container">
    <h2 class="mb-4">Parameter Manager</h2>

    <form id="paramForm" class="row g-3">
      <input type="hidden" id="paramId">
      <div class="col-md-3">
        <input type="text" class="form-control" id="line" placeholder="Line" required>
      </div>
      <div class="col-md-3">
        <input type="text" class="form-control" id="model" placeholder="Model" required>
      </div>
      <div class="col-md-3">
        <input type="text" class="form-control" id="partNo" placeholder="Part No." required>
      </div>
      <div class="col-md-2">
        <input type="number" class="form-control" id="plannedOutput" placeholder="Planned Output" required>
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-success w-100">Save</button>
      </div>
    </form>

    <table class="table table-dark table-striped mt-4">
      <thead>
        <tr>
          <th>Line</th>
          <th>Model</th>
          <th>Part No.</th>
          <th>Planned Output</th>
          <th>Updated At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="paramTable"></tbody>
    </table>
  </div>

  <script>
    async function loadParameters() {
      const res = await fetch('../api/paraManage/paraManage.php?action=read');
      const data = await res.json();
      const tbody = document.getElementById('paramTable');

      console.log(data);
      tbody.innerHTML = '';
      data.data.forEach(row => {
        tbody.innerHTML += `
          <tr>
            <td>${row.line}</td>
            <td>${row.model}</td>
            <td>${row.part_no}</td>
            <td>${row.planned_output.toLocaleString()}</td>
            <td>${new Date(row.updated_at).toLocaleString()}</td>
            <td>
              <button class="btn btn-sm btn-warning" onclick='editParam(${JSON.stringify(row)})'>Edit</button>
              <button class="btn btn-sm btn-danger" onclick='deleteParam(${row.id})'>Delete</button>
            </td>
          </tr>`;
      });
    }

    async function deleteParam(id) {
      if (!confirm("Delete this parameter?")) return;
      await fetch(`../api/paraManage/paraManage.php?action=delete&id=${id}`);
      loadParameters();
    }

    function editParam(data) {
      document.getElementById('paramId').value = data.data.id;
      document.getElementById('line').value = data.data.line;
      document.getElementById('model').value = data.data.model;
      document.getElementById('partNo').value = data.data.part_no;
      document.getElementById('plannedOutput').value = data.data.planned_output;
    }

    document.getElementById('paramForm').addEventListener('submit', async e => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const id = formData.get('paramId');
      const action = id ? 'update' : 'create';

      const payload = {
        id,
        line: formData.get('line'),
        model: formData.get('model'),
        part_no: formData.get('partNo'),
        planned_output: formData.get('plannedOutput')
      };

      await fetch(`../api/paraManage/paraManage.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      e.target.reset();
      document.getElementById('paramId').value = '';
      loadParameters();
    });

    loadParameters();
  </script>
</body>
</html>
