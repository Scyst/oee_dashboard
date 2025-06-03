async function fetchParts() {
    try {
        const res = await fetch('../api/pdTable/get_parts.php');
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

function filterTable() { //filter data that show in table
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

// Convert from "dd/mm/yyyy" or "dd-mm-yyyy" to "yyyy-mm-dd"
function formatDate(dateStr) {
    const parts = dateStr.includes('/') ? dateStr.split('/') : dateStr.split('-');
    if (parts.length !== 3) return '';
    return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
}

function highlightMatch(cell, keyword) {
    const text = cell.textContent;
    const regex = new RegExp(`(${keyword})`, "ig");
    cell.innerHTML = text.replace(regex, `<span class="highlight">$1</span>`);
}

function refetchSummaryInModal() {
    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value;
    const line = document.getElementById("lineInput").value.trim();
    const model = document.getElementById("modelInput").value.trim();
    const partNo = document.getElementById("searchInput").value.trim();
    const status = document.getElementById("status").value.trim();
    const lotNo = document.getElementById("lotInput")?.value.trim() || '';

    const params = new URLSearchParams({
        page: 1,
        limit: 1000, // get all for summary
        startDate,
        endDate,
        line,
        model,
        part_no: partNo,
        count_type: status,
        lot_no: lotNo
    });

    fetch(`../api/pdTable/get_parts.php?${params.toString()}`)
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                window.cachedSummary = result.summary || [];
                window.cachedGrand = result.grand_total || {};
                openSummaryModal(); // re-render updated table
            } else {
                alert("Summary load failed: " + result.message);
            }
        })
        .catch(err => {
            console.error("Summary fetch error", err);
            alert("Failed to load summary.");
        });
}
