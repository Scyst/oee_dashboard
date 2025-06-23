let currentPage = 1;
let totalPages = 1;
const API_URL = '../../api/pdTable/pdTableManage.php';

async function fetchPartsData(page = 1) {
    currentPage = page;
    const filters = {
        part_no: document.getElementById('filterPartNo')?.value,
        lot_no: document.getElementById('filterLotNo')?.value,
        line: document.getElementById('filterLine')?.value,
        model: document.getElementById('filterModel')?.value,
        count_type: document.getElementById('filterCountType')?.value,
        startDate: document.getElementById('filterStartDate')?.value,
        endDate: document.getElementById('filterEndDate')?.value,
    };
    const params = new URLSearchParams({ action: 'get_parts', page: currentPage, limit: 50, ...filters });

    try {
        const response = await fetch(`${API_URL}?${params.toString()}`);
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        renderTable(result.data);
        renderPagination(result.page, result.total, result.limit);
        renderSummary(result.summary, result.grand_total);
    } catch (error) {
        console.error('Failed to fetch parts data:', error);
        document.getElementById('partTableBody').innerHTML = `<tr><td colspan="11" class="text-center text-danger">Error loading data.</td></tr>`;
    }
}

function renderTable(data) {
    const tbody = document.getElementById('partTableBody');
    tbody.innerHTML = ''; // Clear existing rows
    if (!data || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center">No records found.</td></tr>`;
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.dataset.id = row.id;

        const createCell = (text) => {
            const td = document.createElement('td');
            td.textContent = text;
            return td;
        };
        const createCenteredCell = (text) => {
            const td = createCell(text);
            td.className = 'text-center';
            return td;
        };

        const formattedDate = row.log_date ? new Date(row.log_date).toLocaleDateString('en-GB') : '';
        const formattedTime = row.log_time ? row.log_time.substring(0, 8) : '';
        
        tr.appendChild(createCenteredCell(row.id));
        tr.appendChild(createCell(formattedDate));
        tr.appendChild(createCell(formattedTime));
        tr.appendChild(createCell(row.line));
        tr.appendChild(createCell(row.model));
        tr.appendChild(createCell(row.part_no));
        tr.appendChild(createCell(row.lot_no || ''));
        tr.appendChild(createCenteredCell(row.count_value));
        tr.appendChild(createCell(row.count_type));
        
        // Safely create the note cell
        const noteTd = document.createElement('td');
        const noteDiv = document.createElement('div');
        noteDiv.className = 'note-truncate';
        noteDiv.title = row.note || '';
        noteDiv.textContent = row.note || ''; // Use .textContent
        noteTd.appendChild(noteDiv);
        tr.appendChild(noteTd);
        
        // Safely create action buttons
        const actionsTd = document.createElement('td');
        actionsTd.className = 'text-center';
        
        const editButton = document.createElement('button');
        editButton.className = 'btn btn-sm btn-warning';
        editButton.textContent = 'Edit';
        editButton.addEventListener('click', () => openEditModal(row));
        
        const deleteButton = document.createElement('button');
        deleteButton.className = 'btn btn-sm btn-danger';
        deleteButton.textContent = 'Delete';
        deleteButton.addEventListener('click', () => handleDelete(row.id));

        actionsTd.appendChild(editButton);
        actionsTd.appendChild(deleteButton);
        tr.appendChild(actionsTd);

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

function renderSummary(summaryData, grandTotalData) {
    window.cachedSummary = summaryData || [];
    window.cachedGrand = grandTotalData || {};
    const grandSummaryContainer = document.getElementById('grandSummary');
    if (!grandSummaryContainer) return;
    let grandSummaryHTML = '<strong>Grand Total: </strong>';
    if (grandTotalData) {
        grandSummaryHTML += Object.entries(grandTotalData).filter(([, value]) => value > 0).map(([key, value]) => `${key.toUpperCase()}: ${value || 0}`).join(' | ');
    }
    grandSummaryContainer.innerHTML = grandSummaryHTML;
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
                    option.value = item; // Setting .value is safe from XSS
                    datalist.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error(`Failed to populate ${datalistId}:`, error);
    }
}

async function handleDelete(id) {
    if (!confirm(`Are you sure you want to delete Part ID ${id}?`)) return;
    try {
        const response = await fetch(`${API_URL}?action=delete_part&id=${id}`);
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            const rowCount = document.querySelectorAll('#partTableBody tr').length;
            const newPage = (rowCount === 1 && currentPage > 1) ? currentPage - 1 : currentPage;
            fetchPartsData(newPage);
        }
    } catch (error) {
        alert('An error occurred while deleting the part.');
    }
}

function handleFilterChange() {
    fetchPartsData(1);
}

document.addEventListener('DOMContentLoaded', () => {
    const filterInputs = ['filterPartNo', 'filterLotNo', 'filterLine', 'filterModel', 'filterCountType', 'filterStartDate', 'filterEndDate'];
    filterInputs.forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => {
            clearTimeout(window.filterDebounceTimer);
            window.filterDebounceTimer = setTimeout(handleFilterChange, 500);
        });
    });

    document.getElementById('prevPageBtn')?.addEventListener('click', () => {
        if (currentPage > 1) fetchPartsData(currentPage - 1);
    });

    document.getElementById('nextPageBtn')?.addEventListener('click', () => {
        if (currentPage < totalPages) fetchPartsData(currentPage + 1);
    });

    populateDatalist('partNoList', 'get_part_nos');
    populateDatalist('lotList', 'get_lot_numbers');
    populateDatalist('lineList', 'get_lines');
    populateDatalist('modelList', 'get_models');
    
    fetchPartsData(1);
});