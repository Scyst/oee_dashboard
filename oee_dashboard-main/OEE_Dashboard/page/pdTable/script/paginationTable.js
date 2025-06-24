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

        // เราจะใช้ตัวแปร canManage ที่ส่งมาจาก pdTable.php
        renderTable(result.data, window.canManage);
        renderPagination(result.page, result.total, result.limit);
        renderSummary(result.summary, result.grand_total);
    } catch (error) {
        console.error('Failed to fetch parts data:', error);
        document.getElementById('partTableBody').innerHTML = `<tr><td colspan="11" class="text-center text-danger">Error loading data.</td></tr>`;
    }
}

function renderTable(data, canManage) { // รับตัวแปร canManage เข้ามา
    const tbody = document.getElementById('partTableBody');
    tbody.innerHTML = ''; 
    if (!data || data.length === 0) {
        const colSpan = canManage ? 11 : 10;
        tbody.innerHTML = `<tr><td colspan="${colSpan}" class="text-center">No records found.</td></tr>`;
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
        
        const formattedDate = row.log_date ? new Date(row.log_date).toLocaleDateString('en-GB') : '';
        const formattedTime = row.log_time ? row.log_time.substring(0, 8) : '';
        
        tr.appendChild(createCell(row.id));
        tr.appendChild(createCell(formattedDate));
        tr.appendChild(createCell(formattedTime));
        tr.appendChild(createCell(row.line));
        tr.appendChild(createCell(row.model));
        tr.appendChild(createCell(row.part_no));
        tr.appendChild(createCell(row.lot_no || ''));
        tr.appendChild(createCell(row.count_value));
        tr.appendChild(createCell(row.count_type));
        
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
            editButton.addEventListener('click', () => openEditModal(row));
            
            const deleteButton = document.createElement('button');
            deleteButton.className = 'btn btn-sm btn-danger ms-1';
            deleteButton.textContent = 'Delete';
            deleteButton.addEventListener('click', () => handleDelete(row.id));

            actionsTd.appendChild(editButton);
            actionsTd.appendChild(deleteButton);
            tr.appendChild(actionsTd);
        }

        tbody.appendChild(tr);
    });
}


// --- START: แก้ไขฟังก์ชัน renderPagination ทั้งหมด ---
function renderPagination(page, totalItems, limit) {
    totalPages = totalItems > 0 ? Math.ceil(totalItems / limit) : 1;
    currentPage = parseInt(page);
    const paginationContainer = document.getElementById('paginationControls');
    paginationContainer.innerHTML = ''; // Clear old controls

    if (totalPages <= 1) return;

    const createPageItem = (pageNum, text, isDisabled = false, isActive = false) => {
        const li = document.createElement('li');
        li.className = `page-item ${isDisabled ? 'disabled' : ''} ${isActive ? 'active' : ''}`;
        
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = text;
        if (!isDisabled) {
            a.onclick = (e) => {
                e.preventDefault();
                fetchPartsData(pageNum);
            };
        }
        li.appendChild(a);
        return li;
    };

    // "Previous" button
    paginationContainer.appendChild(createPageItem(currentPage - 1, 'Previous', currentPage === 1));

    // Page number buttons
    // Logic to show a limited number of pages, e.g., ... 3 4 5 ...
    // This is a simple implementation showing all pages. For a large number of pages, you might want to add ellipsis logic.
    for (let i = 1; i <= totalPages; i++) {
        paginationContainer.appendChild(createPageItem(i, i, false, i === currentPage));
    }

    // "Next" button
    paginationContainer.appendChild(createPageItem(currentPage + 1, 'Next', currentPage === totalPages));
}
// --- END: แก้ไขฟังก์ชัน renderPagination ทั้งหมด ---


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
                datalist.innerHTML = ''; 
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

async function handleDelete(id) {
    if (!confirm(`Are you sure you want to delete Part ID ${id}?`)) return;
    try {
        const response = await fetch(`${API_URL}?action=delete_part&id=${id}`);
        const result = await response.json();
        showToast(result.message, result.success ? '#28a745' : '#dc3545');
        if (result.success) {
            const rowCount = document.querySelectorAll('#partTableBody tr').length;
            const newPage = (rowCount === 1 && currentPage > 1) ? currentPage - 1 : currentPage;
            fetchPartsData(newPage);
        }
    } catch (error) {
        showToast('An error occurred while deleting the part.', '#dc3545');
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

    // The pagination buttons are now dynamically created, so we attach event listeners to the container
    // The createPageItem function handles the click logic, so we don't need these anymore.
    // document.getElementById('prevPageBtn')?.addEventListener('click', ...);
    // document.getElementById('nextPageBtn')?.addEventListener('click', ...);

    populateDatalist('partNoList', 'get_part_nos');
    populateDatalist('lotList', 'get_lot_numbers');
    populateDatalist('lineList', 'get_lines');
    populateDatalist('modelList', 'get_models');
    
    fetchPartsData(1);
});