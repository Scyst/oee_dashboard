let allParameters = [], currentPage = 1;
const rowsPerPage = 25;
const API_URL = '../../api/paraManage/paraManage.php';

// ฟังก์ชันนี้ถูกต้องดีแล้ว
async function sendRequest(action, method, body = null, urlParams = {}) {
    try {
        urlParams.action = action;
        const queryString = new URLSearchParams(urlParams).toString();
        const url = `${API_URL}?${queryString}`;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const options = {
            method,
            headers: {},
        };
        if (body) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        if (method.toUpperCase() !== 'GET' && csrfToken) {
            options.headers['X-CSRF-TOKEN'] = csrfToken;
        }
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error(`Request for action '${action}' failed:`, error);
        // showToast จะถูกเรียกจาก toast.js ที่เป็น global
        showToast('An unexpected error occurred.', '#dc3545'); 
        return { success: false, message: "Network or server error." }; // คืนค่า object เสมอ
    }
}

function renderTablePage(data) {
    const tbody = document.getElementById('paramTableBody');
    tbody.innerHTML = '';
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const pageData = data.slice(start, end);

    if (pageData.length === 0) {
        // --- ใช้ตัวแปร global 'canManage' ที่ถูกต้อง ---
        const colSpan = canManage ? 7 : 6;
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center">No parameters found.</td></tr>`;
        return;
    }

    pageData.forEach(row => {
        const tr = document.createElement('tr');
        tr.dataset.id = row.id;
        const createCell = (text) => {
            const td = document.createElement('td');
            td.textContent = text;
            return td;
        };
        tr.appendChild(createCell(row.line));
        tr.appendChild(createCell(row.model));
        tr.appendChild(createCell(row.part_no));
        tr.appendChild(createCell(row.sap_no || ''));
        tr.appendChild(createCell(row.planned_output));
        tr.appendChild(createCell(row.updated_at));
        
        if (canManage) {
            const actionsTd = document.createElement('td');
            const buttonWrapper = document.createElement('div');
            buttonWrapper.className = 'd-flex gap-1';
            const editButton = document.createElement('button');
            editButton.className = 'btn btn-sm btn-warning w-100';
            editButton.textContent = 'Edit';
            editButton.addEventListener('click', () => editParameter(row));
            const deleteButton = document.createElement('button');
            deleteButton.className = 'btn btn-sm btn-danger w-100';
            deleteButton.textContent = 'Delete';
            deleteButton.addEventListener('click', () => deleteParameter(row.id));
            buttonWrapper.appendChild(editButton);
            buttonWrapper.appendChild(deleteButton);
            actionsTd.appendChild(buttonWrapper);
            tr.appendChild(actionsTd);
        }
        tbody.appendChild(tr);
    });
}

function renderPaginationControls(totalItems) {
    const totalPages = Math.ceil(totalItems / rowsPerPage);
    const pagination = document.getElementById('paginationControls');
    pagination.innerHTML = '';
    if (totalPages <= 1) return;
    const createPageItem = (page, text = page, isDisabled = false, isActive = false) => {
        const li = document.createElement('li');
        li.className = `page-item ${isDisabled ? 'disabled' : ''} ${isActive ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = text;
        a.onclick = (e) => { e.preventDefault(); if (!isDisabled) goToPage(page); };
        li.appendChild(a);
        return li;
    };
    pagination.appendChild(createPageItem(currentPage - 1, 'Prev', currentPage <= 1));
    for (let i = 1; i <= totalPages; i++) { pagination.appendChild(createPageItem(i, i, false, i === currentPage)); }
    pagination.appendChild(createPageItem(currentPage + 1, 'Next', currentPage >= totalPages));
}

function goToPage(page) {
    currentPage = page;
    filterAndRenderTable();
}

function getFilteredData() {
    const searchTerm = document.getElementById('searchInput').value.toUpperCase();
    if (!searchTerm) return allParameters;
    return allParameters.filter(row => `${row.line} ${row.model} ${row.part_no} ${row.sap_no}`.toUpperCase().includes(searchTerm));
}

function filterAndRenderTable() {
    const filteredData = getFilteredData();
    renderTablePage(filteredData);
    renderPaginationControls(filteredData.length);
}

function editParameter(data) {
    openModal('editParamModal');
    for (const key in data) {
        const input = document.getElementById('editParamModal').querySelector(`#edit_${key}`);
        if (input) input.value = data[key];
    }
}

async function deleteParameter(id) {
    if (!confirm(`Delete parameter ID ${id}?`)) return;
    const result = await sendRequest('delete', 'GET', null, { id: id });
    showToast(result.message || 'Error', !result.success ? '#dc3545' : '#28a745');
    if (result.success) {
        loadParameters();
    }
}

async function loadParameters() {
    const result = await sendRequest('read', 'GET');
    if (result && result.success) {
        allParameters = result.data;
        filterAndRenderTable(); 
    } else {
        showToast(result?.message || 'Failed to load parameters.', '#dc3545');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadParameters(); 
    document.getElementById('searchInput')?.addEventListener('input', () => {
        currentPage = 1;
        filterAndRenderTable();
    });
});