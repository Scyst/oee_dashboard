// paginationTable.js

let currentPage = 1;
const limit = 100;

async function fetchPaginatedParts(page = 1) {
    try {
        const res = await fetch(`api/get_parts.php?page=${page}&limit=${limit}`);
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

document.getElementById('prevPageBtn').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        fetchPaginatedParts(currentPage);
    }
});

document.getElementById('nextPageBtn').addEventListener('click', () => {
    currentPage++;
    fetchPaginatedParts(currentPage);
});

window.onload = () => fetchPaginatedParts(currentPage);
