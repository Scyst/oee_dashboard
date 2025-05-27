// paginationTable.js

let currentPage = 1;
const limit = 100;

async function fetchPaginatedParts(page = 1, startDate = '', endDate = '') {
    try {
        let url = `api/get_parts.php?page=${page}&limit=${limit}`;

        if (startDate && endDate) {
            url += `&startDate=${encodeURIComponent(startDate)}&endDate=${encodeURIComponent(endDate)}`;
        }

        const res = await fetch(url);
        const result = await res.json();

        if (!result.success) throw new Error(result.message);

        const data = result.data;
        const tableBody = document.getElementById('partTableBody');
        tableBody.innerHTML = '';

        data.forEach(row => {
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
                <td>${row.note}</td>
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
