<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Parameter Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { font-family: Arial; padding: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
    th { background-color: #f4f4f4; }
    form { margin-top: 20px; }
    input, select { padding: 5px; margin: 5px; }
    button { padding: 6px 12px; }
  </style>
</head>
<body>
  <h2>Parameter Management</h2>

  <form id="parameterForm">
    <input type="hidden" id="param_id">
    <select id="line" required>
      <option value="">Select Line</option>
      <option value="Line1">Line1</option>
      <option value="Line2">Line2</option>
    </select>
    <input type="text" id="model" placeholder="Model" required>
    <input type="text" id="part_no" placeholder="Part No" required>
    <input type="number" id="planned_output" placeholder="Planned Output" required>
    <button type="submit">Save</button>
    <button type="button" onclick="resetForm()">Clear</button>
  </form>

  <table>
    <thead>
      <tr>
        <th>Line</th>
        <th>Model</th>
        <th>Part No</th>
        <th>Planned Output</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="paramTableBody"></tbody>
  </table>

  <script>
    async function fetchParams() {
      const res = await fetch('../api/get_performance_params.php');
      const data = await res.json();
      const tbody = document.getElementById('paramTableBody');
      tbody.innerHTML = '';
      data.forEach(row => {
        tbody.innerHTML += `
          <tr>
            <td>${row.line}</td>
            <td>${row.model}</td>
            <td>${row.part_no}</td>
            <td>${row.planned_output}</td>
            <td>
              <button onclick='editParam(${JSON.stringify(row)})'>Edit</button>
              <button onclick='deleteParam(${row.id})'>Delete</button>
            </td>
          </tr>`;
      });
    }

    document.getElementById('parameterForm').addEventListener('submit', async e => {
      e.preventDefault();
      const id = document.getElementById('param_id').value;
      const formData = new FormData();
      formData.append('id', id);
      formData.append('line', document.getElementById('line').value);
      formData.append('model', document.getElementById('model').value);
      formData.append('part_no', document.getElementById('part_no').value);
      formData.append('planned_output', document.getElementById('planned_output').value);

      const res = await fetch(`../api/${id ? 'update' : 'add'}_performance_param.php`, {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      alert(result.message);
      resetForm();
      fetchParams();
    });

    function editParam(row) {
      document.getElementById('param_id').value = row.id;
      document.getElementById('line').value = row.line;
      document.getElementById('model').value = row.model;
      document.getElementById('part_no').value = row.part_no;
      document.getElementById('planned_output').value = row.planned_output;
    }

    async function deleteParam(id) {
      if (!confirm('Delete this entry?')) return;
      const res = await fetch(`../api/delete_performance_param.php?id=${id}`);
      const result = await res.json();
      alert(result.message);
      fetchParams();
    }

    function resetForm() {
      document.getElementById('parameterForm').reset();
      document.getElementById('param_id').value = '';
    }

    fetchParams();
  </script>
</body>
</html>
