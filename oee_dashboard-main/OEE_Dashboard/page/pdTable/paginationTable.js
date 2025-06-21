// --- Global State & Constants ---
let currentPage = 1;
let totalPages = 1;
let currentFilters = {};

const API_URL = '../api/pdTable/pdTableManage.php'; 

async function fetchPartsData(page = 1) {
    currentPage = page;
    currentFilters = {
        part_no: document.getElementById('filterPartNo')?.value,
        lot_no: document.getElementById('filterLotNo')?.value,
        line: document.getElementById('filterLine')?.value,
        model: document.getElementById('filterModel')?.value,
        count_type: document.getElementById('filterCountType')?.value,
        startDate: document.getElementById('filterStartDate')?.value,
        endDate: document.getElementById('filterEndDate')?.value,
    };

    const params = new URLSearchParams({
        action: 'get_parts',
        page: currentPage,
        limit: 50,
        ...currentFilters
    });

    try {
        const response = await fetch(`${API_URL}?${params.toString()}`);
        const result = await response.json();

        if (!result.success) throw new Error(result.message);

        renderTable(result.data);
        renderPagination(result.page, result.total, result.limit);
        renderSummary(result.summary, result.grand_total);

    } catch (error) {
        console.error('Failed to fetch data:', error);
        const tbody = document.getElementById('partTableBody');
        if (tbody) tbody.innerHTML = `<tr><td colspan="11" class="text-center text-danger">Error loading data. Please try again.</td></tr>`;
    }
}

function handleFilterChange() {
    fetchPartsData(1);
}

// --- Rendering Functions ---
function renderTable(data) {
    const tbody = document.getElementById('partTableBody');
    tbody.innerHTML = '';
    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center">No matching records found.</td></tr>`;
        return;
    }
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.dataset.id = row.id;
        
        const formattedDate = new Date(row.log_date).toLocaleDateString('en-GB');
        const formattedTime = row.log_time.substring(0, 8);

        tr.innerHTML = `
            <td class="text-center">${row.id}</td>
            <td>${formattedDate}</td>
            <td>${formattedTime}</td>
            <td>${row.line}</td>
            <td>${row.model}</td>
            <td>${row.part_no}</td>
            <td>${row.lot_no || ''}</td>
            <td class="text-center">${row.count_value}</td>
            <td>${row.count_type}</td>
            <td><div class="note-truncate" title="${row.note || ''}">${row.note || ''}</div></td>
            <td class="text-center">
                <button class="btn btn-sm btn-warning" onclick='openEditModal(${JSON.stringify(row)})'>Edit</button>
                <button class="btn btn-sm btn-danger" onclick="handleDelete(${row.id})">Delete</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderPagination(page, totalItems, limit) {
    totalPages = totalItems > 0 ? Math.ceil(totalItems / limit) : 1;
    currentPage = parseInt(page);
    
    const paginationInfo = document.getElementById('pagination-info');
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');

    if (paginationInfo) paginationInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
}

function renderSummary(summaryData, grandTotalData) {
    const grandSummaryContainer = document.getElementById('grandSummary');
    if (!grandSummaryContainer) return;

    let grandSummaryHTML = '<strong>Grand Total: </strong>';
    if (grandTotalData) {
        grandSummaryHTML += Object.entries(grandTotalData)
            .filter(([key, value]) => value > 0)
            .map(([key, value]) => `${key.toUpperCase()}: ${value || 0}`)
            .join(' | ');
    }
    grandSummaryContainer.innerHTML = grandSummaryHTML;
}

// --- Datalist Population ---
async function populateDatalist(datalistId, action) {
    try {
        const response = await fetch(`${API_URL}?action=${action}`);
        const result = await response.json();
        if (result.success) {
            const datalist = document.getElementById(datalistId);
            if (datalist) {
                datalist.innerHTML = result.data.map(item => `<option value="${item}"></option>`).join('');
            }
        }
    } catch (error) {
        console.error(`Failed to populate datalist ${datalistId}:`, error);
    }
}

// --- Other Action Handlers ---
async function handleDelete(id) {
    if (!confirm(`Are you sure you want to delete Part ID ${id}?`)) return;
    try {
        const response = await fetch(`${API_URL}?action=delete_part&id=${id}`);
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            fetchPartsData(currentPage);
        }
    } catch (error) {
        alert('An error occurred while deleting the part.');
        console.error('Delete failed:', error);
    }
}

function openEditModal(rowData) {
    const modal = document.getElementById('editPartModal');
    if(modal) {
        document.getElementById('edit_id').value = rowData.id;
        document.getElementById('edit_date').value = rowData.log_date;
        document.getElementById('edit_time').value = rowData.log_time.substring(0, 5);
        document.getElementById('edit_line').value = rowData.line;
        document.getElementById('edit_model').value = rowData.model;
        document.getElementById('edit_part_no').value = rowData.part_no;
        document.getElementById('edit_lot_no').value = rowData.lot_no;
        document.getElementById('edit_value').value = rowData.count_value;
        document.getElementById('edit_type').value = rowData.count_type;
        document.getElementById('edit_note').value = rowData.note;
        modal.style.display = 'block';
    }
}

// --- Initial Setup ---
document.addEventListener('DOMContentLoaded', () => {
    const filterInputs = ['filterPartNo', 'filterLotNo', 'filterLine', 'filterModel', 'filterCountType', 'filterStartDate', 'filterEndDate'];
    filterInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', () => {
                clearTimeout(window.filterDebounceTimer);
                window.filterDebounceTimer = setTimeout(() => {
                    handleFilterChange();
                }, 500);
            });
        }
    });

    document.getElementById('prevPageBtn')?.addEventListener('click', () => {
        if (currentPage > 1) {
            fetchPartsData(currentPage - 1);
        }
    });

    document.getElementById('nextPageBtn')?.addEventListener('click', () => {
        if (currentPage < totalPages) {
            fetchPartsData(currentPage + 1);
        }
    });

    populateDatalist('partNoList', 'get_part_nos');
    populateDatalist('lotList', 'get_lot_numbers');
    populateDatalist('lineList', 'get_lines');
    populateDatalist('modelList', 'get_models');
    
    fetchPartsData(1);
});