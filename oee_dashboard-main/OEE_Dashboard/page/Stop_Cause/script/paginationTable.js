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
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.dataset.id = row.id;

        const createCell = (text) => { const td = document.createElement('td'); td.textContent = text; return td; };
        const createCenteredCell = (text) => { const td = createCell(text); td.className = 'text-center'; return td; };

        tr.appendChild(createCenteredCell(row.id));
        tr.appendChild(createCell(row.log_date));
        tr.appendChild(createCell(row.stop_begin));
        tr.appendChild(createCell(row.stop_end));
        tr.appendChild(createCenteredCell(row.duration));
        tr.appendChild(createCell(row.line));
        tr.appendChild(createCell(row.machine));
        tr.appendChild(createCell(row.cause));
        tr.appendChild(createCell(row.recovered_by));

        const noteTd = document.createElement('td');
        const noteDiv = document.createElement('div');
        noteDiv.className = 'note-truncate';
        noteDiv.title = row.note || '';
        noteDiv.textContent = row.note || '';
        noteTd.appendChild(noteDiv);
        tr.appendChild(noteTd);
        
        if (canManage) {
            const actionsTd = document.createElement('td');
            actionsTd.className = 'text-center';
            
            const editButton = document.createElement('button');
            editButton.className = 'btn btn-sm btn-warning';
            editButton.textContent = 'Edit';
            editButton.addEventListener('click', () => openEditModal(row.id));
            
            const deleteButton = document.createElement('button');
            deleteButton.className = 'btn btn-sm btn-danger';
            deleteButton.textContent = 'Delete';
            deleteButton.addEventListener('click', () => deleteStop(row.id));

            actionsTd.appendChild(editButton);
            actionsTd.appendChild(deleteButton);
            tr.appendChild(actionsTd);
        }

        tbody.appendChild(tr);
    });
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
    summaryContainer.innerHTML = ''; // Clear previous content

    const formatMins = (mins) => `${Math.floor(mins / 60)}h ${mins % 60}m`;

    const strong = document.createElement('strong');
    strong.textContent = `Total Downtime: ${formatMins(grandTotalMinutes || 0)}`;
    summaryContainer.appendChild(strong);

    if (summaryData && summaryData.length > 0) {
        summaryData.forEach(item => {
            summaryContainer.appendChild(document.createTextNode(' | '));
            const summaryText = `${item.line}: ${item.count} stops (${formatMins(item.total_minutes)})`;
            summaryContainer.appendChild(document.createTextNode(summaryText));
        });
    }
}

async function populateDatalist(datalistId, action) {
    try {
        const response = await fetch(`${API_URL}?action=${action}`);
        const result = await response.json();
        if (result.success) {
            const datalist = document.getElementById(datalistId);
            if (datalist) {
                datalist.innerHTML = ''; // Clear existing options
                result.data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item;
                    datalist.appendChild(option);
                });
            }
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
        showToast(result.message, result.success ? '#28a745' : '#dc3545');
        if (result.success) fetchStopData(currentPage);
    } catch (error) {
        showToast('An error occurred while deleting.', '#dc3545');
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