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
        lot_no: document.getElementById("lotInput")?.value.trim() || '',
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

    const headers = [["Date", "Time", "Line", "Model", "Part No.", "Lot No.", "Quantity", "Type"]];
    const rows = result.data.map(row => [
        row.log_date,
        row.log_time,
        row.line,
        row.model,
        row.part_no,
        row.lot_no,
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

    doc.save("Production_History.pdf");
}

async function exportToExcel() {
    const params = new URLSearchParams({
        startDate: document.getElementById("startDate").value,
        endDate: document.getElementById("endDate").value,
        line: document.getElementById("lineInput")?.value.trim() || '',
        model: document.getElementById("modelInput")?.value.trim() || '',
        part_no: document.getElementById("searchInput")?.value.trim() || '',
        lot_no: document.getElementById("lotInput")?.value.trim() || '',
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

    const headers = ["Date", "Time", "Line", "Model", "Part No.", "Lot No.", "Quantity", "Type", "Note"];

    const data = result.data.map(row => [
        row.log_date,
        row.log_time,
        row.line,
        row.model,
        row.part_no,
        row.lot_no,
        row.count_value,
        row.count_type,
        row.note || ''
    ]);

    // Compute grand totals by type
    const totals = {
        FG: 0,
        NG: 0,
        HOLD: 0,
        REWORK: 0,
        SCRAP: 0,
        ETC: 0
    };

    result.data.forEach(row => {
        const type = row.count_type?.toUpperCase();
        if (totals.hasOwnProperty(type)) {
            totals[type] += row.count_value || 0;
        }
    });

    // Create summary table (1 row: quantity for each type)
    const summaryHeader = ["", "FG", "NG", "HOLD", "REWORK", "SCRAP", "ETC"];
    const summaryRow = [
        "Quantity",
        totals.FG || 0,
        totals.NG || 0,
        totals.HOLD || 0,
        totals.REWORK || 0,
        totals.SCRAP || 0,
        totals.ETC || 0
    ];

    // Prepare full export sheet (summary on top, data below with spacing)
    const exportArray = [];

    exportArray.push(summaryHeader);
    exportArray.push(summaryRow);
    exportArray.push([]); // empty row separator
    exportArray.push(headers);
    exportArray.push(...data);

    const worksheet = XLSX.utils.aoa_to_sheet(exportArray);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "ProductionData");

    XLSX.writeFile(workbook, "Production_History.xlsx");
}

function exportSummaryToExcel() {
    const summary = window.cachedSummary || [];
    const grand = window.cachedGrand || {};

    if (!summary.length) {
        alert("No summary data to export.");
        return;
    }

    // Build headers
    const headers = [
        ["Model", "Part No.", "Lot No", "FG", "NG", "HOLD", "REWORK", "SCRAP", "ETC"]
    ];

    // Build data rows
    const rows = summary.map(row => [
        row.model,
        row.part_no,
        row.lot_no || '',
        row.FG || 0,
        row.NG || 0,
        row.HOLD || 0,
        row.REWORK || 0,
        row.SCRAP || 0,
        row.ETC || 0
    ]);

    // Add grand total as the first row after header
    const grandRow = [
        "Total", "", "",
        grand.FG || 0,
        grand.NG || 0,
        grand.HOLD || 0,
        grand.REWORK || 0,
        grand.SCRAP || 0,
        grand.ETC || 0
    ];

    const worksheet = XLSX.utils.aoa_to_sheet([...headers, grandRow, ...rows]);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Production Summary");

    XLSX.writeFile(workbook, "Production_Summary.xlsx");
}


