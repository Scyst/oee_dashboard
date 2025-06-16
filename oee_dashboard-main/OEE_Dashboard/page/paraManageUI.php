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
    .sticky-bar {
      position: sticky;
      top: 0;
      background-color: #212529;
      z-index: 999;
      padding: 10px 0;
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
      <div class="col-md-2"><input type="text" class="form-control" id="line" placeholder="Line" required /></div>
      <div class="col-md-2"><input type="text" class="form-control" id="model" placeholder="Model" required /></div>
      <div class="col-md-2"><input type="text" class="form-control" id="partNo" placeholder="Part No." required /></div>
      <div class="col-md-2"><input type="text" class="form-control" id="sapNo" placeholder="SAP No." required /></div>
      <div class="col-md-2"><input type="number" class="form-control" id="plannedOutput" placeholder="Planned Output" required /></div>
      <div class="col-md-2 d-flex gap-1">
        <button type="button" id="addBtn" class="btn btn-success w-100">Add</button>
        <button type="button" id="updateBtn" class="btn btn-warning w-100 d-none">Update</button>
      </div>
    </form>

    <hr>

    <div class="row mb-2 align-items-center sticky-bar">
      <div class="col-md-4">
        <input type="text" class="form-control" id="searchInput" placeholder="Search parameters...">
      </div>
      <div class="col-md-5"></div>
      <div class="col-md-3 text-end">
        <button class="btn btn-sm btn-primary me-2" onclick="triggerImport()">Import</button>
        <button class="btn btn-sm btn-info" onclick="exportToExcel()">Export</button>
      </div>
      <input type="file" id="importFile" accept=".csv, .xlsx, .xls" class="form-control mt-2 d-none" onchange="handleImport(event)">
    </div>

    <div>
      <table class="table table-dark table-striped">
        <thead>
          <tr>
            <th>Line</th><th>Model</th><th>Part No.</th><th>SAP No.</th><th>Planned Output</th><th>Updated At</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="paramTable"></tbody>
      </table>
    </div>

    <nav class="sticky-bottom">
      <ul class="pagination justify-content-center" id="paginationControls"></ul>
    </nav>

    <div id="toast" style="position: fixed; top: 20px; right: 20px; background-color: #333; color: white; padding: 10px 16px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.4s ease, transform 0.4s ease; z-index: 9999; transform: translateY(20px);"></div>
  </div>

  <script>
    let allData = [], currentPage = 1;
    const rowsPerPage = 50;

    function renderTablePage(data, page) {
      const tbody = document.getElementById('paramTable');
      tbody.innerHTML = '';
      const start = (page - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      const pageData = data.slice(start, end);
      pageData.forEach(row => {
        tbody.innerHTML += `
          <tr data-id="${row.id}">
            <td contenteditable="true" data-field="line" onfocus="startEdit(this)" onblur="inlineEdit(this, 'line')">${row.line}</td>
            <td contenteditable="true" data-field="model" onfocus="startEdit(this)" onblur="inlineEdit(this, 'model')">${row.model}</td>
            <td contenteditable="true" data-field="part_no" onfocus="startEdit(this)" onblur="inlineEdit(this, 'part_no')">${row.part_no}</td>
            <td contenteditable="true" data-field="sap_no" onfocus="startEdit(this)" onblur="inlineEdit(this, 'sap_no')">${row.sap_no || ''}</td>
            <td contenteditable="true" data-field="planned_output" onfocus="startEdit(this)" onblur="inlineEdit(this, 'planned_output')">${row.planned_output}</td>
            <td>${new Date(row.updated_at).toLocaleString()}</td>
            <td>
              <button class="btn btn-sm btn-warning" onclick='editParam(${JSON.stringify(row)})'>Edit</button>
              <button class="btn btn-sm btn-danger" onclick='deleteParam(${row.id})'>Delete</button>
            </td>
          </tr>`;
      });
      renderPaginationControls(data.length);
    }

    function renderPaginationControls(totalItems) {
      const totalPages = Math.ceil(totalItems / rowsPerPage);
      const pagination = document.getElementById('paginationControls');
      pagination.innerHTML = '';

      const start = Math.max(1, currentPage - 2);
      const end = Math.min(totalPages, currentPage + 2);

      if (currentPage > 1) {
        pagination.innerHTML += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${currentPage - 1})">Prev</a></li>`;
      }

      for (let i = start; i <= end; i++) {
        pagination.innerHTML += `
          <li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${i})">${i}</a>
          </li>`;
      }

      if (currentPage < totalPages) {
        pagination.innerHTML += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${currentPage + 1})">Next</a></li>`;
      }
    }

    function goToPage(page) {
      currentPage = page;
      filterTable();
    }

    function filterTable() {
      const search = document.getElementById('searchInput').value.toUpperCase();
      const filtered = allData.filter(row =>
        `${row.line} ${row.model} ${row.part_no} ${row.sap_no}`.toUpperCase().includes(search)
      );
      renderTablePage(filtered, currentPage);
    }

    let editingCell = null;

    function startEdit(cell) {
      editingCell = {
        cell,
        field: cell.dataset.field,
        original: cell.innerText.trim()
      };
    }

    async function inlineEdit(cell, field) {
      const row = cell.closest('tr');
      const id = row.getAttribute('data-id');
      const newValue = field === 'planned_output'
        ? parseInt(cell.innerText.trim(), 10)
        : cell.innerText.trim().toUpperCase();

      if (!editingCell || !editingCell.original || !id) return;

      const oldValue = editingCell.original;
      editingCell = null; // reset to avoid loop

      // No change = no update
      if (String(newValue) === String(oldValue)) return;

      const confirmEdit = confirm(`Update "${field}" from "${oldValue}" to "${newValue}"?`);
      if (!confirmEdit) {
        cell.innerText = oldValue;
        return;
      }

      const payload = { id, line: null, model: null, part_no: null, sap_no: null, planned_output: null };
      payload[field] = newValue;

      const res = await fetch(`../api/paraManage/paraManage.php?action=update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await res.json();
      if (result.success) {
        showToast(`Updated ${field} successfully!`);
        loadParameters();
      } else {
        showToast(`Failed to update ${field}.`, '#dc3545');
        cell.innerText = oldValue;
      }
    }

    async function loadParameters() {
      const res = await fetch('../api/paraManage/paraManage.php?action=read');
      allData = await res.json();
      currentPage = 1;
      filterTable();
    }

    function resetForm() {
      document.getElementById('paramForm').reset();
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

    document.getElementById('searchInput').addEventListener('input', () => {
      currentPage = 1;
      filterTable();
    });

    loadParameters();
  </script>

  <script src="../script/auto_logout.js"></script>
  <script src="../script/paraManageUI/export_data.js"></script>
</body>
</html>
