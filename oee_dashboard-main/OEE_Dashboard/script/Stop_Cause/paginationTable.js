// paginationTable.js

let currentPage = 1;
const limit = 100;

async function fetchPaginatedParts(page = 1) {
    try {
        // Get filter values
        const startDate = document.getElementById("startDate").value;
        const endDate = document.getElementById("endDate").value;
        const line = document.getElementById("lineInput")?.value.trim() || '';
        const machine = document.getElementById("machineInput")?.value.trim() || '';
        const cause = document.getElementById("searchInput")?.value.trim() || '';

        const params = new URLSearchParams({
            page,
            limit,
            startDate,
            endDate,
            line,
            machine,
            cause: cause
        });

        const res = await fetch(`../api/Stop_Cause/get_stop.php?${params.toString()}`);
        const result = await res.json();
        //console.log(res);

        if (!result.success) throw new Error(result.message);   

        const tableBody = document.getElementById('stopTableBody');
        tableBody.innerHTML = '';

        result.data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.id}</td>
                <td>${row.log_date}</td>
                <td>${row.stop_begin}</td>
                <td>${row.stop_end}</td>
                <td>${row.line}</td>
                <td>${row.machine}</td>
                <td>${row.cause}</td>
                <td>${row.recovered_by}</td>
                <td>${row.note || ''}</td>
                <td>
                    <button onclick="editStop(${row.id})">Edit</button>
                    <button onclick="deleteStop(${row.id})">Delete</button>
                </td>
            `;
            tableBody.appendChild(tr);
        });

        updatePaginationControls(result.page, result.total);
    } catch (error) {
        console.error("Failed to fetch paginated data:", error);
        alert("Error loading stop data.");
    }
}

function editStop(id) {
    fetch(`../api/Stop_Cause/get_stop_by_id.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stop = data.data;
                document.getElementById('edit_id').value = stop.id;
                document.getElementById('edit_date').value = stop.log_date;
                document.getElementById('edit_stopBegin').value = stop.stop_begin;
                document.getElementById('edit_stopEnd').value = stop.stop_end;
                document.getElementById('edit_line').value = stop.line;
                document.getElementById('edit_machine').value = stop.machine;
                document.getElementById('edit_cause').value = stop.cause;
                document.getElementById('edit_recovered_by').value = stop.recovered_by;
                document.getElementById('edit_note').value = stop.note;

                document.getElementById('editStopModal').style.display = 'block';
            } else {
                alert("Failed to load data for editing: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error loading part:", error);
            alert("Failed to fetch stop cause data.");
        });
}

function deleteStop(id) {
    if (!confirm("Are you sure you want to delete this data?")) return;

    fetch("../api/Stop_Cause/delete_stop.php", {
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

function renderCauseSummary(summary) {
    const container = document.getElementById('causeSummary');
    container.innerHTML = '';

    if (summary.length === 0) {
        container.innerHTML = '<p>No stop causes found in this range.</p>';
        return;
    }

    const list = document.createElement('ul');
    summary.forEach(item => {
        const li = document.createElement('li');
        li.textContent = `${item.cause} – ${item.count} times`;
        list.appendChild(li);
    });

    container.appendChild(list);
}

document.getElementById('editStopForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent page reload

    const formData = new FormData(this);
    const data = new URLSearchParams(formData);

    fetch('../api/Stop_Cause/update_stop.php', {
        method: 'POST',
        body: data
    })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                alert("Data updated!");
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
        const res = await fetch("../api/Stop_Cause/add_stop.php", {
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