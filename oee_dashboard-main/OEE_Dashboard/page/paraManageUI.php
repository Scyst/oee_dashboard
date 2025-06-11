<?php include_once("../auth/check_auth.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Parameter Manager</title>
  <script src="../utils/libs/xlsx.full.min.js"></script>
  <script src="../utils/libs/bootstrap.bundle.min.js"></script>
  <style>
    input[type="text"] {
      text-transform: uppercase;
    }
  </style>

  <link rel="stylesheet" href="../utils/libs/bootstrap.min.css">
  <link rel="stylesheet" href="../style/dropdown.css">
  <link rel="stylesheet" href="../style/style.css">
</head>

<body class="bg-dark text-white p-4">
  <?php include('components/nav_dropdown.php'); ?>
  
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Parameter Manager</h2>
    </div>

    <form id="paramForm" class="row g-3">
      <input type="hidden" id="paramId" />
      <div class="col-md-3">
        <input type="text" class="form-control" id="line" placeholder="Line" required />
      </div>
      <div class="col-md-3">
        <input type="text" class="form-control" id="model" placeholder="Model" required />
      </div>
      <div class="col-md-3">
        <input type="text" class="form-control" id="partNo" placeholder="Part No." required />
      </div>
      <div class="col-md-2">
        <input type="number" class="form-control" id="plannedOutput" placeholder="Planned Output" required />
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-success w-100">Save</button>
      </div>
    </form>

    <hr>
    
    
    <div class="row mb-2 align-items-center">
      <!-- Search box on the left -->
      <div class="col-md-3">
        <input type="text" class="form-control" id="searchInput" placeholder="Search parameters...">
      </div>

      <div class="col-md-6 text-end"></div>

      <!-- Import/Export buttons on the right -->
      <div class="col-md-3 text-end">
        <button class="btn btn-sm btn-primary me-2" onclick="triggerImport()">Import</button>
        <button class="btn btn-sm btn-success" onclick="exportToExcel()">Export</button>
      </div>

      <!-- Hidden file input for import -->
      <input type="file" id="importFile" accept=".csv, .xlsx, .xls" class="form-control mt-2 d-none" onchange="handleImport(event)">
    </div>

    <div>
      <table class="table table-dark table-striped">
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

  </div>

  <script>
    let allData = [];

    async function loadParameters() {
      const res = await fetch('../api/paraManage/paraManage.php?action=read');
      const data = await res.json();
      allData = data; // ✅ Store globally for export

      const tbody = document.getElementById('paramTable');
      tbody.innerHTML = '';

      data.forEach(row => {
        tbody.innerHTML += `
          <tr>
            <td>${row.line}</td>
            <td>${row.model}</td>
            <td>${row.part_no}</td>
            <td>${Number(row.planned_output).toLocaleString()}</td>
            <td>${new Date(row.updated_at).toLocaleString()}</td>
            <td>
              <button class="btn btn-sm btn-warning" onclick='editParam(${JSON.stringify(row)})'>Edit</button>
              <button class="btn btn-sm btn-danger" onclick='deleteParam(${row.id})'>Delete</button>
            </td>
          </tr>`;
      });

      filterTable(); // Apply search filter after loading
    }

    // Filter table rows based on search input
    function filterTable() {
      const search = document.getElementById('searchInput').value.toLowerCase();
      const rows = document.querySelectorAll('#paramTable tr');

      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(search) ? '' : 'none';
      });
    }

    function editParam(data) {
      document.getElementById('paramId').value = data.id;
      document.getElementById('line').value = data.line;
      document.getElementById('model').value = data.model;
      document.getElementById('partNo').value = data.part_no;
      document.getElementById('plannedOutput').value = data.planned_output;
    }

    async function deleteParam(id) {
      if (!confirm("Delete this parameter?")) return;
      await fetch(`../api/paraManage/paraManage.php?action=delete&id=${id}`);
      loadParameters();
    }

    document.getElementById('paramForm').addEventListener('submit', async e => {
      e.preventDefault();
      const form = e.target;
      const id = document.getElementById('paramId').value;

      const payload = {
        id,
        line: form.line.value.trim().toUpperCase(),
        model: form.model.value.trim().toUpperCase(),
        part_no: form.partNo.value.trim().toUpperCase(),
        planned_output: parseInt(form.plannedOutput.value, 10)
      };

      const action = id ? 'update' : 'create';
      const res = await fetch(`../api/paraManage/paraManage.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await res.json();
      if (result.success) {
        if (!id) {
          // Only reset if it's a new entry
          form.plannedOutput.value = '';
        }
        await loadParameters();
      } else {
        alert("Failed to save parameter. Make sure the combination is unique.");
      }
    });

    document.getElementById('searchInput').addEventListener('input', filterTable);

    loadParameters();
  </script>

  <script>
    
   
  </script>

  <script src="../script/auto_logout.js"></script>
  <script src="../script/paraManage/export_data.js"></script>

</body>
</html>
