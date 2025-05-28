// paginationTable.js

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

        const params = new URLSearchParams({
            page,
            limit,
            startDate,
            endDate,
            line,
            model,
            part_no: partNo,
            count_type: status
        });

        const res = await fetch(`api/get_parts.php?${params.toString()}`);
        const result = await res.json();
        //
        console.log(res);

        if (!result.success) throw new Error(result.message);   

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
    } catch (error) {
        console.error("Failed to fetch paginated data:", error);
        alert("Error loading parts data.");
    }
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
}, 30000); // 60000ms = 1 minute