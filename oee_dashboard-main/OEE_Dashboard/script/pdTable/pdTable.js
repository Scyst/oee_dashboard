async function fetchParts() {
    try {
        const res = await fetch('api/get_parts.php');
        const data = await res.json();
        //console.log(data);

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

        filterTable(); // Apply filter on initial load //<td>${row.log_time}</td>
    } catch (error) {
        console.error("Failed to fetch part data:", error);
        alert("Error fetching part data from the server.");
    }
}

function filterTable() {
    const searchInput = document.getElementById("searchInput").value.toLowerCase().trim();
    const lineInput = document.getElementById("lineInput").value.toLowerCase().trim();
    const modelInput = document.getElementById("modelInput").value.toLowerCase().trim();
    const statusInput = document.getElementById("status").value.toLowerCase().trim();

    const rows = document.querySelectorAll("#partTable tbody tr");

    rows.forEach(row => {
        // Get original text (before highlighting)
        const partNoCell = row.children[5];
        const lineCell = row.children[3];
        const modelCell = row.children[4];
        const statusCell = row.children[7];

        const partNo = partNoCell.textContent.toLowerCase().trim();
        const line = lineCell.textContent.toLowerCase().trim();
        const model = modelCell.textContent.toLowerCase().trim();
        const status = statusCell.textContent.toLowerCase().trim();

        const matchesPartNo = !searchInput || partNo.includes(searchInput);
        const matchesLine = !lineInput || line.includes(lineInput);
        const matchesModel = !modelInput || model.includes(modelInput);
        const matchesStatus = !statusInput || status.includes(statusInput);

        const isVisible = matchesPartNo && matchesLine && matchesModel && matchesStatus;
        row.style.display = isVisible ? "" : "none";

        // Reset highlighting
        [partNoCell, lineCell, modelCell, statusCell].forEach(cell => {
            cell.innerHTML = cell.textContent;
        });

        // Apply highlight if visible
        if (isVisible) {
            if (searchInput) highlightMatch(partNoCell, searchInput);
            if (lineInput) highlightMatch(lineCell, lineInput);
            if (modelInput) highlightMatch(modelCell, modelInput);
            if (statusInput) highlightMatch(statusCell, statusInput);
        }
    });
}

function highlightMatch(cell, keyword) {
    const text = cell.textContent;
    const regex = new RegExp(`(${keyword})`, "ig");
    cell.innerHTML = text.replace(regex, `<span class="highlight">$1</span>`);
}


// Convert from "dd/mm/yyyy" or "dd-mm-yyyy" to "yyyy-mm-dd"
function formatDate(dateStr) {
    const parts = dateStr.includes('/') ? dateStr.split('/') : dateStr.split('-');
    if (parts.length !== 3) return '';
    return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
}

function deletePart(id) {
    if (!confirm("Are you sure you want to delete this part?")) return;

    fetch("api/delete_part.php", {
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

function editPart(id) {
    fetch(`api/get_part_by_id.php?id=${id}`)
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

document.getElementById('editPartForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent page reload

    const formData = new FormData(this);
    const data = new URLSearchParams(formData);

    fetch('api/update_part.php', {
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


// Call fetch on page load
window.onload = fetchParts;
