// pdTable/paginationTable.js

let currentPage = 1;
const limit = 100;

async function fetchPaginatedParts(page = 1) {
    try {
        // Get filter values
        const startDate = document.getElementById("startDate").value;
        const endDate = document.getElementById("endDate").value;
        const line = document.getElementById("lineInput")?.value.trim() || '';
        const model = document.getElementById("modelInput")?.value.trim() || '';
        const partNo = document.getElementById("searchInput")?.value.trim() || '';
        const status = document.getElementById("status")?.value.trim() || '';
        const lotNo = document.getElementById("lotInput")?.value.trim() || '';

        const params = new URLSearchParams({
            page,
            limit,
            startDate,
            endDate,
            line,
            model,
            part_no: partNo,
            count_type: status,
            lot_no: lotNo // ⬅️ add this line
        });

        const res = await fetch(`../api/pdTable/get_parts.php?${params.toString()}`);
        const result = await res.json();

        if (!result.success) throw new Error(result.message);

        // Populate table
        const tableBody = document.getElementById('partTableBody');
        tableBody.innerHTML = '';
        result.data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.id}</td>
                <td>${row.log_date}</td>
                <td>${row.log_time}</td>
                <td>${row.line}</td>
                <td>${row.model}</td>
                <td>${row.part_no}</td>
                <td>${row.lot_no || ''}</td>
                <td>${row.count_value}</td>
                <td>${row.count_type}</td>
                <td>${row.note || ''}</td>
                <td>
                    <button onclick="editPart(${row.id})">Edit</button>
                    <button onclick="deletePart(${row.id})">Delete</button>
                </td>
            `;
            tableBody.appendChild(tr);
        });

        updatePaginationControls(result.page, result.total);
        renderSummary(result.summary, result.grand_total);
        window.cachedSummary = result.summary || [];

    } catch (error) {
        console.error("Failed to fetch paginated data:", error);
        alert("Error loading parts data.");
    }
}

function renderSummary(summary, grandTotal) {
    const wrapper = document.getElementById('partSummaryWrapper');
    const tableContainer = document.getElementById('partSummary');
    const grandContainer = document.getElementById('grandSummary');

    // Store globally for modal reuse
    window.cachedSummary = summary || [];
    window.cachedGrand = grandTotal || {};

    // Render inline summary (if needed)
    tableContainer.innerHTML = '';
    if (summary.length === 0) {
        tableContainer.innerHTML = '<p>No summary data available.</p>';
    }

    if (grandTotal) {
        grandContainer.textContent = `Total - FG: ${grandTotal.FG || 0} | NG: ${grandTotal.NG || 0} | HOLD: ${grandTotal.HOLD || 0} | REWORK: ${grandTotal.REWORK || 0} | SCRAP: ${grandTotal.SCRAP || 0} | ETC: ${grandTotal.ETC || 0}`;
    }
}

function editPart(id) {
    fetch(`../api/pdTable/get_part_by_id.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const part = data.data;
                document.getElementById('edit_id').value = part.id;
                document.getElementById('edit_date').value = part.log_date;
                document.getElementById('edit_time').value = part.log_time;
                document.getElementById('edit_line').value = part.line;
                document.getElementById('edit_model').value = part.model;
                document.getElementById('edit_part_no').value = part.part_no;
                document.getElementById('edit_lot_no').value = part.lot_no;
                document.getElementById('edit_value').value = part.count_value;
                document.getElementById('edit_type').value = part.count_type;
                document.getElementById('edit_note').value = part.note;

                document.getElementById('editPartModal').style.display = 'block';
            } else {
                alert("Failed to load part for editing: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error loading part:", error);
            alert("Failed to fetch part data.");
        });
}

function deletePart(id) {
    if (!confirm("Are you sure you want to delete this part?")) return;

    fetch("../api/pdTable/delete_part.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `id=${encodeURIComponent(id)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Deleted successfully!");
            fetchPaginatedParts(currentPage); 
        } else {
            alert("Delete failed: " + data.message);
        }
    })
    .catch(err => {
        console.error("Delete error:", err);
        alert("An error occurred while deleting.");
    });
}

function updatePaginationControls(current, totalItems) {
    const totalPages = Math.ceil(totalItems / limit);

    document.getElementById('pagination-info').textContent = `Page ${current} of ${totalPages}`;
    document.getElementById('prevPageBtn').disabled = current <= 1;
    document.getElementById('nextPageBtn').disabled = current >= totalPages;
}

function applyDateRangeFilter() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    currentPage = 1; // Always reset to first page when filtering
    fetchPaginatedParts(currentPage, startDate, endDate);
}

function openSummaryModal() {
    const modal = document.getElementById('summaryModal');
    modal.style.display = 'block';

    const summaryData = window.cachedSummary || [];
    const grandTotal = window.cachedGrand || {};

    const summaryContainer = document.getElementById('summaryTableContainer');
    summaryContainer.innerHTML = '';

    if (summaryData.length === 0) {
        summaryContainer.innerHTML = "<p>No summary data to display.</p>";
        return;
    }

    // Flex container for total + export button
    const headerRow = document.createElement('div');
    headerRow.style.display = 'flex';
    headerRow.style.justifyContent = 'space-between';
    headerRow.style.alignItems = 'center';
    headerRow.style.marginTop = '20px';
    headerRow.style.marginBottom = '10px';
    headerRow.style.gap = '10px';

    // Grand Total
    const grandRow = document.createElement('div');
    grandRow.style.fontWeight = 'bold';
    grandRow.textContent = `Total - FG: ${grandTotal.FG || 0} | NG: ${grandTotal.NG || 0} | HOLD: ${grandTotal.HOLD || 0} | REWORK: ${grandTotal.REWORK || 0} | SCRAP: ${grandTotal.SCRAP || 0} | ETC: ${grandTotal.ETC || 0}`;
    headerRow.appendChild(grandRow);

    // Export Button
    const buttonRow = document.createElement('div');
    const exportBtn = document.createElement('button');
    exportBtn.style.paddingBlock = '0'
    exportBtn.textContent = 'Export to Excel';
    exportBtn.onclick = exportSummaryToExcel; // You must define this function separately
    buttonRow.appendChild(exportBtn);
    headerRow.appendChild(buttonRow);

    summaryContainer.appendChild(headerRow);

    // Table
    const table = document.createElement('table');
    table.style.width = '100%';
    table.style.borderCollapse = 'collapse';
    table.innerHTML = `
        <thead>
            <tr>
                <th>Model</th>
                <th>Part No</th>
                <th>Lot No</th>
                <th class="equal-col">FG</th>
                <th class="equal-col">NG</th>
                <th class="equal-col">HOLD</th>
                <th class="equal-col">REWORK</th>
                <th class="equal-col">SCRAP</th>
                <th class="equal-col">ETC</th>
            </tr>
        </thead>
        <tbody>
            ${summaryData.map(row => `
                <tr>
                    <td>${row.model}</td>
                    <td>${row.part_no}</td>
                    <td>${row.lot_no || ''}</td>
                    <td class="equal-col">${row.FG || 0}</td>
                    <td class="equal-col">${row.NG || 0}</td>
                    <td class="equal-col">${row.HOLD || 0}</td>
                    <td class="equal-col">${row.REWORK || 0}</td>
                    <td class="equal-col">${row.SCRAP || 0}</td>
                    <td class="equal-col">${row.ETC || 0}</td>
                </tr>
            `).join('')}
        </tbody>
    `;
    summaryContainer.appendChild(table);
}

const editForm = document.getElementById('editPartForm');
if (editForm) {
    editForm.addEventListener('submit', function (e) {
        e.preventDefault(); // Prevent page reload

        const formData = new FormData(this);
        const data = new URLSearchParams(formData);

        fetch('../api/pdTable/update_part.php', {
            method: 'POST',
            body: data
        })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert("Part updated!");
                    closeModal('editPartModal');
                    fetchPaginatedParts(currentPage); // Reload the updated table
                } else {
                    alert("Update failed: " + response.message);
                }
            })
            .catch(error => {
                console.error("Update error:", error);
                alert("An error occurred during update.");
            });
    });
}

const addForm = document.getElementById("addPartForm");
if (addForm) {
    addForm.addEventListener("submit", async function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        try {
            const res = await fetch("../api/pdTable/add_part.php", {
                method: "POST",
                body: formData
            });
            const result = await res.json();
            if (result.status === "success") {

                alert(`${result.message}\nLot No: ${result.lot_no}`);
                document.getElementById("add_lot_no").value = result.lot_no;
                fetchPaginatedParts(1);
                closeModal("partModal");
                const tempLot = result.lot_no;
                form.reset();
                document.getElementById("add_lot_no").value = tempLot;
            } else {
                alert("Add failed: " + result.message);
            }
        } catch (err) {
            console.error("Add request failed", err);
            alert("An error occurred.");
        }
    });
}

document.getElementById('prevPageBtn').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        fetchPaginatedParts(currentPage, startDate, endDate);
    }
});

document.getElementById('nextPageBtn').addEventListener('click', () => {
    currentPage++;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    fetchPaginatedParts(currentPage, startDate, endDate);
});

window.onload = () => fetchPaginatedParts(currentPage);

setInterval(() => {
    fetchPaginatedParts(currentPage);
}, 60000); // 60000ms = 1 minute