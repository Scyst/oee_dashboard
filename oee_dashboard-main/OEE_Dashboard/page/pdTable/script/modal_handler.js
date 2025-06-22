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

    // --- Render Grand Totals ---
    let grandTotalHTML = '<strong>Grand Total: </strong>';
    if (grandTotalData) {
        grandTotalHTML += Object.entries(grandTotalData)
            .filter(([key, value]) => value > 0)
            .map(([key, value]) => `${key.toUpperCase()}: ${value || 0}`)
            .join(' | ');
    }
    grandTotalContainer.innerHTML = grandTotalHTML;

    // --- Render Detailed Table ---
    if (summaryData.length === 0) {
        tableContainer.innerHTML = '<p class="text-center mt-3">No summary data to display.</p>';
        openModal('summaryModal');
        return;
    }

    let tableHTML = `
        <table class="table table-dark table-sm table-bordered table-hover mt-3">
            <thead>
                <tr>
                    <th>Model</th><th>Part No.</th><th>Lot No.</th>
                    <th>FG</th><th>NG</th><th>HOLD</th>
                    <th>REWORK</th><th>SCRAP</th><th>ETC.</th>
                </tr>
            </thead>
            <tbody>
    `;
    summaryData.forEach(row => {
        tableHTML += `
            <tr>
                <td>${row.model}</td><td>${row.part_no}</td><td>${row.lot_no || ''}</td>
                <td class="text-center">${row.FG || 0}</td><td class="text-center">${row.NG || 0}</td>
                <td class="text-center">${row.HOLD || 0}</td><td class="text-center">${row.REWORK || 0}</td>
                <td class="text-center">${row.SCRAP || 0}</td><td class="text-center">${row.ETC || 0}</td>
            </tr>
        `;
    });
    tableHTML += `</tbody></table>`;
    
    tableContainer.innerHTML = tableHTML;
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
                alert(result.message);
                if (result.success) {
                    closeModal('addPartModal');
                    addForm.reset();
                    fetchPartsData(1);
                }
            } catch (error) {
                alert('An error occurred while adding data.');
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
                alert(result.message);
                if (result.success) {
                    closeModal('editPartModal');
                    fetchPartsData(currentPage);
                }
            } catch (error) {
                alert('An error occurred while updating data.');
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