let currentPage = 1;
let totalPages = 1;
const API_URL = '../../api/Stop_Cause/stopCauseManage.php';

async function fetchStopData(page = 1) {
    currentPage = page;
    const filters = {
        cause: document.getElementById('filterCause')?.value,
        line: document.getElementById('filterLine')?.value,
        machine: document.getElementById('filterMachine')?.value,
        startDate: document.getElementById('filterStartDate')?.value,
        endDate: document.getElementById('filterEndDate')?.value,
    };
    const params = new URLSearchParams({ action: 'get_stop', page: currentPage, limit: 50, ...filters });

    try {
        const response = await fetch(`${API_URL}?${params.toString()}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        renderTable(result.data);
        renderPagination(result.page, result.total, result.limit);
        renderSummary(result.summary, result.grand_total_minutes);
    } catch (error) {
        console.error('Failed to fetch stop data:', error);
        document.getElementById('stopTableBody').innerHTML = `<tr><td colspan="11" class="text-center text-danger">Error loading data.</td></tr>`;
    }
}

function renderTable(data) {
    const tbody = document.getElementById('stopTableBody');
    tbody.innerHTML = '';
    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center">No records found.</td></tr>`;
        return;
    }
    tbody.innerHTML = data.map(row => `
        <tr data-id="${row.id}">
            <td class="text-center">${row.id}</td><td>${row.log_date}</td><td>${row.stop_begin}</td>
            <td>${row.stop_end}</td><td class="text-center">${row.duration}</td><td>${row.line}</td>
            <td>${row.machine}</td><td>${row.cause}</td><td>${row.recovered_by}</td>
            <td><div class="note-truncate" title="${row.note || ''}">${row.note || ''}</div></td>
            <td class="text-center">
                <button class="btn btn-sm btn-warning" onclick='openEditModal(${row.id})'>Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteStop(${row.id})">Delete</button>
            </td>
        </tr>`).join('');
}

function renderPagination(page, totalItems, limit) {
    totalPages = totalItems > 0 ? Math.ceil(totalItems / limit) : 1;
    currentPage = parseInt(page);
    document.getElementById('pagination-info').textContent = `Page ${currentPage} of ${totalPages}`;
    document.getElementById('prevPageBtn').disabled = currentPage <= 1;
    document.getElementById('nextPageBtn').disabled = currentPage >= totalPages;
}

function renderSummary(summaryData, grandTotalMinutes) {
    const summaryContainer = document.getElementById('causeSummary');
    if (!summaryContainer) return;
    const formatMins = (mins) => `${Math.floor(mins / 60)}h ${mins % 60}m`;
    let summaryHTML = `<strong>Total Downtime: ${formatMins(grandTotalMinutes || 0)}</strong>`;
    if (summaryData && summaryData.length > 0) {
        summaryHTML += ' | ' + summaryData.map(item => `${item.line}: ${item.count} stops (${formatMins(item.total_minutes)})`).join(' | ');
    }
    summaryContainer.innerHTML = summaryHTML;
}

async function populateDatalist(datalistId, action) {
    try {
        const response = await fetch(`${API_URL}?action=${action}`);
        const result = await response.json();
        if (result.success) {
            const datalist = document.getElementById(datalistId);
            if (datalist) datalist.innerHTML = result.data.map(item => `<option value="${item}"></option>`).join('');
        }
    } catch (error) {
        console.error(`Failed to populate ${datalistId}:`, error);
    }
}

async function deleteStop(id) {
    if (!confirm(`Are you sure you want to delete Stop Cause ID ${id}?`)) return;
    try {
        const response = await fetch(`${API_URL}?action=delete_stop&id=${id}`);
        const result = await response.json();
        alert(result.message);
        if (result.success) fetchStopData(currentPage);
    } catch (error) {
        alert('An error occurred while deleting.');
    }
}

function handleFilterChange() {
    fetchStopData(1);
}

document.addEventListener('DOMContentLoaded', () => {
    const filterInputs = ['filterCause', 'filterLine', 'filterMachine', 'filterStartDate', 'filterEndDate'];
    filterInputs.forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => {
            clearTimeout(window.filterDebounceTimer);
            window.filterDebounceTimer = setTimeout(handleFilterChange, 500);
        });
    });

    document.getElementById('prevPageBtn')?.addEventListener('click', () => {
        if (currentPage > 1) fetchStopData(currentPage - 1);
    });

    document.getElementById('nextPageBtn')?.addEventListener('click', () => {
        if (currentPage < totalPages) fetchStopData(currentPage + 1);
    });

    populateDatalist('causeListFilter', 'get_causes');
    populateDatalist('lineListFilter', 'get_lines');
    populateDatalist('machineListFilter', 'get_machines');
    
    fetchStopData(1);
});