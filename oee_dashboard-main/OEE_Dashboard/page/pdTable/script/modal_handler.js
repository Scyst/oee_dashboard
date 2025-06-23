function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.style.display = "block";

    if (modalId === 'addPartModal') {
        const now = new Date();
        const dateStr = now.toISOString().split('T')[0];
        const timeStr = now.toTimeString().split(' ')[0].substring(0, 8);
        
        modal.querySelector('input[name="log_date"]').value = dateStr;
        modal.querySelector('input[name="log_time"]').value = timeStr;
    }
}

function openSummaryModal() {
    const modal = document.getElementById('summaryModal');
    const grandTotalContainer = document.getElementById('summaryGrandTotalContainer');
    const tableContainer = document.getElementById('summaryTableContainer');
    const summaryData = window.cachedSummary || [];
    const grandTotalData = window.cachedGrand || {};

    if (!modal || !tableContainer || !grandTotalContainer) {
        console.error('Summary modal elements not found!');
        return;
    }

    // --- Render Grand Totals (This part is safe as data is numerical) ---
    let grandTotalHTML = '<strong>Grand Total: </strong>';
    if (grandTotalData) {
        grandTotalHTML += Object.entries(grandTotalData)
            .filter(([key, value]) => value > 0)
            .map(([key, value]) => `${key.toUpperCase()}: ${value || 0}`)
            .join(' | ');
    }
    grandTotalContainer.innerHTML = grandTotalHTML;

    // --- Render Detailed Table Securely ---
    tableContainer.innerHTML = ''; // Clear previous content

    if (summaryData.length === 0) {
        const p = document.createElement('p');
        p.className = 'text-center mt-3';
        p.textContent = 'No summary data to display.';
        tableContainer.appendChild(p);
        openModal('summaryModal');
        return;
    }

    const table = document.createElement('table');
    table.className = 'table table-dark table-sm table-bordered table-hover mt-3';

    // Create table header
    const thead = table.createTHead();
    const headerRow = thead.insertRow();
    const headers = ["Model", "Part No.", "Lot No.", "FG", "NG", "HOLD", "REWORK", "SCRAP", "ETC."];
    headers.forEach(text => {
        const th = document.createElement('th');
        th.textContent = text;
        headerRow.appendChild(th);
    });

    // Create table body
    const tbody = table.createTBody();
    summaryData.forEach(row => {
        const tr = tbody.insertRow();

        // Helper to create cells safely with .textContent
        const createCell = (text, className = '') => {
            const td = tr.insertCell();
            td.textContent = text;
            if (className) td.className = className;
        };

        createCell(row.model);
        createCell(row.part_no);
        createCell(row.lot_no || '');
        createCell(row.FG || 0, 'text-center');
        createCell(row.NG || 0, 'text-center');
        createCell(row.HOLD || 0, 'text-center');
        createCell(row.REWORK || 0, 'text-center');
        createCell(row.SCRAP || 0, 'text-center');
        createCell(row.ETC || 0, 'text-center');
    });

    tableContainer.appendChild(table);
    openModal('summaryModal');
}

function openEditModal(rowData) {
    const modal = document.getElementById('editPartModal');
    if (!modal) return;
    
    console.log("Data received for Edit Modal:", rowData);
    
    openModal('editPartModal');
    
    for (const key in rowData) {
        const input = modal.querySelector(`#edit_${key}`);
        if (input) {
            if (key === 'log_time' && typeof rowData[key] === 'string') {
                input.value = rowData[key].substring(0, 8);
            } else {
                input.value = rowData[key];
            }
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = "none";
}

document.addEventListener('DOMContentLoaded', () => {
    const addForm = document.getElementById('addPartForm');
    if (addForm) {
        addForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(addForm).entries());
            try {
                const response = await fetch(`${API_URL}?action=add_part`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                showToast(result.message, result.success ? '#28a745' : '#dc3545');
                if (result.success) {
                    closeModal('addPartModal');
                    addForm.reset();
                    fetchPartsData(1);
                }
            } catch (error) {
                showToast('An error occurred while adding data.', '#dc3545');
            }
        });
    }

    const editForm = document.getElementById('editPartForm');
    if (editForm) {
        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = Object.fromEntries(new FormData(editForm).entries());
            try {
                const response = await fetch(`${API_URL}?action=update_part`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                showToast(result.message, result.success ? '#28a745' : '#dc3545');
                if (result.success) {
                    closeModal('editPartModal');
                    fetchPartsData(currentPage);
                }
            } catch (error) {
                showToast('An error occurred while updating data.', '#dc3545');
            }
        });
    }

    window.addEventListener('click', function (event) {
        document.querySelectorAll('.modal').forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });
});