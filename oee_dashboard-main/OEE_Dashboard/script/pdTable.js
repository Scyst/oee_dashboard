async function fetchParts() {
    const data = [
        { id: 1, log_date: "15/05/2025", log_time: "10:22", line: "Spot", model: "M20", part_no: "120316", count_value: 150, count_type: "FG" },
        { id: 2, log_date: "15/05/2025", log_time: "11:00", line: "Assembly", model: "M20", part_no: "190123", count_value: 90, count_type: "NG" },
        { id: 3, log_date: "15/05/2025", log_time: "11:00", line: "Paint", model: "M20", part_no: "120316", count_value: 90, count_type: "Hold" },
        { id: 4, log_date: "15/05/2025", log_time: "11:00", line: "Bend", model: "M20", part_no: "120316", count_value: 90, count_type: "Rework" }
    ];

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
            <td>
                <button onclick="editPart(${row.id})">Edit</button>
                <button onclick="deletePart(${row.id})">Delete</button>
            </td>
        `;
        tableBody.appendChild(tr);
    });

    // 🔧 Apply filters if any (needed for dynamic data)
    filterTable();
}
function filterTable() {
    const searchInput = document.getElementById("searchInput").value.toLowerCase().trim();
    const modelInput = document.getElementById("product").value.toLowerCase().trim();
    const statusInput = document.getElementById("status").value.toLowerCase().trim();

    const rows = document.querySelectorAll("#partTable tbody tr");

    rows.forEach(row => {
        const partNo = row.children[5].textContent.toLowerCase().trim();
        const model = row.children[4].textContent.toLowerCase().trim();
        const status = row.children[7].textContent.toLowerCase().trim();

        // Track individual matches
        const matchesPartNo = !searchInput || partNo.includes(searchInput);
        const matchesModel = !modelInput || model === modelInput;
        const matchesStatus = !statusInput || status === statusInput;

        // Only show if ALL active filters match
        const isVisible = matchesPartNo && matchesModel && matchesStatus;
        row.style.display = isVisible ? "" : "none";
    });
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
                fetchAndRenderPartTable(); // Refresh the table
            } else {
                alert("Delete failed: " + data.message);
            }
        })
        .catch(err => {
            console.error("Delete error:", err);
            alert("An error occurred while deleting.");
        });
}

function updatePart(data) {
    const formData = new URLSearchParams(data).toString();

    fetch("api/update_part.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert("Part updated.");
                loadPartsTable(); // Refresh table
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Request failed.");
        });
}


// Call fetch on page load
window.onload = fetchParts;