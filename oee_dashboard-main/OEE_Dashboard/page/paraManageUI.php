<?php include_once("../auth/check_auth.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Parameter Manager</title>
  <script src="../utils/libs/xlsx.full.min.js"></script>
  <script src="../utils/libs/bootstrap.bundle.min.js"></script>

  <link rel="stylesheet" href="../utils/libs/bootstrap.min.css">
  <link rel="stylesheet" href="../style/dropdown.css">
  <style>
    input[type="text"] {
        text-transform: uppercase;
    }
  </style>
</head>

<body class="bg-dark text-white p-4">
  <?php include('components/nav_dropdown.php'); ?>
  
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Parameter Manager</h2>
    </div>

    <form id="paramForm" class="row g-3 align-items-center">
      <input type="hidden" id="paramId" />
      <div class="col-md-2">
        <input type="text" class="form-control" id="line" placeholder="Line" required />
      </div>
      <div class="col-md-2">
        <input type="text" class="form-control" id="model" placeholder="Model" required />
      </div>
      <div class="col-md-2">
        <input type="text" class="form-control" id="partNo" placeholder="Part No." required />
      </div>
      <div class="col-md-2">
        <input type="text" class="form-control" id="sapNo" placeholder="SAP No." required />
      </div>
      <div class="col-md-2">
        <input type="number" class="form-control" id="plannedOutput" placeholder="Planned Output" required />
      </div>
      <div class="col-md-1"></div>
      <div class="col-md-1 d-flex gap-1">
        <button type="button" id="addBtn" class="btn btn-success w-100">Add</button>
        <button type="button" id="updateBtn" class="btn btn-warning w-100 d-none">Update</button>
      </div>
    </form>

    <hr>    
    
    <div class="row mb-2 align-items-center">

      <div class="col-md-3">
        <input type="text" class="form-control" id="searchInput" placeholder="Search parameters...">
      </div>

      <div class="col-md-6 text-end"></div>

      <div class="col-md-3 text-end">
        <button class="btn btn-sm btn-primary me-2" style="padding: 6px 12px;" onclick="triggerImport()">Import</button>
        <button class="btn btn-sm btn-info" style="padding: 6px 12px;" onclick="exportToExcel()">Export</button>
      </div>

      <input type="file" id="importFile" accept=".csv, .xlsx, .xls" class="form-control mt-2 d-none" onchange="handleImport(event)">
    </div>

    <div>
      <table class="table table-dark table-striped">
        <thead>
          <tr>
            <th>Line</th>
            <th>Model</th>
            <th>Part No.</th>
            <th>SAP No.</th>
            <th>Planned Output</th>
            <th>Updated At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="paramTable"></tbody>
      </table>
    </div>
    <div id="toast" style="
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
    "></div>

  </div>

  <script>
    let allData = [];

    async function loadParameters() {
      const res = await fetch('../api/paraManage/paraManage.php?action=read');
      const data = await res.json();
      allData = data;

      const tbody = document.getElementById('paramTable');
      tbody.innerHTML = '';

      data.forEach(row => {
        tbody.innerHTML += `
          <tr>
            <td>${row.line}</td>
            <td>${row.model}</td>
            <td>${row.part_no}</td>
            <td>${row.sap_no || ''}</td>
            <td>${Number(row.planned_output).toLocaleString()}</td>
            <td>${new Date(row.updated_at).toLocaleString()}</td>
            <td>
              <button class="btn btn-sm btn-warning" onclick='editParam(${JSON.stringify(row)})'>Edit</button>
              <button class="btn btn-sm btn-danger" onclick='deleteParam(${row.id})'>Delete</button>
            </td>
          </tr>`;
      });

      filterTable();
    }

    // Filter table rows based on search input
    function filterTable() {
      const search = document.getElementById('searchInput').value.toUpperCase();
      const rows = document.querySelectorAll('#paramTable tr');

      rows.forEach(row => {
        const text = row.innerText.toUpperCase();
        row.style.display = text.includes(search) ? '' : 'none';
      });
    }

    function resetForm() {
      const form = document.getElementById('paramForm');
      form.reset();
      document.getElementById('paramId').value = '';
      document.getElementById('addBtn').classList.remove('d-none');
      document.getElementById('updateBtn').classList.add('d-none');
    }

    function editParam(data) {
      document.getElementById('paramId').value = data.id;
      document.getElementById('line').value = data.line;
      document.getElementById('model').value = data.model;
      document.getElementById('partNo').value = data.part_no;
      document.getElementById('sapNo').value = data.sap_no || '';
      document.getElementById('plannedOutput').value = data.planned_output;

      // Toggle buttons
      document.getElementById('addBtn').classList.add('d-none');
      document.getElementById('updateBtn').classList.remove('d-none');
    }

    function showToast(message, color = '#28a745') {
      const toast = document.getElementById('toast');
      toast.innerText = message;
      toast.style.backgroundColor = color;
      toast.style.opacity = 1;
      toast.style.transform = 'translateY(0)';
      
      setTimeout(() => {
        toast.style.opacity = 0;
        toast.style.transform = 'translateY(20px)';
      }, 3000);
    }

    async function deleteParam(id) {
    if (!confirm("Delete this parameter?")) return;
      await fetch(`../api/paraManage/paraManage.php?action=delete&id=${id}`);
      loadParameters();
      resetForm();
      showToast("Parameter deleted successfully!");
    }
    
    document.getElementById('addBtn').addEventListener('click', async () => {
      const form = document.getElementById('paramForm');

      const payload = {
        line: form.line.value.trim().toUpperCase(),
        model: form.model.value.trim().toUpperCase(),
        part_no: form.partNo.value.trim().toUpperCase(),
        sap_no: form.sapNo.value.trim().toUpperCase(),
        planned_output: parseInt(form.plannedOutput.value, 10)
      };

      const res = await fetch(`../api/paraManage/paraManage.php?action=create`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await res.json();
      if (result.success) {
        loadParameters();
        showToast("Parameter added successfully!");
      } else {
        showToast("Failed to create parameter.", "#dc3545");
      }
    });

    document.getElementById('updateBtn').addEventListener('click', async () => {
      const form = document.getElementById('paramForm');
      const id = document.getElementById('paramId').value;

      const payload = {
        id,
        line: form.line.value.trim().toUpperCase(),
        model: form.model.value.trim().toUpperCase(),
        part_no: form.partNo.value.trim().toUpperCase(),
        sap_no: form.sapNo.value.trim().toUpperCase(),
        planned_output: parseInt(form.plannedOutput.value, 10)
      };

      const res = await fetch(`../api/paraManage/paraManage.php?action=update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await res.json();
      if (result.success) {
        loadParameters();
        resetForm();
        showToast("Parameter updated successfully!");
      } else {
        showToast("Failed to update parameter.", "#dc3545");
      }
    });

    document.getElementById('searchInput').addEventListener('input', filterTable);
    loadParameters();
  </script>

  <script src="../script/auto_logout.js"></script>
  <script src="../script/paraManageUI/export_data.js"></script>

</body>
</html>
