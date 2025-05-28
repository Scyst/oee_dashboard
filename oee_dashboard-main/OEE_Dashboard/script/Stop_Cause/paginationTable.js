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

        const res = await fetch(`../api/pdTable/get_parts.php?${params.toString()}`);
        const result = await res.json();
        //console.log(res);

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
                document.getElementById('edit_value').value = part.count_value;
                document.getElementById('edit_type').value = part.count_type;
                document.getElementById('edit_note').value = part.note;

                document.getElementById('editStopModal').style.display = 'block';
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

    fetch("../api/Stop_Cause/delete_part.php", {
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

document.getElementById('editPartForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent page reload

    const formData = new FormData(this);
    const data = new URLSearchParams(formData);

    fetch('../api/Stop_Cause/update_part.php', {
        method: 'POST',
        body: data
    })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                alert("Part updated!");
                closeModal('editStopModal');
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

document.getElementById("addStopForm").addEventListener("submit", async function (e) {
    e.preventDefault(); // prevent page reload

    const form = e.target;
    const formData = new FormData(form);

    try {
        const res = await fetch("../api/Stop_Cause/add_part.php", {
            method: "POST",
            body: formData
        });

        const result = await res.json();
        console.log(result)

        if (result.status === "success") {
            alert(result.message);
            closeModal("stopModal");         // hide the modal
            fetchPaginatedParts(1);          // reload the table from page 1
            form.reset();                    // optional: clear form
        } else {
            alert("Add failed: " + result.message);
        }
    } catch (err) {
        console.error("Add request failed", err);
        alert("An error occurred.");
    }
});




window.onload = () => fetchPaginatedParts(currentPage);

setInterval(() => {
    fetchPaginatedParts(currentPage);
}, 30000); // 60000ms = 1 minute