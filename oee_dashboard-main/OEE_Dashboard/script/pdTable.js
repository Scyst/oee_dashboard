async function fetchParts() {
    //const res = await fetch('api/get_parts.php'); //this is dafault
    //const data = await res.json(); //this is dafault
    const data = [
        { log_date: "15/05/2025", log_time: "10:22", model: "M20", part_no: "120316", count_value: 150, count_type: "FG" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "190123", count_value: 90, count_type: "NG" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "120316", count_value: 90, count_type: "Hold" },
        { log_date: "15/05/2025", log_time: "10:22", model: "M20", part_no: "120316", count_value: 150, count_type: "FG" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "190123", count_value: 90, count_type: "NG" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "120316", count_value: 90, count_type: "Hold" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "120316", count_value: 90, count_type: "Rework" },
        { log_date: "15/05/2025", log_time: "10:22", model: "M20", part_no: "120316", count_value: 150, count_type: "FG" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "190123", count_value: 90, count_type: "NG" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "120316", count_value: 90, count_type: "Hold" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "120316", count_value: 90, count_type: "Rework" },
        { log_date: "15/05/2025", log_time: "10:22", model: "M20", part_no: "120316", count_value: 150, count_type: "FG" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "190123", count_value: 90, count_type: "NG" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "120316", count_value: 90, count_type: "Hold" },
        { log_date: "15/05/2025", log_time: "11:00", model: "M20", part_no: "120316", count_value: 90, count_type: "Rework" }
    ]; //this is test
        
    const tableBody = document.getElementById('partTableBody');
    tableBody.innerHTML = '';

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
      <td>${row.log_date}</td>
      <td>${row.log_time}</td>
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
}

function filterTable() {
    const input = document.getElementById("searchInput").value.toLowerCase();
    const rows = document.querySelectorAll("#partTable tbody tr");
    rows.forEach(row => {
        const partNo = row.children[3].textContent.toLowerCase();
        row.style.display = partNo.includes(input) ? "" : "none";
    });
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