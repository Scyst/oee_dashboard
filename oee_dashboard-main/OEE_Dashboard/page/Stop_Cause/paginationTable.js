// --- Global State & Constants ---
let currentPage = 1;
let totalPages = 1;
const API_URL = '../../api/Stop_Cause/stopCauseManage.php';

// --- Main Data Fetching Function ---
async function fetchStopData(page = 1) {
    currentPage = page;
    const filters = {
        cause: document.getElementById('filterCause')?.value,
        line: document.getElementById('filterLine')?.value,
        machine: document.getElementById('filterMachine')?.value,
        startDate: document.getElementById('filterStartDate')?.value,
        endDate: document.getElementById('filterEndDate')?.value,
    };

    const params = new URLSearchParams({
        action: 'get_stop',
        page: currentPage,
        limit: 50,
        ...filters
    });

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

// --- Rendering Functions ---
function renderTable(data) {
    const tbody = document.getElementById('stopTableBody');
    tbody.innerHTML = '';
    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center">No records found.</td></tr>`;
        return;
    }
    tbody.innerHTML = data.map(row => `
        <tr data-id="${row.id}">
            <td class="text-center">${row.id}</td>
            <td>${row.log_date}</td>
            <td>${row.stop_begin}</td>
            <td>${row.stop_end}</td>
            <td class="text-center">${row.duration}</td>
            <td>${row.line}</td>
            <td>${row.machine}</td>
            <td>${row.cause}</td>
            <td>${row.recovered_by}</td>
            <td><div class="note-truncate" title="${row.note || ''}">${row.note || ''}</div></td>
            <td class="text-center">
                <button class="btn btn-sm btn-warning" onclick='openEditModal(${row.id})'>Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteStop(${row.id})">Delete</button>
            </td>
        </tr>
    `).join('');
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
    let summaryHTML = `<strong>Total Downtime: ${formatMins(grandTotalMinutes)}</strong> | `;
    summaryHTML += summaryData.map(item => `${item.line}: ${item.count} stops (${formatMins(item.total_minutes)})`).join(' | ');
    summaryContainer.innerHTML = summaryHTML;
}

// --- Datalist & Action Handlers ---
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

async function openEditModal(id) {
    try {
        const response = await fetch(`${API_URL}?action=get_stop_by_id&id=${id}`);
        const result = await response.json();
        if (result.success) {
            const data = result.data;
            const modal = document.getElementById('editStopModal');
            for (const key in data) {
                const input = modal.querySelector(`#edit_${key}`);
                if (input) input.value = data[key];
            }
            modal.style.display = 'block';
        } else {
            alert(result.message);
        }
    } catch (error) {
        alert('Failed to fetch details for editing.');
    }
}

function handleFilterChange() {
    fetchStopData(1);
}

// --- Initial Setup ---
document.addEventListener('DOMContentLoaded', () => {
    // Attach event listeners to filter inputs
    const filterInputs = ['filterCause', 'filterLine', 'filterMachine', 'filterStartDate', 'filterEndDate'];
    filterInputs.forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => {
            clearTimeout(window.filterDebounceTimer);
            window.filterDebounceTimer = setTimeout(handleFilterChange, 500);
        });
    });

    // Pagination buttons
    document.getElementById('prevPageBtn')?.addEventListener('click', () => {
        if (currentPage > 1) fetchStopData(currentPage - 1);
    });
    document.getElementById('nextPageBtn')?.addEventListener('click', () => {
        if (currentPage < totalPages) fetchStopData(currentPage + 1);
    });

    // Populate datalists on page load
    populateDatalist('causeListFilter', 'get_causes');
    populateDatalist('lineListFilter', 'get_lines');
    populateDatalist('machineListFilter', 'get_machines');
    
    // Initial data load
    fetchStopData(1);
    
    // Add/Edit form submission handlers
    const addForm = document.getElementById('addStopForm');
    if(addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(addForm);
            const payload = Object.fromEntries(formData.entries());
            try {
                const response = await fetch(`${API_URL}?action=add_stop`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                alert(result.message);
                if(result.success) {
                    closeModal('stopModal');
                    addForm.reset();
                    fetchStopData(1);
                }
            } catch(error) {
                alert('An error occurred while adding data.');
            }
        });
    }

    const editForm = document.getElementById('editStopForm');
    if(editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(editForm);
            const payload = Object.fromEntries(formData.entries());
            try {
                const response = await fetch(`${API_URL}?action=update_stop`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                alert(result.message);
                if(result.success) {
                    closeModal('editStopModal');
                    fetchStopData(currentPage);
                }
            } catch(error) {
                alert('An error occurred while updating data.');
            }
        });
    }
});