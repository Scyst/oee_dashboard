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
        model: document.getElementById("machineInput")?.value.trim() || '',
        part_no: document.getElementById("searchInput")?.value.trim() || '',
        page: 1,
        limit: 100000 // Large number to fetch all
    });

    const response = await fetch(`../api/Stop_Cause/get_stop.php?${params.toString()}`);
    const result = await response.json();

    if (!result.success) {
        alert("Failed to export data.");
        return;
    }

    const headers = [["Date", "Start", "End", "Line", "Machine/Station", "Cause", "Recovered By"]];
    const rows = result.data.map(row => [
        row.log_date,
        row.stop_begin,
        row.stop_end,
        row.line,
        row.machine,
        row.cause,
        row.recovered_by,
        //row.note || ''
    ]);

    doc.setFontSize(16);
    doc.text("Filtered Stop Cause History", 14, 16);

    doc.autoTable({
        head: headers,
        body: rows,
        startY: 20,
        headStyles: { fillColor: [0, 123, 255], halign: 'center' },
        bodyStyles: { halign: 'center' },
        theme: 'striped',
    });

    doc.save("Stop_Cause_History.pdf");
}

async function exportToExcel() {
    const params = new URLSearchParams({
        startDate: document.getElementById("startDate").value,
        endDate: document.getElementById("endDate").value,
        line: document.getElementById("lineInput")?.value.trim() || '',
        model: document.getElementById("machineInput")?.value.trim() || '',
        part_no: document.getElementById("searchInput")?.value.trim() || '',
        page: 1,
        limit: 100000
    });

    const response = await fetch(`../api/Stop_Cause/get_stop.php?${params.toString()}`);
    const result = await response.json();

    if (!result.success) {
        alert("Failed to export data.");
        return;
    }

    const headers = ["Date", "Start", "End", "Line", "Machine/Station", "Cause", "Recovered By", "Note"];
    const data = result.data.map(row => [
        row.log_date,
        row.stop_begin,
        row.stop_end,
        row.line,
        row.machine,
        row.cause,
        row.recovered_by,
        row.note || ''
    ]);

    const worksheet = XLSX.utils.aoa_to_sheet([headers, ...data]);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "FilteredData");

    XLSX.writeFile(workbook, "Stop_Cause_History.xlsx");
}
