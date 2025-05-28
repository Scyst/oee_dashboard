//use in OEE_Dashboard.php

async function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Ensure autoTable is defined
    if (!doc.autoTable) {
        alert("jsPDF AutoTable plugin not loaded.");
        return;
    }

    // Fetch all filtered data from the server
    const params = new URLSearchParams({
        startDate: document.getElementById("startDate").value,
        endDate: document.getElementById("endDate").value,
        line: document.getElementById("lineInput")?.value.trim() || '',
        model: document.getElementById("modelInput")?.value.trim() || '',
        part_no: document.getElementById("searchInput")?.value.trim() || '',
        count_type: document.getElementById("status")?.value.trim() || '',
        page: 1,
        limit: 100000 // Large number to fetch all
    });

    const response = await fetch(`../api/pdTable/get_parts.php?${params.toString()}`);
    const result = await response.json();

    if (!result.success) {
        alert("Failed to export data.");
        return;
    }

    const headers = [["Date", "Time", "Line", "Model", "Part No.", "Quantity", "Type"]];
    const rows = result.data.map(row => [
        row.log_date,
        row.log_time,
        row.line,
        row.model,
        row.part_no,
        row.count_value,
        row.count_type,
        //row.note || ''
    ]);

    doc.setFontSize(16);
    doc.text("Filtered Production History", 14, 16);

    doc.autoTable({
        head: headers,
        body: rows,
        startY: 20,
        headStyles: { fillColor: [0, 123, 255], halign: 'center' },
        bodyStyles: { halign: 'center' },
        theme: 'striped',
    });

    doc.save("Filtered_Production_History.pdf");
}

async function exportToExcel() {
    const params = new URLSearchParams({
        startDate: document.getElementById("startDate").value,
        endDate: document.getElementById("endDate").value,
        line: document.getElementById("lineInput")?.value.trim() || '',
        model: document.getElementById("modelInput")?.value.trim() || '',
        part_no: document.getElementById("searchInput")?.value.trim() || '',
        count_type: document.getElementById("status")?.value.trim() || '',
        page: 1,
        limit: 100000
    });

    const response = await fetch(`../api/pdTable/get_parts.php?${params.toString()}`);
    const result = await response.json();

    if (!result.success) {
        alert("Failed to export data.");
        return;
    }

    const headers = ["Date", "Time", "Line", "Model", "Part No.", "Quantity", "Type", "Note"];
    const data = result.data.map(row => [
        row.log_date,
        row.log_time,
        row.line,
        row.model,
        row.part_no,
        row.count_value,
        row.count_type,
        row.note || ''
    ]);

    const worksheet = XLSX.utils.aoa_to_sheet([headers, ...data]);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "FilteredData");

    XLSX.writeFile(workbook, "Filtered_Production_History.xlsx");
}
