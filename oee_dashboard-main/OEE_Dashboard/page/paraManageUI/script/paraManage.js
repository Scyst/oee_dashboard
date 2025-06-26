"use strict";

const API_ENDPOINT = '../../api/paraManage/paraManage.php';
const ROWS_PER_PAGE = 25;

let allStandardParams = [], allSchedules = [];
let currentPage = 1;

async function sendRequest(action, method, body = null, urlParams = {}) {
    try {
        urlParams.action = action;
        const queryString = new URLSearchParams(urlParams).toString();
        const url = `${API_ENDPOINT}?${queryString}`;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        const options = { method, headers: {} };
        if (method.toUpperCase() !== 'GET' && csrfToken) {
            options.headers['X-CSRF-TOKEN'] = csrfToken;
        }
        if (body) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
        
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error(`Request for action '${action}' failed:`, error);
        showToast('An unexpected error occurred.', '#dc3545');
        return { success: false, message: "Network or server error." };
    }
}

/**
 * 
 * @param {string} modalId
 * @param {object} data
 */
function openEditModal(modalId, data) {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) return;

    for (const key in data) {
        const input = modalElement.querySelector(`[name="${key}"]`);
        if (input) {
            if (input.type === 'checkbox') {
                input.checked = !!parseInt(data[key]);
            } else {
                input.value = data[key];
            }
        }
    }
    openModal(modalId);
}


function renderPagination(containerId, totalItems, currentPage, callback) {
    const totalPages = Math.ceil(totalItems / ROWS_PER_PAGE);
    const pagination = document.getElementById(containerId);
    pagination.innerHTML = '';
    if (totalPages <= 1) return;

    const createPageItem = (page, text, isDisabled) => {
        const li = document.createElement('li');
        li.className = `page-item ${isDisabled ? 'disabled' : ''} ${page === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${text}</a>`;
        li.querySelector('a').onclick = (e) => { e.preventDefault(); if (!isDisabled) callback(page); };
        return li;
    };

    pagination.appendChild(createPageItem(currentPage - 1, 'Prev', currentPage <= 1));
    for (let i = 1; i <= totalPages; i++) {
        pagination.appendChild(createPageItem(i, i, false));
    }
    pagination.appendChild(createPageItem(currentPage + 1, 'Next', currentPage >= totalPages));
}

async function loadStandardParams() {
    const result = await sendRequest('read', 'GET');
    if (result?.success) {
        allStandardParams = result.data;
        filterAndRenderStandardParams();
    } else {
        showToast(result?.message || 'Failed to load parameters.', '#dc3545');
    }
}

function renderStandardParamsTable() {
    const searchTerm = document.getElementById('searchInput').value.toUpperCase();
    const filteredData = searchTerm 
        ? allStandardParams.filter(row => `${row.line || ''} ${row.model || ''} ${row.part_no || ''} ${row.sap_no || ''}`.toUpperCase().includes(searchTerm))
        : allStandardParams;
    
    const tbody = document.getElementById('paramTableBody');
    tbody.innerHTML = '';
    const start = (currentPage - 1) * ROWS_PER_PAGE;
    const pageData = filteredData.slice(start, start + ROWS_PER_PAGE);

    if (pageData.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${canManage ? 7 : 6}" class="text-center">No parameters found.</td></tr>`;
        renderPagination('paginationControls', 0, 1, goToStandardParamPage);
        return;
    }

    pageData.forEach(row => {
        const tr = document.createElement('tr');
        tr.dataset.id = row.id;
        tr.innerHTML = `
            <td>${row.line || ''}</td>
            <td>${row.model || ''}</td>
            <td>${row.part_no || ''}</td>
            <td>${row.sap_no || ''}</td>
            <td>${row.planned_output || ''}</td>
            <td>${row.updated_at || ''}</td>
            ${canManage ? `
            <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                    <button class="btn btn-sm btn-warning" onclick='openEditModal("editParamModal", ${JSON.stringify(row)})'>Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteStandardParam(${row.id})">Delete</button>
                </div>
            </td>` : ''}
        `;
        tbody.appendChild(tr);
    });
    
    renderPagination('paginationControls', filteredData.length, currentPage, goToStandardParamPage);
}

function filterAndRenderStandardParams() {
    currentPage = 1;
    renderStandardParamsTable();
}

function goToStandardParamPage(page) {
    currentPage = page;
    renderStandardParamsTable();
}

async function deleteStandardParam(id) {
    if (!confirm(`Are you sure you want to delete parameter ID ${id}?`)) return;
    const result = await sendRequest('delete', 'DELETE', { id: id }); 
    showToast(result.message, result.success ? '#28a745' : '#dc3545');
    if (result.success) loadStandardParams();
}

async function loadSchedules() {
    const result = await sendRequest('read_schedules', 'GET');
    if (result?.success) {
        allSchedules = result.data;
        renderSchedulesTable();
    } else {
        showToast(result?.message || 'Failed to load schedules.', '#dc3545');
    }
}

function renderSchedulesTable() {
    const tbody = document.getElementById('schedulesTableBody');
    tbody.innerHTML = '';

    if (allSchedules.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${canManage ? 7 : 6}" class="text-center">No schedules found.</td></tr>`;
        return;
    }

    allSchedules.forEach(schedule => {
        const tr = document.createElement('tr');
        tr.dataset.id = schedule.id;
        tr.innerHTML = `
            <td>${schedule.line || ''}</td>
            <td>${schedule.shift_name || ''}</td>
            <td>${schedule.start_time || ''}</td>
            <td>${schedule.end_time || ''}</td>
            <td>${schedule.planned_break_minutes || ''}</td>
            <td><span class="badge ${schedule.is_active ? 'bg-success' : 'bg-secondary'}">${schedule.is_active ? 'Active' : 'Inactive'}</span></td>
            ${canManage ? `
            <td class="text-center">
                <div class="d-flex gap-1 justify-content-center">
                    <button class="btn btn-sm btn-warning" onclick='openEditModal("editScheduleModal", ${JSON.stringify(schedule)})'>Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteSchedule(${schedule.id})">Delete</button>
                </div>
            </td>` : ''}
        `;
        tbody.appendChild(tr);
    });
}

async function deleteSchedule(id) {
    if (!confirm(`Are you sure you want to delete schedule ID ${id}?`)) return;
    const result = await sendRequest('delete_schedule', 'DELETE', { id: id });
    showToast(result.message, result.success ? '#28a745' : '#dc3545');
    if (result.success) loadSchedules();
}

async function loadHealthCheckData(page = 1) {
    const result = await sendRequest('health_check_parameters', 'GET', null, { page: page });
    const listBody = document.getElementById('missingParamsList');
    listBody.innerHTML = '';
    
    if (result?.success) {
        const pageData = result.data;
        const totalRecords = result.totalRecords;

        if (pageData && pageData.length > 0) {
            pageData.forEach(item => {
                listBody.innerHTML += `<tr><td>${item.line}</td><td>${item.model}</td><td>${item.part_no}</td></tr>`;
            });
        } else {
            listBody.innerHTML = `<tr><td colspan="3" class="text-success">Excellent! No missing data found.</td></tr>`;
        }
        
        renderPagination('healthCheckPaginationControls', totalRecords, page, goToHealthCheckPage);
    } else {
        listBody.innerHTML = `<tr><td colspan="3" class="text-danger">Failed to load data.</td></tr>`;
        renderPagination('healthCheckPaginationControls', 0, 1, goToHealthCheckPage);
    }
}

function goToHealthCheckPage(page) {
    loadHealthCheckData(page);
}

document.addEventListener('DOMContentLoaded', () => {
    loadStandardParams();
    document.getElementById('searchInput')?.addEventListener('input', filterAndRenderStandardParams);

    const tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabElms.forEach(tabElm => {
        tabElm.addEventListener('shown.bs.tab', event => {
            const targetTabId = event.target.getAttribute('data-bs-target');
            
            if (targetTabId === '#standardParamsPane') {
                loadStandardParams();
            } else if (targetTabId === '#lineSchedulesPane') {
                loadSchedules();
            } else if (targetTabId === '#healthCheckPane') {
                loadHealthCheckData(1);
            }
        });
    });
});
