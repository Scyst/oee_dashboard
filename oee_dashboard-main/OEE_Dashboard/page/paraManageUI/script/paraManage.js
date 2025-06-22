let allParameters = [], currentPage = 1;
const rowsPerPage = 25;
const API_URL = '../../api/paraManage/paraManage.php';

function showToast(message, color = '#28a745') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = message;
    toast.style.backgroundColor = color;
    toast.style.opacity = 1;
    toast.style.transform = 'translateY(0)';
    setTimeout(() => {
        toast.style.opacity = 0;
        toast.style.transform = 'translateY(20px)';
    }, 3000);
}

async function sendRequest(action, method, body = null, urlParams = {}) {
    try {
        urlParams.action = action;
        const queryString = new URLSearchParams(urlParams).toString();
        const url = `${API_URL}?${queryString}`;

        const options = {
            method,
            headers: {},
        };
        if (body) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        
        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error(`Request for action '${action}' failed:`, error);
        showToast('An unexpected error occurred.', '#dc3545');
        return { success: false };
    }
}

function renderTablePage(data) {
    const tbody = document.getElementById('paramTableBody');
    tbody.innerHTML = '';
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const pageData = data.slice(start, end);

    if (pageData.length === 0) {
        const colSpan = isAdmin ? 7 : 6;
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center">No parameters found.</td></tr>`;
        return;
    }

    pageData.forEach(row => {
        const actionsHTML = isAdmin ? `
        <td>
        <button class="btn btn-sm btn-warning" onclick='editParameter(${JSON.stringify(row)})'>Edit</button>
        <button class="btn btn-sm btn-danger" onclick='deleteParameter(${row.id})'>Delete</button>
        </td>` : '';
        tbody.innerHTML += `<tr data-id="${row.id}"><td>${row.line}</td><td>${row.model}</td><td>${row.part_no}</td><td>${row.sap_no || ''}</td><td>${row.planned_output}</td><td>${row.updated_at}</td>${actionsHTML}</tr>`;
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
        const a = document.createElement('a'); a.className = 'page-link'; a.href = '#'; a.textContent = text;
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
    const modal = document.getElementById('editParamModal');
    if (!modal) return;
    for (const key in data) {
        const input = modal.querySelector(`#edit_${key}`);
        if (input) input.value = data[key];
    }
    openModal('editParamModal');
}

async function deleteParameter(id) {
    if (!confirm(`Delete parameter ID ${id}?`)) return;
    // --- START: การแก้ไขที่สำคัญ ---
    const result = await sendRequest('delete', 'GET', null, { id: id });
    // --- END: การแก้ไขที่สำคัญ ---
    if (result.success) {
        showToast('Parameter deleted successfully!');
        loadParameters();
    } else {
        showToast(result.message || 'Failed to delete parameter.', '#dc3545');
    }
}

async function loadParameters() {
    const result = await sendRequest('read', 'GET');
    if (result.success) {
        allParameters = result.data;
        filterAndRenderTable();
    } else {
        showToast(result.message || 'Failed to load parameters.', '#dc3545');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadParameters();
    document.getElementById('searchInput')?.addEventListener('input', () => {
        currentPage = 1;
        filterAndRenderTable();
    });
});